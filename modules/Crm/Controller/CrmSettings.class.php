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
 * This is the settings class file for handling the all functionalities under settings menu.
 *
 * PHP version 5.3 or >
 *
 * @category   Settings
 * @package    cloudrexx
 * @subpackage module_crm
 * @author     ss4ugroup <ss4ugroup@softsolutions4u.com>
 * @license    BSD Licence
 * @version    1.0.0
 * @link       www.cloudrexx.com
 */

namespace Cx\Modules\Crm\Controller;

/**
 * CrmSettingsException
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Adrian Berger <ab@comvation.com>
 * @package     cloudrexx
 * @subpackage  module_crm
 * @version     1.0.0
 */
class CrmSettingsException extends \Exception
{
}


/**
 * This is the settings class file for handling the all functionalities under settings menu.
 *
 * @category   Settings
 * @package    cloudrexx
 * @subpackage module_crm
 * @author     ss4ugroup <ss4ugroup@softsolutions4u.com>
 * @license    BSD Licence
 * @version    1.0.0
 * @link       www.cloudrexx.com
 */

class CrmSettings extends CrmLibrary
{

    /**
     * Template object
     *
     * @param object
     */
    public $_objTpl;

    /**
     * php 5.3 contructor
     *
     * @param object $objTpl template object
     */
    function __construct($objTpl, $name)
    {
        $this->_objTpl = $objTpl;
        parent::__construct($name);
    }

    /**
     * customer settings overview
     *
     * @global array $_CORELANG
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @global object $objJs
     * @return true
     */
    public function showCustomerSettings()
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase ,$objJs;

        $fn = isset($_REQUEST['fn']) ? $_REQUEST['fn'] : '';
        if (!empty($fn)) {
            switch ($fn) {
            case 'editCustomerTypes':
                    $this->editCustomerTypes();
                break;
            }
            return;
        }
        $mes = isset ($_GET['mes']) ? base64_decode($_GET['mes']) : '';

        switch ($mes) {
        case 'activate':
            $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_ACTIVATED_SUCCESSFULLY'];
            break;
        case 'deactivate':
            $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_DEACTIVATED_SUCCESSFULLY'];
            break;
        case 'error':
            $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_CRM_DEFAULT_CUSTOMER_TYPE_STATUS_ERROR'];
            break;
        }

        $this->_objTpl->addBlockfile('CRM_SETTINGS_FILE', 'settings_block', 'module_'.$this->moduleNameLC.'_settings_customers.html');
        $this->_objTpl->setGlobalVariable('MODULE_NAME', $this->moduleName);

        if (isset($_POST['customer_type_submit']))
            $this->saveCustomerTypes();

        $customerLabel   = isset($_POST['label']) ? contrexx_input2raw($_POST['label']) : '';
        $customerSorting = isset($_POST['sortingNumber']) ? intval($_POST['sortingNumber']) : '';
        $customerStatus  = isset($_POST['activeStatus']) || !isset ($_POST['customer_type_submit']) ? 1 : 0;
        $hrlyRate        = array();

        if (!empty($_POST['currencyName'])) {
            foreach ($_POST['currencyName'] as $currencyId) {
                $hrlyRate[$currencyId] = (isset($_POST['rateValue_'.$currencyId])) ? intval($_POST['rateValue_'.$currencyId]) : 0;
            }
        }
        $this->_objTpl->setVariable(array(
                'TXT_DISPLAY_ADD'            =>    isset($_POST['customer_type_submit']) ? "block" : 'none',
                'TXT_DISPLAY_ENTRIES'        =>    isset($_POST['customer_type_submit']) ? "none"  : 'block',
                'TXT_DISPLAY_ADD_ACTIVE'     =>    isset($_POST['customer_type_submit']) ? "active" : 'inactive',
                'TXT_DISPLAY_ENTRIES_ACTIVE' =>    isset($_POST['customer_type_submit']) ? "inactive"  : 'active',
        ));

        if (isset($_POST['customer_submit'])) {
            $count = count($_POST['form_id']);
            for ($x = 0; $x < $count; $x++) {
                $query = "UPDATE ".DBPREFIX."module_{$this->moduleNameLC}_customer_types
                      SET      pos = '".intval($_POST['form_pos'][$x])."'
                      WHERE    id = '".intval($_POST['form_id'][$x])."'";
                $objDatabase->Execute($query);
            }

            $defaultTypeId = intval($_POST['default']);
            $statusArr     = array();
            $x             = 0;
            foreach ($_POST['form_id'] as $id) {
                $statusArr[$id]           = array();
                $statusArr[$id]['id']     = intval($id);
                $statusArr[$id]['status'] = ($defaultTypeId == $id) ? 1 : 0;
                $statusArr[$id]['pos']    = intval($_POST['form_pos'][$x]);
                $x++;
            }

            // New update Query.
            $idArr = array_map('intval', $_POST['form_id']);
            $ids   = implode(',', $idArr);
            $query = "UPDATE ".DBPREFIX."module_".$this->moduleNameLC."_customer_types SET `default` = CASE id ";

            foreach ($statusArr as $id => $val) {
                $query .= sprintf(" WHEN %d THEN %d", $id, $val['status']);
            }

            $query .= " END WHERE id IN ($ids)";

            $objDatabase->Execute($query);

            $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_CHANGES_UPDATED_SUCCESSFULLY'];
        }

        if (isset($_GET['chg']) && $_GET['chg'] == 1) {
            for ($x = 0; $x < count($_POST['form_id']); $x++) {
                $query = "UPDATE ".DBPREFIX."module_".$this->moduleNameLC."_customer_types
                          SET      pos = '".intval($_POST['form_pos'][$x])."'
                          WHERE    id = '".intval($_POST['form_id'][$x])."'";
                $objDatabase->Execute($query);
            }
            $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_PROJECTSTATUS_SORTING_COMPLETE'];
        }

        $sortField            = isset($_GET['sortf']) ? intval($_GET['sortf']) : 0;
        $sortOrder            = isset($_GET['sorto']) ? intval($_GET['sorto']) : 1;
        $customerFields       = array('pos', 'label','customerTypeId');
        $customerTypeOverview = array();
        $numeric              = array('pos');

        $objResult = $objDatabase->Execute('SELECT `id`,
                                                     `label`,
                                                     `pos`,
                                                     `active`,
                                                     `default`
                                                FROM `'.DBPREFIX.'module_'.$this->moduleNameLC.'_customer_types` ');

        if ($objResult->fields['id'] === null) {
            $this->_objTpl->setVariable(array(
                    'TXT_CRM_CONTAINS_NO_RECORDS'    =>    $_ARRAYLANG['TXT_CRM_CONTAINS_NO_RECORDS'],
                    'TXT_DISPLAY_SELECT_ACTION'  =>    "none"
            ));
            $this->_objTpl->parse('showEntries');
        } else {
            $row = 0;
            while (!$objResult->EOF) {
                $activeImage = ($objResult->fields['active']) ? "../core/Core/View/Media/icons/led_green.gif" : "../core/Core/View/Media/icons/led_red.gif";
                $activeTitle = ($objResult->fields['active']) ? $_ARRAYLANG['TXT_CRM_ACTIVE'] : $_ARRAYLANG['TXT_CRM_INACTIVE'];

                $customerTypeOverview[$row] = array(
                        'pos'            => $objResult->fields['pos'],
                        'active'         => $objResult->fields['active'],
                        'label'          => $objResult->fields['label'],
                        'activeTitle'    => $activeTitle,
                        'activeImage'    => $activeImage,
                        'customerTypeId' => $objResult->fields['id'],
                        'default'        => $objResult->fields['default'],
                );
                $row++;
                $objResult->MoveNext();
            }

            $sorting              = new Sorter();
            $sorting->backwards   = empty($sortOrder);
            $sorting->numeric     = (in_array($customerFields[$sortField], $numeric));
            $customerTypeOverview = $sorting->sort($customerTypeOverview, $customerFields[$sortField]);

            $row = 0;
            foreach ($customerTypeOverview as $customerTypeValues) {

                $this->_objTpl->setVariable(array(
                        'TXT_CUSTOMER_TYPE_ID'        =>  $customerTypeValues['customerTypeId'],
                        'TXT_CUSTOMER_TYPE_LABEL'    =>  contrexx_raw2xhtml($customerTypeValues['label'], ENT_QUOTES),
                        'TXT_PROJECT_ACTIVE_IMAGE'      =>  $customerTypeValues['activeImage'],
                        'TXT_PROJECT_ACTIVE_TITLE'      =>  $customerTypeValues['activeTitle'],
                        'TXT_CUSTOMER_ACTIVE'        =>  $customerTypeValues['active'],
                        'TXT_CUSTOMER_POS_SORT'        =>  $customerTypeValues['pos'],
                        'ENTRY_ROWCLASS'                =>  ($row % 2 == 0) ? 'row1' : 'row2',
                        'TXT_CRM_IMAGE_DELETE'              =>  $_ARRAYLANG['TXT_CRM_IMAGE_DELETE'],
                        'TXT_CRM_IMAGE_EDIT'                =>  $_ARRAYLANG['TXT_CRM_IMAGE_EDIT'],
                        'TXT_CRM_CUSTOMER_TYPE'             =>  $_ARRAYLANG['TXT_CRM_CUSTOMER_TYPE'],
                        'TXT_DEFAULT_STATUS_CHECKED'    =>  ($customerTypeValues['default'] == 1) ? 'checked="checked"' : '',
                ));
                $row++;
                $this->_objTpl->parse('showEntries');
            }
        }

        $this->_objTpl->setVariable('CUSTOMER_TYPES_JAVASCRIPT', $objJs->getCustomerTypeJavascript());
        $this->_objTpl->setVariable(array(
                'PM_CUSTOMER_ORDER_SORT'         =>'&sortf=0&sorto='.($sortOrder?0:1),
                'PM_CUSTOMER_LABEL_SORT'         =>'&sortf=1&sorto='.($sortOrder?0:1),
                'TXT_CUSTOMERGIVENLABEL'         => contrexx_raw2xhtml($customerLabel),
                'CRM_SORTING_NUMBER'             => (int) $customerSorting,
                'CRM_CUSTOMER_TYPE_CHECKED'      => $customerStatus ? 'checked' : '',

                'TXT_CRM_CUSTOMERS'                  => $_ARRAYLANG['TXT_CRM_CUSTOMERS'],
                'TXT_CRM_CUSTOMER_TYPES'             => $_ARRAYLANG['TXT_CRM_CUSTOMER_TYPES'],
                'TXT_CRM_LABEL'                      => $_ARRAYLANG['TXT_CRM_LABEL'],
                'TXT_CRM_TITLE_STATUS'               => $_ARRAYLANG['TXT_CRM_TITLE_STATUS'],
                'TXT_CRM_FUNCTIONS'                  => $_ARRAYLANG['TXT_CRM_FUNCTIONS'],
                'TXT_CRM_SORTING'                    => $_ARRAYLANG['TXT_CRM_SORTING'],
                'TXT_CRM_TITLEACTIVE'                => $_ARRAYLANG['TXT_CRM_TITLEACTIVE'],
                'TXT_CRM_PROJECTSTATUS_SAVE_SORTING' => $_ARRAYLANG['TXT_CRM_PROJECTSTATUS_SAVE_SORTING'],
                'TXT_CRM_SORTING_NUMBER'             => $_ARRAYLANG['TXT_CRM_SORTING_NUMBER'],
                'TXT_CRM_ACTIVATESELECTED'           => $_ARRAYLANG['TXT_CRM_ACTIVATESELECTED'],
                'TXT_CRM_DEACTIVATESELECTED'         => $_ARRAYLANG['TXT_CRM_DEACTIVATESELECTED'],
                'TXT_CRM_SELECT_ACTION'              => $_ARRAYLANG['TXT_CRM_SELECT_ACTION'],
                'TXT_CRM_DELETE_SELECTED'            => $_ARRAYLANG['TXT_CRM_DELETE_SELECTED'],
                'TXT_CRM_ADD_CUSTOMER_TYPES'         => $_ARRAYLANG['TXT_CRM_ADD_CUSTOMER_TYPES'],
                'TXT_CRM_ENTER_LABEL_FIELD'          => $_ARRAYLANG['TXT_CRM_ENTER_LABEL_FIELD'],
                'TXT_CRM_ENTER_LABEL_FIELD_WITHOUT_SPECIAL_CHARACTERS' => $_ARRAYLANG['TXT_CRM_ENTER_LABEL_FIELD_WITHOUT_SPECIAL_CHARACTERS'],
                'TXT_CRM_SELECT_ALL'                 => $_ARRAYLANG['TXT_CRM_SELECT_ALL'],
                'TXT_CRM_DESELECT_ALL'               => $_ARRAYLANG['TXT_CRM_REMOVE_SELECTION'],
                'TXT_CRM_CURRENCY_RATES'             => $_ARRAYLANG['TXT_CRM_CURRENCY_RATES'],
                'TXT_CRM_DEFAULT'                    => $_ARRAYLANG['TXT_CRM_DEFAULT'],
                'TXT_CRM_GENERAL'                    => $_ARRAYLANG['TXT_CRM_GENERAL'],
                'TXT_CRM_NOTES'                      => $_ARRAYLANG['TXT_CRM_NOTES'],
                'TXT_CRM_SAVE'                       => $_ARRAYLANG['TXT_CRM_SAVE'],

        ));
    }

    /**
     * Edit page of cutomer types
     *
     * @param string $labelValue label value
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function editCustomerTypes($labelValue="")
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase ,$objJs;

        $this->_pageTitle = $_ARRAYLANG['TXT_CRM_EDIT_CUSTOMER_TYPE'];
        $this->_objTpl->addBlockfile('CRM_SETTINGS_FILE', 'settings_block', 'module_'.$this->moduleNameLC.'_setting_editcustomer.html');
        $this->_objTpl->setGlobalVariable('MODULE_NAME', $this->moduleName);
        $id                 = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if (empty($id)) {
            \Cx\Core\Csrf\Controller\Csrf::header("location:./index.php?cmd=".$this->moduleName."&act=settings&tpl=customertypes");
            exit();
        }

        $customerLabel      = isset($_POST['label']) ? contrexx_input2raw($_POST['label']) : '';
        $customerSorting    = isset($_POST['sortingNumber']) ? intval($_POST['sortingNumber']) : '';
        $customerStatus     = isset($_POST['activeStatus']) || !isset ($_POST['customer_type_submit']) ? 1 : 0;
        $hrlyRate           = array();

        if (isset($_POST['customer_type_submit'])) {
            $success = true;

            $searchingQuery = "SELECT label FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_types`
                                   WHERE  label = '".contrexx_input2db($customerLabel)."' AND id != $id ";
            $objResult = $objDatabase->Execute($searchingQuery);

            if (!$objResult->EOF) {
                $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_CRM_CUSTOMER_TYPE_ALREADY_EXIST'];
                $success = false;
            } else {
                $insertCustomerTypes = "UPDATE `".DBPREFIX."module_".$this->moduleNameLC."_customer_types`
                                            SET    `label`             = '".contrexx_input2db($customerLabel)."',
                                                   `pos`               = '".intval($customerSorting)."',
                                                   `active`            = '".intval($customerStatus)."'
                                            WHERE id =$id";

                $db = $objDatabase->Execute($insertCustomerTypes);
                if ($db)
                    $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_CUSTOMER_TYPES_UPDATED_SUCCESSFULLY'];
                else
                    $success = false;
            }

            if ($success) {
                \Cx\Core\Csrf\Controller\Csrf::header("location:./index.php?cmd=".$this->moduleName."&act=settings&tpl=customertypes");
                exit();
            }
        } else {
            $objResult =   $objDatabase->Execute('SELECT  id,label, pos, active
                                                  FROM '.DBPREFIX.'module_'.$this->moduleNameLC.'_customer_types WHERE id = '.$id);

            $customerLabel      = $objResult->fields['label'];
            $customerSorting    = $objResult->fields['pos'];
            $customerStatus     = $objResult->fields['active'];
        }

        $this->_objTpl->setVariable(array(
                'CUSTOMER_TYPES_JAVASCRIPT'         => $objJs->editCustomerTypeJavascript(),
                'TXT_CUSTOMER_TYPE_ID'              => (int) $id,
                'TXT_LABEL_VALUE'            => contrexx_raw2xhtml($customerLabel),
                'CRM_CUSTOMER_TYPE_SORTING_NUMBER'  => (int) $customerSorting,
                'TXT_ACTIVATED_VALUE'               => $customerStatus ? 'checked' : '',

                'TXT_CRM_CUSTOMERS'                     => $_ARRAYLANG['TXT_CRM_CUSTOMERS'],
                'TXT_CRM_LABEL'                         => $_ARRAYLANG['TXT_CRM_LABEL'],
                'TXT_CRM_SAVE'                          => $_ARRAYLANG['TXT_CRM_SAVE'],
                'TXT_CRM_BACK'                          => $_ARRAYLANG['TXT_CRM_BACK'],
                'TXT_CRM_TITLEACTIVE'                   => $_ARRAYLANG['TXT_CRM_TITLEACTIVE'],
                'TXT_CRM_FUNCTIONS'                     => $_ARRAYLANG['TXT_CRM_FUNCTIONS'],
                'TXT_CRM_SELECT_ACTION'                 => $_ARRAYLANG['TXT_CRM_SELECT_ACTION'],
                'TXT_CRM_DELETE_SELECTED'               => $_ARRAYLANG['TXT_CRM_DELETE_SELECTED'],
                'TXT_CRM_ADD_CUSTOMER_TYPES'            => $_ARRAYLANG['TXT_CRM_ADD_CUSTOMER_TYPES'],
                'TXT_CRM_EDIT_CUSTOMER_TYPE'            => $_ARRAYLANG['TXT_CRM_EDIT_CUSTOMER_TYPE'],
                'TXT_CRM_ENTER_LABEL_FIELD'             => $_ARRAYLANG['TXT_CRM_ENTER_LABEL_FIELD'],
                'TXT_CUSTOMER_TYPE_SORTING_NUMBER'  => $_ARRAYLANG['TXT_CRM_SORTING_NUMBER'],
                'TXT_CRM_ENTER_LABEL_FIELD_WITHOUT_SPECIAL_CHARACTERS' => $_ARRAYLANG['TXT_CRM_ENTER_LABEL_FIELD_WITHOUT_SPECIAL_CHARACTERS'],
                'TXT_CRM_CURRENCY_RATES'                => $_ARRAYLANG['TXT_CRM_CURRENCY_RATES'],
                'CSRF_PARAM'                        => \Cx\Core\Csrf\Controller\Csrf::param(),
        ));
    }
    /**
     * store the customer type
     *
     * @global array $_CORELANG
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    public function saveCustomerTypes()
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;
        $this->_pageTitle = $_ARRAYLANG['TXT_CRM_SETTINGS'];

        $success = true;

        $customerLabel      = isset($_POST['label']) ? contrexx_input2raw($_POST['label']) : '';
        $customerSorting    = isset($_POST['sortingNumber']) ? intval($_POST['sortingNumber']) : '';
        $customerStatus     = isset($_POST['activeStatus']) || !isset ($_POST['customer_type_submit']) ? 1 : 0;

        $searchingQuery = "SELECT label FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_types`
                               WHERE  label = '".contrexx_raw2db($customerLabel)."'";
        $objResult = $objDatabase->Execute($searchingQuery);

        if (!$objResult->EOF) {
            $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_CRM_CUSTOMER_TYPE_ALREADY_EXIST'];
            $success = false;
        } else {
            $insertCustomerTypes = "INSERT INTO `".DBPREFIX."module_".$this->moduleNameLC."_customer_types`
                                        SET    `label`             = '".contrexx_input2db($customerLabel)."',
                                               `pos`               = '".intval($customerSorting)."',
                                               `active`            = '".intval($customerStatus)."'
                                               ";
            $db = $objDatabase->Execute($insertCustomerTypes);
            if ($db)
                $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_CUSTOMER_TYPES_ADDED_SUCCESSFULLY'];
            else
                $success = false;
        }

        if ($success) {
            \Cx\Core\Csrf\Controller\Csrf::header("location:./index.php?cmd=".$this->moduleName."&act=settings&tpl=customertypes");
            exit();
        }

    }

    /**
     * Currency overview
     *
     * @global array $_CORELANG
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @global object $objJs
     * @return true
     */
    public function currencyoverview()
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase, $objJs;

        $fn = isset($_REQUEST['fn']) ? $_REQUEST['fn'] : '';
        if (!empty($fn)) {
            switch ($fn) {
            case 'editcurrency':
                $this->editCurrency();
                break;
            }
            return;
        }

        $this->_objTpl->addBlockfile('CRM_SETTINGS_FILE', 'settings_block', 'module_'.$this->moduleNameLC.'_settings_currency.html');
        $this->_pageTitle = $_ARRAYLANG['TXT_CRM_SETTINGS'];

        if (isset ($_POST['currency_submit'])) {
            $this->addCurrency();
        }

        $this->_objTpl->setGlobalVariable(array(
                'MODULE_NAME' => $this->moduleName
        ));
        if (!isset($_GET['mes'])) {
            $_GET['mes']='';
        }
        switch ($_GET['mes']) {
        case 'updatecurrency' :
            $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_CURRENCY_UPDATED_SUCCESSFULLY'];
            break;
        case 'changesupdate' :
            $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_CHANGES_UPDATED_SUCCESSFULLY'];
            break;
        }

        if (!empty($this->_strErrMessage)) {
            $this->_objTpl->setVariable(array(
                    'TXT_DISPLAY_ADD'      =>    "block",
                    'TXT_DISPLAY_ENTRIES'  =>    "none",
                    'TXT_DESCRIPTION_VALUE' => $_SESSION['description']
            ));
            unset($_SESSION['description']);
        } else {
            $this->_objTpl->setVariable(array(
                    'TXT_DISPLAY_ADD'      =>    "none",
                    'TXT_DISPLAY_ENTRIES'  =>    "block"
            ));
        }
        //sort
        if (isset($_GET['chg']) and $_GET['chg'] == 1 ) {
            for ($x = 0; $x < count($_POST['form_id']); $x++) {
                $query = "UPDATE ".DBPREFIX."module_".$this->moduleNameLC."_currency
                               SET pos = '".intval($_POST['form_pos'][$x])."'
                             WHERE id = '".intval($_POST['form_id'][$x])."'";
                $objDatabase->Execute($query);
            }
            $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_PROJECTSTATUS_SORTING_COMPLETE'];
        }

        if (isset($_POST['currencyfield_submit'])) {

            for ($x = 0; $x < count($_POST['form_id']); $x++) {
                $default = ($_POST['form_id'][$x] == $_POST['default']) ? 1 : 0;
                $query = "UPDATE ".DBPREFIX."module_".$this->moduleNameLC."_currency
                              SET pos              = '".intval($_POST['form_pos'][$x])."',
                                  default_currency = '".intval($default)."'
                             WHERE id = '".intval($_POST['form_id'][$x])."'";
                $objDatabase->Execute($query);
                $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_CHANGES_UPDATED_SUCCESSFULLY'];
            }
        }

        $sortField = isset ($_GET['sortf']) ? intval($_GET['sortf']) : 0;
        $sortOrder = isset ($_GET['sorto']) ? intval($_GET['sorto']) : 1;
        $customerFields      = array('pos','name','id','active');
        $currencyeOverview   = array();
        $numeric             = array('pos');
        $key                 = 0;

        $objData = $objDatabase->Execute('SELECT id, name, active, pos, default_currency FROM `'.DBPREFIX.'module_'.$this->moduleNameLC.'_currency`');

        $row = "row2";
        if ($objData->fields['id'] == null) {
            $this->_objTpl->setVariable(array(
                    'TXT_CRM_CONTAINS_NO_RECORDS'    =>    $_ARRAYLANG['TXT_CRM_CONTAINS_NO_RECORDS'],
                    'TXT_DISPLAY_SELECT_ACTION'  =>    "none"
            ));
            $this->_objTpl->parse('showNoEntries');
        } else {

            while (!$objData->EOF) {

                $currencyeOverview[$key] = array(
                        'pos'                      => $objData->fields['pos'],
                        'name'                     => trim($objData->fields['name']),
                        'id'                       => $objData->fields['id'],
                        'active'                   => $objData->fields['active'],
                        'default'                  => $objData->fields['default_currency']
                );

                $key++;
                $objData->MoveNext();
            }
            $sorting               = new Sorter();
            $sorting->backwards    = empty($sortOrder);
            $sorting->numeric      = (in_array($customerFields[$sortField], $numeric));
            $currencyeOverview     = $sorting->sort($currencyeOverview, $customerFields[$sortField], $customerFields[2]);

            foreach ($currencyeOverview as $key => $currency) {
                $activeImage = $currencyeOverview[$key]['active'] ? "../core/Core/View/Media/icons/led_green.gif" : "../core/Core/View/Media/icons/led_red.gif";
                $activeTitle = $currencyeOverview[$key]['active'] ? $_ARRAYLANG['TXT_CRM_ACTIVE']    : $_ARRAYLANG['TXT_CRM_INACTIVE'];

                $this->_objTpl->setVariable(array(
                        'TXT_CURRENCY_NAME'            => contrexx_raw2xhtml($currency['name']),
                        'TXT_CURRENCY_ID'              => $currency['id'],
                        'TXT_CURRENCY_ACTIVE_IMAGE'    => $activeImage,
                        'TXT_CURRENCY_ACTIVE_TITLE'    => $activeTitle,
                        'TXT_CURRENCY_POS'             => $currency['pos'],
                        'TXT_CURRENCY_ACTIVE'          => $currency['active'],
                        'TXT_CURRENCY_DEFAULT'         => $currency['default'] == '1' ? 'checked' : '',
                        'TXT_CRM_IMAGE_EDIT'           => $_ARRAYLANG['TXT_EDIT'],
                        'TXT_CRM_IMAGE_DELETE'         => $_ARRAYLANG['TXT_DELETE'],
                        'ENTRY_ROWCLASS'               => $row = ($row == "row1") ? "row2" : "row1"

                ));
                $this->_objTpl->parse('currency_entries');
                $objData->MoveNext();
            }
        }

        // Hourly rate
        $hrlyRate  = array();
        $objResult = $objDatabase->Execute('SELECT id,label FROM  '.DBPREFIX.'module_'.$this->moduleNameLC.'_customer_types WHERE  active!="0" ORDER BY pos,label');
        while (!$objResult->EOF) {
            $this->_objTpl->setVariable(array(
                'CRM_CUSTOMER_TYPE'     => contrexx_raw2xhtml($objResult->fields['label']),
                'CRM_CUSTOMERTYPE_ID'   => (int) $objResult->fields['id'],
                'PM_CURRENCY_HOURLY_RATE'   => !empty($hrlyRate[$objResult->fields['id']]) ? intval($hrlyRate[$objResult->fields['id']]) : 0,
            ));
            $this->_objTpl->parse("hourlyRate");
            $objResult->MoveNext();
        }

        $settings = $this->getSettings();
        $settings['allow_pm'] ? $this->_objTpl->touchBlock("show-rates") : $this->_objTpl->hideBlock("show-rates");

        $this->_objTpl->setVariable(array(
                'PM_CURRENCY_ORDER_SORT'             => '&sortf=0&sorto='.($sortOrder?0:1),
                'PM_CURRENCY_NAME_SORT'              => '&sortf=1&sorto='.($sortOrder?0:1),
                'TXT_CRM_CURRENCY'                   => $_ARRAYLANG['TXT_CRM_CURRENCY'],
                'TXT_CRM_ADD_CURRENCY'               => $_ARRAYLANG['TXT_CRM_ADD_CURRENCY'],
                'TXT_CRM_TITLE_STATUS'               => $_ARRAYLANG['TXT_CRM_TITLE_STATUS'],
                'TXT_CRM_NAME'                       => $_ARRAYLANG['TXT_CRM_LABEL'],
                'TXT_CRM_SAVE'                       => $_ARRAYLANG['TXT_CRM_SAVE'],
                'TXT_CRM_NOTES'                      => $_ARRAYLANG['TXT_CRM_NOTES'],
                'TXT_CRM_SORTING'                    => $_ARRAYLANG['TXT_CRM_SORTING'],
                'TXT_CRM_SORTING_NUMBER'             => $_ARRAYLANG['TXT_CRM_SORTING_NUMBER'],
                'TXT_CRM_ACTIVATESELECTED'           => $_ARRAYLANG['TXT_CRM_ACTIVATESELECTED'],
                'TXT_CRM_DEACTIVATESELECTED'         => $_ARRAYLANG['TXT_CRM_DEACTIVATESELECTED'],
                'TXT_CRM_TITLEACTIVE'                => $_ARRAYLANG['TXT_CRM_TITLEACTIVE'],
                'TXT_CRM_FUNCTIONS'                  => $_ARRAYLANG['TXT_CRM_FUNCTIONS'],
                'TXT_CRM_SELECT_ALL'                 => $_ARRAYLANG['TXT_CRM_SELECT_ALL'],
                'TXT_CRM_PROJECTSTATUS_SAVE_SORTING' => $_ARRAYLANG['TXT_CRM_PROJECTSTATUS_SAVE_SORTING'],
                'TXT_CRM_DESELECT_ALL'               => $_ARRAYLANG['TXT_CRM_REMOVE_SELECTION'],
                'TXT_CRM_SELECT_ACTION'              => $_ARRAYLANG['TXT_CRM_SELECT_ACTION'],
                'TXT_CRM_DELETE_SELECTED'            => $_ARRAYLANG['TXT_CRM_DELETE_SELECTED'],
                'TXT_CRM_CUSTOMER_TYPES'             => $_ARRAYLANG['TXT_CRM_CUSTOMER_TYPES'],
                'TXT_CRM_GENERAL'                    => $_ARRAYLANG['TXT_CRM_GENERAL'],
                'TXT_CRM_CURRENCY_RATES'             => $_ARRAYLANG['TXT_CRM_CURRENCY_RATES'],
                'TXT_CRM_HOURLY_RATE'                => $_ARRAYLANG['TXT_CRM_HOURLY_RATE'],
                'TXT_CRM_DEFAULT'                    => $_ARRAYLANG['TXT_CRM_DEFAULT'],
                'PM_SETTINGS_CURRENCY_JAVASCRIPT'    => $objJs->getAddCurrencyJavascript(),
        ));
    }

    /**
     * get the edit currency page
     *
     * @param integer $labelValue label value
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function editCurrency($labelValue="")
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase, $objJs;

        $this->_pageTitle = $_ARRAYLANG['TXT_CRM_EDIT_CURRENCY'];
        $this->_objTpl->addBlockfile('CRM_SETTINGS_FILE', 'settings_block', 'module_'.$this->moduleNameLC.'_setting_editcurrency.html');
        $this->_objTpl->setGlobalVariable(array(
                'MODULE_NAME' => $this->moduleName
        ));

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if (empty($id)) {
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            \Cx\Core\Csrf\Controller\Csrf::header($cx->getCodeBaseOffsetPath(). $cx->getBackendFolderName()."/index.php?cmd=".$this->moduleName."&act=settings&tpl=currency");
            exit();
        }

        $label      = isset($_POST['name']) ? contrexx_input2raw($_POST['name']) : '';
        $sorting    = isset($_POST['sortingNumber']) ? (int) $_POST['sortingNumber'] : '';
        $status     = isset($_POST['activeStatus']) ? 1 : 0;
        $hrlyRate           = array();
        if (!empty($_POST['customerType'])) {
            foreach ($_POST['customerType'] as $customerTypeId) {
                $hrlyRate[$customerTypeId] = (isset($_POST['rateValue_'.$customerTypeId])) ? intval($_POST['rateValue_'.$customerTypeId]) : 0;
            }
        }

        if (isset($_POST['currency_submit'])) {
            $success = true;

            $searchDuplicateQuery = "SELECT name
                                        FROM `".DBPREFIX."module_{$this->moduleNameLC}_currency`
                                        WHERE name='$label' AND id != $id";
            $objData = $objDatabase->Execute($searchDuplicateQuery);

            if ($objData->RecordCount()) {
                $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_CRM_CURRENCY_ALREADY_EXISTS'];
            } else {
                $updateProjectTypes = "UPDATE `".DBPREFIX."module_{$this->moduleNameLC}_currency`
                                     SET  `name`  = '$label',
                                          `pos`   = '$sorting',
                                          `active`= '$status',
                                          `hourly_rate` = '".json_encode($hrlyRate)."'
                                     WHERE id = $id";
                $objDatabase->Execute($updateProjectTypes);
                $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_CURRENCY_UPDATED_SUCCESSFULLY'];

                \Cx\Core\Csrf\Controller\Csrf::header("location:./index.php?cmd=".$this->moduleName."&act=settings&tpl=currency");
                exit();
            }
        } else {
            $objResult =   $objDatabase->Execute("SELECT  id, name,pos, hourly_rate, active FROM ".DBPREFIX."module_{$this->moduleNameLC}_currency WHERE id = $id");

            $label      = $objResult->fields['name'];
            $sorting    = $objResult->fields['pos'];
            $status     = $objResult->fields['active'];
            $hrlyRate   = json_decode($objResult->fields['hourly_rate'], true);
        }

        // Hourly rate
        $settings = $this->getSettings();
        if ($settings['allow_pm']) {
            $objResult = $objDatabase->Execute('SELECT id,label FROM  '.DBPREFIX.'module_'.$this->moduleNameLC.'_customer_types WHERE  active!="0" ORDER BY pos,label');
            while (!$objResult->EOF) {
                $this->_objTpl->setVariable(array(
                    'CRM_CUSTOMER_TYPE'     => contrexx_raw2xhtml($objResult->fields['label']),
                    'CRM_CUSTOMERTYPE_ID'   => (int) $objResult->fields['id'],
                    'PM_CURRENCY_HOURLY_RATE'   => !empty($hrlyRate[$objResult->fields['id']]) ? intval($hrlyRate[$objResult->fields['id']]) : 0,
                ));
                $this->_objTpl->parse("hourlyRate");
                $objResult->MoveNext();
            }
        }
        $settings['allow_pm'] ? $this->_objTpl->touchBlock("show-rates") : $this->_objTpl->hideBlock("show-rates");
        $this->_objTpl->setVariable(array(
            'TXT_SORTINGNUMBER'       => (int) $sorting,
            'TXT_CURRENCY_ID'          => (int) $id,
            'TXT_NAME_VALUE'          => contrexx_raw2xhtml($label),
            'TXT_ACTIVATED_VALUE'     => $status ? 'checked' : '',

            'TXT_CRM_EDIT_CURRENCY'   => $_ARRAYLANG['TXT_CRM_EDIT_CURRENCY'],
            'TXT_CRM_NAME'            => $_ARRAYLANG['TXT_CRM_LABEL'],
            'TXT_CRM_FUNCTIONS'       => $_ARRAYLANG['TXT_CRM_FUNCTIONS'],
            'TXT_CRM_SAVE'            => $_ARRAYLANG['TXT_CRM_SAVE'],
            'TXT_CRM_SORTING_NUMBER'  => $_ARRAYLANG['TXT_CRM_SORTING_NUMBER'],
            'TXT_CRM_TITLEACTIVE'     => $_ARRAYLANG['TXT_CRM_TITLEACTIVE'],
            'TXT_CRM_BACK'            => $_ARRAYLANG['TXT_CRM_BACK'],
            'TXT_CRM_SELECT_ACTION'   => $_ARRAYLANG['TXT_CRM_SELECT_ACTION'],
            'TXT_CRM_DELETE_SELECTED' => $_ARRAYLANG['TXT_CRM_DELETE_SELECTED'],
            'TXT_CRM_CURRENCY_RATES'  => $_ARRAYLANG['TXT_CRM_CURRENCY_RATES'],
            'TXT_CRM_HOURLY_RATE'     => $_ARRAYLANG['TXT_CRM_HOURLY_RATE'],
            'CSRF_PARAM'              => \Cx\Core\Csrf\Controller\Csrf::param(),
            'CURRENCY_JAVASCRIPT'     => $objJs->getAddCurrencyJavascript()
        ));
    }

    /**
     * store currency
     *
     * @global array $_CORELANG
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @global object $objJs
     * @return true
     */
    public function addCurrency()
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase,$objJs;

        $this->_pageTitle = $_ARRAYLANG['TXT_CRM_SETTINGS'];

        if ($_POST['currency_submit']) {

            $settings = array('name' => $_POST['name'],'sortingNumber' => $_POST['sortingNumber']);

            $nameValue = $settings['name'];
            $SortingNum = $settings['sortingNumber'];
            $searchingQuery = 'SELECT name from `'.DBPREFIX.'module_'.$this->moduleNameLC.'_currency` WHERE name = '."'$nameValue'";
            $objResult = $objDatabase->Execute($searchingQuery);

            $hrlyRate           = array();
            if (!empty($_POST['customerType'])) {
                foreach ($_POST['customerType'] as $customerTypeId) {
                    $hrlyRate[$customerTypeId] = (isset($_POST['rateValue_'.$customerTypeId])) ? intval($_POST['rateValue_'.$customerTypeId]) : 0;
                }
            }

            if (!$objResult->EOF) {
                $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_CRM_CURRENCY_ALREADY_EXISTS'];
                return;
            } else {
                $activeValue = isset($_POST['activeStatus']) ? 1 : 0;

                $insertQuery = "INSERT INTO ".DBPREFIX."module_{$this->moduleNameLC}_currency
                                    SET name    = '{$settings['name']}',
                                        pos     = '{$settings['sortingNumber']}',
                                        active  = '$activeValue',
                                        `hourly_rate`       = '".json_encode($hrlyRate)."'";

                $objDatabase->Execute($insertQuery);
                $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_CURRENCY_ADDED_SUCCESSFULLY'];
            }
        }
        $this->_objTpl->setVariable(array(
                'TXT_CRM_CURRENCY'                    => $_ARRAYLANG['TXT_CRM_CURRENCY'],
                'TXT_CRM_ADD_CURRENCY'                => $_ARRAYLANG['TXT_CRM_ADD_CURRENCY'],
                'TXT_CRM_NAME'                        => $_ARRAYLANG['TXT_CRM_NAME'],
                'TXT_CRM_SORTING_NUMBER'              => $_ARRAYLANG['TXT_CRM_SORTING_NUMBER'],
                'TXT_CRM_FUNCTIONS'                   => $_ARRAYLANG['TXT_CRM_FUNCTIONS'],
                'TXT_CRM_SELECT_ALL'                  => $_ARRAYLANG['TXT_CRM_SELECT_ALL'],
                'TXT_CRM_DESELECT_ALL'                => $_ARRAYLANG['TXT_CRM_REMOVE_SELECTION'],
                'TXT_CRM_SELECT_ACTION'               => $_ARRAYLANG['TXT_CRM_SELECT_ACTION'],
                'TXT_CRM_DELETE_SELECTED'             => $_ARRAYLANG['TXT_CRM_DELETE_SELECTED'],
                'PM_SETTINGS_CURRENCY_JAVASCRIPT' => $objJs->getAddCurrencyJavascript(),
        ));
        \Cx\Core\Csrf\Controller\Csrf::header('location:./index.php?cmd=Crm&act=settings&tpl=currency');
        exit();
    }

    /**
     * task type overview
     *
     * @global object $objDatabase
     * @global array $_ARRAYLANG
     * @return true
     */
    public function taskTypesoverview()
    {
        global $objDatabase,$_ARRAYLANG;

        //For task type Upload
        $options = array(
            'id' => 'taskUploader',
            'style' => 'display:none;',
            'upload-limit' => 1
        );
        $uploaderCodeTaskType = $this->initUploader('taskUploadFinished', 'taskCallbackJs', '', $_ARRAYLANG['TXT_BROWSE'], $options);
        $redirectUrl = \Cx\Core\Csrf\Controller\Csrf::enhanceURI('index.php?cmd=Crm&act=getImportFilename');
        $this->_objTpl->setVariable(array(
            'COMBO_UPLOADER_CODE_TASK_TYPE' => $uploaderCodeTaskType,
            'REDIRECT_URL'                  => $redirectUrl
        ));

        $fn = isset($_REQUEST['fn']) ? $_REQUEST['fn'] : '';
        if (!empty($fn)) {
            switch ($fn) {
            case 'editTaskType':
                $this->editTaskType();
                break;
            }
            return;
        }

        $objTpl = $this->_objTpl;
        $objTpl->addBlockfile('CRM_SETTINGS_FILE', 'settings_block', 'module_'.$this->moduleNameLC.'_settings_task_types.html');
        $this->_pageTitle = $_ARRAYLANG['TXT_CRM_SETTINGS'];
        $objTpl->setGlobalVariable(array(
                'MODULE_NAME' => $this->moduleName,
                'TXT_CRM_IMAGE_EDIT' => $_ARRAYLANG['TXT_CRM_IMAGE_EDIT'],
                'TXT_CRM_IMAGE_DELETE' => $_ARRAYLANG['TXT_CRM_IMAGE_DELETE'],
        ));
        \JS::activate("jquery");

        $msg = isset($_REQUEST['msg']) ? base64_decode($_REQUEST['msg']) : '';
        switch ($msg) {
        case 'taskUpdated':
            $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_TASK_TYPE_UPDATED_SUCCESSFULLY'];
            break;
        default:
            break;
        }

        $action          = (isset ($_REQUEST['actionType'])) ? $_REQUEST['actionType'] : '';
        $tasktypeIds     = (isset($_REQUEST['taskTypeId'])) ? array_map('intval', $_REQUEST['taskTypeId']) : 0;
        $tasktypeSorting = (isset($_REQUEST['sorting'])) ? array_map('intval', $_REQUEST['sorting']) : 0;
        $ajax            = isset($_REQUEST['ajax']);

        switch ($action) {
        case 'changestatus':
            $this->activateTaskType((int) $_GET['taskTypeId']);
            if ($ajax) exit();
        case 'activate':
            $this->activateTaskTypes($tasktypeIds);
            break;
        case 'deactivate':
            $this->activateTaskTypes($tasktypeIds, true);
            break;
        case 'delete':
            $this->deleteTaskTypes($tasktypeIds);
            break;
        case 'deletecatalog':
            $this->deleteTaskType((int) $_GET['taskTypeId']);
            if ($ajax) exit();
            break;
        default:
            break;
        }
        if (!empty ($action)) {
            $this->saveSortingTaskType($tasktypeSorting);
            if ($action == 'savesorting' || $action == 'Save')
                $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_PROJECTSTATUS_SORTING_COMPLETE'];
        }

        if (isset($_POST['saveTaskType'])) {
            $this->saveTaskTypes();
            $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_TASK_TYPE_ADDED_SUCCESSFULLY'];
        }

        $this->getModifyTaskTypes();

        $this->showTaskTypes();

        $objTpl->setVariable(array(
                'TXT_CRM_ICON'                 => $_ARRAYLANG['TXT_CRM_ICON'],
                'TXT_CRM_ICON_PATH'            => \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesCrmWebPath().'/',
                'TXT_CRM_TASK_TYPES'           => $_ARRAYLANG['TXT_CRM_TASK_TYPES'],
                'TXT_CRM_ADD_TASK_TYPE'        => $_ARRAYLANG['TXT_CRM_ADD_TASK_TYPE'],
                'TXT_CRM_TASK_TYPE_STATUS'     => $_ARRAYLANG['TXT_CRM_TASK_TYPE_STATUS'],
                'TXT_CRM_FUNCTIONS'            => $_ARRAYLANG['TXT_CRM_FUNCTIONS'],
                'TXT_CRM_NO_TASKTYPES'         => $_ARRAYLANG['TXT_CRM_NO_TASKTYPES'],
                'TXT_CRM_SAVE'                 => $_ARRAYLANG['TXT_CRM_SAVE'],
                'TXT_CRM_SELECT_ALL'           => $_ARRAYLANG['TXT_CRM_SELECT_ALL'],
                'TXT_CRM_DESELECT_ALL'         => $_ARRAYLANG['TXT_CRM_REMOVE_SELECTION'],
                'TXT_CRM_SELECT_ACTION'        => $_ARRAYLANG['TXT_CRM_SELECT_ACTION'],
                'TXT_CRM_DELETE_SELECTED'      => $_ARRAYLANG['TXT_CRM_DELETE_SELECTED'],
                'TXT_CRM_ACTIVATE_SELECTED'    => $_ARRAYLANG['TXT_CRM_ACTIVATE_SELECTED'],
                'TXT_CRM_DEACTIVATE_SELECTED'  => $_ARRAYLANG['TXT_CRM_DEACTIVATE_SELECTED'],
                'TXT_CRM_SAVE_SORTING'         => $_ARRAYLANG['TXT_CRM_SAVE_SORTING'],
                'TXT_SELECT_ENTRIES'           => $_ARRAYLANG['TXT_CRM_NO_OPERATION'],
                'TXT_CRM_STATUS_SUCCESSFULLY_CHANGED' => $_ARRAYLANG['TXT_CRM_TASK_TYPE_STATUS_CHANGED_SUCCESSFULLY'],
                'TXT_CRM_ARE_YOU_SURE_DELETE_ENTRIES' => $_ARRAYLANG['TXT_CRM_ARE_YOU_SURE_DELETE_ENTRIES'],
                'TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT' => $_ARRAYLANG['TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT'],
        ));
    }

    /**
     * Edit the task type
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function editTaskType()
    {
        global $objDatabase, $_ARRAYLANG;

        \JS::activate("jquery");
        // Activate validation scripts
        \JS::registerCSS("lib/javascript/validationEngine/css/validationEngine.jquery.css");
        \JS::registerJS("lib/javascript/validationEngine/js/languages/jquery.validationEngine-en.js");
        \JS::registerJS("lib/javascript/validationEngine/js/jquery.validationEngine.js");
        \JS::registerCSS("lib/javascript/chosen/chosen.css");
        \JS::registerJS("lib/javascript/chosen/chosen.jquery.js");

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if (isset($_POST['saveTaskType'])) {
            $this->saveTaskTypes($id);
            $msg = "taskUpdated";
            \Cx\Core\Csrf\Controller\Csrf::header("Location:./index.php?cmd=".$this->moduleName."&act=settings&tpl=tasktypes&msg=".base64_encode($msg));
            exit();
        }

        $objTpl = $this->_objTpl;
        $this->_pageTitle = $_ARRAYLANG['TXT_CRM_EDIT_TASK_TYPE'];
        $objTpl->addBlockfile('CRM_SETTINGS_FILE', 'settings_block', "module_{$this->moduleNameLC}_settings_edit_task_types.html");

        $this->getModifyTaskTypes($id);

        $objTpl->setVariable(array(
                'TXT_CRM_ADD_TASK_TYPE'     => $_ARRAYLANG['TXT_CRM_EDIT_TASK_TYPE'],
                'TXT_CRM_BACK1'                 => $_ARRAYLANG['TXT_CRM_BACK1'],
                'CSRF_PARAM'                => \Cx\Core\Csrf\Controller\Csrf::param(),
                'TXT_BROWSE'                   => $_ARRAYLANG['TXT_BROWSE'],
                'TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT' => $_ARRAYLANG['TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT']
        ));

    }
    /**
     * change status of task type
     *
     * @param array $tasktypeIds asd
     * @param bool  $deactivate  dedd
     *
     * @global  object   $objDatabase
     * @global  array    $_ARRAYLANG
     * @return true
     */
    public function activateTaskTypes($tasktypeIds, $deactivate = false)
    {
        global $objDatabase,$_ARRAYLANG;

        if (!empty($tasktypeIds) && is_array($tasktypeIds)) {

            $ids = implode(',', $tasktypeIds);
            $setValue = $deactivate ? 0 : 1;
            $query = "UPDATE `".DBPREFIX."module_".$this->moduleNameLC."_task_types` SET `status` = CASE id ";
            foreach ($tasktypeIds as $count => $idValue) {
                $query .= sprintf("WHEN %d THEN $setValue ", $idValue);
            }
            $query .= "END WHERE id IN ($ids)";
            $objResult = $objDatabase->Execute($query);

            $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_TASK_TYPE_STATUS_CHANGED_SUCCESSFULLY'];

        }
    }

    /**
     * activate the task type
     *
     * @param integer $tasktypeId id
     *
     * @global object $objDatabase
     * @return true
     */
    public function activateTaskType($tasktypeId)
    {
        global $objDatabase;

        if (!$tasktypeId)
            return;

        $query = $objDatabase->Execute("UPDATE `".DBPREFIX."module_".$this->moduleNameLC."_task_types` SET `status` = IF(status = 1, 0, 1) WHERE id = $tasktypeId");

    }

    /**
     * save sorting
     *
     * @param array $tasktypeSorting id array
     *
     * @global <type> $objDatabase
     * @global <type> $_ARRAYLANG
     * @return true
     */
    public function saveSortingTaskType($tasktypeSorting)
    {
        global $objDatabase,$_ARRAYLANG;

        if (!empty($tasktypeSorting) && is_array($tasktypeSorting)) {

            $ids = implode(',', array_keys($tasktypeSorting));

            $query = "UPDATE `".DBPREFIX."module_".$this->moduleNameLC."_task_types` SET `sorting` = CASE id ";
            foreach ($tasktypeSorting as $idValue => $value ) {
                $query .= sprintf("WHEN %d THEN %d ", $idValue, $value);
            }
            $query .= "END WHERE id IN ($ids)";
            $objResult = $objDatabase->Execute($query);
        }
    }

    /**
     * delete task type
     *
     * @param array $tasktypeIds id
     *
     * @global object $objDatabase
     * @global array $_ARRAYLANG
     * @return true
     */
    public function deleteTaskTypes($tasktypeIds)
    {
        global $objDatabase,$_ARRAYLANG;

        if (!empty($tasktypeIds) && is_array($tasktypeIds)) {

            $ids = implode(',', $tasktypeIds);

            $query = "DELETE FROM `".DBPREFIX."module_".$this->moduleNameLC."_task_types` WHERE id IN ($ids) AND system_defined != 1";
            $objResult = $objDatabase->Execute($query);

            $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_TASK_TYPE_DELETED_SUCCESSFULLY'];
        }
    }

    /**
     * delete task type
     *
     * @param integer $tasktypeId Id of tasktype
     *
     * @global object $objDatabase
     * @global array $_ARRAYLANG
     * @return true
     */

    public function deleteTaskType($tasktypeId)
    {
        global $objDatabase,$_ARRAYLANG;

        $query = "DELETE FROM `".DBPREFIX."module_".$this->moduleNameLC."_task_types` WHERE id = $tasktypeId AND system_defined != 1";
        $objResult = $objDatabase->Execute($query);
        echo $_ARRAYLANG['TXT_CRM_TASK_TYPE_DELETED_SUCCESSFULLY'];
    }

    /**
     * settings general
     *
     * @global <type> $objDatabase
     * @global <type> $_ARRAYLANG
     * @return true
     */
    public function showGeneralSettings()
    {
        global $objDatabase,$_ARRAYLANG, $_CORELANG;
        $this->_objTpl->addBlockfile('CRM_SETTINGS_FILE', 'settings_block', 'module_'.$this->moduleNameLC.'_settings_general.html');
        $this->_pageTitle = $_ARRAYLANG['TXT_CRM_SETTINGS'];
        $objTpl = $this->_objTpl;
        $objTpl->hideBlock('insufficient-warning');
        if (isset($_POST['save'])) {

            $settings = array(
                    'allow_pm'                             => (!$this->isPmInstalled ? 0 : (isset($_POST['allowPm']) ? 1 : 0)),
                    'create_user_account'                  => isset($_POST['create_user_account']) ? 1 : 0,
                    'contact_amount_enabled'               => isset($_POST['contact_amount_enabled']) ? 1 : 0,
                    'customer_default_language_backend'    => isset($_POST['default_language_backend']) ? (int) $_POST['default_language_backend'] : 0,
                    'customer_default_language_frontend'   => isset($_POST['default_language_frontend']) ? (int) $_POST['default_language_frontend'] : 0,
                    'default_user_group'                   => isset($_POST['default_user_group']) ? (int) $_POST['default_user_group'] : 0,
                    'user_account_mantatory'               => isset($_POST['user_account_mantatory']) ? 1 : 0,
                    'emp_default_user_group'               => isset($_POST['emp_default_user_group']) ? (int) $_POST['emp_default_user_group'] : 0,
                    'default_country_value'                => isset($_POST['default_country_value']) ? (int) $_POST['default_country_value'] : 0,
                    'user_profile_attribute_industry_type' => isset($_POST['user_profile_attribute_industry_type']) ? $_POST['user_profile_attribute_industry_type'] : 0,
                    'user_profile_attribute_company_size'  => isset($_POST['user_profile_attribute_company_size']) ? $_POST['user_profile_attribute_company_size'] : 0,
                    'user_profile_attribute_customer_type' => isset($_POST['user_profile_attribute_customer_type']) ? $_POST['user_profile_attribute_customer_type'] : 0,
            );
            \Cx\Core\Setting\Controller\Setting::init('Crm', 'config');
            // save industry_typ and company_size user profil attribut id
            \Cx\Core\Setting\Controller\Setting::set('user_profile_attribute_industry_type', $settings['user_profile_attribute_industry_type']);
            \Cx\Core\Setting\Controller\Setting::set('user_profile_attribute_company_size', $settings['user_profile_attribute_company_size']);
            \Cx\Core\Setting\Controller\Setting::set('user_profile_attribute_customer_type', $settings['user_profile_attribute_customer_type']);
            \Cx\Core\Setting\Controller\Setting::updateAll();


            foreach ($settings as $settings_var => $settings_val) {
                $updateAllowPm = 'UPDATE '.DBPREFIX.'module_'.$this->moduleNameLC.'_settings
                                    SET `setvalue` = "'.contrexx_input2db($settings_val).'"
                                        WHERE setname = "'.$settings_var.'"';
                $objDatabase->Execute($updateAllowPm);
            }

            $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_CHANGES_UPDATED_SUCCESSFULLY'];
        }

        $settings = $this->getSettings();

        if (isset($settings['emp_default_user_group']) && !empty ($settings['emp_default_user_group'])) {
            $groupId = array();
            $groupValidation = $objDatabase->Execute("SELECT group_id FROM ".DBPREFIX."access_group_static_ids WHERE access_id = {$this->customerAccessId}");
            if ($groupValidation && $groupValidation->RecordCount() > 0) {
                while (!$groupValidation->EOF) {
                    array_push($groupId, (int) $groupValidation->fields['group_id']);
                    $groupValidation->MoveNext();
                }
            }
            if (!in_array($settings['emp_default_user_group'], $groupId)) {
                $objTpl->setVariable('CRM_INSUFFICIENT_WARNING', $_ARRAYLANG['TXT_CRM_SETTINGS_EMPLOYEE_ACCESS_ERROR']);
                $objTpl->touchBlock('insufficient-warning');
            }
        }
        if($settings['create_user_account'] == 1){
            $this->createProfilAttributes();
        }

        $frontendLangs = \FWLanguage::getActiveFrontendLanguages();
        if (!empty($frontendLangs)) { // parse frontend languages
            $objTpl->setVariable(array(
                'CRM_LANG_NAME'     => $_ARRAYLANG['TXT_CRM_STANDARD'],
                'CRM_LANG_VALUE'    => 0,
                'CRM_LANG_SELECTED' => $settings['customer_default_language_frontend'] == 0 ? "selected='selected'" : ''
            ));
            $objTpl->parse("langFrontend");
            foreach($frontendLangs as $frontendLang) {
                $objTpl->setVariable(array(
                    'CRM_LANG_NAME'     => contrexx_raw2xhtml($frontendLang['name']),
                    'CRM_LANG_VALUE'    => (int) $frontendLang['id'],
                    'CRM_LANG_SELECTED' => $settings['customer_default_language_frontend'] == $frontendLang['id'] ? "selected='selected'" : ''
                ));
                $objTpl->parse("langFrontend");
            }
        }

        $backendLangs = \FWLanguage::getActiveBackendLanguages();
        if (!empty($backendLangs)) {
            $objTpl->setVariable(array(
                'CRM_LANG_NAME'  => $_ARRAYLANG['TXT_CRM_STANDARD'],
                'CRM_LANG_VALUE' => 0,
                'CRM_LANG_SELECTED' => $settings['customer_default_language_backend'] == 0 ? "selected='selected'" : ''
            ));
            $objTpl->parse("langBackend");
            foreach ($backendLangs as $backendLang) {
                $objTpl->setVariable(array(
                    'CRM_LANG_NAME'     => contrexx_raw2xhtml($backendLang['name']),
                    'CRM_LANG_VALUE'    => (int) $backendLang['id'],
                    'CRM_LANG_SELECTED' => $settings['customer_default_language_backend'] == $backendLang['id'] ? "selected='selected'" : ''
                ));
                $objTpl->parse("langBackend");
            }
        }

        $objFWUser      = \FWUser::getFWUserObject();
        $objGroupIds    = $objFWUser->objGroup->getGroups($filter = array('is_active' => true));
        if ($objGroupIds) {
            while (!$objGroupIds->EOF) {
                $objTpl->setVariable(array(
                        'CRM_GROUP_NAME'            => contrexx_raw2xhtml($objGroupIds->getName()),
                        'CRM_GROUP_VALUE'           => (int) $objGroupIds->getId(),
                        'CRM_USER_GROUP_SELECTED'   => $settings['default_user_group'] == $objGroupIds->getId() ? "selected='selected'" : ''
                ));
                $objTpl->parse("userGroup");
                $objGroupIds->next();
            }
        }

        //show backend groups
        $objBackendGroupIds    = $objFWUser->objGroup->getGroups($filter = array('is_active' => true, 'type' => 'backend'));
        if ($objBackendGroupIds) {
            while (!$objBackendGroupIds->EOF) {
                $objTpl->setVariable(array(
                        'CRM_GROUP_NAME'            => contrexx_raw2xhtml($objBackendGroupIds->getName()),
                        'CRM_GROUP_VALUE'           => (int) $objBackendGroupIds->getId(),
                        'CRM_USER_GROUP_SELECTED'   => $settings['emp_default_user_group'] == $objBackendGroupIds->getId() ? "selected='selected'" : ''
                ));
                $objTpl->parse("empUserGroup");
                $objBackendGroupIds->next();
            }
        }
        $countries = $this->getCountry();

        foreach ($countries As $key => $value) {
            if ($settings['default_country_value'] == $value['id']) {
                $selected = "selected='selected'";
            } else {
                $selected = '';
            }
            $objTpl->setVariable(array(
                'CRM_DEFAULT_COUNTRY_ID'        => (int) $value['id'],
                'CRM_DEFAULT_COUNTRY_NAME'      => contrexx_raw2xhtml($value['name']),
                'CRM_DEFAULT_COUNTRY_SELECTED'  => $selected
            ));
            $objTpl->parse("default_country");
        }

        $objTpl->setVariable(array(
            'CRM_ALLOW_PM'                   => ($settings['allow_pm']) ? "checked='checked'" : '',
            'CRM_CREATE_ACCOUNT_USER'        => ($settings['create_user_account']) ? "checked='checked'" : '',
            'CRM_CONTACT_AMOUNT_ENABLED'     => ($settings['contact_amount_enabled']) ? "checked='checked'" : '',
            'CRM_ACCOUNT_MANTATORY'          => ($settings['user_account_mantatory']) ? "checked='checked'" : '',
        ));

        $objTpl->setVariable(array(
                'TXT_CRM_ALLOW_PM'               => $_ARRAYLANG["TXT_CRM_ALLOW_PM"],
                'TXT_CRM_DEFAULT_COUNTRY'        => $_ARRAYLANG["TXT_CRM_DEFAULT_COUNTRY"],
                'TXT_CRM_SELECT_COUNTRY'         => $_ARRAYLANG["TXT_CRM_SELECT_COUNTRY"],
                'TXT_CRM_CUSTOMERS'              => $_ARRAYLANG['TXT_CRM_CUSTOMERS'],
                'TXT_CRM_LANGUAGE'               => $_ARRAYLANG['TXT_CRM_TITLE_LANGUAGE'],
                'TXT_CRM_BACKEND'                => $_ARRAYLANG['TXT_CRM_BACKEND'],
                'TXT_CRM_FRONTEND'               => $_ARRAYLANG['TXT_CRM_FRONTEND'],
                'TXT_CRM_ALLOW_PM_EXPLANATION'   => $_ARRAYLANG["TXT_CRM_ALLOW_PM_EXPLANATION"],
                'TXT_CRM_SAVE'                   => $_ARRAYLANG['TXT_CRM_SAVE'],
                'TXT_CRM_DEFAULT_LANGUAGE'       => $_ARRAYLANG['TXT_CRM_DEFAULT_LANGUAGE'],
                'TXT_CRM_DEFAULT_USER_GROUP'     => $_ARRAYLANG['TXT_CRM_DEFAULT_USER_GROUP'],
                'TXT_CRM_CREATE_ACCOUNT_USER'    => $_ARRAYLANG['TXT_CRM_CREATE_ACCOUNT_USER'],
                'TXT_CRM_CREATE_ACCOUNT_USER_TIP'=> $_ARRAYLANG['TXT_CRM_CREATE_ACCOUNT_USER_TIP'],
                'TXT_CRM_PROFILE_ATTRIBUT_INDUSTRY_TYPE'=> $_ARRAYLANG['TXT_CRM_PROFILE_ATTRIBUT_INDUSTRY_TYPE'],
                'TXT_CRM_PROFILE_ATTRIBUT_COMPANY_SIZE'=> $_ARRAYLANG['TXT_CRM_PROFILE_ATTRIBUT_COMPANY_SIZE'],
                'TXT_CRM_PROFILE_ATTRIBUT_CUSTOMER_TYPE'=> $_ARRAYLANG['TXT_CRM_PROFILE_ATTRIBUT_CUSTOMER_TYPE'],

                'MODULE_NAME'                    => $this->moduleName,
                'TXT_CRM_NOTES'                  => $_ARRAYLANG['TXT_CRM_NOTES'],
                'TXT_CRM_GENERAL'                => $_ARRAYLANG['TXT_CRM_GENERAL'],
                'TXT_CRM_CURRENCY'               => $_ARRAYLANG['TXT_CRM_CURRENCY'],
                'TXT_CRM_CUSTOMER_TYPES'         => $_ARRAYLANG['TXT_CRM_CUSTOMER_TYPES'],
                'TXT_CRM_EMPLOYEE'               => $_ARRAYLANG['TXT_CRM_SETTINGS_EMPLOYEE'],
                'TXT_CRM_EMP_DEFAULT_USER_GROUP' => $_ARRAYLANG['TXT_CRM_EMP_DEFAULT_USER_GROUP'],
                'TXT_CRM_SETTINGS_EMP_TOOLTIP'   => $_ARRAYLANG['TXT_CRM_SETTINGS_EMPLOYEE_TOOLTIP'],
                'TXT_CRM_ACCOUNT_ARE_MANTATORY'  => $_ARRAYLANG['TXT_CRM_ACCOUNT_ARE_MANTATORY'],
                'TXT_CRM_CONTACT_AMOUNT_ENABLED' => $_ARRAYLANG['TXT_CRM_CONTACT_AMOUNT_ENABLED'],
                'CRM_PROFILE_ATTRIBUT_INDUSTRY_TYPE_DROPDOWN' =>\Html::getSelect(
                                                                'user_profile_attribute_industry_type',
                                                                \User_Profile_Attribute::getCustomAttributeNameArray(),
                                                                \Cx\Core\Setting\Controller\Setting::getValue('user_profile_attribute_industry_type', 'Crm'),
                                                                '', '', 'tabindex="0" style="width: 270px;"'),
                'CRM_PROFILE_ATTRIBUT_COMPANY_SIZE_DROPDOWN' => \Html::getSelect(
                                                                'user_profile_attribute_company_size',
                                                                \User_Profile_Attribute::getCustomAttributeNameArray(),
                                                                \Cx\Core\Setting\Controller\Setting::getValue('user_profile_attribute_company_size', 'Crm'),
                                                                '', '', 'tabindex="0" style="width: 270px;"'),
                'CRM_PROFILE_ATTRIBUT_CUSTOMER_TYPE_DROPDOWN' => \Html::getSelect(
                                                                'user_profile_attribute_customer_type',
                                                                \User_Profile_Attribute::getCustomAttributeNameArray(),
                                                                \Cx\Core\Setting\Controller\Setting::getValue('user_profile_attribute_customer_type', 'Crm'),
                                                                '', '', 'tabindex="0" style="width: 270px;"'),
        ));

        if (!$this->isPmInstalled)
            $objTpl->hideBlock('allowPmModule');
    }

    /**
     *
     * This code creates the crm setting for company size, customerType and industryType if the do not exist and
     * also creates the profile attributes and links them with the new settings in the crm
     *
     * @global <type> $_CORELANG
     * @global <type> $_ARRAYLANG
     * @autor Adrian Berger <ab@comvation.com>
     * @return void
     */
    function createProfilAttributes()
    {
        global $_CORELANG, $_ARRAYLANG;
        $objFWUser = \FWUser::getFWUserObject();
        $objUser = $objFWUser->objUser;

        $objInit = \Env::get('init');

        // save lang id and arrayLangs, because they will be replaced temporary with another language and after that
        // we need the arrays in this language, because otherwise the user gets the site in a wrong language
        $backendLangId = $objInit->backendLangId;
        $_tempCORELANG = $_CORELANG;
        $_tempARRAYLANG = $_ARRAYLANG;

        // get all languages, so we can load the placeholder for all languages
        $FWLanguages = new \FWLanguage();
        $languages = $FWLanguages->getLanguageArray();
        $attributNameAfterLang = Array(
            'TXT_CRM_COMPANY_SIZE' => 'user_profile_attribute_company_size',
            'TXT_CRM_INDUSTRY_TYPE' => 'user_profile_attribute_industry_type',
            'TXT_CRM_CUSTOMER_TYPE' => 'user_profile_attribute_customer_type',
        );
        \Cx\Core\Setting\Controller\Setting::init('Crm', 'config');
        foreach($attributNameAfterLang as $key => $attributName){

            if(!\Cx\Core\Setting\Controller\Setting::isDefined($attributName)){
                if(!$objUser->objAttribute->getAttributeIdByName($_ARRAYLANG[$key]) !== null){
                    $attribut = $objUser->objAttribute->getById(0);
                    $attribut->init();

                    $placeholderArr = Array();
                    foreach($languages as $language){
                        $objInit->backendLangId = $language["id"];
                        $langArr = $objInit->loadLanguageData();
                        $placeholderArr[$language["id"]] = $langArr[$key];
                    }

                    $attribut->setNames($placeholderArr);
                    $attribut->setType('text');
                    $attribut->setParent(0);

                    if(!$attribut->store()){
                        throw new \Cx\Modules\Crm\Controller\CrmSettingsException('Failed to create User_Profile_Attribute for '.$key);
                    }
                }
                \CX\Core\Setting\Controller\Setting::add(
                    $attributName,
                    $objUser->objAttribute->getAttributeIdByName($_ARRAYLANG[$key]),
                    false,
                    'dropdown_user_custom_attribute',
                    '',
                    'config'
                );
            }

        }

        // restore the original language settings, so the page loads in the correct language
        $objInit->backendLangId = $backendLangId;
        $_CORELANG = $_tempCORELANG;
        $_ARRAYLANG = $_tempARRAYLANG;
    }

    /**
     * settings for mail tempalte design
     *
     * @global <type> $objDatabase
     * @global <type> $_ARRAYLANG
     * @return true
     */
    function mailTemplates()
    {
        global $_CORELANG, $_ARRAYLANG;

        $_REQUEST['active_tab'] = 1;
        if (   isset($_REQUEST['act'])
            && $_REQUEST['act'] == 'mailtemplate_edit') {
            $_REQUEST['active_tab'] = 2;
        }
        \Cx\Core\MailTemplate\Controller\MailTemplate::deleteTemplate('Crm');
        // If there is anything to be stored, and if that fails, return to
        // the edit view in order to save the posted form content
        $result_store = \Cx\Core\MailTemplate\Controller\MailTemplate::storeFromPost('Crm');
        if ($result_store === false) {
            $_REQUEST['active_tab'] = 2;
        }
        $objTemplate = null;
        \Cx\Core\Setting\Controller\Setting::show_external(
            $objTemplate,
            $_CORELANG['TXT_CORE_MAILTEMPLATES'],
            \Cx\Core\MailTemplate\Controller\MailTemplate::overview('Crm', 'config',
                \Cx\Core\Setting\Controller\Setting::getValue('numof_mailtemplate_per_page_backend', 'Crm')
            )->get()
        );

        \Cx\Core\Setting\Controller\Setting::show_external(
            $objTemplate,
            (empty($_REQUEST['key'])
              ? $_CORELANG['TXT_CORE_MAILTEMPLATE_ADD']
              : $_CORELANG['TXT_CORE_MAILTEMPLATE_EDIT']),
            \Cx\Core\MailTemplate\Controller\MailTemplate::edit('Crm')->get()
        );

        \Cx\Core\Setting\Controller\Setting::show_external(
            $objTemplate,
            $_ARRAYLANG['TXT_CRM_PLACEHOLDERS'],
            $this->getCrmModulePlaceHolders()
        );

        $this->_objTpl->addBlock('CRM_MAIL_SETTINGS_FILE',
            'settings_block', $objTemplate->get());
        $this->_objTpl->touchBlock('settings_block');

    }
    /**
     * get crm module placeholders
     *
     * @return array
     */
    function getCrmModulePlaceHolders()
    {
        global $_ARRAYLANG;

        $objTemplate = new \Cx\Core\Html\Sigma(\Cx\Core\Core\Controller\Cx::instanciate()->getCodeBaseModulePath().'/'.$this->moduleName.'/View/Template/Backend');
        $objTemplate->setErrorHandling(PEAR_ERROR_DIE);
        if (!$objTemplate->loadTemplateFile('module_'.$this->moduleNameLC.'_settings_placeholders.html'))
            die("Failed to load template 'module_'.$this->moduleNameLC.'_settings_placeholders.html'");

        $objTemplate->setVariable(array(
            'TXT_CRM_PLACEHOLDERS'                  => $_ARRAYLANG['TXT_CRM_PLACEHOLDERS'],
            'TXT_CRM_GENERAL'                       => $_ARRAYLANG['TXT_CRM_GENERAL'],
            'TXT_CRM_MAIL_TEMPLATE_ONE'             => CRM_EVENT_ON_ACCOUNT_UPDATED,
            'TXT_CRM_MAIL_TEMPLATE_TWO'             => CRM_EVENT_ON_USER_ACCOUNT_CREATED,
            'TXT_CRM_MAIL_TEMPLATE_THREE'           => CRM_EVENT_ON_TASK_CREATED,
            'TXT_CRM_ASSIGNED_USER_EMAIL'           => $_ARRAYLANG['TXT_CRM_ASSIGNED_USER_EMAIL'],
            'TXT_CRM_ASSIGNED_USER_NAME'            => $_ARRAYLANG['TXT_CRM_ASSIGNED_USER_NAME'],
            'TXT_CRM_CONTACT_DETAILS_LINK'          => $_ARRAYLANG['TXT_CRM_CONTACT_DETAILS_LINK'],
            'TXT_CRM_CONTACT_DETAILS_URL'           => $_ARRAYLANG['TXT_CRM_CONTACT_DETAILS_URL'],
            'TXT_CRM_TASK_NAME'                     => $_ARRAYLANG['TXT_CRM_TASK_NAME'],
            'TXT_CRM_DOMAIN'                        => $_ARRAYLANG['TXT_CRM_DOMAIN'],
            'TXT_CRM_TASK_LINK'                     => $_ARRAYLANG['TXT_CRM_TASK_LINK'],
            'TXT_CRM_TASK_LINK_SOURCE'              => $_ARRAYLANG['TXT_CRM_TASK_LINK_SOURCE'],
            'TXT_CRM_TASK_DUE_DATE'                 => $_ARRAYLANG['TXT_CRM_TASK_DUE_DATE'],
            'TXT_CRM_TASK_CREATED_USER'             => $_ARRAYLANG['TXT_CRM_TASK_CREATED_USER'],
            'TXT_CRM_TASK_DESCRIPTION_TEXT_VERSION' => $_ARRAYLANG['TXT_CRM_TASK_DESCRIPTION_TEXT_VERSION'],
            'TXT_CRM_TASK_DESCRIPTION_HTML_VERSION' => $_ARRAYLANG['TXT_CRM_TASK_DESCRIPTION_HTML_VERSION'],
            'TXT_CRM_CONTACT_FIRSTNAME'             => $_ARRAYLANG['TXT_CRM_CONTACT_FIRSTNAME'],
            'TXT_CRM_CONTACT_LASTNAME'              => $_ARRAYLANG['TXT_CRM_CONTACT_LASTNAME'],
            'TXT_CRM_CONTACT_SALUTATION'            => $_ARRAYLANG['TXT_CRM_CONTACT_SALUTATION'],
            'TXT_CRM_CONTACT_GENDER'                => $_ARRAYLANG['TXT_CRM_CONTACT_GENDER'],
            'TXT_CRM_CUSTOMER_CONTACT_EMAIL'        => $_ARRAYLANG['TXT_CRM_CUSTOMER_CONTACT_EMAIL'],
            'TXT_CRM_CUSTOMER_COMPANY'              => $_ARRAYLANG['TXT_CRM_CUSTOMER_COMPANY'],
            'TXT_CRM_CUSTOMER_CONTACT_USER_NAME'    => $_ARRAYLANG['TXT_CRM_CUSTOMER_CONTACT_USER_NAME'],
            'TXT_CRM_CUSTOMER_CONTACT_PASSWORD'     => $_ARRAYLANG['TXT_CRM_CUSTOMER_CONTACT_PASSWORD'],
            'TXT_CRM_CONTACT_KEY'                   => $_ARRAYLANG['TXT_CRM_CONTACT_KEY'],
        ));

        return $objTemplate->get();
    }

    /**
    * show all company size
    *
    * @global array  $_ARRAYLANG
    * @global object $objDatabase
    *
    * @return null
    */
    function showCompanySize() {
        global $_ARRAYLANG, $objDatabase;

        \JS::activate("jquery");

        $subTpl = isset($_GET['subTpl']) ? $_GET['subTpl'] : '';

        $this->settingsController = new CrmSettings($this->_objTpl, $this->moduleName);

        $fields = array(
            'company_size' => isset($_POST['companySize']) ? contrexx_input2raw($_POST['companySize']) : '',
            'sorting'      => isset($_POST['sorting']) ? contrexx_input2raw($_POST['sorting']) : '',
            'status'       => isset($_POST['status']) ? 1 : (empty($_POST) ? 1 : 0)
        );

        if (!empty($subTpl)) {
            switch ($subTpl) {
                case 'modifyCompanySize':
                    $this->modifyCompanySize($fields);
                    break;
            }
            return;
        }

        //get the post values
        $action  = (isset($_REQUEST['actionType'])) ? $_REQUEST['actionType'] : '';
        $entries = (isset($_REQUEST['companySizeEntry'])) ? array_map('intval', $_REQUEST['companySizeEntry']) : 0;
        $sorting = (isset($_REQUEST['sorting'])) ? array_map('intval', $_REQUEST['sorting']) : 0;

        $id = isset($_GET['id']) ? contrexx_input2raw($_GET['id']) : 0;

        switch ($action) {
            case 'changestatus':
                $this->activateCompanySize($id);
            case 'activate':
                $this->activateCompanySize($entries);
                break;
            case 'deactivate':
                $this->activateCompanySize($entries, true);
                break;
            case 'delete':
                $companySizeIds = (isset($_GET['ajax'])) ? $id : $entries;
                $this->deleteCompanySize($companySizeIds);
                break;
            default:
                break;
        }
        if (isset($_GET['ajax']))
            exit();

        if (!empty($action) || isset($_POST['save_entries'])) {
            $this->saveSortingCompanySize($sorting);
        }

        $objTpl = $this->_objTpl;
        $objTpl->addBlockfile('CRM_SETTINGS_FILE', 'settings_block', 'module_crm_settings_company_size.html');
        $this->_pageTitle = $_ARRAYLANG['TXT_CRM_SETTINGS'];

        if (isset($_POST['save'])) {
            //insert
            $query = \SQL::insert('module_' . $this->moduleNameLC . '_company_size', $fields, array('escape' => true));
            $db = $objDatabase->Execute($query);
            if ($db) {
                $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_ENTRY_ADDED_SUCCESS'];
            } else {
                $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_CRM_ENTRY_ADDED_ERROR'];
            }
        }

        $this->getCompanySize();
        $this->parseCompanySizePlaceholders();
    }

    /**
     * update the company size
     *
     * @global object $objDatabase
     * @global array  $_ARRAYLANG
     * @param  array  $fields  post values
     *
     * @return null
     */
    function modifyCompanySize($fields) {
        global $objDatabase, $_ARRAYLANG;
        $objTpl = $this->_objTpl;
        $objTpl->addBlockfile('CRM_SETTINGS_FILE', 'settings_block', 'module_'.$this->moduleNameLC.'_settings_modify_company_size.html');

        $id = isset($_GET['id']) ? $_GET['id'] : 0;

        //Get the company size
        $this->getCompanySize($id);
        //parse the placeholders
        $this->parseCompanySizePlaceholders();
        if (isset($_POST['save'])) {
            if(!empty($id)){
                //update
                $query  = \SQL::update('module_'.$this->moduleNameLC.'_company_size', $fields, array('escape' => true)).' WHERE `id` = '.$id;
            }
            $objResult = $objDatabase->Execute($query);
            if ($objResult) {
                $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_ENTRY_UPDATED_SUCCESS'];
                \Cx\Core\Csrf\Controller\Csrf::header("location:./index.php?cmd=" . $this->moduleName . "&act=settings&tpl=companySize");
                exit();
            }else{
                $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_CRM_ENTRY_UPDATE_ERROR'];
            }
        }

    }

    /**
     * Get the all company sizes / get specific company size by id
     *
     * @global object  $objDatabase
     * @param  integer $id company size id
     *
     * @return null
     */
    function getCompanySize($id = 0) {
        global $objDatabase;

        $id = isset($_GET['id']) ? $_GET['id'] : 0;
        $where = (!empty($id)) ? 'WHERE id=' . $id : '';

        $objResult = $objDatabase->Execute('SELECT * FROM `' . DBPREFIX .
                'module_' . $this->moduleNameLC . '_company_size` '
                . $where . ' ORDER BY sorting ASC');

        $row = "row2";
        if ($objResult) {
            while (!$objResult->EOF) {
                $numberOfCompanies = $objDatabase->getOne("SELECT COUNT(id) FROM `".DBPREFIX."module_{$this->moduleNameLC}_contacts` WHERE company_size =". contrexx_raw2db($objResult->fields['id'] ));
                $this->_objTpl->setVariable(array(
                    'CRM_COMPANY_SIZE_ID'       => contrexx_raw2xhtml($objResult->fields['id']),
                    'CRM_COMPANY_SIZE'          => contrexx_raw2xhtml($objResult->fields['company_size']),
                    'CRM_COMPANY_SIZE_SORTING'  => contrexx_raw2xhtml($objResult->fields['sorting']),
                    'CRM_COMPANY_SIZE_STATUS'   => contrexx_raw2xhtml($objResult->fields['status']) ? '../core/Core/View/Media/icons/led_green.gif' : '../core/Core/View/Media/icons/led_red.gif',
                    'CRM_NUMBER_OF_COMPANIES'   => contrexx_raw2xhtml($numberOfCompanies),
                    'CRM_ROW_CLASS'             => $row = ($row == "row2" ? "row1" : "row2"),
                    'CRM_COMPANY_SIZE_STATUS_CHECKED' => $objResult->fields['status'] == 1 ? 'checked' : '',
                ));
                (empty($id)) ? $this->_objTpl->parse("showCompanySizeOverview") : '';
                $objResult->MoveNext();
            }
        }
    }

    /**
     * parse the company size place holders
     *
     * @global array $_ARRAYLANG
     */
    function parseCompanySizePlaceholders() {
        global $_ARRAYLANG;
        $this->_objTpl->setVariable(array(
            'TXT_CRM_COMPANY_SIZE'                       => $_ARRAYLANG['TXT_CRM_COMPANY_SIZE'],
            'TXT_CRM_ADD_COMPANY_SIZE'                   => $_ARRAYLANG['TXT_CRM_ADD_COMPANY_SIZE'],
            'TXT_CRM_EDIT_COMPANY_SIZE'                  => $_ARRAYLANG['TXT_CRM_EDIT_COMPANY_SIZE'],
            'TXT_CRM_NUMBER_OF_COMPANIES'                => $_ARRAYLANG['TXT_CRM_NUMBER_OF_COMPANIES'],
            'TXT_CRM_SORTING_NUMBER'                     => $_ARRAYLANG['TXT_CRM_SORTING_NUMBER'],
            'TXT_CRM_TITLEACTIVE'                        => $_ARRAYLANG['TXT_CRM_TITLEACTIVE'],
            'TXT_CRM_SAVE'                               => $_ARRAYLANG['TXT_CRM_SAVE'],
            'TXT_CRM_FUNCTIONS'                          => $_ARRAYLANG['TXT_CRM_FUNCTIONS'],
            'TXT_CRM_SORTING'                            => $_ARRAYLANG['TXT_CRM_SORTING'],
            'TXT_STATUS'                                 => $_ARRAYLANG['TXT_STATUS'],
            'TXT_CRM_CHANGE_STATUS'                      => $_ARRAYLANG['TXT_CRM_CHANGE_STATUS'],
            'TXT_CRM_SELECT_ALL'                         => $_ARRAYLANG['TXT_CRM_SELECT_ALL'],
            'TXT_CRM_REMOVE_SELECTION'                   => $_ARRAYLANG['TXT_CRM_REMOVE_SELECTION'],
            'TXT_CRM_SELECT_ACTION'                      => $_ARRAYLANG['TXT_CRM_SELECT_ACTION'],
            'TXT_CRM_ACTIVATESELECTED'                   => $_ARRAYLANG['TXT_CRM_ACTIVATESELECTED'],
            'TXT_CRM_DEACTIVATESELECTED'                 => $_ARRAYLANG['TXT_CRM_DEACTIVATESELECTED'],
            'TXT_CRM_DELETE_SELECTED'                    => $_ARRAYLANG['TXT_CRM_DELETE_SELECTED'],
            'TXT_CRM_ENTRY_DELETED_SUCCESS'              => $_ARRAYLANG['TXT_CRM_ENTRY_DELETED_SUCCESS'],
            'TXT_CRM_NOTHING_SELECTED'                   => $_ARRAYLANG['TXT_CRM_NOTHING_SELECTED'],
            'TXT_CRM_ACTIVATED_SUCCESSFULLY'             => $_ARRAYLANG['TXT_CRM_ACTIVATED_SUCCESSFULLY'],
            'TXT_CRM_DEACTIVATED_SUCCESSFULLY'           => $_ARRAYLANG['TXT_CRM_DEACTIVATED_SUCCESSFULLY'],
            'TXT_CRM_ARE_YOU_SURE_DELETE_ENTRIES'        => $_ARRAYLANG['TXT_CRM_ARE_YOU_SURE_DELETE_ENTRIES'],
            'TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT'    => $_ARRAYLANG['TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT'],
            'TXT_CRM_ARE_YOU_SURE_DELETE_SELECTED_ENTRIES'  => $_ARRAYLANG['TXT_CRM_ARE_YOU_SURE_DELETE_SELECTED_ENTRIES']
        ));
    }

}
