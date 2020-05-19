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
 * Admin Class CRM
 *
 * @category   CrmManager
 * @package    cloudrexx
 * @subpackage module_crm
 * @author     SoftSolutions4U Development Team <info@softsolutions4u.com>
 * @copyright  2012 and CLOUDREXX CMS - CLOUDREXX AG
 * @license    trial license
 * @link       www.cloudrexx.com
 */

namespace Cx\Modules\Crm\Controller;

/**
 * Admin Class CRM
 *
 * @category   CrmManager
 * @package    cloudrexx
 * @subpackage module_crm
 * @author     SoftSolutions4U Development Team <info@softsolutions4u.com>
 * @copyright  2012 and CLOUDREXX CMS - CLOUDREXX AG
 * @license    trial license
 * @link       www.cloudrexx.com
 */

class CrmManager extends CrmLibrary
{
    /**
     * Template object
     *
     * @access private
     * @var string
     */
    var $_objTpl;

    /**
     * Page title
     *
     * @access private
     * @var string
     */
    var $_pageTitle;

    /**
     *  class Javascript;
     * @var js
     */
    var $objJs = '';

    /**
     * Function Action Name
     *
     * @access private
     * @var string
     */
    public $act = '';

    /**
     * Constructor
     */

    function CrmManager()
    {
        $this->__construct();

    }

    /**
     * PHP5 constructor
     *
     * @global object $objTemplate
     * @global array $_ARRAYLANG
     */
    public function __construct($name)
    {

        global $objTemplate, $_ARRAYLANG, $objJs;
        parent::__construct($name);
        $objJs = new CrmJavascript();
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $this->_mediaPath = $cx->getWebsiteMediaCrmPath();
        $this->_objTpl = new \Cx\Core\Html\Sigma($cx->getCodeBaseModulePath().'/'.$this->moduleName.'/View/Template/Backend');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);

        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
        $this->act = $_REQUEST['act'];

        $contentNavigation = '';

        if (\Permission::checkAccess($this->customerAccessId, 'static', true)) {
            $contentNavigation .= "<a href='index.php?cmd=".$this->moduleName."&act=customers' class='".($this->act == 'customers' ? 'active' : '')."'  title='".$_ARRAYLANG['TXT_CRM_CUSTOMERS']."'>{$_ARRAYLANG
                ['TXT_CRM_CUSTOMERS']}</a>";
        }

        $contentNavigation .= "<a href='index.php?cmd=".$this->moduleName."&act=task' class='".($this->act == 'task' ? 'active' : '')."' title='{$_ARRAYLANG['TXT_CRM_TASKS']}'>{$_ARRAYLANG
                ['TXT_CRM_TASKS']}</a>
             <a href='index.php?cmd=".$this->moduleName."&act=deals' class='".($this->act == 'deals' ? 'active' : '')."' title='{$_ARRAYLANG['TXT_CRM_OPPORTUNITY']}'>{$_ARRAYLANG
                ['TXT_CRM_OPPORTUNITY']}</a>";

        if (\Permission::checkAccess($this->adminAccessId, 'static', true)) {
            $contentNavigation .= "<a href='index.php?cmd=".$this->moduleName."&act=settings' class='".(($this->act == 'settings' || $this->act == 'mailtemplate_overview' || $this->act == 'mailtemplate_edit') ? 'active' : '')."' title='".$_ARRAYLANG['TXT_CRM_SETTINGS']."'>".
                $_ARRAYLANG['TXT_CRM_SETTINGS']."</a>";
        }

        if ($objTemplate instanceof \Cx\Core\Html\Sigma) {
            $objTemplate->setVariable("CONTENT_NAVIGATION", $contentNavigation);
        }

        $dispatcher = CrmEventDispatcher::getInstance();
        $default_handler = new \Cx\Modules\Crm\Model\Events\CrmDefaultEventHandler();

        $dispatcher->addHandler(CRM_EVENT_ON_USER_ACCOUNT_CREATED, $default_handler);
        $dispatcher->addHandler(CRM_EVENT_ON_TASK_CREATED, $default_handler);
        $dispatcher->addHandler(CRM_EVENT_ON_ACCOUNT_UPDATED, $default_handler);

        $this->_initCrmModule();
    }

    /**
     * Set the backend page
     *
     * @access public
     * @global object $objTemplate
     * @global array $_ARRAYLANG
     *
     * @return null
     */
    function getPage()
    {

        global $objTemplate, $_ARRAYLANG;

        if (!isset($_GET['act'])) {
            $_GET['act']='';
        }

        switch ($_GET['act']) {
        case 'customersearch':
                $this->getCustomerSearch();
            break;
        case 'checkuseravailablity':
                $this->checkUserAvailablity();
            break;
        case 'uploadProfilePhoto':
                $this->uploadProfilePhoto();
            break;
        case 'updateProfileImage':
                $this->updateProfilePhoto();
            break;
        case 'fileupload':
                $this->uploadFiles();
            break;
        case 'getcontactdocuments':
                $this->getContactDocuments();
            break;
        case 'addcontact':
                $this->addContact();
            break;
        case 'getcustomers':
                $this->getCustomers();
            break;
        case 'autosuggest':
                $this->autoSuggest();
            break;
        case 'getdomains':
                $this->getCustomerDomains();
            break;
        case 'deals':
                $this->dealsOverview();
            break;
        case 'getcontacttasks':
                $this->getContactTasks();
            break;
        case 'getcontactprojects':
                $this->getcontactprojects();
            break;
        case 'getcontactdeals':
                $this->getContactDeals();
            break;
        case 'deleteContacts':
                $this->deleteContacts();
            break;
        case 'getlinkcontacts':
                $this->getLinkContacts();
            break;
        case 'customertooltipdetail':
                $this->customerTooltipDetail();
            break;
        case 'notesdetail':
                $this->notesDetail();
            break;
        case 'changecontactstatus':
                $this->changeCustomerContactStatus();
            break;
        case 'exportvcf':
                $this->exportVcf();
            break;
        case 'changecustomerstatus':
                $this->changeCustomerStatus();
            break;
        case 'deleteCustomers':
                $this->deleteCustomers();
            break;
        case 'customersChangeStatus':
                $this->customersChangeStatus();
            break;
        case 'mailtemplate_overview':
        case 'mailtemplate_edit':
                $_GET['tpl'] = 'mail';
        case 'settings':
                \Permission::checkAccess($this->adminAccessId, 'static');
                $this->settingsSubmenu();
            break;
        case 'managecontact':
                $this->_modifyContact();
            break;
        case 'deleteCurrency':
                $this->deleteCurrency();
            break;
        case 'editcurrency':
                $this->editCurrency();
            break;
        case 'noteschangestatus':
                $this->notesChangeStatus();
            break;
        case 'deleteCustomerTypes':
                $this->deleteCustomerTypes();
            break;
        case 'moveDocument':
                $this->moveDocumentToTarget();
            break;
        case 'getImportFilename':
                $this->getImportFilename();
            break;
        case 'export':
                $this->csvExport();
            break;
        case 'InsertCSV' :
                $this->InsertCSV();
            break;
        case 'task':
                $this->showTasks();
            break;
        case 'checkAccountId':
                $this->checkAccountId();
            break;
        case 'customers':
        default:
            if (\Permission::checkAccess($this->customerAccessId, 'static', true)) {
                $this->showCustomers();
            } else {
                $this->checkCustomerIdentity();
                \Permission::noAccess();
            }
            break;
        }

        $objTemplate->setVariable(array(
                'CONTENT_TITLE'             => isset($_SESSION['pageTitle']) ? $_SESSION['pageTitle'] : $this->_pageTitle,
                'CONTENT_OK_MESSAGE'        => isset($_SESSION['strOkMessage']) ? $_SESSION['strOkMessage'] : $this->_strOkMessage,
                'CONTENT_STATUS_MESSAGE'    => isset($_SESSION['strErrMessage']) ? $_SESSION['strErrMessage'] : $this->_strErrMessage,
                'CONTENT_WARNING_MESSAGE'   => isset($_SESSION['strWarMessage']) ? $_SESSION['strWarMessage'] : $this->_strWarMessage,
                'ADMIN_CONTENT'             => $this->_objTpl->get()
        ));
        unset($_SESSION['pageTitle']);
        unset($_SESSION['strOkMessage']);
        unset($_SESSION['strErrMessage']);
        unset($_SESSION['strWarMessage']);
    }

    /**
     * check the customer identity
     *
     * @param boolean $return boolean
     *
     * @global array $_ARRAYLANG
     * @return true
     */
    public function checkCustomerIdentity($return = false)
    {

        $customer_id = $this->getCustomerId();
        if ($customer_id) {
            if ((isset($_GET['id']) && ($_GET['id'] == $customer_id)) || (isset($_GET['id']) && in_array($_GET['id'], $this->getCustomerContacts($customer_id)))) {
                return true;
            } else {
                if ($return) {
                    return false;
                }
                \Cx\Core\Csrf\Controller\Csrf::header("Location:./index.php?cmd=".$this->moduleName."&act=customers&tpl=showcustdetail&id={$customer_id}");
                exit();
            }
        }

        return true;
    }

    /**
     * get the logged customer id
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    public function getCustomerId()
    {
        global $objDatabase;

        $objFWUser  = \FWUser::getFWUserObject();
        $userid     = $objFWUser->objUser->getId();

        $objResult = $objDatabase->selectLimit("SELECT `id` FROM `".DBPREFIX."module_{$this->moduleNameLC}_contacts` WHERE `user_account` = {$userid}", 1);
        if ($objResult && $objResult->RecordCount()) {
            return (int) $objResult->fields['id'];
        }

        return false;
    }

    /**
     * get the contacts of the given customer
     *
     * @param integer $customerId customer id
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     *
     * @return true
     */
    public function getCustomerContacts($customerId)
    {
        global $objDatabase;

        $contacts = array();
        if ($customerId) {
            $query = "SELECT `id` FROM `".DBPREFIX."module_{$this->moduleNameLC}_contacts` WHERE `contact_customer` = {$customerId}";
            $objResult = $objDatabase->Execute($query);

            if ($objResult) {
                while (!$objResult->EOF) {
                    array_push($contacts, $objResult->fields['id']);
                    $objResult->MoveNext();
                }
            }
        }

        return $contacts;
    }

    /**
     * returns the notes details of the customer
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function notesDetail()
    {
        global $_ARRAYLANG, $objDatabase, $wysiwygEditor, $FCKeditorBasePath ,$objJs;

        \JS::activate("cx");

        $json = array();
        $this->_objTpl->loadTemplateFile('module_'.$this->moduleNameLC.'_customer_notes_history.html');
        $this->_objTpl->setGlobalVariable(array(
                'MODULE_NAME' => $this->moduleName
        ));

        $custId = (isset($_REQUEST['id']))? (int) trim($_REQUEST['id']):0;
        $noteId = (isset($_GET['nid']))? (int) $_GET['nid'] : 0;  //Requset from pm module
        $projectid  = isset($_REQUEST['projectid']) ? (int) $_REQUEST['projectid'] : 0; //Requset from pm module at the time of ajax

        $intPerpage = 50;
        $intPage    = (isset($_GET['page']) ? (int) $_GET['page']-1 : 0) * $intPerpage;

        if (!empty($noteId)) {
            $filter_note_id = " AND comment.notes_type_id = ".$noteId;
        }

        if (!empty($custId)) {
            $objComment = $objDatabase->Execute("SELECT comment.id,
                                                        customer_id,
                                                        notes_type_id,
                                                        comment,
                                                        added_date,
                                                        date,
                                                        notes.icon,
                                                        notes.name AS notes,
                                                        comment.updated_on,
                                                        comment.user_id,
                                                        comment.updated_by
                                                    FROM ".DBPREFIX."module_".$this->moduleNameLC."_customer_comment AS comment
                                                    LEFT JOIN ".DBPREFIX."module_".$this->moduleNameLC."_notes AS notes
                                                       ON comment.notes_type_id = notes.id
                                                    WHERE customer_id = '$custId' $filter_note_id ORDER BY added_date DESC LIMIT $intPage, $intPerpage");
            if ($objComment->RecordCount() == 0 && $_GET['ajax'] == true) {
                $json['msg'] = '0';
            }

            if ($objComment->RecordCount() == 0) {
                $this->_objTpl->hideBlock('showComment');
                $this->_objTpl->touchBlock('noNotesEntries');
            } else {
                $this->_objTpl->touchBlock('showComment');
                $this->_objTpl->hideBlock('noNotesEntries');
            }

            $row = 'row2';
            while (!$objComment->EOF) {
                if (!empty ($objComment->fields['icon'])) {
                    $iconPath = \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesCrmWebPath().'/'.contrexx_raw2xhtml($objComment->fields['icon'])."_16X16.thumb";
                } else {
                    $iconPath  = '/modules/Crm/View/Media/customer_note.png';
                }
                $this->_objTpl->setVariable(array(
                        'TXT_COMMENT_ID'              => (int) $objComment->fields['id'],
                        'TXT_COMMENT_CUSTOMER_ID'     => (int) $objComment->fields['customer_id'],
                        'CRM_COMMENT_ADDEDDATETIME'   => date('Y-m-d h:i A', strtotime($objComment->fields['added_date'])),
                        'CRM_COMMENT_UPDATEDDATETIME' => !empty($objComment->fields['updated_on']) ? date('Y-m-d h:i A', strtotime($objComment->fields['updated_on'])) : '-',
                        'CRM_COMMENT_DATE'            => contrexx_raw2xhtml($objComment->fields['date']),
                        'CRM_NOTES_TYPE'              => contrexx_raw2xhtml($objComment->fields['notes']),
                        'CRM_NOTES_TYPE_ICON'         => $iconPath,
                        'CRM_NOTES_TYPE_ID'           => intval($objComment->fields['notes_type_id']),
                        'CRM_ADDED_USER'              => $this->getUserName($objComment->fields['user_id']),
                        'CRM_UPDATED_USER'            => $this->getUserName($objComment->fields['updated_by']),
                        'TXT_CRM_COMMENT_DESCRIPTION' => $this->stripOnlyTags($objComment->fields['comment'], '<script><iframe>', $stripContent = false),
                        'TXT_CRM_IMAGE_EDIT'          => $_ARRAYLANG['TXT_CRM_IMAGE_EDIT'],
                        'TXT_CRM_IMAGE_DELETE'        => $_ARRAYLANG['TXT_CRM_IMAGE_DELETE'],
                        'ENTRY_ROWCLASS'              => $row = ($row == 'row1') ? 'row2' : 'row1',
                        'TXT_CUST_ID'                 => $custId,
                        'CRM_REDIRECT'                => (isset($_REQUEST['design']) && $_REQUEST['design'] == 'pm') ? "&redirect=".base64_encode("./index.php?cmd=".$this->moduleName."&act=projects&tpl=showcustnotes&id={$custId}&projectid={$projectid}") : "",
                        'TXT_DISPLAY'                 => 'display: block',
                ));
                $this->_objTpl->parse('showComment');
                $objComment->MoveNext();
            }
        }
        $this->_objTpl->setGlobalVariable(array(
                'CRM_CUST_ID'                  => $custId,
                'CSRF_PARAM'                   => \Cx\Core\Csrf\Controller\Csrf::param(),
                'TXT_CRM_NO_RECORDS_FOUND'     => $_ARRAYLANG['TXT_CRM_NO_RECORDS_FOUND'],
                'TXT_CRM_NOTES_TYPE'           => $_ARRAYLANG['TXT_CRM_NOTE_TYPE'],
                'TXT_CRM_SHOW_COMMENT_HISTORY' => $_ARRAYLANG['TXT_CRM_SHOW_COMMENT_HISTORY'],
                'TXT_CRM_COMMENT_TITLE'        => $_ARRAYLANG['TXT_CRM_COMMENT_TITLE'],
                'TXT_CRM_COMMENT_DATE_TIME'    => $_ARRAYLANG['TXT_CRM_COMMENT_DATE_TIME'],
                'TXT_CRM_TASK_FUNCTIONS'       => $_ARRAYLANG['TXT_CRM_TASK_FUNCTIONS'],
                'TXT_CRM_DUE_DATE'             => $_ARRAYLANG['TXT_CRM_DUE_DATE'],
                'TXT_CRM_ADD_NOTE'             => $_ARRAYLANG['TXT_CRM_NOTES_ADD'],
                'TXT_CRM_ADDED_BY'             => $_ARRAYLANG['TXT_CRM_ADDED_BY'],
                'TXT_CRM_LAST_UPDATED_BY'      => $_ARRAYLANG['TXT_CRM_LAST_UPDATED_BY']
        ));

        if (isset($_GET['ajax'])) {
            $this->_objTpl->hideBlock("skipAjaxBlock");
            $this->_objTpl->hideBlock("skipAjaxBlock1");
        } else {
            $this->_objTpl->touchBlock("skipAjaxBlock");
            $this->_objTpl->touchBlock("skipAjaxBlock1");
        }
        $json['content'] = $this->makeLinksInTheContent($this->_objTpl->get());
        echo $result = json_encode($json);
        exit();
    }

    /**
     * Shows the Customer overview page
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function showCustomers()
    {
        global $_ARRAYLANG, $objDatabase, $objJs, $_LANGID;

        $tpl = isset ($_GET['tpl']) ? $_GET['tpl'] : '';
        if (!empty($tpl)) {
            switch($tpl) {
            case 'showcustdetail' :
                    $this->showCustomerDetail();
                break;
            case 'managecontact':
                    $this->_modifyContact();
                break;
            case 'addnote':
                    $this->_modifyNotes();
                break;
            }
            return;
        }

        \JS::activate("cx");
        \JS::activate("jqueryui");
        \JS::registerJS("modules/Crm/View/Script/main.js");
        \JS::registerJS("modules/Crm/View/Script/customerTooltip.js");
        \JS::registerCSS("modules/Crm/View/Style/customerTooltip.css");
        \JS::registerCSS("modules/Crm/View/Style/main.css");

        $this->_objTpl->loadTemplateFile('module_'.$this->moduleNameLC.'_customer_overview.html');

        $settings = $this->getSettings();

        $delValue         = isset($_GET['delId']) ? intval($_GET['delId']) : 0;
        $activeId         = isset($_GET['activeId']) ? intval($_GET['activeId']) : 0;
        $activeValue      = isset($_GET['active']) ? intval($_GET['active']) : 0;

        $this->_pageTitle = $_ARRAYLANG['TXT_CRM_CUSTOMER_OVERVIEW'];
        $this->_objTpl->setGlobalVariable('MODULE_NAME', $this->moduleName);

        if (!empty($delValue)) {
            $this->deleteCustomers();
        }
        if (!empty($activeId)) {
            $this->changeActive($activeId, $activeValue);
        }

        $mes = isset($_REQUEST['mes']) ? base64_decode($_REQUEST['mes']) : '';
        if (!empty($mes)) {
            switch($mes) {
            case "customerupdated":
                    $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_CUSTOMER_DETAILS_UPDATED_SUCCESSFULLY'];
                break;
            case "customeradded":
                    $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_CUSTOMER_ADDED_SUCCESSFULLY'];
                break;
            case "contactdeleted":
                    $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_CUSTOMER_CONTACT_DELETED_SUCCESSFULLY'];
                break;
            case "contactadded":
                    $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_CUSTOMER_CONTACT_ADDED_SUCCESSFULLY'];
                break;
            case "contactupdated":
                    $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_CUSTOMER_CONTACT_UPDATED_SUCCESSFULLY'];
                break;
            case "deleted":
                    $this->_strOkMessage  = $_ARRAYLANG['TXT_CRM_CUSTOMER_DETAILS_DELETED_SUCCESSFULLY'];
                break;
            }
        }

        $searchFields = array(
            'companyname_filter'  => isset($_REQUEST['companyname_filter']) ? contrexx_input2raw($_REQUEST['companyname_filter']) : '',
            'contactSearch'       => isset($_GET['contactSearch']) ? (array) $_GET['contactSearch'] : array(1,2),
            'advanced-search'     => isset($_REQUEST['advanced-search']) ? $_REQUEST['advanced-search'] : '',
            's_name'              => isset($_REQUEST['s_name']) ? $_REQUEST['s_name'] : '',
            's_email'             => isset($_REQUEST['s_email']) ? $_REQUEST['s_email'] : '',
            's_address'           => isset($_REQUEST['s_address']) ? $_REQUEST['s_address'] : '',
            's_city'              => isset($_REQUEST['s_city']) ? $_REQUEST['s_city'] : '',
            's_postal_code'       => isset($_REQUEST['s_postal_code']) ? $_REQUEST['s_postal_code'] : '',
            's_notes'             => isset($_REQUEST['s_notes']) ? $_REQUEST['s_notes'] : '',
            'customer_type'       => isset($_REQUEST['customer_type']) ? $_REQUEST['customer_type'] : '',
            'filter_membership'   => isset($_REQUEST['filter_membership']) ? $_REQUEST['filter_membership'] : array(),
            'term'                => isset($_REQUEST['term']) ? contrexx_input2raw($_REQUEST['term']) : '',
            'sorto'               => isset($_REQUEST['sorto']) ? $_REQUEST['sorto'] : '',
            'sortf'               => isset($_REQUEST['sortf']) ? $_REQUEST['sortf'] : 0,
        );

        $searchLink = '';

        // This is the function to show the A-Z letters
        $this->parseLetterIndexList('index.php?cmd='.$this->moduleName.'&act=customers', 'companyname_filter', $searchFields['companyname_filter']);
        $searchLink .= (!empty($searchFields['companyname_filter'])) ? "&companyname_filter=".$searchFields['companyname_filter'] : '';


        $searchContactTypeFilter = array_map('intval', array_unique($searchFields['contactSearch']));
        foreach ($searchContactTypeFilter as $value) {
                $searchLink .= "&contactSearch[]=$value";
        }

        if (isset($searchFields['advanced-search'])) {
            $searchLink .= "&s_name={$searchFields['s_name']}&s_email={$searchFields['s_email']}&s_address={$searchFields['s_address']}&s_city={$searchFields['s_city']}&s_postal_code={$searchFields['s_postal_code']}&s_notes={$searchFields['s_notes']}";
        }

        $membershipLink = '';
        foreach ($searchFields['filter_membership'] as $membershipFilter) {
            $membershipLink .= '&filter_membership[]=' . $membershipFilter;
        }

        $searchLink .= "&customer_type={$searchFields['customer_type']}&term={$searchFields['term']}" . $membershipLink;

        $sortLink = "&sorto={$searchFields['sorto']}&sortf={$searchFields['sortf']}";

        $query = $this->getContactsQuery($searchFields, $searchFields['sortf'], $searchFields['sorto']);

        /* Start Paging ------------------------------------ */
        $intPos             = (isset($_GET['pos'])) ? intval($_GET['pos']) : 0;
        $intPerPage         = $this->getPagingLimit();
        $this->_objTpl->setVariable('ENTRIES_PAGING', getPaging($this->countRecordEntries($query), $intPos, "./index.php?cmd=".$this->moduleName."&act=customers$searchLink$sortLink", false, true, $intPerPage));

        $pageLink           = "&pos=$intPos";
        /* End Paging -------------------------------------- */

        $selectLimit = " LIMIT $intPos, $intPerPage";

        $query = $query. $selectLimit;

        $objResult = $objDatabase->Execute($query);

        if ($objResult && $objResult->RecordCount() == 0) {
            $errMsg = "<div width='100%'>" . $_ARRAYLANG['TXT_CRM_CONTAINS_NO_RECORDS'] . "</div>";
            $this->_objTpl->setVariable('TXT_NORECORDFOUND_ERROR', $errMsg);
        }

        $row       = 'row2';

        $sortOrder = ($searchFields['sorto'] == 0) ? 1 : 0;
        //Apply standard values.
        $this->_objTpl->setGlobalVariable(array(
                'TXT_CRM_CUSTOMER_OVERVIEW'     => $_ARRAYLANG['TXT_CRM_CUSTOMER_OVERVIEW'],
                'TXT_DISPLAY_ENTRIES'           => 'none',
                'TXT_CRM_FILTERS'               =>  $_ARRAYLANG['TXT_CRM_FILTERS'],
                'TXT_DELETE_ENTRIES'            =>  $_ARRAYLANG['TXT_CRM_ARE_YOU_SURE_DELETE_ENTRIES'],
                'TXT_DELETE_SELECTED_ENTRIES'   =>  $_ARRAYLANG['TXT_CRM_ARE_YOU_SURE_DELETE_SELECTED_ENTRIES'],
                'TXT_CRM_TITLE_COMPANY_NAME'    =>  $_ARRAYLANG['TXT_CRM_TITLE_COMPANY_NAME'],
                'TXT_CRM_TITLE_NAME'            =>  $_ARRAYLANG['TXT_CRM_TITLE_NAME'],
                'TXT_CRM_NAME'                  =>  $_ARRAYLANG['TXT_CRM_NAME'],
                'TXT_CRM_FAMILY_NAME'           =>  $_ARRAYLANG['TXT_CRM_FAMILY_NAME'],
                'TXT_CRM_TITLE_CUSTOMERTYPE'    =>  $_ARRAYLANG['TXT_CRM_TITLE_CUSTOMERTYPE']  ,
                'TXT_CRM_TITLE_POSTAL_CODE'     =>  $_ARRAYLANG['TXT_CRM_TITLE_POSTAL_CODE']  ,
                'TXT_CUSTOMER_ID'               =>  $_ARRAYLANG['TXT_CRM_TITLE_CUSTOMERID'],
                'TXT_CRM_TITLE_TELEPHONE'       =>  $_ARRAYLANG['TXT_CRM_TITLE_TELEPHONE']  ,
                'TXT_CRM_TITLE_ADDEDDATE'       =>  $_ARRAYLANG['TXT_CRM_TITLE_ADDEDDATE']  ,
                'TXT_CRM_TITLE_ACTIVE'          =>  $_ARRAYLANG['TXT_CRM_TITLE_ACTIVE']  ,
                'TXT_CRM_TITLE_FUNCTIONS'       =>  $_ARRAYLANG['TXT_CRM_TITLE_FUNCTIONS']  ,
                'TXT_CRM_SELECT_ACTION'         =>  $_ARRAYLANG['TXT_CRM_SELECT_ACTION']  ,
                'TXT_CRM_SELECT_ALL'            =>  $_ARRAYLANG['TXT_CRM_SELECT_ALL'],
                'TXT_CRM_REMOVE_SELECTION'      =>  $_ARRAYLANG['TXT_CRM_REMOVE_SELECTION'],
                'TXT_CRM_SELECT_ACTION'         =>  $_ARRAYLANG['TXT_CRM_SELECT_ACTION'],
                'TXT_CRM_ACTIVATESELECTED'      =>  $_ARRAYLANG['TXT_CRM_ACTIVATESELECTED'],
                'TXT_CRM_DEACTIVATESELECTED'    =>  $_ARRAYLANG['TXT_CRM_DEACTIVATESELECTED'],
                'TXT_CRM_DELETE_SELECTED'       =>  $_ARRAYLANG['TXT_CRM_DELETE_SELECTED'],
                'TXT_CRM_SERVICE_PLAN'          =>  $_ARRAYLANG['TXT_CRM_SERVICE_PLAN'],
                'TXT_TICKET'                    =>  $_ARRAYLANG['TXT_TICKET'],
                'TXT_CRM_SUPPORT_CASES'         =>  $_ARRAYLANG['TXT_CRM_SUPPORT_CASES'],
                'TXT_CRM_SUPPORT_TICKET'        =>  $_ARRAYLANG['TXT_CRM_SUPPORT_TICKET'],
                'TXT_CRM_DATE'                  =>  $_ARRAYLANG['TXT_CRM_DATE'],
                'TXT_CRM_TITLE'                 =>  $_ARRAYLANG['TXT_CRM_TITLE'],
                'TXT_CRM_AMOUNT'                =>  $_ARRAYLANG['TXT_CRM_AMOUNT'],
                'TXT_CRM_DESCRIPTION'           =>  $_ARRAYLANG['TXT_CRM_DESCRIPTION'],
                'TXT_CRM_TITLE_STATUS'          =>  $_ARRAYLANG['TXT_CRM_TITLE_STATUS'],
                'TXT_CRM_HOSTING'               =>  $_ARRAYLANG['TXT_CRM_HOSTING'],
                'TXT_CRM_START_DATE'            =>  $_ARRAYLANG['TXT_CRM_START_DATE'],
                'TXT_CRM_DOMAIN'                =>  $_ARRAYLANG['TXT_CRM_DOMAIN'],
                'TXT_CRM_INVOICESENT'           =>  $_ARRAYLANG['TXT_CRM_INVOICESENT'],
                'TXT_CRM_NEXT_INVOICE'          =>  $_ARRAYLANG['TXT_CRM_NEXT_INVOICE'],
                'TXT_CRM_ISSUE_DATE'            =>  $_ARRAYLANG['TXT_CRM_ISSUE_DATE'],
                'TXT_CRM_VADIL_UNTIL'           =>  $_ARRAYLANG['TXT_CRM_VADIL_UNTIL'],
                'TXT_CRM_CASES_USED'            =>  $_ARRAYLANG['TXT_CRM_CASES_USED'],
                'TXT_CRM_CUSTOMER'              =>  $_ARRAYLANG['TXT_CRM_CUSTOMER'],
                'TXT_CRM_DOMAIN'                =>  $_ARRAYLANG['TXT_CRM_DOMAIN'],
                'TXT_CRM_PRICE'                 =>  $_ARRAYLANG['TXT_CRM_PRICE'],
                'TXT_CRM_ADDITIONAL_INFORMATION'=>  $_ARRAYLANG['TXT_CRM_ADDITIONAL_INFORMATION'],
                'TXT_STATUS'                    =>  $_ARRAYLANG['TXT_STATUS'],
                'TXT_CRM_TITLE_EMAIL'           =>  $_ARRAYLANG['TXT_CRM_TITLE_EMAIL'],
                'TXT_CRM_CUSTOMER_EXPORT'       =>  $_ARRAYLANG['TXT_CRM_EXPORT_NAME'],
                'TXT_CRM_ADD_NEW_CUSTOMER'      =>  $_ARRAYLANG['TXT_CRM_ADD_NEW_CUSTOMER'],
                'TXT_CRM_ADD_NEW_CONTACT'       =>  $_ARRAYLANG['TXT_CRM_ADD_NEW_CONTACT'],
                'TXT_CRM_CONTACTS'              =>  $_ARRAYLANG['TXT_CRM_PERSONS'],
                'TXT_CRM_CUSTOMERS'             =>  $_ARRAYLANG['TXT_CRM_TITLE_COMPANY_NAME'],
                'TXT_CRM_CUSTOMER_SEARCH_HINT'  =>  $_ARRAYLANG['TXT_CRM_CUSTOMER_SEARCH_HINT'],
                'TXT_CRM_ADVANCED_SEARCH'       =>  $_ARRAYLANG['TXT_CRM_ADVANCED_SEARCH'],
                'TXT_CRM_COMPANY_NAME'          =>  $_ARRAYLANG['TXT_CRM_TITLE_COMPANY_NAME'],
                'TXT_CRM_CONTACT_NAME'          =>  $_ARRAYLANG['TXT_CRM_CUSTOMER_CONTACT_NAME'],
                'TXT_CRM_PRIMARY_EMAIL'         =>  $_ARRAYLANG['TXT_CRM_TITLE_EMAIL'],
                'TXT_CRM_ADDRESS'               =>  $_ARRAYLANG['TXT_CRM_TITLE_ADDRESS'],
                'TXT_CRM_CITY'                  =>  $_ARRAYLANG['TXT_CRM_TITLE_CITY'],
                'TXT_CRM_CUSTOMER_TYPE'         =>  $_ARRAYLANG['TXT_CRM_CUSTOMER_TYPE'],
                'TXT_CRM_POSTAL_CODE'           =>  $_ARRAYLANG['TXT_CRM_TITLE_POSTAL_CODE'],
                'TXT_CRM_DESCRIPTION'           =>  $_ARRAYLANG['TXT_CRM_DESCRIPTION'],
                'TXT_CRM_FILTER_CUSTOMER_TYPE'  =>  $_ARRAYLANG['TXT_CRM_FILTER_CUSTOMER_TYPE'],
                'TXT_CRM_SEARCH'                =>  $_ARRAYLANG['TXT_CRM_SEARCH'],
                'TXT_CRM_FILTER_MEMBERSHIP'     =>  $_ARRAYLANG['TXT_CRM_FILTER_MEMBERSHIP'],
                'TXT_CRM_CUSTOMERID'            =>  $_ARRAYLANG['TXT_CRM_TITLE_CUSTOMERID'],
                'TXT_CRM_CUSTOMERTYPE'          =>  $_ARRAYLANG['TXT_CRM_TITLE_CUSTOMERTYPE'],
                'TXT_CRM_NOTES'                 =>  $_ARRAYLANG['TXT_CRM_COMMENT_TITLE'],
                'TXT_CRM_TASKS'                 =>  $_ARRAYLANG['TXT_CRM_TASKS'],
                'TXT_CRM_OPPURTUNITIES'         =>  $_ARRAYLANG['TXT_CRM_OPPORTUNITY'],
                'TXT_CRM_CONTACT'               =>  $_ARRAYLANG['TXT_CRM_CONTACT'],
                'TXT_CRM_ACTIVITIES'            =>  $_ARRAYLANG['TXT_CRM_ACTIVITIES'],
                'TXT_CRM_NOTHING_SELECTED'      =>  $_ARRAYLANG['TXT_CRM_NOTHING_SELECTED'],
                'TXT_CRM_SURE_TO_DELETE_SELECTED_ENTRIES'  =>  $_ARRAYLANG['TXT_CRM_SURE_TO_DELETE_SELECTED_ENTRIES'],
                'TXT_CRM_ARE_YOU_SURE_TO_DELETE_THE_ENTRY' =>  $_ARRAYLANG['TXT_CRM_ARE_YOU_SURE_TO_DELETE_THE_ENTRY'],
                'CRM_ADVANCED_SEARCH_STYLE'     =>  (isset($_GET['advanced-search'])) ? "" : "display:none;",
                'CRM_ADVANCED_SEARCH_CLASS'     =>  (isset($_GET['advanced-search'])) ? "arrow-up" : "arrow-down",
                'CRM_SEARCH_LINK'               =>  $searchLink,
                'CRM_NAME_SORT'                 =>  "&sortf=0&sorto=$sortOrder",
                'CRM_ACTIVITIES_SORT'           =>  "&sortf=1&sorto=$sortOrder",
                'CRM_DATE_SORT'                 =>  "&sortf=2&sorto=$sortOrder",
                'CRM_LAST_NAME_SORT'            =>  "&sortf=3&sorto=$sortOrder",

                'CRM_CUSTOMER_CHECKED'          =>  in_array(1, $searchContactTypeFilter) ? "checked" : '',
                'CRM_CONTACT_CHECKED'           =>  in_array(2, $searchContactTypeFilter) ? "checked" : '',
                'CRM_SEARCH_TERM'               =>  contrexx_input2xhtml($searchFields['term']),
                'CRM_SEARCH_NAME'               =>  contrexx_input2xhtml($searchFields['s_name']),
                'CRM_SEARCH_EMAIL'              =>  contrexx_input2xhtml($searchFields['s_email']),
                'CRM_SEARCH_ADDRESS'            =>  contrexx_input2xhtml($searchFields['s_address']),
                'CRM_SEARCH_CITY'               =>  contrexx_input2xhtml($searchFields['s_city']),
                'CRM_SEARCH_ZIP'                =>  contrexx_input2xhtml($searchFields['s_postal_code']),
                'CRM_SEARCH_NOTES'              =>  contrexx_input2xhtml($searchFields['s_notes']),
                'CRM_ACCESS_PROFILE_IMG_WEB_PATH'=> \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesCrmProfileWebPath(),
                'TXT_CRM_ENTER_SEARCH_TERM'     =>  $_ARRAYLANG['TXT_CRM_ENTER_SEARCH_TERM'],
                'CRM_REDIRECT_LINK'             =>  '&redirect='.base64_encode($searchLink.$sortLink.$pageLink),
        ));

        // There are two different options for filtering by name:
        // 1.) Filter first- or lastname if contact only filter is set
        // 2.) Filter first- / company-name if contacts and companies are shown
        if(
            count($searchFields['contactSearch']) == 1 &&
            in_array('2', $searchFields['contactSearch'])
        ){
            $this->_objTpl->touchBlock('contactNameFilter');
        } else {
            $this->_objTpl->touchBlock('standardNameFilter');
        }

        $this->getCustomerTypeDropDown($this->_objTpl, isset($_GET['customer_type']) ? $_GET['customer_type'] : 0, 'customerTypes', array('is_hide' => true));

        $this->membership = new \Cx\Modules\Crm\Model\Entity\Membership();
        $this->getOverviewMembershipDropdown($this->_objTpl, $this->membership, isset($_GET['filter_membership']) ? $_GET['filter_membership'] : array(), 'memberships', array('is_hide' => true));

        $this->_objTpl->setGlobalVariable('TXT_CRM_DOWNLOAD_VCARD', $_ARRAYLANG['TXT_CRM_DOWNLOAD_VCARD']);

        $row = "row2";
        $today = date('Y-m-d');
        $opLinkId = $settings['allow_pm'] ? 4 : 3; //Tab count of details page
        if ($objResult) {
            while (!$objResult->EOF) {
                $notesCount = $objDatabase->getOne("SELECT count(1) AS notesCount FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_comment` AS com WHERE com.customer_id ={$objResult->fields['id']}");
                $tasksCount = $objDatabase->getOne("SELECT count(1) AS tasksCount FROM `".DBPREFIX."module_{$this->moduleNameLC}_task` AS task WHERE task.customer_id = {$objResult->fields['id']}");
                $dealsCount = $objDatabase->getOne("SELECT count(1) AS dealsCount FROM `".DBPREFIX."module_{$this->moduleNameLC}_deals` AS deal WHERE deal.customer = {$objResult->fields['id']}");
                if ($objResult->fields['contact_type'] == 1) {
                    if (($objResult->fields['status'] == "1")) {
                        $activeImage = '../core/Core/View/Media/icons/led_green.gif';
                        $activeValue = 1;
                        $imageTitle  = $_ARRAYLANG['TXT_CRM_ACTIVE'];
                    } else {
                        $activeValue = 0;
                        $activeImage = '../core/Core/View/Media/icons/led_red.gif';
                        $imageTitle  = $_ARRAYLANG['TXT_CRM_INACTIVE'];
                    }
                    $this->_objTpl->setVariable(array(
                            'ENTRY_ID'                  => (int) $objResult->fields['id'],
                            'CRM_COMPANY_NAME'          => "<a href='?cmd=".$this->moduleName."&act=customers&tpl=showcustdetail&id={$objResult->fields['id']}' title='details'>".contrexx_raw2xhtml($objResult->fields['customer_name'])."</a>",
                            'TXT_ACTIVE_IMAGE'          => $activeImage,
                            'TXT_ACTIVE_VALUE'          => $activeValue,
                            'CRM_CUSTOMER_ID'           => contrexx_raw2xhtml($objResult->fields['customer_id']),
                            'CRM_CONTACT_PHONE'         => contrexx_raw2xhtml($objResult->fields['phone']),
                            'CRM_CONTACT_EMAIL'         => contrexx_raw2xhtml($objResult->fields['email']),
                            'CRM_ADDED_DATE'            => contrexx_raw2xhtml($objResult->fields['added_date']),
                            'CRM_ACTIVITIES_COUNT'      => $objResult->fields['activities'],
                            'CRM_CONTACT_NOTES_COUNT'   => "<a href='?cmd=".$this->moduleName."&act=customers&tpl=showcustdetail&id={$objResult->fields['id']}#notes' title=''>{$_ARRAYLANG['TXT_CRM_COMMENT_TITLE']} ({$notesCount})</a>",
                            'CRM_CONTACT_TASK_COUNT'    => "<a href='?cmd=".$this->moduleName."&act=customers&tpl=showcustdetail&id={$objResult->fields['id']}#tasks' title=''>{$_ARRAYLANG['TXT_CRM_TASKS']} ({$tasksCount})</a>",
                            'CRM_CONTACT_DEALS_COUNT'   => "<a href='?cmd=".$this->moduleName."&act=customers&tpl=showcustdetail&id={$objResult->fields['id']}#deals' title=''>{$_ARRAYLANG['TXT_CRM_OPPORTUNITY']} ({$dealsCount})</a>",
                            'CRM_CONTACT_ADDED_NEW'     => strtotime($today) == strtotime($objResult->fields['added_date']) ? '<img src="../modules/Crm/View/Media/new.png" alt="new" />' : '',
                            'CRM_ROW_CLASS'             => $row = ($row == "row2") ? "row1" : "row2",
                            'CRM_CONTACT_PROFILE_IMAGE' => !empty($objResult->fields['profile_picture']) ? contrexx_raw2xhtml($objResult->fields['profile_picture'])."_40X40.thumb" : 'profile_company_small.png',
                            'TXT_CRM_IMAGE_EDIT'        => $_ARRAYLANG['TXT_CRM_IMAGE_EDIT'],
                            'TXT_CRM_IMAGE_DELETE'      => $_ARRAYLANG['TXT_CRM_IMAGE_DELETE']
                    ));
                    $this->_objTpl->parse("showCustomers");
                    $this->_objTpl->hideBlock("showContacts");
                }

                if ($objResult->fields['contact_type'] == 2) {
                    if (($objResult->fields['status'] == "1")) {
                        $activeImage = '../core/Core/View/Media/icons/led_green.gif';
                        $activeValue = 1;
                        $imageTitle  = $_ARRAYLANG['TXT_CRM_ACTIVE'];
                    } else {
                        $activeValue = 0;
                        $activeImage = '../core/Core/View/Media/icons/led_red.gif';
                        $imageTitle  = $_ARRAYLANG['TXT_CRM_INACTIVE'];
                    }
                    $this->_objTpl->setVariable(array(
                            'ENTRY_ID'                  => (int) $objResult->fields['id'],
                            'CRM_CONTACT_NAME'          => "<a href='?cmd=".$this->moduleName."&act=customers&tpl=showcustdetail&id={$objResult->fields['id']}' title='details'>".contrexx_raw2xhtml($objResult->fields['customer_name']." ".$objResult->fields['contact_familyname']).'</a>',
                            'CRM_COMPNAY_NAME'          => (!empty($objResult->fields['contactCustomer'])) ? $_ARRAYLANG['TXT_CRM_TITLE_COMPANY_NAME']." : <a class='crm-companyInfoCardLink personPopupTrigger' href='./index.php?cmd=Crm&act=customers&tpl=showcustdetail&id={$objResult->fields['contactCustomerId']}' rel='{$objResult->fields['contactCustomerId']}' > ". contrexx_raw2xhtml($objResult->fields['contactCustomer'])."</a>" : '',
                            'TXT_ACTIVE_IMAGE'          => $activeImage,
                            'TXT_ACTIVE_VALUE'          => $activeValue,
                            'CRM_CONTACT_PHONE'         => contrexx_raw2xhtml($objResult->fields['phone']),
                            'CRM_CONTACT_EMAIL'         => contrexx_raw2xhtml($objResult->fields['email']),
                            'CRM_ADDED_DATE'            => contrexx_raw2xhtml($objResult->fields['added_date']),
                            'CRM_ACTIVITIES_COUNT'      => $objResult->fields['activities'],
                            'CRM_CONTACT_NOTES_COUNT'   => "<a href='?cmd=".$this->moduleName."&act=customers&tpl=showcustdetail&id={$objResult->fields['id']}#notes' title=''>{$_ARRAYLANG['TXT_CRM_COMMENT_TITLE']} ({$notesCount})</a>",
                            'CRM_CONTACT_TASK_COUNT'    => "<a href='?cmd=".$this->moduleName."&act=customers&tpl=showcustdetail&id={$objResult->fields['id']}#tasks' title=''>{$_ARRAYLANG['TXT_CRM_TASKS']} ({$tasksCount})</a>",
                            'CRM_CONTACT_DEALS_COUNT'   => "<a href='?cmd=".$this->moduleName."&act=customers&tpl=showcustdetail&id={$objResult->fields['id']}#deals' title=''>{$_ARRAYLANG['TXT_CRM_OPPORTUNITY']} ({$dealsCount})</a>",
                            'CRM_CONTACT_ADDED_NEW'     => strtotime($today) == strtotime($objResult->fields['added_date']) ? '<img src="../modules/Crm/View/Media/new.png" alt="new" />' : '',
                            'CRM_ROW_CLASS'             => $row = ($row == "row2") ? "row1" : "row2",
                            'CRM_CONTACT_PROFILE_IMAGE' => !empty($objResult->fields['profile_picture']) ? contrexx_raw2xhtml($objResult->fields['profile_picture'])."_40X40.thumb" : 'profile_person_small.png',
                            'TXT_CRM_IMAGE_EDIT'        => $_ARRAYLANG['TXT_CRM_IMAGE_EDIT'],
                            'TXT_CRM_IMAGE_DELETE'      => $_ARRAYLANG['TXT_CRM_IMAGE_DELETE']
                    ));
                    $this->_objTpl->parse("showContacts");
                    $this->_objTpl->hideBlock("showCustomers");
                }
                $this->_objTpl->parse("showEntries");
                $objResult->MoveNext();
            }
        }

    }

    /**
     * Shows the Customer details page
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function showCustomerDetail()
    {
        global $_ARRAYLANG, $objDatabase,$objJs, $_LANGID;

        \JS::activate("cx");
        \JS::activate("jqueryui");
        \JS::registerJS("modules/Crm/View/Script/main.js");
        \JS::registerJS("lib/javascript/jquery.ui.tabs.js");
        \JS::registerJS("modules/Crm/View/Script/customerTooltip.js");
        \JS::registerJS("lib/javascript/jquery.form.js");
        \JS::registerCSS("modules/Crm/View/Style/main.css");
        \JS::registerCSS("modules/Crm/View/Style/customerTooltip.css");
        \JS::registerJS("modules/Crm/View/Script/jquery-scrolltofixed.js");

        $objTpl = $this->_objTpl;
        $objTpl->loadTemplateFile('module_'.$this->moduleNameLC.'_customer_details.html');

        $contactId = (int) $_GET['id'];
        $settings  = $this->getSettings();
        $objTpl->setGlobalVariable(array(
                'MODULE_NAME'    => $this->moduleName,
                'PM_MODULE_NAME' => $this->pm_moduleName,
                'TXT_CRM_FUNCTIONS'  => $_ARRAYLANG['TXT_CRM_FUNCTIONS'],
                'TXT_CRM_DOWNLOAD_VCARD' => $_ARRAYLANG['TXT_CRM_DOWNLOAD_VCARD'],
                'ENTRY_ID'          => (int) $contactId,
        ));

        $mes = isset($_REQUEST['mes']) ? base64_decode($_REQUEST['mes']) : '';
        if (!empty($mes)) {
            switch($mes) {
            case "customerupdated":
                    $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_CUSTOMER_DETAILS_UPDATED_SUCCESSFULLY'];
                break;
            case "customeradded":
                    $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_CUSTOMER_ADDED_SUCCESSFULLY'];
                break;
            case "contactdeleted":
                    $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_CUSTOMER_CONTACT_DELETED_SUCCESSFULLY'];
                break;
            case "contactadded":
                    $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_CUSTOMER_CONTACT_ADDED_SUCCESSFULLY'];
                break;
            case "contactupdated":
                    $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_CUSTOMER_CONTACT_UPDATED_SUCCESSFULLY'];
                break;
            case "deleted":
                    $this->_strOkMessage  = $_ARRAYLANG['TXT_CRM_CUSTOMER_DETAILS_DELETED_SUCCESSFULLY'];
                break;
            case "projectUpdated":
                    $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_PROJECT_UPDATED_STATUS_MESSAGE'];
                break;
            case "projectAdded":
                    $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_PROJECTS_SUCESSMESSAGE'];
                break;
            case "projectDelete":
                    $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_PROJECT_DELETED_STATUS_MESSAGE'];
                break;
            case "commentAdded":
                    $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_COMMENT_SUCESSMESSAGE'];
                break;
            case "commentEdited":
                    $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_COMMENT_UPDATESUCESSMESSAGE'];
                break;
            case "dealsAdded":
                    $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_DEALS_ADDED_SUCCESSFULLY'];
                break;
            case "dealsUpdated":
                    $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_DEALS_UPDATED_SUCCESSFULLY'];
                break;
            case "CommentDelete":
                    $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_COMMENT_DELETESUCESSMESSAGE'];
                break;
            case 'taskDeleted':
                    $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_TASK_DELETE_MESSAGE'];
                break;
            }
        }

        if (isset($_SESSION['TXT_MSG_OK'])) {
            $this->_strOkMessage = $_SESSION['TXT_MSG_OK'];
            unset($_SESSION['TXT_MSG_OK']);
        }

        if (isset($_REQUEST['deleteComment'])) {
            $this->deleteCustomerComment();
        }

        if ($contactId) {
            //For Profile Photo Upload
            $options = array(
                'id' => 'proPhotoUploader',
                'upload-limit' => 1,
                'style' => 'display:none;'
            );
            $uploaderCode2 = $this->initUploader('proPhotoUploadFinished', 'proPhotoCallbackJs', array($contactId), '', $options);
            $redirectUrl = \Cx\Core\Csrf\Controller\Csrf::enhanceURI('index.php?cmd=Crm&act=getImportFilename&custId='.$contactId);
            $this->_objTpl->setVariable(array(
                'COMBO_UPLOADER_CODE2' => $uploaderCode2,
                'REDIRECT_URL'         => $redirectUrl
            ));

            //For document Upload
            $uploaderCode3 = $this->initUploader('docUploadFinished', 'docUploadCallbackJs', array($contactId), '', array('id' => 'documentUploader', 'style' => 'display:none;'));
            $this->_objTpl->setVariable(array(
                'COMBO_UPLOADER_CODE3' => $uploaderCode3
            ));

            $this->contact = new \Cx\Modules\Crm\Model\Entity\CrmContact();
            $this->contact->load($contactId);
            $custDetails = $this->contact->getCustomerDetails();

            $objMails  = $objDatabase->Execute("SELECT email, email_type FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_emails` WHERE contact_id = $contactId ORDER BY is_primary DESC, id ASC");
            if ($objMails) {
                if ($objMails->RecordCount()) {
                    $first = true;
                    while (!$objMails->EOF) {
                        if (!empty($objMails->fields['email'])) {
                            if ($first)
                                $objTpl->setVariable("CRM_CONTACT_EMAIL_PRIMARY", $this->formattedWebsite($objMails->fields['email'], 7));

                            $objTpl->setVariable("CRM_CONTACT_EMAIL", $this->formattedWebsite($objMails->fields['email'], 7)." <span class='description'>(".$_ARRAYLANG[$this->emailOptions[$objMails->fields['email_type']]].")</span>");
                            $objTpl->parse("contact_email_list");
                            $first = false;
                        }
                        $objMails->MoveNext();
                    }
                    $objTpl->setVariable("CRM_EMAIL_SHOW_ALL", $objMails->RecordCount() > 1 ? 'inline' : 'none');
                } else {
                    $objTpl->hideBlock("contactEmails");
                }
            }

            $ObjPhone = $objDatabase->Execute("SELECT phone, phone_type FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_phone` WHERE contact_id = $contactId ORDER BY is_primary DESC, id ASC");
            if ($ObjPhone) {
                if ($ObjPhone->RecordCount()) {
                    $first = true;
                    while (!$ObjPhone->EOF) {
                        if (!empty($ObjPhone->fields['phone'])) {
                            if ($first)
                                $objTpl->setVariable("CRM_CONTACT_PHONE_PRIMARY", (!empty($ObjPhone->fields['phone'])) ? contrexx_input2xhtml($ObjPhone->fields['phone']) : '');

                            $objTpl->setVariable("CRM_CONTACT_PHONE", !empty($ObjPhone->fields['phone']) ? contrexx_input2xhtml($ObjPhone->fields['phone'])." <span class='description'>(".$_ARRAYLANG[$this->phoneOptions[$ObjPhone->fields['phone_type']]].")</span>" : '');
                            $objTpl->parse('contact_phones_list');
                            $first = false;
                        }

                        $ObjPhone->MoveNext();
                    }
                    $objTpl->setVariable("CRM_PHONE_SHOW_ALL", $ObjPhone->RecordCount() > 1 ? 'inline' : 'none');
                } else {
                    $objTpl->hideBlock("contactPhones");
                }
            }

            $objWeb   = $objDatabase->Execute("SELECT url, url_profile FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_websites` WHERE contact_id = $contactId ORDER BY is_primary DESC, id ASC");
            if ($objWeb) {
                if ($objWeb->RecordCount()) {
                    $first = true;
                    while (!$objWeb->EOF) {
                        if (!empty($objWeb->fields['url'])) {
                            if ($first) {
                                $objTpl->setVariable(array(
//                                        'CRM_WEBSITE_TYPE_PRIMARY'    => !empty($objWeb->fields['url']) ? '<span class="description">('.$_ARRAYLANG[$this->websiteProfileOptions[$objWeb->fields['url_profile']]].')</span>' : '',
                                        'CRM_WEBSITE_URL_PRIMARY'     => !empty($objWeb->fields['url']) ? $this->formattedWebsite($objWeb->fields['url']) : '',
                                ));
                            }

                            $objTpl->setVariable(array(
//                                    'CRM_WEBSITE_TYPE'    => !empty($objWeb->fields['url']) ? '<span class="description">('.$_ARRAYLANG[$this->websiteProfileOptions[$objWeb->fields['url_profile']]].')</span>' : '',
                                    'CRM_WEBSITE_URL'     => !empty($objWeb->fields['url']) ? $this->formattedWebsite($objWeb->fields['url']) : '',
                            ));
                            $objTpl->parse("contact_website_list");
                            $first = false;
                        }

                        $objWeb->MoveNext();
                    }
                    $objTpl->setVariable("CRM_WEBSITE_SHOW_ALL", $objWeb->RecordCount() > 1 ? 'inline' : 'none');
                } else {
                    $objTpl->hideBlock("contactWebsite");
                }
            }

            $objWebSocial   = $objDatabase->Execute("SELECT url, url_profile FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_social_network` WHERE contact_id = $contactId ORDER BY is_primary DESC, id ASC");
            if ($objWebSocial) {
                if ($objWebSocial->RecordCount()) {
                    $first = true;
                    while (!$objWebSocial->EOF) {
                        if (!empty($objWebSocial->fields['url'])) {
                            if ($first) {
                                $objTpl->setVariable(array(
//                                        'CRM_SOCIAL_TYPE_PRIMARY'    => !empty($objWeb->fields['url']) ? '<span class="description">('.$_ARRAYLANG[$this->socialProfileOptions[$objWeb->fields['url_profile']]].')</span>' : '',
                                        'CRM_SOCIAL_URL_PRIMARY'     => !empty($objWebSocial->fields['url']) ? $this->formattedWebsite($objWebSocial->fields['url'], $objWebSocial->fields['url_profile']) : '',
                                ));
                            }
                            $objTpl->setVariable(array(
                                    'CRM_SOCIAL_TYPE'    => !empty($objWebSocial->fields['url']) ? '<span class="description">('.$_ARRAYLANG[$this->socialProfileOptions[$objWebSocial->fields['url_profile']]].')</span>' : '',
                                    'CRM_SOCIAL_URL'     => !empty($objWebSocial->fields['url']) ? $this->formattedWebsite($objWebSocial->fields['url'], $objWebSocial->fields['url_profile']) : '',
                            ));
                            $objTpl->parse("contact_social_list");
                            $first = false;
                        }
                        $objWebSocial->MoveNext();
                    }
                    $objTpl->setVariable("CRM_SOCIAL_SHOW_ALL", $objWebSocial->RecordCount() > 1 ? 'inline' : 'none');
                } else {
                    $objTpl->hideBlock("contactSocial");
                }
            }

            $objAddr  = $objDatabase->Execute("SELECT * FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_address` WHERE contact_id = $contactId ORDER BY is_primary DESC, id ASC");
            if ($objAddr) {
                if ($objAddr->RecordCount()) {
                    $first = true;
                    while (!$objAddr->EOF) {
                        if (!empty($objAddr->fields['address'])) {

                            $addrLine  = contrexx_input2xhtml($objAddr->fields['address']);
                            $addrArr   = array();
                            !empty($objAddr->fields['city']) ? $addrArr[] = (!empty($objAddr->fields['zip']) ? contrexx_input2xhtml($objAddr->fields['zip']).' ' : '').contrexx_input2xhtml($objAddr->fields['city'])  : '';
                            !empty($objAddr->fields['state']) ? $addrArr[] = contrexx_input2xhtml($objAddr->fields['state'])  : '';
                            $country   = contrexx_input2xhtml($objAddr->fields['country']);

                            $address  = '';
                            $address .= !empty($objAddr->fields['address']) ? "$addrLine<br> "  : '';
                            $address .= !empty($addrArr) ? implode('<br>', $addrArr).",<br> "  : '';
                            $address .= !empty($objAddr->fields['country']) ? "$country."  : '';
                            $addressFull = '';
                            $addressFull .= "<a class='add-map' target='_blank' href='http://maps.google.com/maps?q={$addrLine},". implode(',', $addrArr) .",{$country}'><img src='../core/Core/View/Media/icons/pin.png' title='{$_ARRAYLANG['TXT_CRM_SHOW_ON_MAP']}' alt='{$_ARRAYLANG['TXT_CRM_SHOW_ON_MAP']}'/></a>";

                            if ($first)
                                $addressFirst = $addressFull.$address;
                                $objTpl->setVariable("CRM_CONTACT_ADDRESS_PRIMARY", (!empty($addressFirst)) ? ($addressFirst) : '');

                            $addressFull .= $address."<span class='description'>(".$_ARRAYLANG[$this->addressTypes[$objAddr->fields['Address_Type']]].")</span><br>";

                            $objTpl->setVariable("CRM_CONTACT_ADDRESS", (!empty($addressFull)) ? ($addressFull) : '');
                            $objTpl->parse("contact_address_list");
                            $first = false;
                        }
                        $objAddr->MoveNext();
                    }
                    $objTpl->setVariable("CRM_ADDRESS_SHOW_ALL", $objAddr->RecordCount() > 1 ? 'inline' : 'none');
                } else {
                    $objTpl->hideBlock("contactAddresses");
                }
            }

            if (!$objAddr->RecordCount() && !$objWebSocial->RecordCount() && !$objWeb->RecordCount() && !$ObjPhone->RecordCount() && !$objMails->RecordCount()) {
                $objTpl->hideBlock("showGeneralInfo");
            }

            $objMembership = $objDatabase->Execute("SELECT
                                                            `membership_id`, msl.value AS membership
                                                         FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_membership` AS cm
                                                          LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_memberships` AS ms
                                                            ON cm.membership_id = ms.id
                                                          LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_membership_local` AS msl
                                                            ON ms.id = msl.entry_id AND lang_id = {$_LANGID}
                                                         WHERE `contact_id` = {$contactId}");
            $membershipLink = array();
            if ($objMembership) {
                while (!$objMembership->EOF) {
                    $membershipLink[] = "<a href='?cmd=".$this->moduleName."&act=customers&filter_membership[]={$objMembership->fields['membership_id']}'>". contrexx_raw2xhtml($objMembership->fields['membership']) ."</a>";
                    $objMembership->MoveNext();
                }
            }
            if ($settings['contact_amount_enabled'] && isset($custDetails['contact_amount'])) {
                $objTpl->setVariable(
                    array(
                        'CRM_CONTACT_AMOUNT' => $custDetails['contact_amount'],
                        'TXT_CRM_AMOUNT' => $_ARRAYLANG['TXT_CRM_AMOUNT'],
                    )
                );
            } else {
                $objTpl->hideBlock('contactAmount');
            }
            if (
                !empty($custDetails['updated_date']) &&
                $custDetails['updated_date'] != '0000-00-00 00:00:00'
            ) {
                $objTpl->setVariable(
                    array(
                        'CRM_CONTACT_LAST_UPDATE' => $custDetails['updated_date'],
                        'TXT_CRM_CONTACT_LAST_UPDATE' => $_ARRAYLANG['TXT_CRM_LASTUPDATE'],
                    )
                );
            } else {
                $objTpl->hideBlock('contactLastUpdate');
            }
            if (!empty($custDetails['notes'])) {
                $objTpl->setVariable(
                    array(
                        'CRM_CONTACT_NOTES' => contrexx_raw2xhtml($custDetails['notes']),
                        'TXT_CRM_CONTACT_NOTES' => $_ARRAYLANG['TXT_CRM_DESCRIPTION'],
                    )
                );
            } else {
                $objTpl->hideBlock('contactNotes');
            }
            if ($custDetails['contact_type'] == 1) {
                $custDetails['cType'] ? $objTpl->touchBlock('companyCustomerType') : $objTpl->hideBlock('companyCustomerType');
                $custDetails['industry_name'] ? $objTpl->touchBlock('companyIndustryType') : $objTpl->hideBlock('companyIndustryType');
                $membershipLink ? $objTpl->touchBlock('companyMembership') : $objTpl->hideBlock('companyMembership');

                $objTpl->setVariable(array(
                        'CRM_COMPANY_NAME'      => contrexx_raw2xhtml($custDetails['customer_name']),
                        'CRM_CUSTOMERID'        => contrexx_raw2xhtml($custDetails['customer_id']),
                        'CRM_CUSTOMER_TYPE'     => "<a title='filter' href='?cmd=".$this->moduleName."&act=customers&customer_type={$custDetails['customer_type']}'>".contrexx_raw2xhtml($custDetails['cType']).'</a>',
                        'CRM_CUSTOMER_CURRENCY' => contrexx_raw2xhtml($custDetails['currency']),
                        'CRM_INDUSTRY_TYPE'     => contrexx_raw2xhtml($custDetails['industry_name']),
                        'CRM_CUSTOMER_COMPANY_SIZE' => contrexx_raw2xhtml($custDetails['companySize']),
                        'CRM_CONTACT_PROFILE_IMAGE' => !empty($custDetails['profile_picture']) ? contrexx_raw2xhtml($custDetails['profile_picture']).".thumb" : 'profile_company_big.png',

                        'TXT_CRM_COMPANY_SIZE'        => $_ARRAYLANG['TXT_CRM_COMPANY_SIZE'],
                        'TXT_CRM_NAME'                => $_ARRAYLANG['TXT_CRM_CONTACT_NAME'],
                        'TXT_CRM_WEBSITE'             => $_ARRAYLANG['TXT_CRM_WEBSITE'],
                        'TXT_CRM_CUSTOMERTYPE'        => $_ARRAYLANG['TXT_CRM_TITLE_CUSTOMERTYPE'],
                        'TXT_CRM_CUSTOMERID'          => $_ARRAYLANG['TXT_CRM_TITLE_CUSTOMERID'],
                        'TXT_CRM_CUSTOMER_CURRENCY'   => $_ARRAYLANG['TXT_CRM_TITLE_CURRENCY'],
                ));
                $objTpl->parse("customerGeneral");
                $objTpl->hideBlock("contactGeneral");

                // Contacts Display
                $objContacts = $objDatabase->Execute("SELECT con.id,
                                                     con.contact_familyname,
                                                     con.customer_name,
                                                     con.customer_type,
                                                     con.contact_customer,
                                                     con.contact_type,
                                                     con.status,
                                                     con.added_date,
                                                     e.email,
                                                     p.phone,
                                                     l.label,
                                                     con.profile_picture
                                                     FROM `".DBPREFIX."module_{$this->moduleNameLC}_contacts` as con
                                                    LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_emails` as e
                                                    ON (e.contact_id=con.id AND e.is_primary = '1')
                                                    LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_phone` as p
                                                    ON (p.contact_id=con.id AND p.is_primary = '1')
                                                    LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_customer_types` as l
                                                    ON (l.id=con.customer_type)
                                                    WHERE contact_customer=$contactId ORDER BY con.id DESC");
                if ($objContacts) {
                    $row = 'row2';
                    while (!$objContacts->EOF) {
                        $activeImage = $objContacts->fields['status'] ? '../core/Core/View/Media/icons/led_green.gif' : '../core/Core/View/Media/icons/led_red.gif';
                        $this->_objTpl->setVariable(array(
                                'CRM_CONTACT_ID'     => (int) $objContacts->fields['id'],
                                'CUSTOMER_CONTACT_ID'=> contrexx_raw2xhtml($objContacts->fields['contact_customer']),
                                'CRM_CONTACT_CUSTOMER' => (!empty($custDetails['customer_name'])) ? "Company : <a class='crm-companyInfoCardLink personPopupTrigger' href='javascript:void(0)' rel='{$objContacts->fields['contact_customer']}' > ". contrexx_raw2xhtml($custDetails['customer_name'])."</a>" : '',
                                'CRM_CONTACT_NAME'   => "<a href='?cmd=Crm&act=customers&tpl=showcustdetail&id={$objContacts->fields['id']}'> ".contrexx_raw2xhtml($objContacts->fields['customer_name'] .' '.$objContacts->fields['contact_familyname'])."</a>",
                                'CRM_CONTACT_EMAIL'  => contrexx_raw2xhtml($objContacts->fields['email']),
                                'CRM_CONTACT_PHONE'  => contrexx_raw2xhtml($objContacts->fields['phone']),
                                'CRM_CONTACT_STATUS' => $activeImage,
                                'CRM_CONTACT_ADDED'  => $objContacts->fields['added_date'],
                                'CRM_CONTACT_TYPE'   => $objContacts->fields['label'],
                                'ROW_CLASS'          => $row = ($row == 'row2') ? 'row1': 'row2',
                                'CRM_CONTACTS_PROFILE_IMAGE'     => !empty($objContacts->fields['profile_picture']) ? contrexx_raw2xhtml($objContacts->fields['profile_picture'])."_40X40.thumb" : 'profile_person_small.png',
                                'CONTACT_REDIRECT_LINK' => '&redirect='.base64_encode("&act=customers&tpl=showcustdetail&id=$contactId"),
                        ));
                        $this->_objTpl->parse('customerContacts');
                        $objContacts->MoveNext();
                    }
                }
                if ($custDetails['contact_type'] != 1) {
                    $this->_objTpl->hideBlock("displayContacts");
                } else {
                    $objTpl->setVariable(array(
                            'TXT_CRM_CONTACT_NAME'  => $_ARRAYLANG['TXT_CRM_CONTACT_NAME'],
                            'TXT_CRM_CUSTOMERTYPE'  => $_ARRAYLANG['TXT_CRM_TITLE_CUSTOMERTYPE'],
                            'TXT_CRM_TITLE_TELEPHONE'   => $_ARRAYLANG['TXT_CRM_TITLE_TELEPHONE'],
                            'TXT_CUSTOMER_ADDEDDATE' => $_ARRAYLANG['TXT_CRM_TITLE_ADDEDDATE'],
                            'TXT_CRM_CONTACT_STATUS' => $_ARRAYLANG['TXT_CRM_CONTACT_STATUS'],
                            'TXT_CRM_ADD_CONTACT'        => $_ARRAYLANG['TXT_CRM_ADD_CONTACT']
                    ));
                    $this->_objTpl->touchBlock("displayContacts");
                }
            }
            if ($custDetails['contact_type'] == 2) {
                $custDetails['contact_role'] ? $objTpl->touchBlock("contactRole") : $objTpl->hideBlock("contactRole");
                $membershipLink ? $objTpl->touchBlock("contactMembership") : $objTpl->hideBlock("contactMembership");
                $langId = $custDetails['contact_language'];
                $langName = \FWLanguage::getLanguageParameter($langId, 'name');
                $langName ? $objTpl->touchBlock("contactLang") : $objTpl->hideBlock("contactLang");

                $objAttribute = \FWUser::getFWUserObject()->objUser->objAttribute->getById('title_' . $custDetails['salutation']);
                $salutationLabel = '';
                if (!$objAttribute->EOF) {
                    $salutationLabel = $objAttribute->getName();
                }
                $objTpl->setVariable(array(
                        'CRM_CONTACT_SALUTATION'    => $salutationLabel,
                        'CRM_CONTACT_NAME'          => contrexx_raw2xhtml($custDetails['customer_name']),
                        'CRM_CONTACT_FAMILY_NAME'   => contrexx_raw2xhtml($custDetails['contact_familyname']),
                        'CRM_CONTACT_ROLE'          => contrexx_raw2xhtml($custDetails['contact_role']),
                        'CRM_COMPNAY_NAME'          => (!empty($custDetails['contactCustomerId'])) ? "<a class='personPopupTrigger' href='?cmd=Crm&act=customers&tpl=showcustdetail&id={$custDetails['contactCustomerId']}' rel='{$custDetails['contactCustomerId']}' > ". contrexx_raw2xhtml($custDetails['contactCustomer'])."</a>" : '',
                        'CRM_CONTACT_LANGUAGE'      => contrexx_raw2xhtml($langName),
                        'CRM_CUSTOMER_CURRENCY'     => contrexx_raw2xhtml($custDetails['currency']),
                        'CRM_CONTACT_PROFILE_IMAGE' => !empty($custDetails['profile_picture']) ? contrexx_raw2xhtml($custDetails['profile_picture']).".thumb" : 'profile_person_big.png',
                        'CRM_CUSTOMERTYPE'          => "<a title='filter' href='./index.php?cmd=".$this->moduleName."&act=customers&customer_type={$custDetails['customer_type']}'>".contrexx_raw2xhtml($custDetails['cType']).'</a>',

                        'TXT_CRM_NAME'              => $_ARRAYLANG['TXT_CRM_CONTACT_NAME'],
                        'TXT_CRM_FAMILY_NAME'       => $_ARRAYLANG['TXT_CRM_FAMILY_NAME'],
                        'TXT_CRM_CONTACT_ROLE'      => $_ARRAYLANG['TXT_CRM_ROLE'],
                        'TXT_CRM_COMPNAY_NAME'      => $_ARRAYLANG['TXT_CRM_TITLE_COMPANY_NAME'],
                        'TXT_CRM_CUSTOMERTYPE'      => $_ARRAYLANG['TXT_CRM_TITLE_CUSTOMERTYPE'],
                        'TXT_CRM_CUSTOMER_CURRENCY' => $_ARRAYLANG['TXT_CRM_TITLE_CURRENCY'],
                        'TXT_CRM_CONTACT_LANGUAGE'  => $_ARRAYLANG['TXT_CRM_TITLE_LANGUAGE'],
                ));
                if (empty($custDetails['contactCustomerId'])) {
                    $objTpl->parse("contactCustomerType");
                    $objTpl->parse("contactCurrency");
                    $objTpl->hideBlock("contactCustomer");
                } else {
                    $objTpl->parse("contactCustomer");
                    $objTpl->hideBlock("contactCustomerType");
                    $objTpl->hideBlock("contactCurrency");
//                    $objTpl->touchBlock("emptyContactCurrency");
                }
                $objTpl->parse("contactGeneral");
                $objTpl->hideBlock("customerGeneral");
            }

            $objTpl->setVariable(array(
                    'CRM_CONTACT_NAME'        => ($custDetails['contact_type'] == 1) ? contrexx_raw2xhtml($custDetails['customer_name']) : contrexx_raw2xhtml($custDetails['customer_name']." ".$custDetails['contact_familyname']),
                    'EDIT_LINK'               => ($custDetails['contact_type'] != 1) ? "index.php?cmd=Crm&redirect=showcustdetail&act=customers&tpl=managecontact&amp;type=contact&amp;id=$contactId&redirect=".base64_encode("&act=customers&tpl=showcustdetail&id=$contactId") : "index.php?cmd=Crm&amp;act=customers&tpl=managecontact&amp;id=$contactId&redirect=".base64_encode("&act=customers&tpl=showcustdetail&id=$contactId"),
            ));
        }

        $objTpl->setGlobalVariable(array(
                'TXT_CRM_TASK_OPEN'           => $_ARRAYLANG['TXT_CRM_TASK_OPEN'],
                'TXT_CRM_TASK_COMPLETED'      => $_ARRAYLANG['TXT_CRM_TASK_COMPLETED'],
                'TXT_CRM_CUSTOMER_OVERVIEW'   => $_ARRAYLANG['TXT_CRM_OVERVIEW'],
                'TXT_CRM_NOTES_ADD'           => $_ARRAYLANG['TXT_CRM_NOTES_ADD'],
                'TXT_CRM_HISTROY'             => $_ARRAYLANG['TXT_CRM_HISTROY'],
                'TXT_CRM_PROFILE'             => $_ARRAYLANG['TXT_CRM_PROFILE'],
                'TXT_CRM_CONTACTS'            => $_ARRAYLANG['TXT_CRM_CONTACTS'],
                'TXT_CRM_PROFILE_INFO'        => $_ARRAYLANG['TXT_CRM_PROFILE_INFORMATION'],
                'TXT_CRM_GENERAL_INFO'        => $_ARRAYLANG['TXT_CRM_GENERAL_INFORMATION'],
                'TXT_CRM_CONTACT_EMAIL'       => $_ARRAYLANG['TXT_CRM_TITLE_EMAIL'],
                'TXT_CRM_CONTACT_PHONE'       => $_ARRAYLANG['TXT_CRM_PHONE'],
                'TXT_CRM_CONTACT_WEBSITE'     => $_ARRAYLANG['TXT_CRM_WEBSITE'],
                'TXT_CRM_SOCIAL_NETWORK'      => $_ARRAYLANG['TXT_CRM_SOCIAL_NETWORK'],
                'TXT_CRM_SALUTATION'          => $_ARRAYLANG['TXT_CRM_SALUTATION'],
                'TXT_CRM_CONTACT_ADDRESSES'   => $_ARRAYLANG['TXT_CRM_TITLE_ADDRESS'],
                'TXT_CRM_IMAGE_DELETE'        => $_ARRAYLANG['TXT_CRM_IMAGE_DELETE'],
                'TXT_CRM_IMAGE_EDIT'          => $_ARRAYLANG['TXT_CRM_IMAGE_EDIT'],
                'TXT_CRM_TASKS'               => $_ARRAYLANG['TXT_CRM_TASKS'],
                'TXT_CRM_PROJECTS'            => $_ARRAYLANG['TXT_CRM_PROJECTS'],
                'TXT_CRM_DEALS'               => $_ARRAYLANG['TXT_CRM_OPPORTUNITY'],
                'TXT_CRM_CUSTOMER_CONTACT'    => $_ARRAYLANG['TXT_CRM_CUSTOMER_CONTACT'],
                'TXT_CRM_INDUSTRY_TYPE'       => $_ARRAYLANG['TXT_CRM_INDUSTRY_TYPE'],
                'TXT_CRM_MEMBERSHIP'          => $_ARRAYLANG['TXT_CRM_MEMBERSHIP'],
                'TXT_CRM_WEBSITE'             => $_ARRAYLANG['TXT_CRM_WEBSITE'],
                'TXT_CRM_PROFILE_PHTO_TITLE'  => $_ARRAYLANG['TXT_CRM_PROFILE_PHTO_TITLE'],
                'TXT_CRM_PROFILE_PHOTO_TITLE1'=> $_ARRAYLANG['TXT_CRM_PROFILE_PHOTO_TITLE1'],
                'TXT_CRM_PROFILE_PHOTO_TITLE2'=> $_ARRAYLANG['TXT_CRM_PROFILE_PHOTO_TITLE2'],
                'TXT_CRM_PROFILE_PHOTO_DES'   => $_ARRAYLANG['TXT_CRM_PROFILE_PHOTO_DES'],
                'TXT_CRM_CHANGE_PHOTO'        => $_ARRAYLANG['TXT_CRM_CHANGE_PHOTO'],
                'TXT_CRM_DOCUMENTS'           => $_ARRAYLANG['TXT_CRM_DOCUMENTS'],
                'TXT_CRM_DOCUMENT_UPLOAD'     => $_ARRAYLANG['TXT_CRM_DOCUMENT_UPLOAD'],
                'TXT_CRM_UPLOAD_FILES'        => $_ARRAYLANG['TXT_CRM_UPLOAD_FILES'],
                'TXT_CRM_UPLOAD_FILES_DES'    => $_ARRAYLANG['TXT_CRM_UPLOAD_FILES_DES'],
                'TXT_CRM_CONFIRM_DELETE_ENTRY'=> $_ARRAYLANG['TXT_CRM_ARE_YOU_SURE_DELETE_ENTRIES'],
                'TXT_CRM_LOADING'             => $_ARRAYLANG['TXT_CRM_LOADING'],
                'TXT_CRM_TASK'                => $_ARRAYLANG['TXT_CRM_TASK'],
                'TXT_CRM_NOTES_TYPE'          => $_ARRAYLANG['TXT_CRM_NOTE_TYPE'],
                'TXT_CRM_SHOW_COMMENT_HISTORY'=> $_ARRAYLANG['TXT_CRM_SHOW_COMMENT_HISTORY'],
                'TXT_CRM_COMMENT_TITLE'       => $_ARRAYLANG['TXT_CRM_COMMENT_TITLE'],
                'TXT_CRM_COMMENT_DATE_TIME'   => $_ARRAYLANG['TXT_CRM_COMMENT_DATE_TIME'],
                'TXT_CRM_TASK_FUNCTIONS'      => $_ARRAYLANG['TXT_CRM_TASK_FUNCTIONS'],
                'TXT_CRM_DUE_DATE'            => $_ARRAYLANG['TXT_CRM_DUE_DATE'],
                'TXT_CRM_ADD_NOTE'            => $_ARRAYLANG['TXT_CRM_NOTES_ADD'],
                'TXT_CRM_ADDED_BY'            => $_ARRAYLANG['TXT_CRM_ADDED_BY'],
                'TXT_CRM_LAST_UPDATED_BY'     => $_ARRAYLANG['TXT_CRM_LAST_UPDATED_BY'],
                'TXT_CRM_TASK_STATUS'         => $_ARRAYLANG['TXT_CRM_TASK_STATUS'],
                'TXT_CRM_TASK_TITLE'          => $_ARRAYLANG['TXT_CRM_TASK_TITLE'],
                'TXT_CRM_TASK_TYPE'           => $_ARRAYLANG['TXT_CRM_TASK_TYPE'],
                'TXT_CRM_CUSTOMER_NAME'       => $_ARRAYLANG['TXT_CRM_CUSTOMER_NAME'],
                'TXT_CRM_TASK_RESPONSIBLE'    => $_ARRAYLANG['TXT_CRM_TASK_RESPONSIBLE'],
                'TXT_CRM_FUNCTIONS'           => $_ARRAYLANG['TXT_CRM_FUNCTIONS'],
                'TXT_CRM_ADD_TASK'            => $_ARRAYLANG['TXT_CRM_ADD_TASK'],
                'TXT_CRM_ACTIVE'              => $_ARRAYLANG['TXT_CRM_ACTIVE'],
                'TXT_CRM_PROJECT_ID'          => $_ARRAYLANG['TXT_CRM_PROJECT_ID'],
                'TXT_CRM_PROJECT_NAME'        => $_ARRAYLANG['TXT_CRM_PROJECT_NAME'],
                'TXT_CRM_PROJECT_QUOTED_PRICE'=> $_ARRAYLANG['TXT_CRM_PROJECT_QUOTED_PRICE'],
                'TXT_CRM_CUSTOMER_NAME'       => $_ARRAYLANG['TXT_CRM_CUSTOMER_NAME'],
                'TXT_CRM_PROJECT_STATUS'      => $_ARRAYLANG['TXT_CRM_PROJECT_STATUS'],
                'TXT_CRM_PROJECT_RESPONSIBLE' => $_ARRAYLANG['TXT_CRM_PROJECT_RESPONSIBLE'],
                'TXT_CRM_PROJECT_TARGET_DATE' => $_ARRAYLANG['TXT_CRM_PROJECT_TARGET_DATE'],
                'TXT_CRM_PROJECTS'            => $_ARRAYLANG['TXT_CRM_PROJECTS'],
                'TXT_CRM_TITLE_FUNCTIONS'     => $_ARRAYLANG['TXT_CRM_TITLE_FUNCTIONS'],
                'TXT_CRM_ADD_PROJECT'         => $_ARRAYLANG['TXT_CRM_ADD_PROJECT'],
                'TXT_CRM_DEALS_OVERVIEW'      => $_ARRAYLANG['TXT_CRM_DEALS_OVERVIEW'],
                'TXT_CRM_DEALS_PERCENTAGE'    => $_ARRAYLANG['TXT_CRM_DEALS_PERCENTAGE'],
                'TXT_CRM_DEALS_TITLE'         => $_ARRAYLANG['TXT_CRM_DEALS_TITLE'],
                'TXT_CRM_DEALS_CUSTOMER_NAME' => $_ARRAYLANG['TXT_CRM_CUSTOMER_NAME'],
                'TXT_CRM_DEALS_DUE_DATE'      => $_ARRAYLANG['TXT_CRM_DUE_DATE'],
                'TXT_CRM_DEALS_RESPONSIBLE'   => $_ARRAYLANG['TXT_CRM_PROJECT_RESPONSIBLE'],
                'TXT_CRM_OF_CONTACTS'         => $_ARRAYLANG['TXT_CRM_OF_CONTACTS'],
                'TXT_CRM_FUNCTIONS'           => $_ARRAYLANG['TXT_CRM_FUNCTIONS'],
                'TXT_CRM_ADD_OPPURTUNITY'     => $_ARRAYLANG['TXT_CRM_ADD_DEAL_TITLE'],
                'TXT_CRM_DOCUMENTS'           => $_ARRAYLANG['TXT_CRM_DOCUMENTS'],
                'TXT_CRM_DOCUMENT_NAME'       => $_ARRAYLANG['TXT_CRM_DOCUMENT_NAME'],
                'TXT_CRM_ADDED_BY'            => $_ARRAYLANG['TXT_CRM_ADDED_BY'],
                'TXT_CRM_ADDED_DATE'          => $_ARRAYLANG['TXT_CRM_ADDED_DATE'],
                'TXT_CRM_FUNCTIONS'           => $_ARRAYLANG['TXT_CRM_FUNCTIONS'],
                'TXT_CRM_ADD_DOCUMENTS'       => $_ARRAYLANG['TXT_CRM_ADD_DOCUMENTS'],
                'CRM_REDIRECT_LINK'           => '&redirect='.base64_encode("&act=customers&tpl=showcustdetail&id=$contactId"),
                'CRM_REDIRECT_LINK_PROJECT'   => "&redirect=".base64_encode("&cmd=".$this->moduleName."&act=customers&tpl=showcustdetail&id={$contactId}"),
                'CRM_ACCESS_PROFILE_IMG_WEB_PATH'   => \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesCrmProfileWebPath(),
                'CRM_CUSTOMER_MEMBERSHIP'     => implode(' , ', $membershipLink),
                'TXT_CRM_CUSTOMER_DETAILS'    =>  ($custDetails['contact_type'] == 1) ? $_ARRAYLANG['TXT_CRM_CUSTOMER_DETAILS'] : $_ARRAYLANG['TXT_CRM_CONTACT_DETAILS'],
                'CRM_ADD_CONTACT_REDIRECT'    => "&redirect=".base64_encode("&act=customers&tpl=showcustdetail&id=$contactId"),
        ));

        ($this->isPmInstalled && !empty($settings['allow_pm'])) ? $objTpl->touchBlock("contactsProjectsTab") : $objTpl->hideBlock("contactsProjectsTab");
        ($this->isPmInstalled && !empty($settings['allow_pm'])) ? $objTpl->touchBlock("contactsProjectsTabDetails") : $objTpl->hideBlock("contactsProjectsTabDetails");
        ($custDetails['contact_type'] == 1) ? $objTpl->touchBlock("contactSwitchTab") : $objTpl->hideBlock("contactSwitchTab");

        $this->_pageTitle = $custDetails['contact_type'] == 1 ? $_ARRAYLANG['TXT_CRM_CUSTOMER_DETAILS'] : $_ARRAYLANG['TXT_CRM_CONTACT_DETAILS'];
    }

    /**
     * remove the styles sheet on shadow box page
     */
    function pmRemoveStylesAddcustomer()
    {

        $style = <<<END
       <style type="text/css">
       #contrexx_header,#navigation, #footer_top, #footer, #nav_tree, .subnavbar_level1, #subnavbar_level2,
       #bottom_border {
       display: none;
       }
      </style>
END;
        return $style;
    }

    /**
     * remove the styles sheet on shadow box page
     */
    function pmRemoveStylesShowcustomers()
    {

        $style = <<<END
    <style type="text/css">
    #contrexx_header,#navigation, #footer_top, #footer, #nav_tree, .subnavbar_level1,  #bottom_border {
   display: none;
   }
   </style>
END;
        return $style;
    }

    /**
     * delete customer related details
     *
     * @global array $_ARRAYLANG
     */
    function deleteCustomers()
    {
        global $_ARRAYLANG;
        $id = intval($_GET['id']);
        $contact = new \Cx\Modules\Crm\Model\Entity\CrmContact();
        if (!empty($id)) {
            if ($contact->delete($id)) {
                $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_DELETED_SUCCESSFULLY'];
            }
        } else {
            $deleteIds = $_POST['selectedEntriesId'];
            foreach ($deleteIds as $id) {
                if ($contact->delete($id)) {
                    $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_DELETED_SUCCESSFULLY'];
                }
            }
        }
        if (isset($_GET['ajax']))
            exit();
        $message = base64_encode("deleted");
        $redirect = isset($_GET['redirect']) ? base64_decode($_GET['redirect']) : '';
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        \Cx\Core\Csrf\Controller\Csrf::header("location:".$cx->getCodeBaseOffsetPath(). $cx->getBackendFolderName()."/index.php?cmd=".$this->moduleName."&act=customers$redirect&mes=$message");
        exit();
    }

    /**
     * chnage the customer status
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function customersChangeStatus()
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;

        if(isset($_GET['status']) && isset($_GET['id'])){
            $status = ($_GET['status'] == 0) ? 1 : 0;
            $id     = $_GET['id'];
            $query = 'UPDATE '.DBPREFIX.'module_'.$this->moduleNameLC.'_contacts SET status='.$status.' WHERE id = '.$id;
            $objDatabase->Execute($query);
        }
        if ($_REQUEST['type'] == "activate") {
            $arrStatusNote = $_POST['selectedEntriesId'];
            if ($arrStatusNote != null) {
                foreach ($arrStatusNote as $noteId) {
                    $query = "UPDATE ".DBPREFIX."module_".$this->moduleNameLC."_contacts SET status='1' WHERE id=$noteId";
                    $objDatabase->Execute($query);
                }
            }
            $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_ACTIVATED_SUCCESSFULLY'];
        }
        if ($_REQUEST['type'] == "deactivate") {
            $arrStatusNote = $_POST['selectedEntriesId'];
            if ($arrStatusNote != null) {
                foreach ($arrStatusNote as $noteId) {
                    $query = "UPDATE ".DBPREFIX."module_".$this->moduleNameLC."_contacts SET status='0' WHERE id=$noteId";
                    $objDatabase->Execute($query);
                }
            }
            $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_DEACTIVATED_SUCCESSFULLY'];
        }
        $this->showCustomers();
    }

    /**
     * change the customer type status
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function customerTypeChangeStatus()
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;
        $status = ($_GET['status'] == 0) ? 1 : 0;
        $id     = $_GET['id'];
        $defaultId = $objDatabase->getOne('SELECT id FROM `'.DBPREFIX.'module_'.$this->moduleNameLC.'_customer_types` WHERE `default` = "1"');
        if (!empty($id)) {
            if ($defaultId != $id) {
                $query  = 'UPDATE '.DBPREFIX.'module_'.$this->moduleNameLC.'_customer_types SET active='.$status.' WHERE id = '.$id;
                $objDatabase->Execute($query);
                $mes = ($status) ?  'activate' : 'deactivate';
            } else {
                $mes = 'error';
            }
        }
        if ($_REQUEST['type'] == "activate") {
            $arrStatusNote = $_POST['selectedEntriesId'];
            if ($arrStatusNote != null) {
                foreach ($arrStatusNote as $noteId) {
                    $query = "UPDATE ".DBPREFIX."module_".$this->moduleNameLC."_customer_types SET active='1' WHERE id=$noteId";
                    $objDatabase->Execute($query);
                }
            }
            $mes = 'activate';
        }
        if ($_REQUEST['type'] == "deactivate") {
            $arrStatusNote = $_POST['selectedEntriesId'];
            if ($arrStatusNote != null) {
                foreach ($arrStatusNote as $noteId) {
                    if ($defaultId != $noteId) {
                        $query = "UPDATE ".DBPREFIX."module_".$this->moduleNameLC."_customer_types SET active='0' WHERE id=$noteId";
                        $objDatabase->Execute($query);
                        $mes = 'deactivate';
                    } else {
                        $mes = 'error';
                    }
                }
            }
        }
        $message = base64_encode($mes);
        \Cx\Core\Csrf\Controller\Csrf::header("Location: ./index.php?cmd=".$this->moduleName."&act=settings&tpl=customertypes&mes={$message}");

    }

    /**
     * get settings submenu
     *
     * @global array $_ARRAYLANG
     */
    function settingsSubmenu()
    {
        global $_ARRAYLANG;
        $this->_objTpl->loadTemplateFile('module_'.$this->moduleNameLC.'_settings_submenu.html', true, true);
        $this->_pageTitle = $_ARRAYLANG['TXT_CRM_SETTINGS'];

        $tpl = isset($_GET['tpl']) ? $_GET['tpl'] : '';
        $this->settingsController = new CrmSettings($this->_objTpl, $this->moduleName);

        switch ($tpl) {
        case 'interface':
                $this->showInterface();
            break;
        case 'membership':
                $this->showMembership();
            break;
        case 'companySize':
                $this->settingsController->showCompanySize();
            break;
        case 'customertypes':
                $this->settingsController->showCustomerSettings();
            break;
        case 'currencyChangeStatus':
                $this->currencyChangeStatus();
            break;
        case 'customerTypeChangeStatus':
                $this->customerTypeChangeStatus();
            break;
        case 'opstages':
                $this->showOpportunityStages();
            break;
        case 'currency':
                $this->settingsController->currencyoverview();
            break;
        case 'notes':
                $this->notesOverview();
            break;
        case 'tasktypes':
                $this->settingsController->taskTypesoverview();
            break;
        case 'mail':
                $this->settingsController->mailTemplates();
            break;
        case 'industry':
                $this->showIndustry();
            break;
        default:
                $tpl = "overview";
                $this->settingsController->showGeneralSettings();
            break;
        }

        $this->_objTpl->setVariable(array(
                'MODULE_NAME'                    => $this->moduleName,
                'TXT_CRM_NOTES'                  => $_ARRAYLANG['TXT_CRM_NOTES'],
                'TXT_CRM_GENERAL'                => $_ARRAYLANG['TXT_CRM_GENERAL'],
                'TXT_CRM_CURRENCY'               => $_ARRAYLANG['TXT_CRM_CURRENCY'],
                'TXT_CRM_TASK_TYPES'             => $_ARRAYLANG['TXT_CRM_TASK_TYPES'],
                'TXT_CRM_CUSTOMER_TYPES'         => $_ARRAYLANG['TXT_CRM_CUSTOMER_TYPES'],
                'TXT_CRM_SUCCESS_RATE'           => $_ARRAYLANG['TXT_CRM_SUCCESS_RATE'],
                'TXT_CRM_DEALS_STAGES'           => $_ARRAYLANG['TXT_CRM_DEALS_STAGES'],
                'TXT_CRM_CUSTOMER_INDUSTRY'      => $_ARRAYLANG['TXT_CRM_CUSTOMER_INDUSTRY'],
                'TXT_CRM_COMPANY_SIZE'           => $_ARRAYLANG['TXT_CRM_COMPANY_SIZE'],
                'TXT_CRM_MAIL_TEMPLATE'          => $_ARRAYLANG['TXT_CRM_MAIL_TEMPLATE'],
                'TXT_CRM_INTERFACE'              => $_ARRAYLANG['TXT_CRM_INTERFACE'],
                'TXT_CRM_CUSTOMER_MEMBERSHIP'    => $_ARRAYLANG['TXT_CRM_CUSTOMER_MEMBERSHIP'],
                strtoupper($tpl)."_ACTIVE"       => 'active'
        ));
    }

    /**
     * settings success rate page
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function showSuccessRate()
    {
        global $_ARRAYLANG, $objDatabase ,$objJs;

        \JS::activate('jquery');
        $objTpl = $this->_objTpl;
        $objTpl->addBlockfile('CRM_SETTINGS_FILE', 'settings_block', 'module_'.$this->moduleNameLC.'_settings_success_rate.html');
        $this->_pageTitle = $_ARRAYLANG['TXT_CRM_SETTINGS'];
        $objTpl->setGlobalVariable("MODULE_NAME", $this->moduleName);

        $fn = isset($_GET['fn']) ? $_GET['fn'] : '';
        switch ($fn) {
        case 'modify':
            $this->saveSuccessRate();
            if (isset($_GET['ajax']))
                exit();
            break;
        default:
            break;
        }

        $action = (isset ($_REQUEST['actionType'])) ? $_REQUEST['actionType'] : '';
        $successEntries = (isset($_REQUEST['successEntry'])) ? array_map('intval', $_REQUEST['successEntry']) : 0;
        $successEntriesorting = (isset($_REQUEST['sorting'])) ? array_map('intval', $_REQUEST['sorting']) : 0;

        switch ($action) {
        case 'changestatus':
            $this->activateSuccessRate((array) $_GET['id']);
            if (isset($_GET['ajax']))
                exit();
        case 'activate':
            $this->activateSuccessRate($successEntries);
            break;
        case 'deactivate':
            $this->activateSuccessRate($successEntries, true);
            break;
        case 'delete':
            $this->deleteSuccessRates($successEntries);
            break;
        case 'deletesuccessrate':
            $this->deleteSuccessRate();
            if (isset($_GET['ajax']))
                exit();
            break;
        default:
            break;
        }
        if (!empty ($action) || isset($_POST['save_entries'])) {
            $this->saveSuccessRate($successEntrySorting);
        }

        $label  = isset ($_POST['label']) ? contrexx_input2raw(trim($_POST['label'])) : '';
        $rate   = isset ($_POST['rate']) ? contrexx_input2raw(trim($_POST['rate'])) : '';
        $status = isset ($_POST['status']) ? 1 : (isset($_POST['add_rate']) ? 0 : 1);
        if (isset($_POST['add_rate'])) {
            if (!empty($label) && !empty($rate)) {
                $query = "INSERT INTO `".DBPREFIX."module_{$this->moduleNameLC}_success_rate`
                                    SET label   = '".contrexx_raw2db($label)."',
                                        rate    = '".contrexx_raw2db($rate)."',
                                        status  =  $status,
                                        sorting = 0";
                $db = $objDatabase->Execute($query);
                $label = '';
                $rate = '';
                $status = 0;
                if ($db)
                    $this->_strOkMessage = "Success probability added successfully";
                else
                    $this->_strErrMessage = "Error in saving Record";
            } else {
                $this->_strErrMessage = "All values must be filled out";
            }
        }

        $objResult = $objDatabase->Execute("SELECT * FROM `".DBPREFIX."module_{$this->moduleNameLC}_success_rate` ORDER BY sorting ASC");

        $row = "row2";
        if ($objResult) {
            while (!$objResult->EOF) {
                $objTpl->setVariable(array(
                        'ENTRY_ID'          => (int) $objResult->fields['id'],
                        'CRM_LABEL'         => contrexx_raw2xhtml($objResult->fields['label']),
                        'CRM_SORTING'       => contrexx_raw2xhtml($objResult->fields['sorting']),
                        'CRM_SUCCESS_RATE'  => contrexx_raw2xhtml($objResult->fields['rate']),
                        'CRM_SUCCESS_STATUS'=> $objResult->fields['status'] ? '../core/Core/View/Media/icons/led_green.gif' : '../core/Core/View/Media/icons/led_red.gif',
                        'ROW_CLASS'         => $row = ($row == "row2" ? "row1" : "row2"),
                ));
                $objTpl->parse("successRateEntries");
                $objResult->MoveNext();
            }
        }

        $objTpl->setVariable(array(
            'TXT_STATUS'                => $_ARRAYLANG['TXT_STATUS'],
            'TXT_CRM_LABEL'             => $_ARRAYLANG['TXT_CRM_LABEL'],
            'TXT_CRM_ADD_RATE'          => $_ARRAYLANG['TXT_CRM_ADD_RATE'],
            'TXT_CRM_SAVE'              => $_ARRAYLANG['TXT_CRM_SAVE'],
            'TXT_CRM_SUCCESS_RATES'     => $_ARRAYLANG['TXT_CRM_SUCCESS_RATES'],
            'TXT_CRM_SORTING'           => $_ARRAYLANG['TXT_CRM_SORTING'],
            'TXT_CRM_FUNCTIONS'         => $_ARRAYLANG['TXT_CRM_FUNCTIONS'],
            'TXT_CRM_SELECT_ALL'        => $_ARRAYLANG['TXT_CRM_SELECT_ALL'],
            'TXT_CRM_REMOVE_SELECTION'  => $_ARRAYLANG['TXT_CRM_REMOVE_SELECTION'],
            'TXT_CRM_SELECT_ACTION'     => $_ARRAYLANG['TXT_CRM_SELECT_ACTION'],
            'TXT_CRM_ACTIVATESELECTED'  => $_ARRAYLANG['TXT_CRM_ACTIVATESELECTED'],
            'TXT_CRM_DEACTIVATESELECTED'=> $_ARRAYLANG['TXT_CRM_DEACTIVATESELECTED'],
            'TXT_CRM_DELETE_SELECTED'   => $_ARRAYLANG['TXT_CRM_DELETE_SELECTED'],
            'TXT_CRM_NOTHING_SELECTED'  => $_ARRAYLANG['TXT_CRM_NOTHING_SELECTED'],
            'TXT_CRM_SURE_TO_DELETE_SELECTED_ENTRIES'   => $_ARRAYLANG['TXT_CRM_SURE_TO_DELETE_SELECTED_ENTRIES'],
            'TXT_CRM_ARE_YOU_SURE_TO_DELETE_THE_ENTRY'  => $_ARRAYLANG['TXT_CRM_ARE_YOU_SURE_TO_DELETE_THE_ENTRY'],
            'CRM_RATE_LABEL'            => contrexx_raw2xhtml($label),
            'CRM_RATE_VALUE'            => contrexx_raw2xhtml($rate),
            'CRM_RATE_CHECKED'          => ($status) ? 'checked' : ''
        ));

    }

    /**
     * store the success rate
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function saveSuccessRate()
    {
        global $objDatabase;

        // New update Query
        $idArr = array_map(intval, array_keys($_POST['sorting']));
        $ids = implode(',', $idArr);

        $query = "UPDATE ".DBPREFIX."module_".$this->moduleNameLC."_success_rate SET `sorting` = CASE id ";
        foreach ($_POST['sorting'] as $id => $val) {
            $query .= sprintf(" WHEN %d THEN %d", (int) $id, $val);
        }

        $query .= " END,
                            `label` = CASE id ";
        foreach ($_POST['label'] as $id => $val) {
            $query .= sprintf(" WHEN %d THEN '%s'", (int) $id, contrexx_input2db($val));
        }
        $query .= " END,
                            `rate` = CASE id ";
        foreach ($_POST['rate'] as $id => $val) {
            $query .= sprintf(" WHEN %d THEN '%s'", (int) $id, contrexx_input2db($val));
        }
        $query .= " END WHERE id IN ($ids)";
        $db = $objDatabase->Execute($query);

    }

    /**
     * show opportunity stages
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function showOpportunityStages()
    {
        global $_ARRAYLANG, $objDatabase ,$objJs;

        \JS::activate('jquery');
        $objTpl = $this->_objTpl;
        $objTpl->addBlockfile('CRM_SETTINGS_FILE', 'settings_block', 'module_'.$this->moduleNameLC.'_settings_stages.html');
        $this->_pageTitle = $_ARRAYLANG['TXT_CRM_SETTINGS'];
        $objTpl->setGlobalVariable("MODULE_NAME", $this->moduleName);

        $fn = isset($_GET['fn']) ? $_GET['fn'] : '';
        switch ($fn) {
        case 'modify':
            $this->saveStage();
            if (isset($_GET['ajax']))
                exit();
            break;
        default:
            break;
        }

        $action = (isset ($_REQUEST['actionType'])) ? $_REQUEST['actionType'] : '';
        $stageEntries = (isset($_REQUEST['stageEntry'])) ? array_map('intval', $_REQUEST['stageEntry']) : 0;
        $stageEntriesorting = (isset($_REQUEST['sorting'])) ? array_map('intval', $_REQUEST['sorting']) : 0;

        switch ($action) {
        case 'changestatus':
            $this->activateStage((array) $_GET['id']);
            if (isset($_GET['ajax']))
                exit();
        case 'activate':
            $this->activateStage($stageEntries);
            break;
        case 'deactivate':
            $this->activateStage($stageEntries, true);
            break;
        case 'delete':
            $this->deleteStages($stageEntries);
            break;
        case 'deletestage':
            $this->deleteStage();
            if (isset($_GET['ajax']))
                exit();
            break;
        default:
            break;
        }
        if (isset($_POST['save_entries'])) {
            $this->saveStageSorting($stageEntriesorting);
        }

        $label  = isset ($_POST['label']) ? contrexx_input2raw(trim($_POST['label'])) : '';
        $stage   = isset ($_POST['stage']) ? contrexx_input2raw(trim($_POST['stage'])) : '';
        $status = isset ($_POST['status']) ? 1 : (isset($_POST['add_stage']) ? 0 : 1);
        if (isset($_POST['add_stage'])) {
            if (!empty($label) && !empty($stage)) {
                $query = "INSERT INTO `".DBPREFIX."module_{$this->moduleNameLC}_stages`
                                    SET label   = '".contrexx_raw2db($label)."',
                                        stage    = '".contrexx_raw2db($stage)."',
                                        status  =  $status,
                                        sorting = 0";
                $db = $objDatabase->Execute($query);
                $label = '';
                $stage = '';
                $status = 1;
                if ($db)
                    $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_STAGES_SAVED'];
                else
                    $this->_strErrMessage = "Error in saving Record";
            } else {
                $this->_strErrMessage = "All values must be filled out";
            }
        }

        $objResult = $objDatabase->Execute("SELECT * FROM `".DBPREFIX."module_{$this->moduleNameLC}_stages` ORDER BY sorting ASC");

        $row = "row2";
        if ($objResult) {
            while (!$objResult->EOF) {
                $objTpl->setVariable(array(
                        'ENTRY_ID'          => (int) $objResult->fields['id'],
                        'CRM_LABEL'         => contrexx_raw2xhtml($objResult->fields['label']),
                        'CRM_SORTING'       => contrexx_raw2xhtml($objResult->fields['sorting']),
                        'CRM_STAGE'         => contrexx_raw2xhtml($objResult->fields['stage']),
                        'CRM_STAGE_STATUS'  => $objResult->fields['status'] ? '../core/Core/View/Media/icons/led_green.gif' : '../core/Core/View/Media/icons/led_red.gif',
                        'ROW_CLASS'         => $row = ($row == "row2" ? "row1" : "row2"),
                ));
                $objTpl->parse("stageEntries");
                $objResult->MoveNext();
            }
        }

        $objTpl->setGlobalVariable(array(
            'TXT_CRM_IMAGE_EDIT'         => $_ARRAYLANG['TXT_CRM_IMAGE_EDIT'],
            'TXT_IMAGE_SAVE'             => $_ARRAYLANG['TXT_CRM_SAVE'],
            'TXT_CRM_IMAGE_DELETE'       => $_ARRAYLANG['TXT_CRM_IMAGE_DELETE'],
        ));

        $objTpl->setVariable(array(
                'TXT_STATUS'                        => $_ARRAYLANG['TXT_STATUS'],
                'TXT_CRM_LABEL'                     => $_ARRAYLANG['TXT_CRM_LABEL'],
                'TXT_CRM_ADD_STAGE'                 => $_ARRAYLANG['TXT_CRM_ADD_STAGE'],
                'TXT_CRM_SAVE'                      => $_ARRAYLANG['TXT_CRM_SAVE'],
                'TXT_CRM_DEALS_STAGES'              => $_ARRAYLANG['TXT_CRM_DEALS_STAGES'],
                'TXT_CRM_DEALS_STAGE'               => $_ARRAYLANG['TXT_CRM_DEALS_STAGE'],
                'TXT_CRM_SORTING'                   => $_ARRAYLANG['TXT_CRM_SORTING'],
                'TXT_CRM_FUNCTIONS'                 => $_ARRAYLANG['TXT_CRM_FUNCTIONS'],
                'TXT_CRM_SELECT_ALL'                => $_ARRAYLANG['TXT_CRM_SELECT_ALL'],
                'TXT_CRM_REMOVE_SELECTION'          => $_ARRAYLANG['TXT_CRM_REMOVE_SELECTION'],
                'TXT_CRM_SELECT_ACTION'             => $_ARRAYLANG['TXT_CRM_SELECT_ACTION'],
                'TXT_CRM_ACTIVATESELECTED'          => $_ARRAYLANG['TXT_CRM_ACTIVATESELECTED'],
                'TXT_CRM_DEACTIVATESELECTED'        => $_ARRAYLANG['TXT_CRM_DEACTIVATESELECTED'],
                'TXT_CRM_DELETE_SELECTED'           => $_ARRAYLANG['TXT_CRM_DELETE_SELECTED'],
                'CRM_STAGE_LABEL'                   => contrexx_raw2xhtml($label),
                'CRM_STAGE_VALUE'                   => contrexx_raw2xhtml($stage),
                'CRM_STAGE_CHECKED'                 => ($status) ? 'checked' : '',
                'TXT_PRODUCTS_SELECT_ENTRIES'       => $_ARRAYLANG['TXT_CRM_NOTHING_SELECTED'],
                'TXT_CRM_STATUS_SUCCESSFULLY_CHANGED' => $_ARRAYLANG['TXT_CRM_CHANGE_STATUS'],
                'TXT_CRM_EDIT_MESSAGE'              => $_ARRAYLANG['TXT_CRM_CHANGES_UPDATED_SUCCESSFULLY'],
                'TXT_CRM_DELETE_MESSAGE'            => $_ARRAYLANG['TXT_CRM_ENTRY_DELETED_SUCCESS'],
                'TXT_ACTIVE'                        => $_ARRAYLANG['TXT_ACTIVE'],
                'TXT_CRM_ARE_YOU_SURE_DELETE_ENTRIES'=> $_ARRAYLANG['TXT_CRM_ARE_YOU_SURE_DELETE_ENTRIES'],
                'TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT'=> $_ARRAYLANG['TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT'],
        ));

    }

    /**
     * save opportunity stages
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function saveStage()
    {
        global $objDatabase;

        // New update Query
        $idArr = array_map('intval', array_keys($_POST['sorting']));
        $ids = implode(',', $idArr);

        $query = "UPDATE ".DBPREFIX."module_".$this->moduleNameLC."_stages SET `sorting` = CASE id ";
        foreach ($_POST['sorting'] as $id => $val) {
            $query .= sprintf(" WHEN %d THEN %d", (int) $id, $val);
        }

        $query .= " END,
                            `label` = CASE id ";
        foreach ($_POST['label'] as $id => $val) {
            $query .= sprintf(" WHEN %d THEN '%s'", (int) $id, contrexx_input2db($val));
        }
        $query .= " END,
                            `stage` = CASE id ";
        foreach ($_POST['rate'] as $id => $val) {
            $query .= sprintf(" WHEN %d THEN '%s'", (int) $id, contrexx_input2db($val));
        }
        $query .= " END WHERE id IN ($ids)";
        $db = $objDatabase->Execute($query);

    }

    /**
     * delete customer types
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function deleteCustomerTypes()
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        $query = 'SELECT `id` FROM `'.DBPREFIX.'module_'.$this->moduleNameLC.'_customer_types` WHERE `default` = 1';
        $chkdefaultType = $objDatabase->Execute($query);
        $defaultId = $chkdefaultType->fields['id'];

        if (!empty($id)) {
            $query = 'SELECT 1 FROM `'.DBPREFIX.'module_'.$this->moduleNameLC.'_contacts` WHERE `customer_type` = '.$id;
            $chkCustomerTypes = $objDatabase->Execute($query);
            if ($chkCustomerTypes->RecordCount() == 0) {
                if ($defaultId != $id) {
                    $deleteQuery = 'DELETE FROM   `'.DBPREFIX.'module_'.$this->moduleNameLC.'_customer_types`
                                    WHERE          id = '.$id;
                    $objDatabase->Execute($deleteQuery);
                    $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_CUSTOMER_TYPES_DELETED_SUCCESSFULLY'];
                } else {

                    $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_CRM_DEFAULT_CUSTOMER_TYPES_CANNOT_BE_DELETED'];
                }
            } else {
                $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_CRM_CUSTOMER_TYPES_CANNOT_BE_DELETED'];
            }
        } else {
            $deleteIds = array_map(intval, $_POST['selectedEntriesId']);
            $deleteIdstring = implode(',', $deleteIds);

            $query = 'SELECT customer_type FROM `'.DBPREFIX.'module_'.$this->moduleNameLC.'_contacts` WHERE `customer_type` IN ('.$deleteIdstring.')';
            $chkCustomerTypes = $objDatabase->Execute($query);

            $idContainsCustomer = array();
            while (!$chkCustomerTypes->EOF) {
                $idContainsCustomer[] = $chkCustomerTypes->fields['customer_type'];
                $chkCustomerTypes->MoveNext();
            }

            foreach ($deleteIds as $id) {
                if (in_array($id, $idContainsCustomer)) {
                    $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_CRM_CUSTOMER_TYPES_CANNOT_BE_DELETED'];
                    continue;
                }
                if ($defaultId == $id) {
                    $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_CRM_DEFAULT_CUSTOMER_TYPES_CANNOT_BE_DELETED'];
                    continue;
                }
                $deleteQuery = 'DELETE FROM `'.DBPREFIX.'module_'.$this->moduleNameLC.'_customer_types`
                                WHERE        id = '.$id;
                $objDatabase->Execute($deleteQuery);
                $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_CUSTOMER_TYPES_DELETED_SUCCESSFULLY'];
            }
        }
        \Cx\Core\Csrf\Controller\Csrf::header('location:./index.php?cmd=Crm&act=settings&tpl=customertypes');
        exit();
    }

    /**
     * add or edit contact
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function _modifyContact()
    {
        global $_ARRAYLANG, $objDatabase ,$objJs, $_LANGID, $_CORELANG;

        \JS::activate('cx');
        \JS::activate("jquery");
        \JS::activate("jqueryui");
        $objFWUser = \FWUser::getFWUserObject();
        \FWUser::getUserLiveSearch(array(
            'minLength' => 3,
            'canCancel' => true,
            'canClear'  => true));
        \JS::registerJS("modules/Crm/View/Script/main.js");
        \JS::registerJS("modules/Crm/View/Script/contact.js");
        \JS::registerCSS("modules/Crm/View/Style/main.css");
        \JS::registerCSS("modules/Crm/View/Style/contact.css");
        \JS::registerCSS("lib/javascript/chosen/chosen.css");
        \JS::registerJS("lib/javascript/chosen/chosen.jquery.js");
        $cxjs = \ContrexxJavascript::getInstance();
        $cxjs->setVariable('TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT', $_ARRAYLANG['TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT'], 'modifyContact');

        $mes = isset($_REQUEST['mes']) ? base64_decode($_REQUEST['mes']) : '';
        if (!empty ($mes)) {
            switch ($mes) {
            case "customerupdated":
                    $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_CUSTOMER_DETAILS_UPDATED_SUCCESSFULLY'];
                break;
            case "customeradded":
                    $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_CUSTOMER_ADDED_SUCCESSFULLY'];
                break;
            case "contactupdated":
                    $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_CUSTOMER_CONTACT_UPDATED_SUCCESSFULLY'];
                break;
            case "contactadded":
                    $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_CUSTOMER_CONTACT_ADDED_SUCCESSFULLY'];
                break;
            }
        }
        $settings  = $this->getSettings();

        $_GET['type'] = isset($_GET['type']) ? $_GET['type'] : 'customer';
        $redirect     = isset($_REQUEST['redirect']) ? $_REQUEST['redirect'] : base64_decode('&act=customers');

        $this->_pageTitle = (isset($_REQUEST['id'])) ? $_ARRAYLANG["TXT_CRM_EDIT_".strtoupper($_GET['type'])] : $_ARRAYLANG["TXT_CRM_ADD_".strtoupper($_GET['type'])] ;
        $this->_objTpl->loadTemplateFile('module_'.$this->moduleNameLC.'_customer_modify.html');
        $this->_objTpl->setGlobalVariable("MODULE_NAME", $this->moduleName);

        $id               = (isset($_REQUEST['id'])) ? intval($_REQUEST['id']) : 0;

        $this->contact = new \Cx\Modules\Crm\Model\Entity\CrmContact();
        !empty($id) ? $this->contact->id = $id : '';
        $contactType      = (isset($_GET['type']) && $_GET['type'] == 'contact') ? 2 : 1;

        //person
        $this->contact->family_name      = (isset($_POST['family_name'])) ? contrexx_input2raw($_POST['family_name']) : '';
        $this->contact->contact_title    = (isset($_POST['contact_title'])) ? contrexx_input2raw($_POST['contact_title']) : '';
        $this->contact->contact_amount   = (isset($_POST['contact_amount'])) ? contrexx_input2raw($_POST['contact_amount']) : '';
        $this->contact->contact_role     = (isset($_POST['contact_role'])) ? contrexx_input2raw($_POST['contact_role']) : '';
        $this->contact->contact_language = (isset($_POST['contact_language'])) ? (int) $_POST['contact_language'] : (empty($id) ? $_LANGID : 0);
        $this->contact->contact_customer = isset($_POST['company']) ? (int) $_POST['company'] : (isset($_GET['custId']) ? (int) $_GET['custId'] : 0);
        $this->contact->contactType      = $contactType;
        $this->contact->companySize      = isset($_POST['companySize']) ? contrexx_input2raw($_POST['companySize']) : 0;
        $this->contact->contact_gender   = isset($_POST['contact_gender']) ? (int) $_POST['contact_gender'] : 0;
        $this->contact->salutation       = isset($_POST['salutation']) ? (int) $_POST['salutation'] : 0;
        $this->contact->emailDelivery    = empty($_POST) || isset($_POST['emailDelivery']) ? 1 : 0;

        $accountUserID                   = (isset($_POST['contactId'])) ? intVal($_POST['contactId']) : 0;
        $accountUserEmail                = (isset($_POST['contact_email'])) ? contrexx_input2raw($_POST['contact_email']) : '';
        $accountUserPassword             = (isset($_POST['contact_password'])) ? contrexx_input2raw($_POST['contact_password']) : '';
        $sendLoginDetails                = isset($_POST['send_account_notification']);

        $this->contact->account_id       = 0;

        // customer
        $tpl = isset($_REQUEST['tpl']) ? contrexx_input2db($_REQUEST['tpl']) : '';
        if (isset($_GET['design']) && $_GET['design'] == 'custom') {
            $this->_objTpl->setVariable(array(
                    'PM_REMOVE_BACKGROUND_STYLE'             => $this->pmRemoveStylesAddcustomer(),
                    'PM_AJAX_SAVE_FROM_SHADOWBOX_JAVASCRIPT' => $objJs->pmAjaxformSubmitForShadowbox($tpl),
            ));
        }

        $defaultTypeId  = $objDatabase->getOne('SELECT `id` FROM '.DBPREFIX.'module_'.$this->moduleNameLC.'_customer_types WHERE `default` = 1');

        $this->contact->customerId          = isset($_POST['customerId']) ? contrexx_input2raw($_POST['customerId']) : '';
        $this->contact->customerType        = isset($_POST['customer_type']) ? (int) $_POST['customer_type'] : (empty($id) ? $defaultTypeId : '');
        $this->contact->customerName        = isset($_POST['companyName']) ? contrexx_input2raw($_POST['companyName']) : '';
        $this->contact->addedUser           = $objFWUser->objUser->getId();
        $this->contact->currency            = isset($_POST['currency']) ? (int) $_POST['currency'] : '';
        $this->contact->datasource          = 1;


        $customerContacts    = isset($_POST['companyContacts']) ? array_map('intval', (array) $_POST['companyContacts']) : array();
        $assignedMembersShip = isset($_POST['assigned_memberships']) ? array_map('intval', (array) $_POST['assigned_memberships']) : array();

        $this->contact->notes        = isset($_POST['notes']) ? contrexx_input2raw($_POST['notes']) : '';
        $this->contact->industryType = isset($_POST['industryType']) ? (int) $_POST['industryType'] : 0;
        $this->contact->user_name    = isset($_POST['contact_username']) ? contrexx_input2raw($_POST['contact_username']) : '';

        if (isset($_POST['save_contact']) || isset($_POST['save_add_new_contact'])) {
            $msg = '';
            switch(true) {
            case ($contactType == 1 && !empty($id)):
                    $msg = "customerupdated";
                break;
            case ($contactType == 2 && !empty($id)):
                    $msg = "contactupdated";
                break;
            case ($contactType == 1):
                    $msg = "customeradded";
                break;
            case ($contactType == 2):
                    $msg = "contactadded";
                break;
            default:
                break;
            }
            $result = $this->parseContacts($_POST);

            // unset customer type, customerId the contact have customer
            if (($this->contact->contactType == 2) && $this->contact->contact_customer != 0) {
                $this->contact->customerType = 0;
                $this->contact->currency     = 0;
                $this->contact->customerId   = '';
            }
            $accountMandatory = !empty($accountUserEmail) ? false : !$settings['user_account_mantatory'];
            if (!$settings['create_user_account'] || ($contactType == 1) || (!empty($accountUserEmail) && $this->addUser($accountUserEmail, $accountUserPassword, $sendLoginDetails, $result, $accountUserID)) || $accountMandatory) {

                $this->contact->save();

                $this->updateCustomerMemberships((array) $assignedMembersShip, $this->contact->id);
                if ($contactType == 2) { // For contact
                    //$this->save
                } else {
                    $this->updateCustomerContacts((array) $customerContacts, $this->contact->id);
                }

                // insert Emails
                $objDatabase->Execute("DELETE FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_emails` WHERE `contact_id` = {$this->contact->id}");
                $query = "INSERT INTO `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_emails` (email, email_type, is_primary, contact_id) VALUES ";

                $values = array();
                foreach ($result['contactemail'] as $value) {
                    if (!empty($value['value']))
                    $values[] = "('".contrexx_input2db($value['value'])."', '".(int) $value['type']."', '".(int) $value['primary']."', '".$this->contact->id."')";
                }

                if (is_array($values) && !empty ($values)) {
                    $query .= implode(",", $values);
                    $objDatabase->Execute($query);
                }

                // insert Phone
                $objDatabase->Execute("DELETE FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_phone` WHERE `contact_id` = {$this->contact->id}");
                $query = "INSERT INTO `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_phone` (phone, phone_type, is_primary, contact_id) VALUES ";

                $values = array();
                foreach ($result['contactphone'] as $value) {
                    if (!empty($value['value']))
                    $values[] = "('".contrexx_input2db($value['value'])."', '".(int) $value['type']."', '".(int) $value['primary']."', '".$this->contact->id."')";
                }

                if (is_array($values) && !empty ($values)) {
                    $query .= implode(",", $values);
                    $objDatabase->Execute($query);
                }

                // insert Website
                $assignedWebsites = array();
                foreach ($result['contactwebsite'] as $value) {
                    if (!empty($value['value'])) {
                        $fields = array(
                                    'url'           => contrexx_input2raw($value['value']),
                                    'url_profile'   => (int) $value['profile'],
                                    'is_primary'    => $value['primary'],
                                    'contact_id'    => $this->contact->id
                                  );
                        if (!empty($value['id'])) {
                            array_push($assignedWebsites, $value['id']);
                            $query  = \SQL::update("module_{$this->moduleNameLC}_customer_contact_websites", $fields, array('escape' => true))." WHERE `id` = {$value['id']} AND `contact_id` = {$this->contact->id}";
                            $objDatabase->Execute($query);
                        } else {
                            $query  = \SQL::insert("module_{$this->moduleNameLC}_customer_contact_websites", $fields, array('escape' => true));
                            $db = $objDatabase->Execute($query);
                            if ($db)
                                array_push($assignedWebsites, $objDatabase->INSERT_ID());
                        }
                    }
                }

                $whereWebId = !empty($assignedWebsites) ? " AND `id` NOT IN (".implode(',', $assignedWebsites).")" : "" ;
                $objDatabase->Execute("DELETE FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_websites` WHERE `contact_id` = {$this->contact->id} $whereWebId");

                // insert social networks
                $assignedSocialNetwork = array();
                foreach ($result['contactsocial'] as $value) {
                    if (!empty($value['value'])) {
                        $fields = array(
                                    'id'            => array('val' => !empty($value['id']) ? (int) $value['id'] : null, 'omitEmpty' => true) ,
                                    'url'           => contrexx_input2raw($value['value']),
                                    'url_profile'   => (int) $value['profile'],
                                    'is_primary'    => $value['primary'],
                                    'contact_id'    => $this->contact->id
                                  );
                        if (!empty($value['id'])) {
                            array_push($assignedSocialNetwork, $value['id']);
                            $query  = \SQL::update("module_{$this->moduleNameLC}_customer_contact_social_network", $fields, array('escape' => true))." WHERE `id` = {$value['id']} AND `contact_id` = {$this->contact->id}";
                            $objDatabase->Execute($query);
                        } else {
                            $query  = \SQL::insert("module_{$this->moduleNameLC}_customer_contact_social_network", $fields, array('escape' => true));
                            $db = $objDatabase->Execute($query);
                            if ($db)
                                array_push($assignedSocialNetwork, $objDatabase->INSERT_ID());
                        }
                    }
                }

                $whereWebId = !empty($assignedSocialNetwork) ? " AND `id` NOT IN (".implode(',', $assignedSocialNetwork).")" : "" ;
                $objDatabase->Execute("DELETE FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_social_network` WHERE `contact_id` = {$this->contact->id} $whereWebId");


                // insert address
                $objDatabase->Execute("DELETE FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_address` WHERE `contact_id` = {$this->contact->id}");
                $query = "INSERT INTO `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_address` (address, city, state, zip, country, Address_Type, is_primary, contact_id) VALUES ";

                $values = array();
                foreach ($result['contactAddress'] as $value) {
                    if (!empty($value['address']) || !empty($value['city']) || !empty($value['state']) || !empty($value['zip']) || !empty($value['country']))
                    $values[] = "('".contrexx_input2db($value['address'])."', '".contrexx_input2db($value['city'])."', '".contrexx_input2db($value['state'])."', '".contrexx_input2db($value['zip'])."', '".contrexx_input2db($value['country'])."', '".intval($value['type'])."', '".intval($value['primary'])."', '".$this->contact->id."')";
                }

                if (is_array($values) && !empty ($values)) {
                    $query .= implode(",", $values);
                    $objDatabase->Execute($query);
                }

                $ChckCount = 0;
                if (!empty($id)) {
                    $contactId = $this->contact->contact_customer;
                }
                if ($this->contact->contactType == 2) {
                    $contactId = $this->contact->contact_customer;
                }

                $customerId = $this->contact->id;
                $customerName = $this->contact->customerName;

                // notify the staff's
                $this->notifyStaffOnContactAccModification($this->contact->id, $this->contact->customerName, $this->contact->family_name, $this->contact->contact_gender);

                // ajax request
                if (isset($_GET['design']) && $_GET['design'] == 'custom') {
                    $returnString = array(
                            'errChk'       => $ChckCount,
                            'customerId'   => $customerId,
                            'customerName' => $customerName,
                            'contactId'    => $contactId,
                            'msg'          => $msg
                    );
                    echo json_encode($returnString);
                    exit();
                }

                if (isset($_POST['save_add_new_contact'])) {
                    $contactTypeUrl = $contactType == 2 ? '&type=contact' : '';
                    \Cx\Core\Csrf\Controller\Csrf::header("Location:./index.php?cmd=".$this->moduleName."&act=customers&tpl=managecontact&mes=".base64_encode($msg).$contactTypeUrl);
                    exit();
                }
                \Cx\Core\Csrf\Controller\Csrf::header("Location:./index.php?cmd=".$this->moduleName."&act=customers&mes=".base64_encode($msg).base64_decode($redirect));
                exit();
            } elseif (empty($accountUserEmail)) {
                $this->_strErrMessage = $_ARRAYLANG['TXT_CRM_EMAIL_EMPTY'];
            }
        } elseif ($this->contact->load($id)) {

            if ($contactType == 1) {
                $objContact = $objDatabase->Execute("SELECT `id` FROM `".DBPREFIX."module_{$this->moduleNameLC}_contacts` WHERE `contact_customer` = {$this->contact->id}");
                if ($objContact) {
                    while (!$objContact->EOF) {
                        $customerContacts[] = (int) $objContact->fields['id'];
                        $objContact->MoveNext();
                    }
                }
            }

            $objMemberShips = $objDatabase->Execute("SELECT `membership_id` FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_membership` WHERE `contact_id` = {$this->contact->id}");
            if ($objMemberShips) {
                while (!$objMemberShips->EOF) {
                    $assignedMembersShip[] = (int) $objMemberShips->fields['membership_id'];
                    $objMemberShips->Movenext();
                }
            }

            // Get emails and phones
            $objEmails = $objDatabase->Execute("SELECT * FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_emails` WHERE contact_id = {$this->contact->id} ORDER BY id ASC");
            if ($objEmails) {
                while (!$objEmails->EOF) {
                    $result['contactemail'][] = array("type" => $objEmails->fields['email_type'], "primary" => $objEmails->fields['is_primary'], "value" => $objEmails->fields['email']);
                    $objEmails->MoveNext();
                }
            }
            $objPhone = $objDatabase->Execute("SELECT * FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_phone` WHERE contact_id = {$this->contact->id} ORDER BY id ASC");
            if ($objPhone) {
                while (!$objPhone->EOF) {
                    $result['contactphone'][] = array("type" => $objPhone->fields['phone_type'], "primary" => $objPhone->fields['is_primary'], "value" => $objPhone->fields['phone']);
                    $objPhone->MoveNext();
                }
            }
            $objWebsite = $objDatabase->Execute("SELECT * FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_websites` WHERE contact_id = {$this->contact->id} ORDER BY id ASC");
            if ($objWebsite) {
                while (!$objWebsite->EOF) {
                    $result['contactwebsite'][] = array("id" => $objWebsite->fields['id'], "profile" => $objWebsite->fields['url_profile'], "primary" => $objWebsite->fields['is_primary'], "value" => $objWebsite->fields['url']);
                    $objWebsite->MoveNext();
                }
            }
            $objSocial = $objDatabase->Execute("SELECT * FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_social_network` WHERE contact_id = {$this->contact->id} ORDER BY id ASC");
            if ($objSocial) {
                while (!$objSocial->EOF) {
                    $result['contactsocial'][] = array("id" => $objSocial->fields['id'], "profile" => $objSocial->fields['url_profile'], "primary" => $objSocial->fields['is_primary'], "value" => $objSocial->fields['url']);
                    $objSocial->MoveNext();
                }
            }
            $objAddress = $objDatabase->Execute("SELECT * FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_address` WHERE contact_id = {$this->contact->id} ORDER BY id ASC");
            if ($objAddress) {
                while (!$objAddress->EOF) {
                    $result['contactAddress'][] = array("address" => $objAddress->fields['address'], "city" => $objAddress->fields['city'], "state" => $objAddress->fields['state'], "zip" => $objAddress->fields['zip'], "country" => $objAddress->fields['country'], "type" => $objAddress->fields['Address_Type'], "primary" => $objAddress->fields['is_primary']);
                    $objAddress->MoveNext();
                }
            }
        }

        // reset the email and phone fields
        if (empty($result['contactemail'])) $result['contactemail'][] = array("type" => ($contactType == 1) ? 1 : 0, "primary" => 1, "value" => "");
        if (empty($result['contactphone'])) $result['contactphone'][] = array("type" => 1, "primary" => 1, "value" => "");
        if (empty($result['contactwebsite'])) $result['contactwebsite'][] = array("id" => 0, "profile" => ($contactType == 1) ? 3 : 1, "primary" => 1, "value" => "");
        if (empty($result['contactsocial'])) $result['contactsocial'][] = array("id" => 0, "profile" => 4, "primary" => 1, "value" => "");
        if (empty($result['contactAddress'])) $result['contactAddress'][] = array("address" => '', "city" => '', "state" => '', "zip" => "", "country" => "", "type" => 2, "primary" => 1);

        if (!empty($result['contactemail'])) {
            $Count = 1;
            //$showEmail = false;
            $showEmail = true;
            foreach ($result['contactemail'] as $email) {
                if (!empty($email['value']) && !$showEmail)
                    $showEmail = true;

                $this->_objTpl->setVariable(array(
                        'CRM_CONTACT_EMAIL_NAME'    => "contactemail_{$Count}_{$email['type']}_{$email['primary']}",
                        'CRM_CONTACT_EMAIL'         => contrexx_raw2xhtml($email['value']),
                        'CRM_EMAIL_OPTION'          => $_ARRAYLANG[$this->emailOptions[$email['type']]],
                        'CRM_CONTACT_EMAIL_PRIMARY' => ($email['primary']) ? "primary_field" : "not_primary_field",
                ));
                $block = $contactType == 1 ? "customerEmailContainer" : "contactEmailContainer";
                $this->_objTpl->parse($block);
                $Count++;
            }
        }
        if (!empty($result['contactphone'])) {
            foreach ($result['contactphone'] as $phone) {
                $this->_objTpl->setVariable(array(
                        'CRM_CONTACT_PHONE_NAME'    => "contactphone_{$Count}_{$phone['type']}_{$phone['primary']}",
                        'CRM_CONTACT_PHONE'         => contrexx_raw2xhtml($phone['value']),
                        'CRM_PHONE_OPTION'          => $_ARRAYLANG[$this->phoneOptions[$phone['type']]],
                        'CRM_CONTACT_PHONE_PRIMARY' => ($phone['primary']) ? "primary_field" : "not_primary_field",
                ));
                $block = $contactType == 1 ? "customerPhoneContainer" : "contactPhoneContainer";
                $this->_objTpl->parse($block);
                $Count++;
            }
        }
        if (!empty($result['contactwebsite'])) {
            foreach ($result['contactwebsite'] as $website) {
                $this->_objTpl->setVariable(array(
                        'CRM_CONTACT_WEBSITE_NAME'    => "contactwebsite_{$Count}_{$website['profile']}_{$website['primary']}",
                        'CRM_CONTACT_WEBSITE'         => contrexx_raw2xhtml(html_entity_decode($website['value'], ENT_QUOTES, CONTREXX_CHARSET)),
                        'CRM_WEBSITE_PROFILE'         => !empty($this->websiteProfileOptions[$website['profile']]) ? $_ARRAYLANG[$this->websiteProfileOptions[$website['profile']]] : '',
                        'CRM_WEBSITE_OPTION'          => isset($website['type']) && $website['type'] != '' && !empty($this->websiteOptions[$website['type']]) ? $_ARRAYLANG[$this->websiteOptions[$website['type']]] : '',
                        'CRM_CONTACT_WEB_ID_NAME'     => "website_{$Count}",
                        'CRM_CONTACT_WEB_ID'          => (int) $website['id'],
                        'CRM_CONTACT_WEBSITE_PRIMARY' => ($website['primary']) ? "primary_field" : "not_primary_field",
                ));
                $block = $contactType == 1 ? "customerwebsiteContainer" : "contactwebsiteContainer";
                $this->_objTpl->parse($block);
                $Count++;
            }
        }

        if (!empty($result['contactsocial'])) {
            foreach ($result['contactsocial'] as $social) {
                $this->_objTpl->setVariable(array(
                        'CRM_CONTACT_SOCIAL_NAME'     => "contactsocial_{$Count}_{$social['profile']}_{$social['primary']}",
                        'CRM_CONTACT_SOCIAL'          => contrexx_raw2xhtml(html_entity_decode($social['value'], ENT_QUOTES, CONTREXX_CHARSET)),
                        'CRM_SOCIAL_PROFILE'          => $_ARRAYLANG[$this->socialProfileOptions[$social['profile']]],
                        'CRM_CONTACT_SOCIAL_ID_NAME'  => "social_{$Count}",
                        'CRM_CONTACT_SOCIAL_ID'       => (int) $social['id'],
                        'CRM_CONTACT_SOCIAL_PRIMARY'  => ($social['primary']) ? "primary_field" : "not_primary_field",
                ));
                $block = $contactType == 1 ? "customerSocialLinkContainer" : "contactSocialLinkContainer";
                $this->_objTpl->parse($block);
                $Count++;
            }
        }

        if (!empty($result['contactAddress'])) {
            $showAddress = false;

            foreach ($result['contactAddress'] as $address) {
                if (!empty($address['address']) && !$showAddress)
                    $showAddress = true;

                $primary = ($address['primary']) ? 1 : 0;
                $this->_objTpl->setVariable(array(
                        'CRM_CONTACT_ADDRESS_NAME'  => "contactAddress_{$Count}_1_{$primary}",
                        'CRM_CONTACT_ADDRESS_VALUE' => contrexx_raw2xhtml($address['address']),
                        'CRM_CONTACT_CITY_NAME'     => "contactAddress_{$Count}_2_{$primary}",
                        'CRM_CONTACT_CITY_VALUE'    => contrexx_raw2xhtml($address['city']),
                        'CRM_CONTACT_STATE_NAME'    => "contactAddress_{$Count}_3_{$primary}",
                        'CRM_CONTACT_STATE_VALUE'   => contrexx_raw2xhtml($address['state']),
                        'CRM_CONTACT_ZIP_NAME'      => "contactAddress_{$Count}_4_{$primary}",
                        'CRM_CONTACT_ZIP_VALUE'     => contrexx_raw2xhtml($address['zip']),
                        'CRM_CONTACT_COUNTRY_NAME'  => "contactAddress_{$Count}_5_{$primary}",
                        'CRM_CONTACT_COUNTRY_VALUE' => $this->getContactAddressCountry($this->_objTpl, $address['country'], $contactType == 1 ? "customerCrmCountry" : 'crmCountry'),
                        'CRM_CONTACT_ADDR_TYPE_NAME'  => "contactAddress_{$Count}_6_{$primary}",
                        'CRM_CONTACT_ADDR_TYPE_VALUE' => $this->getContactAddrTypeCountry($this->_objTpl, $address['type'], $contactType == 1 ? "customerAddressType" : 'addressType'),
                        'CRM_CONTACT_ADDRESS_PRIMARY' => ($primary) ? "primary_field_address" : "not_primary_field_address",
                ));
                $block = $contactType == 1 ? "customerAddressContainer" : "contactAddressContainer";
                $this->_objTpl->parse($block);
                $Count++;
            }
        }
        $this->getContactAddressCountry($this->_objTpl, '', $contactType == 1 ? "customerAdditionalcrmCountry" : 'additionalcrmCountry');
        $this->getContactAddrTypeCountry($this->_objTpl, 2, $contactType == 1 ? "customerAdditionaladdressType" : 'additionaladdressType');

        // special fields for contacts
        foreach (\FWLanguage::getActiveFrontendLanguages() as $frontendLang) {
            $langBlocks = array('showAddtionalContactLanguages' . $contactType);
            if($contactType == 1){
                $langBlocks[] = ('ContactLanguages');
            }
            foreach($langBlocks as $langBlock) {
                $this->_objTpl->setVariable(array(
                        'TXT_LANG_ID'    =>  (int) $frontendLang['id'],
                        'TXT_LANG_NAME'     =>  contrexx_raw2xhtml($frontendLang['name']),
                        'TXT_LANG_SELECT'   =>  ($frontendLang['id'] == $this->contact->contact_language) ? "selected=selected" : "",
                ));
                $this->_objTpl->parse($langBlock);
            }
        }

        // special fields for customer
        if ($contactType == 1) {
            $this->getCustomerTypeDropDown($this->_objTpl, $this->contact->customerType, 'customerTypes', array('is_hide' => false)); // Customer Types

            // Parse the contacts
            if (!empty($customerContacts)) {
                $objContacts = $objDatabase->Execute("SELECT `id`, `customer_name`, `contact_familyname` FROM `".DBPREFIX."module_{$this->moduleNameLC}_contacts` WHERE `id` IN (".implode(',', $customerContacts).")");
                if ($objContacts) {
                    $row = "row2";
                    while (!$objContacts->EOF) {
                        $this->_objTpl->setVariable(array(
                                'CRM_CONTACT_ID'     => $objContacts->fields['id'],
                                'CRM_CONTACT_NAME'   => contrexx_raw2xhtml($objContacts->fields['contact_familyname']." ".$objContacts->fields['customer_name']),
                                'ROW_CLASS'               => $row = ($row == 'row2') ? "row1" : "row2",
                        ));
                        $this->_objTpl->parse("customerContacts");
                        $objContacts->MoveNext();
                    }
                }
            }
            $this->_objTpl->setVariable('CRM_CONTACTS_HEADER_CLASS', (!empty ($customerContacts)) ? 'header-collapse' : 'header-expand');

            // parse currency
            $this->getCustomerCurrencyDropDown($this->_objTpl, $this->contact->currency, "currency");
            // parse companysize
            $this->getCompanySizeDropDown($this->_objTpl, $this->contact->companySize, "companySize");
        } else {
            $this->getCustomerTypeDropDown($this->_objTpl, $this->contact->customerType, "contactCustomerTypes", array('is_hide' => false));     // Customer Types
            $this->getCustomerCurrencyDropDown($this->_objTpl, $this->contact->currency, "contactCurrency");  // currency
        }

        $memberships          = array_keys($this->getMemberships());
        $membershipBlock      = $contactType == 1 ? "assignedGroup" : "contactMembership";
        $this->getMembershipDropdown($this->_objTpl, $memberships, $membershipBlock, $assignedMembersShip);

        if (!empty($this->contact->account_id)) {
            $objUser = $objFWUser->objUser->getUser($this->contact->account_id);
            if ($objUser) {
                $accountName    = contrexx_raw2xhtml(\FWUser::getParsedUserTitle($objUser));
            }
        } else {
            $objUser = false;
        }

        $objAttribute = \FWUser::getFWUserObject()->objUser->objAttribute->getById('title');
        if (!$objAttribute->EOF) {
            $titleKeys = $objAttribute->getChildren();
            foreach ($titleKeys as $title) {
                $value = $objAttribute->getById($title)->getMenuOptionValue();
                $this->_objTpl->setVariable(array(
                    'SALUTATION_SELECT'     =>  $value == $this->contact->salutation ? 'selected=selected' : '',
                    'SALUTATION_ID'         =>  $value,
                    'TXT_SALUTATION_NAME'   =>  $objAttribute->getById($title)->getName(),
                ));
                $this->_objTpl->parse("crmContactSalutationOptions");
            }
        }

        $this->_objTpl->setVariable(array(
            'CRM_ADDRESS_HEADER_CLASS'      => $showAddress ? 'header-collapse' : 'header-expand',
            'CRM_ADDRESS_BLOCK_DISPLAY'     => $showAddress ? 'table-row-group' : 'none',
            'CRM_DESCRIPTION_HEADER_CLASS'  => !empty($this->contact->notes) ? 'header-collapse' : 'header-expand',
            'CRM_DESCRIPTION_BLOCK_DISPLAY' => !empty($this->contact->notes) ? 'table-row-group' : 'none',

            'CRM_MEMBERSHIP_HEADER_CLASS'   => !empty($assignedMembersShip) ? 'header-collapse' : 'header-expand',
            'CRM_MEMBERSHIP_BLOCK_DISPLAY'  => !empty($assignedMembersShip) ? 'table-row-group' : 'none',
        ));

        $this->_objTpl->setGlobalVariable(array(
                'TXT_CON_FAMILY'            => contrexx_raw2xhtml($this->contact->family_name),
                'TXT_CON_ROLE'              => contrexx_raw2xhtml($this->contact->contact_role),
                'CRM_INPUT_COUNT'           => $Count,
                'CRM_CONTACT_COMPANY_ID'    => (int) $this->contact->contact_customer,
                'CRM_CONTACT_COMPANY'       => ($this->contact->contact_customer!=null) ? contrexx_raw2xhtml($objDatabase->getOne("SELECT `customer_name` FROM `".DBPREFIX."module_{$this->moduleNameLC}_contacts` WHERE id = {$this->contact->contact_customer} ")) : '',
                'CRM_CONTACT_NOTES'         => contrexx_raw2xhtml($this->contact->notes),
                'CRM_INDUSTRY_DROPDOWN'     => $this->listIndustryTypes($this->_objTpl, 2, $this->contact->industryType),

                'CRM_CUSTOMERID'            => contrexx_input2xhtml($this->contact->customerId),
                'CRM_COMPANY_NAME'          => contrexx_input2xhtml($this->contact->customerName),
                'CRM_CONTACT_TITLE'         => contrexx_input2xhtml($this->contact->contact_title),
                'CRM_CONTACT_AMOUNT'        => $this->contact->contact_amount ? contrexx_input2xhtml($this->contact->contact_amount) : '',
                'CRM_UPDATED_DATE'          => date(ASCMS_DATE_FORMAT_DATETIME, strtotime($this->contact->updated_date)),
                'CRM_CONTACT_ID'            => $this->contact->id != null ? $this->contact->id : 0,
                'CRM_CONTACT_USER_ID'       => $this->contact->account_id != null ? $this->contact->account_id : 0,
                'CRM_CONTACT_USERNAME'      => $objUser ? contrexx_raw2xhtml($objUser->getEmail()) : '',
                'CRM_CONTACT_ACCOUNT_USERNAME' => $objUser ? $accountName : ' ',
                'CRM_CONTACT_SHOW_PASSWORD' => "style='display: none;'",
                'CRM_CONTACT_RANDOM_PASSWORD' => \User::make_password(),
                'CRM_GENDER_FEMALE_SELECTED'=> $this->contact->contact_gender == 1 ? 'selected' : '',
                'CRM_GENDER_MALE_SELECTED'  => $this->contact->contact_gender == 2 ? 'selected' : '',
                'CRM_CONTACT_TYPE'          => ($contactType == 1) ? 'company' : 'contact',
                'CRM_ACCOUNT_MANTORY'       => ($settings['create_user_account'] && $settings['user_account_mantatory']) ? '<font color="red">*</font>' : '',
                'CRM_ACCOUNT_MANTORY_CLASS' => ($settings['create_user_account'] && $settings['user_account_mantatory']) ? 'mantatory' : '',
                'CRM_EMAIL_DELIVERY'        => $this->contact->emailDelivery ? 'checked="checked"' : '',

                'TXT_CRM_EMPLOYEE'          => $_ARRAYLANG['TXT_CRM_EMPLOYEE'],
                'TXT_CRM_CITY'              => $_ARRAYLANG['TXT_CRM_TITLE_CITY'],
                'TXT_CRM_STATE'             => $_ARRAYLANG['TXT_CRM_STATE'],
                'TXT_CRM_ZIP_CODE'          => $_ARRAYLANG['TXT_CRM_ZIP_CODE'],
                'TXT_CRM_EDITCUSTOMERCONTACT_TITLE' => (isset($_REQUEST['id'])) ? $_ARRAYLANG["TXT_CRM_EDIT_".strtoupper($_GET['type'])] : $_ARRAYLANG["TXT_CRM_ADD_".strtoupper($_GET['type'])],
                'TXT_CRM_INDUSTRY_TYPE'     => $_ARRAYLANG['TXT_CRM_INDUSTRY_TYPE'],
                'TXT_CRM_DATASOURCE'        => $_ARRAYLANG['TXT_CRM_DATASOURCE'],
                'TXT_CRM_OPTION'            => $_ARRAYLANG['TXT_CRM_WORK'],
                'TXT_CRM_EMAIL_DEFAULT_OPTION'=>($contactType == 1) ? $_ARRAYLANG['TXT_CRM_HOME'] : $_ARRAYLANG['TXT_CRM_WORK'],
                'TXT_CRM_PROFILE_OPTION'    => ($contactType == 1) ? $_ARRAYLANG['TXT_CRM_BUSINESS1'] : $_ARRAYLANG['TXT_CRM_WORK'],
                'TXT_CRM_SOCIAL_PROFILE_OPTION' => $_ARRAYLANG['TXT_CRM_FACEBOOK'],
                'TXT_CRM_NAME'                  => $_ARRAYLANG['TXT_CRM_NAME'],
                'TXT_CRM_EMAIL'                 => $_ARRAYLANG['TXT_CRM_EMAIL'],
                'TXT_CRM_PHONE'                 => $_ARRAYLANG['TXT_CRM_PHONE'],
                'TXT_CRM_TITLE_LANGUAGE'        => $_ARRAYLANG['TXT_CRM_TITLE_LANGUAGE'],
                'TXT_CRM_ROLE'                  => $_ARRAYLANG['TXT_CRM_ROLE'],
                'TXT_CRM_TITLE'                 => $_ARRAYLANG['TXT_CRM_TITLE'],
                'TXT_CRM_AMOUNT'                =>  $_ARRAYLANG['TXT_CRM_AMOUNT'],
                'TXT_CRM_FAMILY_NAME'           => $_ARRAYLANG['TXT_CRM_FAMILY_NAME'],
                'TXT_CRM_TITLE_SELECT_LANGUAGE' => $_ARRAYLANG['TXT_CRM_TITLE_SELECT_LANGUAGE'],
                'TXT_CRM_HOME'              => $_ARRAYLANG['TXT_CRM_HOME'],
                'TXT_CRM_WORK'              => $_ARRAYLANG['TXT_CRM_WORK'],
                'TXT_CRM_BUSINESS1'         => $_ARRAYLANG['TXT_CRM_BUSINESS1'],
                'TXT_CRM_BUSINESS2'         => $_ARRAYLANG['TXT_CRM_BUSINESS2'],
                'TXT_CRM_BUSINESS3'         => $_ARRAYLANG['TXT_CRM_BUSINESS3'],
                'TXT_CRM_PRIVATE'           => $_ARRAYLANG['TXT_CRM_PRIVATE'],
                'TXT_CRM_OTHERS'            => $_ARRAYLANG['TXT_CRM_OTHERS'],
                'TXT_CRM_MOBILE'            => $_ARRAYLANG['TXT_CRM_MOBILE'],
                'TXT_CRM_FAX'               => $_ARRAYLANG['TXT_CRM_FAX'],
                'TXT_CRM_DIRECT'            => $_ARRAYLANG['TXT_CRM_DIRECT'],
                'TXT_CRM_DESCRIPTION'       => $_ARRAYLANG['TXT_CRM_DESCRIPTION'],
                'TXT_COMPANY_NAME'          => $_ARRAYLANG['TXT_CRM_TITLE_COMPANY_NAME'],
                'TXT_CRM_WEBSITE_SOCIAL_NETWORK' => $_ARRAYLANG['TXT_CRM_WEBSITE_SOCIAL_NETWORK'],
                'TXT_CRM_WEBSITE'           => $_ARRAYLANG['TXT_CRM_WEBSITE'],
                'TXT_CRM_SKYPE'             => $_ARRAYLANG['TXT_CRM_SKYPE'],
                'TXT_CRM_TWITTER'           => $_ARRAYLANG['TXT_CRM_TWITTER'],
                'TXT_CRM_LINKEDIN'          => $_ARRAYLANG['TXT_CRM_LINKEDIN'],
                'TXT_CRM_FACEBOOK'          => $_ARRAYLANG['TXT_CRM_FACEBOOK'],
                'TXT_CRM_LIVEJOURNAL'       => $_ARRAYLANG['TXT_CRM_LIVEJOURNAL'],
                'TXT_CRM_MYSPACE'           => $_ARRAYLANG['TXT_CRM_MYSPACE'],
                'TXT_CRM_GMAIL'             => $_ARRAYLANG['TXT_CRM_GMAIL'],
                'TXT_CRM_BLOGGER'           => $_ARRAYLANG['TXT_CRM_BLOGGER'],
                'TXT_CRM_YAHOO'             => $_ARRAYLANG['TXT_CRM_YAHOO'],
                'TXT_CRM_MSN'               => $_ARRAYLANG['TXT_CRM_MSN'],
                'TXT_CRM_ICQ'               => $_ARRAYLANG['TXT_CRM_ICQ'],
                'TXT_CRM_JABBER'            => $_ARRAYLANG['TXT_CRM_JABBER'],
                'TXT_CRM_AIM'               => $_ARRAYLANG['TXT_CRM_AIM'],
                'TXT_CRM_GOOGLE_PLUS'       => $_ARRAYLANG['TXT_CRM_GOOGLE_PLUS'],
                'TXT_CRM_XING'              => $_ARRAYLANG['TXT_CRM_XING'],
                'TXT_CRM_ADDRESS'           => $_ARRAYLANG['TXT_CRM_TITLE_ADDRESS'],
                'TXT_CRM_SELECT_COUNTRY'    => $_ARRAYLANG['TXT_CRM_SELECT_COUNTRY'],
                'TXT_CRM_OVERVIEW'              => $_ARRAYLANG['TXT_CRM_OVERVIEW'],
                'TXT_CRM_ARE_YOU_SURE_DELETE_ENTRIES' => $_ARRAYLANG['TXT_CRM_ARE_YOU_SURE_DELETE_ENTRIES'],
                'TXT_CRM_ARE_YOU_SURE_DELETE_SELECTED_ENTRIES' => $_ARRAYLANG['TXT_CRM_ARE_YOU_SURE_DELETE_SELECTED_ENTRIES'],
                'TXT_CRM_ACCOUNT_EMAIL'       => $_ARRAYLANG['TXT_CRM_ACCOUNT_EMAIL'],
                'TXT_CRM_ACCOUNT_PASSWORD'    => $_ARRAYLANG['TXT_CRM_ACCOUNT_PASSWORD'],
                'TXT_CRM_SEND_LOGIN_DETAILS'  => $_ARRAYLANG['TXT_CRM_SEND_LOGIN_DETAILS'],
                'TXT_CRM_CHOOSE_MEMBERSHIPS'  => $_ARRAYLANG['TXT_CRM_CHOOSE_MEMBERSHIPS'],
                'TXT_CRM_EMAIL_DELIVERY'      => $_ARRAYLANG['TXT_CRM_EMAIL_DELIVERY'],

                'TXT_CRM_COMPANY_NAME'        =>    $_ARRAYLANG['TXT_CRM_TITLE_COMPANY_NAME'],
                'TXT_CRM_LASTUPDATE'          =>    $_ARRAYLANG['TXT_CRM_LASTUPDATE'],
                'TXT_CRM_CUSTOMERTYPE'        =>    $_ARRAYLANG['TXT_CRM_TITLE_CUSTOMERTYPE'],
                'TXT_CRM_SOCIAL_NETWORK'      =>    $_ARRAYLANG['TXT_CRM_SOCIAL_NETWORK'],
                'TXT_CRM_GENDER'              =>    $_ARRAYLANG['TXT_CRM_GENDER'],
                'TXT_CRM_SALUTATION'          =>    $_ARRAYLANG['TXT_CRM_SALUTATION'],
                'TXT_CRM_NOT_SPECIFIED'       =>    $_ARRAYLANG['TXT_CRM_NOT_SPECIFIED'],
                'TXT_CRM_GENDER_MALE'         =>    $_ARRAYLANG['TXT_CRM_GENDER_MALE'],
                'TXT_CRM_GENDER_FEMALE'       =>    $_ARRAYLANG['TXT_CRM_GENDER_FEMALE'],
                'TXT_CRM_CUSTOMERID'          =>    $_ARRAYLANG['TXT_CRM_TITLE_CUSTOMERID'],
                'TXT_CRM_CURRENCY'            =>    $_ARRAYLANG['TXT_CRM_TITLE_CURRENCY'],
                'TXT_CRM_COMPANY_SIZE'        =>    $_ARRAYLANG['TXT_CRM_COMPANY_SIZE'],
                'TXT_CRM_PLEASE_SELECT'       =>    $_ARRAYLANG['TXT_CRM_PLEASE_SELECT'],
                'TXT_CRM_GENERAL_INFORMATION' =>    $_ARRAYLANG['TXT_CRM_GENERAL_INFORMATION'],
                'TXT_CRM_PROFILE_INFORMATION' =>    $_ARRAYLANG['TXT_CRM_PROFILE_INFORMATION'],
                'TXT_CRM_ALL_PERSONS'         =>    $_ARRAYLANG['TXT_CRM_ALL_PERSONS'],
                'TXT_CRM_ADD_CONTACT'         =>    $_ARRAYLANG['TXT_CRM_ADD_OR_LINK_CONTACT'],
                'TXT_CRM_ENTER_WEBSITE'       =>    $_ARRAYLANG['TXT_CRM_ENTER_WEBSITE'],
                'TXT_CRM_WEBSITE_NAME'            =>    $_ARRAYLANG['TXT_CRM_WEBSITE_NAME'],
                'TXT_CRM_FUNCTIONS'               =>    $_ARRAYLANG['TXT_CRM_FUNCTIONS'],
                'TXT_CRM_SELECT_FROM_CONTACTS'=>    $_ARRAYLANG['TXT_CRM_SELECT_FROM_CONTACTS'],
                'TXT_CRM_NO_MATCHES'          =>    $_ARRAYLANG['TXT_CRM_NO_MATCHES'],
                'TXT_CRM_ADD_NEW'             =>    $_ARRAYLANG['TXT_CRM_ADD_NEW'],
                'TXT_CANCEL'                  =>    $_ARRAYLANG['TXT_CANCEL'],
                'TXT_CRM_WEBSITE'             =>    $_ARRAYLANG['TXT_CRM_WEBSITE'],
                'TXT_CRM_ADD_WEBSITE'         =>    $_ARRAYLANG['TXT_CRM_ADD_WEBSITE'],
                'TXT_CRM_PLEASE_SELECT'       =>    $_ARRAYLANG['TXT_CRM_PLEASE_SELECT'],
                'TXT_CRM_WEBSITES'            =>    $_ARRAYLANG['TXT_CRM_WEBSITES'],
                'BTN_SAVE'                    =>    $_ARRAYLANG['TXT_CRM_SAVE'],
                'TXT_CRM_ADD_NEW_CUSTOMER'    =>    $_ARRAYLANG['TXT_CRM_ADD_NEW_CUSTOMER'],
                'TXT_CRM_ADD_NEW_CONTACT'     =>    $_ARRAYLANG['TXT_CRM_ADD_NEW_CONTACT'],
                'TXT_CRM_PROFILE'             =>    $_ARRAYLANG['TXT_CRM_PROFILE'],
                'TXT_CRM_ACCOUNT'             =>    $_ARRAYLANG['TXT_CRM_ACCOUNT'],
                'TXT_CORE_SEARCH_USER'        =>    $_ARRAYLANG['TXT_CORE_SEARCH_USER'],
                'TXT_CRM_ADVANCED_OPTIONS'        =>    $_ARRAYLANG['TXT_CRM_ADVANCED_OPTIONS'],
                'TXT_CRM_MEMBERSHIP'          =>    $_ARRAYLANG['TXT_CRM_MEMBERSHIP'],
                'TXT_CRM_ADD_NEW_ACCOUNT'     =>    $_ARRAYLANG['TXT_CRM_ADD_NEW_ACCOUNT'],
                'TXT_CRM_FIND_CONTACT_BY_NAME'=>    $_ARRAYLANG['TXT_CRM_FIND_CONTACT_BY_NAME'],
                'TXT_CRM_FIND_COMPANY_BY_NAME'=>    $_ARRAYLANG['TXT_CRM_FIND_COMPANY_BY_NAME'],
                'TXT_CRM_SAVE_CONTACT'        =>    ($contactType == 2) ? $_ARRAYLANG['TXT_CRM_SAVE_PERSON'] : $_ARRAYLANG['TXT_CRM_SAVE_COMPANY'],
                'TXT_CRM_SAVE_AND_ADD_NEW_CONTACT'  => ($contactType == 2) ? $_ARRAYLANG['TXT_CRM_SAVE_AND_ADD_NEW_PERSON'] : $_ARRAYLANG['TXT_CRM_SAVE_AND_ADD_NEW_COMPANY'],
                'TXT_CRM_SELECT_CUSTOMER_WATERMARK' => $this->contact->customerName == null ? 'crm-watermark' : '',
                'COMPANY_MENU_ACTIVE'         => ($contactType == 1) ? 'active' : '',
                'CONTACT_MENU_ACTIVE'         => ($contactType == 2) ? 'active' : '',
                'CRM_REDIRECT_LINK'           => $redirect,
        ));

        // If updated_date is null, its a new entry. If not, show the date of the
        // last update.
        if ($this->contact->updated_date) {
            $this->_objTpl->touchBlock("crmLastUpdate");
        }
        // Only show amount input if it is enabled in settings
        if ($settings['contact_amount_enabled']){
            $this->_objTpl->touchBlock('customeramount' . $contactType);
        }
        if ($contactType == 2) {    // If contact type eq to `contact`
            if ($settings['create_user_account']) {
                $this->_objTpl->touchBlock("contactUserName");
                $this->_objTpl->touchBlock("contactPassword");
                $this->_objTpl->touchBlock("show-account-details");
                $this->_objTpl->touchBlock("contactSendNotification");
            } else {
                $this->_objTpl->hideBlock("contactUserName");
                $this->_objTpl->hideBlock("contactPassword");
                $this->_objTpl->hideBlock("show-account-details");
                $this->_objTpl->touchBlock("emptyContactUserName");
                $this->_objTpl->touchBlock("emptyContactPassword");
            }

            $this->_objTpl->parse("contactBlock");
            $this->_objTpl->hideBlock("customerBlock");
            $this->_objTpl->hideBlock("customerAdditionalBlock");
            $this->_objTpl->touchBlock("contactWebsiteOptions");
            $this->_objTpl->hideBlock("companyWebsiteOptions");
        } else {
            $this->_objTpl->parse("customerBlock");
            $this->_objTpl->parse("customerAdditionalBlock");
            $this->_objTpl->hideBlock("contactBlock");
            $this->_objTpl->touchBlock("companyWebsiteOptions");
            $this->_objTpl->hideBlock("contactWebsiteOptions");
        }
    }

    /**
     * change the status
     *
     * @param integer $id    record id
     * @param integer $value record value
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function changeActive($id, $value)
    {
        global $_ARRAYLANG,$objDatabase;

        $value = ($value == 1) ? 0 : 1;
        $updateQuery = 'UPDATE '.DBPREFIX.'module_'.$this->moduleNameLC.'_contacts SET status = '.$value.'
                        WHERE    id='.$id;
        $objDatabase->Execute($updateQuery);
        $_SESSION['strOkMessage'] = ($value == 1) ? $_ARRAYLANG['TXT_CRM_ACTIVATED_SUCCESSFULLY'] : $_ARRAYLANG['TXT_CRM_DEACTIVATED_SUCCESSFULLY'];
    }

    /**
     * add/edit of notes page
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function _modifyNotes()
    {
        global $objDatabase, $_ARRAYLANG;

        \JS::activate('jqueryui');
        \JS::registerJS('modules/Crm/View/Script/main.js');
        \JS::registerCSS('modules/Crm/View/Style/main.css');
        $objFWUser  = \FWUser::getFWUserObject();

        $id             = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $customerId     = isset($_GET['cust_id']) ? (int) $_GET['cust_id'] : 0;
        $noteTypeId     = isset($_POST['notes_type']) ? (int) $_POST['notes_type'] : 0;
        $noteDate       = isset($_POST['date']) ? contrexx_input2raw($_POST['date']) : date('Y-m-d');
        $projectid      = isset($_REQUEST['projectid']) ? (int) $_REQUEST['projectid'] : 0;
        $redirect       = isset($_REQUEST['redirect']) ? $_REQUEST['redirect'] : base64_encode("./index.php?cmd=".$this->moduleName."&act=customers&tpl=showcustdetail&id={$customerId}");

        $description    = isset($_POST['customer_comment']) ? $_POST['customer_comment'] : '';

        $userid     = $objFWUser->objUser->getId();

        $this->_pageTitle       = ($id) ? $_ARRAYLANG['TXT_CRM_NOTES_EDIT'] : $_ARRAYLANG['TXT_CRM_NOTES_ADD'];

        $objTpl     = $this->_objTpl;
        $objTpl->loadTemplateFile("module_{$this->moduleNameLC}_comments_modify.html");
        $objTpl->setGlobalVariable(array(
                'MODULE_NAME' => $this->moduleName ,
        ));

        if ($customerId) {

            if (isset($_POST['comment_submit'])) {
                if (!empty($noteTypeId) && !empty($customerId)) {
                    $fields = array(
                        'customer_id'   => (int) $customerId,
                        'notes_type_id' => (int) $noteTypeId,
                        'comment'       => $description,
                        'date'          => $noteDate
                    );
                    if ($id) {
                        $fields['updated_on'] = date('Y-m-d H:i:s');
                        $fields['updated_by'] = (int) $userid;
                        $sql = \SQL::update("module_{$this->moduleNameLC}_customer_comment", $fields, array('escape' => true))." WHERE id = $id";
                    } else {
                        $fields['added_date'] = date('Y-m-d H:i:s');
                        $fields['user_id']    = (int) $userid;
                        $sql = \SQL::insert("module_{$this->moduleNameLC}_customer_comment", $fields, array('escape' => true));
                    }

                    $db = $objDatabase->Execute($sql);
                    if ($db) {
                        $_SESSION['TXT_MSG_OK'] = ($id) ? $_ARRAYLANG['TXT_CRM_COMMENT_UPDATESUCESSMESSAGE'] : $_ARRAYLANG['TXT_CRM_COMMENT_SUCESSMESSAGE'];
                        \Cx\Core\Csrf\Controller\Csrf::header("Location: ".base64_decode($redirect));
                        exit();
                    } else {
                        $this->_strErrMessage = "Some thing went wrong";
                    }
                } else {
                    $this->_strErrMessage = "Some thing went wrong";
                }
            } elseif ($id) {
                $objResult = $objDatabase->Execute("SELECT notes_type_id,
                                                           comment,
                                                           c.date,
                                                           n.name as notes_type
                                                     FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_comment` AS c
                                                       LEFT JOIN ".DBPREFIX."module_".$this->moduleNameLC."_notes AS n
                                                         ON c.notes_type_id = n.id
                                                     WHERE c.id = $id AND customer_id = $customerId");
                if ($objResult) {
                    $noteTypeId  = $objResult->fields['notes_type_id'];
                    $description = $objResult->fields['comment'];
                    $noteType    = $objResult->fields['notes_type'];
                    $noteDate    = $objResult->fields['date'];
                }
            } else if (empty($id)) {
                $noteTypeId = isset ($_GET['notes_type']) ? (int) $_GET['notes_type'] : '';
                if (!empty ($noteTypeId)) {
                    $noteTypeName = $objDatabase->Execute("SELECT id,name FROM ".DBPREFIX."module_".$this->moduleNameLC."_notes WHERE status=1 AND id = $noteTypeId");
                    $noteType     = $noteTypeName->fields['name'];
                }
            }

            $objResult = $objDatabase->Execute("SELECT id, name, icon FROM ".DBPREFIX."module_".$this->moduleNameLC."_notes WHERE status=1 ORDER BY pos");
            if ($objResult) {
                while (!$objResult->EOF) {
                    if (!empty ($objResult->fields['icon'])) {
                        $iconPath = \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesCrmWebPath().'/'.contrexx_raw2xhtml($objResult->fields['icon'])."_16X16.thumb";
                    } else {
                        $iconPath  = '../modules/Crm/View/Media/customer_note.png';
                    }
                    $this->_objTpl->setVariable(array(
                            'CRM_NOTE_TYPE_ID'  => (int) $objResult->fields['id'],
                            'CRM_NOTE_TYPE'     => contrexx_raw2xhtml($objResult->fields['name']),
                            'CRM_NOTE_TYPE_ICON'=> $iconPath
                    ));
                    $this->_objTpl->parse('NoteType');
                    $objResult->MoveNext();
                }
            }

            $objTpl->setVariable(array(
                'CRM_NOTES_TYPE_ID'         => (int) $noteTypeId,
                'CRM_NOTES_TYPE'            => ($noteTypeId) ? contrexx_raw2xhtml($noteType) : $_ARRAYLANG['TXT_CRM_TASK_SELECTNOTES'],
                'CRM_NOTES_DATE'            => contrexx_raw2xhtml($noteDate),
                'CRM_CUSTOMER_ID'           => $customerId,
                'CRM_COMMENT_DESCRIPTION'   =>  new \Cx\Core\Wysiwyg\Wysiwyg('customer_comment', contrexx_raw2xhtml($description))
            ));
        } else {
            $this->_strErrMessage = "Customer should not be empty";
        }

        $objTpl->setVariable(array(
            'TXT_CRM_NOTES_TITLE'           => ($id) ? $_ARRAYLANG['TXT_CRM_NOTES_EDIT'] : $_ARRAYLANG['TXT_CRM_NOTES_ADD'],
            'TXT_CRM_COMMENT_DESCRIPTION'   => $_ARRAYLANG['TXT_CRM_COMMENT_DESCRIPTION'],
            'TXT_CRM_OVERVIEW_NOTESTYPE'    => $_ARRAYLANG['TXT_CRM_OVERVIEW_NOTESTYPE'],
            'TXT_CRM_CUSTOMER_OVERVIEW'     => $_ARRAYLANG['TXT_CRM_CUSTOMER_OVERVIEW'],
            'TXT_CRM_SAVE'                  => $_ARRAYLANG['TXT_CRM_SAVE'],
            'TXT_CRM_BACK'                  => $_ARRAYLANG['TXT_CRM_BACK'],
            'TXT_CRM_DUE_DATE'              => $_ARRAYLANG['TXT_CRM_DUE_DATE'],
            'TXT_CRM_BACK_LINK'             => isset ($_GET['notes_type']) ? "./index.php?cmd=pm&act=projects&tpl=projectdetails&projectid=$projectid" : base64_decode($redirect)
        ));
    }

    /**
     * Delete the Comment single
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function deleteCustomerComment()
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;

        $id         = $_REQUEST['commentid'];
        $customerId = $_REQUEST['id'];
        $redirect   = isset($_REQUEST['redirect']) ? $_REQUEST['redirect'] : base64_encode("./index.php?cmd=".$this->moduleName."&act=customers&tpl=showcustdetail&id={$customerId}");

        if (!empty($id)) {

            $deleteQuery = 'DELETE FROM `'.DBPREFIX.'module_'.$this->moduleNameLC.'_customer_comment` WHERE id = '.$id;
            $objDatabase->Execute($deleteQuery);

            if (isset($redirect))
                $_SESSION['TXT_MSG_OK'] = $_ARRAYLANG['TXT_CRM_COMMENT_DELETESUCESSMESSAGE'];
            \Cx\Core\Csrf\Controller\Csrf::header("Location: ".base64_decode($redirect));
        }
        die();
    }

    /**
     * show the interface
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function showInterface()
    {
        global $_ARRAYLANG;

        $tpl = isset($_GET['subTpl']) ? $_GET['subTpl'] : '';
        $_SESSION['pageTitle'] = $_ARRAYLANG['TXT_CRM_SETTINGS'];

        $this->crmInterfaceController = new CrmInterface($this->_objTpl, $this->moduleName);

        switch ($tpl) {
        case 'export':
                $this->crmInterfaceController->showExport();
            break;
        case 'exportcsv':
                $this->crmInterfaceController->csvExport();
            break;
        case 'importCsv';
                $this->crmInterfaceController->csvImport();
            break;
        case 'importoptions':
                $importOptions = $this->crmInterfaceController->getImportOptions();
                echo json_encode($importOptions);
                exit();
            break;
        case 'save':
                $this->crmInterfaceController->saveCsvData();
            break;
        case 'getCsvRecord':
                $this->crmInterfaceController->getCsvRecord();
            break;
        case 'import':
        default:
                $this->crmInterfaceController->showImport();
            break;
        }

        return ;

        $this->_objTpl->addBlockfile('CRM_SETTINGS_FILE', 'settings_block', 'module_'.$this->moduleNameLC.'_interface_entries.html');
        $this->_objTpl->setGlobalVariable(array(
                'MODULE_NAME' => $this->moduleName
        ));
        // Passing javascript functions to template.
        $this->_objTpl->setVariable(array(
                //  'INTERFACE_JAVASCRIPT'    =>    $this->getInterfaceJavascript(),
        ));
        // Passing the place holders to the template page
        $this->_objTpl->setVariable(array(
                'TXT_CRM_EXPORT_NAME'                   => $_ARRAYLANG['TXT_CRM_EXPORT_NAME'],
                'TXT_CRM_IMPORT_NAME'                   => $_ARRAYLANG['TXT_CRM_IMPORT_NAME'],
                'TXT_CRM_EXPORT_INFO'                 => $_ARRAYLANG['TXT_CRM_EXPORT_INFO'],
                'TXT_CRM_FUNCTIONS'               => $_ARRAYLANG['TXT_CRM_FUNCTIONS'],
                'TXT_CRM_EXPORT_CUSTOMER_CSV'     => $_ARRAYLANG['TXT_CRM_EXPORT_CUSTOMER_CSV'],
                'TXT_CRM_IMPORT_PAGE_HEADING'     => $_ARRAYLANG['TXT_CRM_IMPORT_PAGE_HEADING'],
                'TXT_CRM_IMPORT_FILE_TYPE'     => $_ARRAYLANG['TXT_CRM_IMPORT_FILE_TYPE'],
                'TXT_CRM_IMPORT_CSV_TXT'     => $_ARRAYLANG['TXT_CRM_IMPORT_CSV_TXT'],
                'TXT_CRM_CHOOSE_FILE'     => $_ARRAYLANG['TXT_CRM_CHOOSE_FILE'],
                'TXT_CRM_CSV_SEPARATOR'     => $_ARRAYLANG['TXT_CRM_CSV_SEPARATOR'],
                'TXT_CRM_CSV_ENCLOSURE'     => $_ARRAYLANG['TXT_CRM_CSV_ENCLOSURE'],
                'TXT_CRM_CSV_TABLE'     => $_ARRAYLANG['TXT_CRM_CSV_TABLE'],
                'TXT_CRM_CSV_CHOOSE_TABLE'     => $_ARRAYLANG['TXT_CRM_CSV_CHOOSE_TABLE'],
                'TXT_CRM_CUSTOMERS'     => $_ARRAYLANG['TXT_CRM_CUSTOMERS'],
                'INTERFACE_JAVASCRIPT'    =>    $objJs->getInterfaceJavascript(),
        ));
    }

    /**
     * notes overview page
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function notesOverview()
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase, $objJs;

        //For notes type Upload
        $options = array(
            'id' => 'notesUploader',
            'upload-limit' => 1,
            'style' => 'display:none;'
        );
        $uploaderCodeTaskType = $this->initUploader('notesUploadFinished', 'notesCallbackJs', '', $_ARRAYLANG['TXT_BROWSE'], $options);
        $redirectUrl = \Cx\Core\Csrf\Controller\Csrf::enhanceURI('index.php?cmd=Crm&act=getImportFilename');
        $this->_objTpl->setVariable(array(
            'COMBO_UPLOADER_CODE_NOTES'     => $uploaderCodeTaskType,
            'REDIRECT_URL'                  => $redirectUrl
        ));

        $fn = isset ($_REQUEST['fn']) ? $_REQUEST['fn'] : '';
        if (!empty($fn)) {
            switch ($fn) {
            case 'editnotestype':
                $this->editnotes();
            break;
            }
            return;
        }
        $this->_objTpl->addBlockfile('CRM_SETTINGS_FILE', 'settings_block', 'module_'.$this->moduleNameLC.'_settings_notes.html');
        $this->_pageTitle = $_ARRAYLANG['TXT_CRM_SETTINGS'];
        $this->_objTpl->setGlobalVariable(array(
                'MODULE_NAME' => $this->moduleName
        ));

        if (!isset($_GET['message'])) {
            $_GET['message']='';
        }
        switch ($_GET['message']) {
        case 'updatenotes' :
                $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_NOTES_UPDATED'];
            break;
        }

        $name       = isset($_POST['name'])? contrexx_input2db($_POST['name']):'';
        $status     = isset($_POST['status'])? intval($_POST['status']):'';
        $icon       = isset($_POST['icon'])? contrexx_input2db($_POST['icon']):'';
        $position   = isset($_POST['sorting'])? intval($_POST['sorting']):'';
        $id         = isset($_GET['idr'])? intval($_GET['idr']):'';

        if (isset($_GET['idr'])) {
            $objComment = $objDatabase->Execute("SELECT notes_type_id FROM ".DBPREFIX."module_".$this->moduleNameLC."_customer_comment WHERE notes_type_id = '$id'");
            if ($objComment->fields['notes_type_id'] != $id) {
                $objResult = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_".$this->moduleNameLC."_notes WHERE id = '$id'");
                $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_NOTES_DELETED'];
            } else {
                $this->_strErrMessage = $_ARRAYLANG['TXT_CRM_NOTES_ERROR'];
            }
        }

        if (isset($_GET['chg']) and $_GET['chg'] == 1 and isset($_POST['selected']) and is_array($_POST['selected'])) {
            if ($_POST['form_activate'] != '' or $_POST['form_deactivate'] != '') {
                $ids = $_POST['selected'];
                $to  = $_POST['form_activate'] ? 1 : 0;
                foreach ($ids as $id) {
                    $query = "UPDATE ".DBPREFIX."module_".$this->moduleNameLC."_notes
                                                                   SET   status  = '".$to."'
                                                                   WHERE id      = '".intval($id)."'";
                    $objDatabase->SelectLimit($query, 1);
                }
                $this->_strOkMessage = ($to == 1) ? $_ARRAYLANG['TXT_CRM_ACTIVATED_SUCCESSFULLY'] : $_ARRAYLANG['TXT_CRM_DEACTIVATED_SUCCESSFULLY'];
            }
            if ($_POST['form_delete'] != '') {
                $ids = $_POST['selected'];
                $x   = 0;
                foreach ($ids as $id) {
                    $objComment = $objDatabase->Execute("SELECT notes_type_id FROM ".DBPREFIX."module_".$this->moduleNameLC."_customer_comment WHERE notes_type_id = '$id'");
                    if ($objComment->fields['notes_type_id'] != $id) {
                        $query = "DELETE FROM ".DBPREFIX."module_".$this->moduleNameLC."_notes
                                                                       WHERE system_defined != 1 AND id = '".intval($id)."'";
                        $objDelete = $objDatabase->SelectLimit($query, 1);
                        if ($objDelete) {
                            $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_NOTES_DELETED'];
                        }
                    } else {
                        $this->_strErrMessage = $_ARRAYLANG['TXT_CRM_NOTES_ERROR'];
                    }
                }
            }
        }
        if (isset($_GET['chg']) and $_GET['chg'] == 1 and $_POST['form_sort'] == 1) {
            for ($x = 0; $x < count($_POST['form_id']); $x++) {
                $query = "UPDATE ".DBPREFIX."module_".$this->moduleNameLC."_notes
                                                  SET   pos   = '".intval($_POST['form_pos'][$x])."'
                                                  WHERE id    = '".intval($_POST['form_id'][$x])."'";
                $objDatabase->Execute($query);
            }
            $this->_strOkMessage = ($_POST['form_sort'] == 1) ? $_ARRAYLANG['TXT_CRM_SORTING_COMPLETE'] : '';
        }
        if (isset($_POST['save'])) {
            $validate = $this->validation($name);
            if ($validate) {
                $objResult = $objDatabase->Execute("INSERT ".DBPREFIX."module_".$this->moduleNameLC."_notes SET name   ='$name',
                                                                                                                  status = '$status',
                                                                                                                  icon   = '$icon',
                                                                                                                  pos    = '$position'");
                $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_NOTES_INSERTED'];
            } else {
                $this->_strErrMessage = $_ARRAYLANG['TXT_CRM_ERROR'];
            }
        }
        if (isset($_POST['notes_save'])) {
            for ($x = 0; $x < count($_POST['form_id']); $x++) {
                $query = "UPDATE ".DBPREFIX."module_".$this->moduleNameLC."_notes
                                           SET   pos   = '".intval($_POST['form_pos'][$x])."'
                                           WHERE id    = '".intval($_POST['form_id'][$x])."'";
                $objDatabase->Execute($query);
            }
            $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_SORTING_COMPLETE'];
        }

        $sortf = isset($_GET['sortf']) && isset($_GET['sorto']) ? (($_GET['sortf'] == 1)? 'pos':'name') : 'pos';
        $sorto = isset($_GET['sortf']) && isset($_GET['sorto']) ? (($_GET['sorto'] == 'ASC') ? 'DESC' : 'ASC') : 'ASC';
        $objResult = $objDatabase->Execute("SELECT id, name, status, pos, system_defined, icon FROM ".DBPREFIX."module_".$this->moduleNameLC."_notes ORDER BY $sortf $sorto");

        $row = 'row2';
        while (!$objResult->EOF) {
            $stat = $objResult->fields['status'];

            if ($objResult->fields['system_defined']) {
                $this->_objTpl->hideBlock('noteDeleteIcon');
            } else {
                $this->_objTpl->touchBlock('noteDeleteIcon');
            }

            if (!empty ($objResult->fields['icon'])) {
                $iconPath = \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesCrmWebPath().'/'.contrexx_raw2xhtml($objResult->fields['icon'])."_16X16.thumb";
            } else {
                $iconPath  = '../modules/Crm/View/Media/customer_note.png';
            }

            $this->_objTpl->setVariable(array(
                    'TXT_NOTES_ID'      => (int) $objResult->fields['id'],
                    'TXT_NOTES_NAME'    => contrexx_raw2xhtml($objResult->fields['name']),
                    'TXT_NOTES_ICON'    => $iconPath,
                    'TXT_NOTES_STATVAL' => $stat,
                    'TXT_NOTES_STATUS'  => ($stat == 1)? 'green':'red',
                    'TXT_NOTES_SORTING' => (int) $objResult->fields['pos'],
                    'TXT_ROW'           => $row = ($row == 'row2') ? 'row1' : 'row2',
                    'TXT_ORDER'         => $sorto
            ));

            $this->_objTpl->parse('users');
            $objResult->MoveNext();
        }
        $this->_objTpl->setVariable(array(
                'TXT_CRM_ICON'                      => $_ARRAYLANG['TXT_CRM_ICON'],
                'TXT_CRM_GENERAL'                   => $_ARRAYLANG['TXT_CRM_GENERAL'],
                'TXT_CRM_CUSTOMER_TYPES'            => $_ARRAYLANG['TXT_CRM_CUSTOMER_TYPES'],
                'TXT_CRM_CURRENCY'                  => $_ARRAYLANG['TXT_CRM_CURRENCY'],
                'TXT_CRM_NOTES'                     => $_ARRAYLANG['TXT_CRM_NOTES'],
                'TXT_CRM_NAME'                      => $_ARRAYLANG['TXT_CRM_LABEL'],
                'TXT_CRM_SAVE'                      => $_ARRAYLANG['TXT_CRM_SAVE'],
                'TXT_CRM_TITLEACTIVE'               => $_ARRAYLANG['TXT_CRM_TITLEACTIVE'],
                'TXT_CRM_SORTING_NUMBER'            => $_ARRAYLANG['TXT_CRM_SORTING_NUMBER'],
                'TXT_CRM_ADD_NOTES_TYPES'           => $_ARRAYLANG['TXT_CRM_ADD_NOTES_TYPES'],
                'TXT_CRM_TITLE_STATUS'              => $_ARRAYLANG['TXT_CRM_TITLE_STATUS'],
                'TXT_CRM_SORTING'                   => $_ARRAYLANG['TXT_CRM_SORTING'],
                'TXT_CRM_FUNCTIONS'                 => $_ARRAYLANG['TXT_CRM_FUNCTIONS'],
                'TXT_ENTRIES_MARKED'                => $_ARRAYLANG['TXT_ENTRIES_MARKED'],
                'TXT_CRM_SELECT_ALL'                => $_ARRAYLANG['TXT_CRM_SELECT_ALL'],
                'TXT_CRM_DESELECT_ALL'              => $_ARRAYLANG['TXT_CRM_REMOVE_SELECTION'],
                'TXT_CRM_SELECT_ACTION'             => $_ARRAYLANG['TXT_CRM_SELECT_ACTION'],
                'TXT_CRM_NO_OPERATION'              => $_ARRAYLANG['TXT_CRM_NO_OPERATION'],
                'TXT_CRM_ACTIVATESELECTED'          => $_ARRAYLANG['TXT_CRM_ACTIVATESELECTED'],
                'TXT_CRM_DEACTIVATESELECTED'        => $_ARRAYLANG['TXT_CRM_DEACTIVATESELECTED'],
                'TXT_CRM_PROJECTSTATUS_SAVE_SORTING'=> $_ARRAYLANG['TXT_CRM_PROJECTSTATUS_SAVE_SORTING'],
                'TXT_CRM_NOTES_DELETED'             => $_ARRAYLANG['TXT_CRM_NOTES_DELETED'],
                'TXT_CRM_DELETE_CONFIRM'            => $_ARRAYLANG['TXT_CRM_DELETE_CONFIRM'],
                'TXT_CRM_CHANGE_STATUS'             => $_ARRAYLANG['TXT_CRM_CHANGE_STATUS'],
                'TXT_CRM_DELETE_SELECTED'           => $_ARRAYLANG['TXT_CRM_DELETE_SELECTED'],
                'PM_SETTINGS_CURRENCY_JAVASCRIPT' => $objJs->getAddNotesJavascript(),
                'TXT_BROWSE'                        => $_ARRAYLANG['TXT_BROWSE'],
                'TXT_CRM_ARE_YOU_SURE_DELETE_ENTRIES' => $_ARRAYLANG['TXT_CRM_ARE_YOU_SURE_DELETE_ENTRIES'],
        ));
    }

    /**
     * edit notes page
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function editnotes()
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase, $objJs;

        $this->_objTpl->addBlockfile('CRM_SETTINGS_FILE', 'settings_block', 'module_'.$this->moduleNameLC.'_settings_editnotes.html');
        $this->_pageTitle = $_ARRAYLANG['TXT_CRM_EDIT_SETTINGS'];
        $this->_objTpl->setGlobalVariable(array(
                'MODULE_NAME' => $this->moduleName
        ));

        $id       = isset($_GET['id'])? intval($_GET['id']):'';
        $name     = isset($_POST['name'])? contrexx_input2db($_POST['name']):'';
        $icon     = isset($_POST['icon'])? contrexx_input2db($_POST['icon']):'';
        $status   = isset($_POST['status'])? intval($_POST['status']):'';
        $position = isset($_POST['sorting'])? intval($_POST['sorting']):'';

        if (isset($_POST['currency_submit'])) {

            if (!empty($id) && $this->validation($name, $id)) {
                $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_".$this->moduleNameLC."_notes
                                                                                            SET   name    = '$name',
                                                                                                  status  = '$status',
                                                                                                  icon    = '$icon',
                                                                                                  pos     = '$position'
                                                                                            WHERE id      = '$id'");
                $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_NOTES_UPDATED'];
                $cx = \Cx\Core\Core\Controller\Cx::instanciate();
                \Cx\Core\Csrf\Controller\Csrf::header("Location:".$cx->getCodeBaseOffsetPath(). $cx->getBackendFolderName()."/index.php?cmd=".$this->moduleName."&act=settings&tpl=notes");
                exit();
            } else {
                $this->_strErrMessage = $_ARRAYLANG['TXT_CRM_ERROR'];
            }
        } else {
            $objResult = $objDatabase->Execute("SELECT id,name,status,pos,icon FROM ".DBPREFIX."module_".$this->moduleNameLC."_notes WHERE id = '$id'");

            $name     = $objResult->fields['name'];
            $icon     = $objResult->fields['icon'];
            $status   = $objResult->fields['status'];
            $position = $objResult->fields['pos'];
        }

        $this->_objTpl->setVariable(array(
                'TXT_NOTESNAME'   =>  contrexx_raw2xhtml($name),
                'TXT_NOTESICON'   =>  contrexx_raw2xhtml($icon),
                'TXT_NOTESSTATUS' =>  ($status == 1) ? 'checked':'',
                'TXT_NOTESPOS'    =>  (int) $position,
        ));
        $this->_objTpl->setVariable(array(
                'TXT_CRM_ICON'                      => $_ARRAYLANG['TXT_CRM_ICON'],
                'TXT_CRM_NOTES'                     => $_ARRAYLANG['TXT_CRM_NOTES'],
                'TXT_CRM_NAME'                      => $_ARRAYLANG['TXT_CRM_LABEL'],
                'TXT_CRM_TITLEACTIVE'               => $_ARRAYLANG['TXT_CRM_TITLEACTIVE'],
                'TXT_CRM_SORTING_NUMBER'            => $_ARRAYLANG['TXT_CRM_SORTING_NUMBER'],
                'TXT_CRM_ADD_NOTES_TYPES'           => $_ARRAYLANG['TXT_CRM_ADD_NOTES_TYPES'],
                'TXT_CRM_TITLE_STATUS'              => $_ARRAYLANG['TXT_CRM_TITLE_STATUS'],
                'TXT_CRM_SORTING'                   => $_ARRAYLANG['TXT_CRM_SORTING'],
                'TXT_CRM_NAME'                      => $_ARRAYLANG['TXT_CRM_LABEL'],
                'TXT_CRM_FUNCTIONS'                 => $_ARRAYLANG['TXT_CRM_FUNCTIONS'],
                'TXT_CRM_SAVE'                      => $_ARRAYLANG['TXT_CRM_SAVE'],
                'TXT_CRM_EDIT_NOTES'                => $_ARRAYLANG['TXT_CRM_EDIT_NOTES'],
                'TXT_CRM_BACK'                      => $_ARRAYLANG['TXT_CRM_BACK'],
                'TXT_BROWSE'                        => $_ARRAYLANG['TXT_BROWSE'],
                'PM_SETTINGS_CURRENCY_JAVASCRIPT' => $objJs->getAddCurrencyJavascript(),
                'CSRF_PARAM'                    => \Cx\Core\Csrf\Controller\Csrf::param(),
        ));

    }

    /**
     * validation for the notes
     *
     * @param string  $name validation name
     * @param integer $id   id
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function validation($name, $id = 0)
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;
        $objResult = $objDatabase->Execute("SELECT name FROM ".DBPREFIX."module_".$this->moduleNameLC."_notes WHERE name='$name' AND id != $id");
        if ($objResult->fields['name'] == $name) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * delete currency entry
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function deleteCurrency()
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;
        $id = $_GET['id'];
        $defaultId = $objDatabase->getOne('SELECT id FROM `'.DBPREFIX.'module_'.$this->moduleNameLC.'_currency` WHERE default_currency = "1"');

        if (!empty($id)) {
            if ($defaultId != $id) {
                $deleteQuery = 'DELETE FROM `'.DBPREFIX.'module_'.$this->moduleNameLC.'_currency` WHERE default_currency != 1 AND id = '.$id;
                $objDatabase->Execute($deleteQuery);
                $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_CURRENCY_DELETED_SUCCESSFULLY'];
            } else {
                $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_CRM_DEFAULT_CURRENCY_ERROR'];
            }
        } else {
            $deleteIds = $_POST['selectedEntriesId'];
            foreach ($deleteIds as $id) {
                if ($defaultId != $id) {
                    $deleteQuery = 'DELETE FROM `'.DBPREFIX.'module_'.$this->moduleNameLC.'_currency` WHERE default_currency != 1 AND id = '.$id;
                    $objDatabase->Execute($deleteQuery);
                    $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_CURRENCY_DELETED_SUCCESSFULLY'];
                } else {
                    $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_CRM_DEFAULT_CURRENCY_ERROR'];
                }
            }
        }
        \Cx\Core\Csrf\Controller\Csrf::header('location:./index.php?cmd=Crm&act=settings&tpl=currency');
        exit();
    }

    /**
     * change the currency status
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function currencyChangeStatus()
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;
        $status = ($_GET['status'] == 0) ? 1 : 0;
        $id     = intval($_GET['id']);
        $defaultId = $objDatabase->getOne('SELECT id FROM `'.DBPREFIX.'module_'.$this->moduleNameLC.'_currency` WHERE default_currency = "1"');
        if (!empty($id)) {
            if ($defaultId != $id) {
                $query = 'UPDATE '.DBPREFIX.'module_'.$this->moduleNameLC.'_currency SET active='.$status.' WHERE default_currency != "1" AND id = '.$id;
                $objDatabase->Execute($query);
                $_SESSION['strOkMessage'] = ($status == 1) ? $_ARRAYLANG['TXT_CRM_ACTIVATED_SUCCESSFULLY'] : $_ARRAYLANG['TXT_CRM_DEACTIVATED_SUCCESSFULLY'];
            } else {
                $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_CRM_DEFAULT_CURRENCY_STATUS_ERROR'];
            }
        }

        if ($_REQUEST['type'] == "activate") {
            $arrStatusNote = $_POST['selectedEntriesId'];
            if ($arrStatusNote != null) {
                foreach ($arrStatusNote as $noteId) {
                    $query = "UPDATE ".DBPREFIX."module_".$this->moduleNameLC."_currency SET active='1' WHERE id=$noteId";
                    $objDatabase->Execute($query);
                }
            }
            $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_ACTIVATED_SUCCESSFULLY'];
        }
        if ($_REQUEST['type'] == "deactivate") {
            $arrStatusNote = $_POST['selectedEntriesId'];
            if ($arrStatusNote != null) {
                foreach ($arrStatusNote as $noteId) {
                    if ($defaultId != $noteId) {
                        $query = "UPDATE ".DBPREFIX."module_".$this->moduleNameLC."_currency SET active='0' WHERE default_currency != '1' AND id=$noteId";
                        $objDatabase->Execute($query);
                    } else {
                        $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_CRM_DEFAULT_CURRENCY_STATUS_ERROR'];
                    }
                }
            }
            $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_DEACTIVATED_SUCCESSFULLY'];
        }
        \Cx\Core\Csrf\Controller\Csrf::header("Location: ./index.php?cmd=".$this->moduleName."&act=settings&tpl=currency");
        $_GET['tpl'] = 'currency';
        $this->settingsSubmenu();
    }

    /**
     * change the notes status
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function notesChangeStatus()
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;

        $status = ($_GET['stat'] == 0) ? 1 : 0;
        $id     = intval($_GET['ids']);

        if (!empty($id)) {
            $query = 'UPDATE '.DBPREFIX.'module_'.$this->moduleNameLC.'_notes SET status='.$status.' WHERE id = '.$id;
            $objDatabase->Execute($query);
            $this->_strOkMessage = ($status == 1) ? $_ARRAYLANG['TXT_CRM_ACTIVATED_SUCCESSFULLY'] : $_ARRAYLANG['TXT_CRM_DEACTIVATED_SUCCESSFULLY'];
        }
        $_GET['tpl'] = 'notes';
        $this->settingsSubmenu();
    }

    /**
     * change the customer status
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function changeCustomerStatus()
    {
        global $objDatabase, $_ARRAYLANG;

        $customerId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

        if ($customerId) {
            $objDatabase->Execute("UPDATE `".DBPREFIX."module_{$this->moduleNameLC}_customers`
                                        SET is_active = 0
                                    WHERE id=$customerId");
            echo $_ARRAYLANG['TXT_CRM_CUSTOMER_DEACTIVATE_STATUS'];
        }
        exit();
    }

    /**
     * strips only the given tags
     *
     * @param String  $str          name
     * @param tags    $tags         which needs to be strip
     * @param boolean $stripContent stripContent
     *
     * @return string
     */
    function stripOnlyTags($str, $tags, $stripContent=false)
    {
        $content = '';
        if (!is_array($tags)) {
            $tags = (strpos($str, '>') !== false ? explode('>', str_replace('<', '', $tags)) : array($tags));
            if(end($tags) == '') array_pop($tags);
        }
        foreach ($tags as $tag) {
            if ($stripContent)
                $content = '(.+</'.$tag.'(>|\s[^>]*>)|)';
            $str = preg_replace('#</?'.$tag.'(>|\s[^>]*>)'.$content.'#is', '', $str);
        }
        return $str;
    }

    /**
     * export the customer vcf
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function exportVcf()
    {
        global $objDatabase;

        $id   = (int) $_GET['id'];

        $query    = "SELECT c.`customer_name`,
                            c.`contact_familyname`,
                            c.`contact_type`,
                            c.`contact_role`,
                            c.`contact_customer`,
                            con.`customer_name` AS company,
                            con.`id` AS companyId
                        FROM `".DBPREFIX."module_{$this->moduleNameLC}_contacts` AS c
                        LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_contacts` AS con
                         ON c.`contact_customer` =con.`id`
                        WHERE c.`id` = $id";

        $mailQry  = "SELECT email, email_type, is_primary FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_emails` WHERE contact_id = $id AND email_type IN (0,1) ORDER BY id DESC";
        $phoneQry = "SELECT phone, phone_type, is_primary FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_phone` WHERE contact_id = $id AND phone_type IN (0,1,2,3) ORDER BY id DESC";
        $webQry   = "SELECT url, url_type     FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_websites` WHERE contact_id = $id AND `is_primary` = '1' ORDER BY id DESC";
        $addrQry  = "SELECT *                 FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_address` WHERE contact_id = $id AND Address_Type IN (0,5) ORDER BY id DESC";

        if (false != ($objRS = $objDatabase->Execute($query))  && false != ($objMail = $objDatabase->Execute($mailQry)) && false != ($objPhone = $objDatabase->Execute($phoneQry)) && false != ($objWeb = $objDatabase->Execute($webQry)) && false != ($objAddr = $objDatabase->Execute($addrQry))) {

            $isWorkEmail = false;
            $isHomeEmail = false;
            while (!$objMail->EOF) {
                switch (true) {
                case ($objMail->fields['email_type'] == 0 && $objMail->fields['is_primary'] == 0):
                    $isHomeEmail      = true;
                    $homeEmail        = utf8_decode($objMail->fields['email']);
                    break;
                case ($objMail->fields['email_type'] == 0 && $objMail->fields['is_primary'] == 1):
                    $isHomeEmail      = true;
                    $primaryHomeEmail = utf8_decode($objMail->fields['email']);
                    break;
                case ($objMail->fields['email_type'] == 1 && $objMail->fields['is_primary'] == 0):
                    $isWorkEmail      = true;
                    $workEmail        = utf8_decode($objMail->fields['email']);
                    break;
                case ($objMail->fields['email_type'] == 1 && $objMail->fields['is_primary'] == 1):
                    $isWorkEmail      = true;
                    $primaryWorkEmail = utf8_decode($objMail->fields['email']);
                    break;
                default:
                    break;
                }
                $objMail->MoveNext();
            }
            $isWorkPhone = false;
            $isHomePhone = false;
            while (!$objPhone->EOF) {
                switch (true) {
                case ($objPhone->fields['phone_type'] == 0 && $objPhone->fields['is_primary'] == 0):
                        $isHomePhone           = true;
                        $homeTelephone         = utf8_decode($objPhone->fields['phone']);
                    break;
                case ($objPhone->fields['phone_type'] == 0 && $objPhone->fields['is_primary'] == 1):
                        $isHomePhone           = true;
                        $primaryHomeTelephone  = utf8_decode($objPhone->fields['phone']);
                    break;
                case ($objPhone->fields['phone_type'] == 1 && $objPhone->fields['is_primary'] == 0):
                        $isWorkPhone           = true;
                        $wrkTelephone          = utf8_decode($objPhone->fields['phone']);
                    break;
                case ($objPhone->fields['phone_type'] == 1 && $objPhone->fields['is_primary'] == 1):
                        $isWorkPhone           = true;
                        $primaryWrkTelephone   = utf8_decode($objPhone->fields['phone']);
                    break;
                case ($objPhone->fields['phone_type'] == 2 && $objPhone->fields['is_primary'] == 0):
                        $celephone             = utf8_decode($objPhone->fields['phone']);
                    break;
                case ($objPhone->fields['phone_type'] == 2 && $objPhone->fields['is_primary'] == 1):
                        $primaryCelephone      = utf8_decode($objPhone->fields['phone']);
                    break;
                case ($objPhone->fields['phone_type'] == 3 && $objPhone->fields['is_primary'] == 0):
                        $fax                   = utf8_decode($objPhone->fields['phone']);
                    break;
                case ($objPhone->fields['phone_type'] == 3 && $objPhone->fields['is_primary'] == 1):
                        $primaryFax            = utf8_decode($objPhone->fields['phone']);
                    break;
                default:
                    break;
                }
                $objPhone->MoveNext();
            }
            while (!$objWeb->EOF) {
                $homeWebsite      = utf8_decode(html_entity_decode(contrexx_raw2xhtml($objWeb->fields['url']), ENT_QUOTES, CONTREXX_CHARSET));
                $workWebsite      = utf8_decode(html_entity_decode(contrexx_raw2xhtml($objWeb->fields['url']), ENT_QUOTES, CONTREXX_CHARSET));
                $objWeb->MoveNext();
            }
            $workAddr = false;
            $homeAddr = false;
            while (!$objAddr->EOF) {
                switch (true) {
                case ($objAddr->fields['Address_Type'] == 0 && $objAddr->fields['is_primary'] == 0):
                        $homeAddr       = true;
                        $homeAddress    = utf8_decode($objAddr->fields['address']);
                        $homeCity       = utf8_decode($objAddr->fields['city']);
                        $homeState      = utf8_decode($objAddr->fields['state']);
                        $homePostalcode = utf8_decode($objAddr->fields['zip']);
                        $homeCountry    = utf8_decode($objAddr->fields['country']);
                    break;
                case ($objAddr->fields['Address_Type'] == 0 && $objAddr->fields['is_primary'] == 1):
                        $homeAddr       = true;
                        $pryHomeAddress    = utf8_decode($objAddr->fields['address']);
                        $pryHomeCity       = utf8_decode($objAddr->fields['city']);
                        $pryHomeState      = utf8_decode($objAddr->fields['state']);
                        $pryHomePostalcode = utf8_decode($objAddr->fields['zip']);
                        $pryHomeCountry    = utf8_decode($objAddr->fields['country']);
                    break;
                case ($objAddr->fields['Address_Type'] == 5 && $objAddr->fields['is_primary'] == 0):
                        $workAddr       = true;
                        $workAddress    = utf8_decode($objAddr->fields['address']);
                        $workCity       = utf8_decode($objAddr->fields['city']);
                        $workState      = utf8_decode($objAddr->fields['state']);
                        $workPostalcode = utf8_decode($objAddr->fields['zip']);
                        $workCountry    = utf8_decode($objAddr->fields['country']);
                    break;
                case ($objAddr->fields['Address_Type'] == 5 && $objAddr->fields['is_primary'] == 1):
                        $workAddr       = true;
                        $pryWorkAddress    = utf8_decode($objAddr->fields['address']);
                        $pryWorkCity       = utf8_decode($objAddr->fields['city']);
                        $pryWorkState      = utf8_decode($objAddr->fields['state']);
                        $pryWorkPostalcode = utf8_decode($objAddr->fields['zip']);
                        $pryWorkCountry    = utf8_decode($objAddr->fields['country']);
                    break;
                default:
                    break;
                }
                $objAddr->MoveNext();
            }


            if ($objRS->fields['contact_type'] == 1) {
                $firstName = utf8_decode($objRS->fields['customer_name']);
                $primary   = true;
            } elseif ($objRS->fields['contact_type'] == 2) {
                $firstName   = utf8_decode($objRS->fields['customer_name']);
                $lastName    = utf8_decode($objRS->fields['contact_familyname']);
                $role        = utf8_decode($objRS->fields['contact_role']);
                $companyName = utf8_decode($objRS->fields['company']);
                //person without company
                $primary     = !empty ($objRS->fields['contact_customer']) ? true : false;
            }

                if (!$workAddr || !$isWorkEmail || !$isWorkPhone || !$isHomeEmail || !$isHomePhone || !$homeAddr) {
                    $objContactCompany = $objDatabase->Execute("SELECT email.email,
                                                                     phone.phone,
                                                                     addr.*
                                                                  FROM `".DBPREFIX."module_{$this->moduleNameLC}_contacts` AS c
                                                                  LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_emails` as email
                                                                   ON (c.id = email.contact_id AND email.is_primary = '1')
                                                                  LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_phone` as phone
                                                                   ON (c.id = phone.contact_id AND phone.is_primary = '1')
                                                                  LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_address` as addr
                                                                   ON (c.id = addr.contact_id AND addr.is_primary = '1')
                                                                  WHERE c.`id` = {$id}");
                if (!$isWorkEmail) {
                    $workEmail    = utf8_decode($objContactCompany->fields['email']);
                }
                if (!$isHomeEmail) {
                    $homeEmail    = utf8_decode($objContactCompany->fields['email']);
                }
                if (!$isWorkPhone) {
                    $wrkTelephone = utf8_decode($objContactCompany->fields['phone']);
                }
                if (!$isHomePhone) {
                    $homeTelephone = utf8_decode($objContactCompany->fields['phone']);
                }
                if (!$workAddr) {
                    $workAddress    = utf8_decode($objContactCompany->fields['address']);
                    $workCity       = utf8_decode($objContactCompany->fields['city']);
                    $workState      = utf8_decode($objContactCompany->fields['state']);
                    $workPostalcode = utf8_decode($objContactCompany->fields['zip']);
                    $workCountry    = utf8_decode($objContactCompany->fields['country']);
                }
                if (!$homeAddr) {
                    $homeAddress    = utf8_decode($objContactCompany->fields['address']);
                    $homeCity       = utf8_decode($objContactCompany->fields['city']);
                    $homeState      = utf8_decode($objContactCompany->fields['state']);
                    $homePostalcode = utf8_decode($objContactCompany->fields['zip']);
                    $homeCountry    = utf8_decode($objContactCompany->fields['country']);
                }
            }

            $vc        = new CrmVcard();
            $vc->data['customer_name']      = $firstName." ".$lastName;
            $vc->data['company']            = $companyName;
            $vc->data['email1']             = !$primary ? (!empty ($primaryHomeEmail) ? $primaryHomeEmail : $homeEmail) : (!empty ($primaryWorkEmail) ? $primaryWorkEmail : $workEmail);
            $vc->data['email2']             = !$primary ? (!empty ($primaryWorkEmail) ? $primaryWorkEmail : $workEmail) : (!empty ($primaryHomeEmail) ? $primaryHomeEmail : $homeEmail);
            $vc->data['title']              = $role;
            $vc->data['first_name']         = $firstName;
            $vc->data['last_name']          = $lastName;
            $vc->data['work_address']       = !empty ($pryWorkAddress) ? $pryWorkAddress : $workAddress;
            $vc->data['work_city']          = !empty ($pryWorkCity) ? $pryWorkCity : $workCity;
            $vc->data['work_tele']          = !empty ($primaryWrkTelephone) ? $primaryWrkTelephone : $wrkTelephone;
            $vc->data['work_postal_code']   = !empty ($pryWorkPostalcode) ? $pryWorkPostalcode : $workPostalcode;
            $vc->data['work_country']       = !empty ($pryWorkCountry) ? $pryWorkCountry : $workCountry;
            if (!$primary) {
                $vc->data['home_pref']      = ",pref";
            }
            $vc->data['home_address']       = !empty ($pryHomeAddress) ? $pryHomeAddress : $homeAddress;
            $vc->data['home_city']          = !empty ($pryHomeCity) ? $pryHomeCity : $homeCity;
            $vc->data['home_country']       = !empty ($pryHomeCountry) ? $pryHomeCountry : $homeCountry;
            $vc->data['home_postal_code']   = !empty ($pryHomePostalcode) ? $pryHomePostalcode : $homePostalcode;
            $vc->data['home_tel']           = !empty ($primaryHomeTelephone) ? $primaryHomeTelephone : $homeTelephone;
            $vc->data['cell_tel']           = !empty ($primaryCelephone) ? $primaryCelephone : $celephone;
            $vc->data['office_tel']         = !empty ($primaryWrkTelephone) ? $primaryWrkTelephone : $wrkTelephone;
            $vc->data['fax_tel']            = !empty ($primaryFax) ? $primaryFax : $fax;
            $vc->data['fax_home']           = !empty ($primaryFax) ? $primaryFax : $fax;
            $vc->data['work_url']           = $workWebsite;
            $vc->data['home_url']           = $homeWebsite;
            $vc->download();
        }
        exit();
    }

    /**
     * change the customer contact status
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function changeCustomerContactStatus()
    {
        global $objDatabase;

        $objTemplate = $this->_objTpl;

        $id = (int) $_GET['id'];

        if ($id) {
            $result = $objDatabase->Execute("UPDATE `".DBPREFIX."module_{$this->moduleNameLC}_contacts` SET `status` = IF(status = 1, 0, 1) WHERE id = $id");
        }

        exit();
    }

    /**
     * get task submenu
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function submenu()
    {
        global $_ARRAYLANG;
        $this->_objTpl->loadTemplateFile('module_'.$this->moduleNameLC.'_tasks.html', true, true);

        $this->_objTpl->setVariable(array(
                'MODULE_NAME'                    => $this->moduleName,
                "TXT_CRM_OVERVIEW"   => $_ARRAYLANG['TXT_CRM_OVERVIEW'],
                'TXT_CRM_ADD_TASK'  => $_ARRAYLANG['TXT_CRM_ADD_TASK'],
                'TXT_CRM_ADD_IMPORT'  => $_ARRAYLANG['TXT_CRM_ADD_IMPORT'],
                'TXT_CRM_ADD_EXPORT'  => $_ARRAYLANG['TXT_CRM_ADD_EXPORT'],
        ));
    }

    /**
     * get task overview page
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function showTasks()
    {
        global $_ARRAYLANG, $objDatabase;

        $this->crmTaskController = new CrmTask($this->_objTpl, $this->moduleName);
        $tpl = isset($_GET['tpl']) ? $_GET['tpl'] : '';

        switch ($tpl) {
        case 'modify':
                $this->crmTaskController->_modifyTask();
            break;
        case 'cstatus':
                $this->crmTaskController->changeTaskStatus();
                exit();
            break;
        case 'deleteTask':
                $this->crmTaskController->deleteTask();
            break;
        default:
                $this->crmTaskController->overview();
            break;
        }

        return;
    }

    /**
     * customer tool tip
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function customerTooltipDetail()
    {
        global $_ARRAYLANG, $objDatabase, $objJs;

        $objtpl  = $this->_objTpl;
        $this->_objTpl->loadTemplateFile('module_'.$this->moduleNameLC.'_customer_tooltip_detail.html');
        $objtpl->setGlobalVariable("MODULE_NAME", $this->moduleName);
        $contactid = isset($_REQUEST['contactid']) ? (int) $_REQUEST['contactid'] : 0;

        if (!empty($contactid)) {

            $contactCount = $objDatabase->getOne("SELECT count(1) FROM `".DBPREFIX."module_{$this->moduleNameLC}_contacts` WHERE contact_customer = $contactid");

            $query = "SELECT   c.id,
                               c.customer_name,
                               email.email,
                               phone.phone,
                               addr.address, addr.city, addr.state, addr.zip, addr.country
                       FROM `".DBPREFIX."module_{$this->moduleNameLC}_contacts` AS c
                       LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_contacts` AS con
                         ON c.contact_customer =con.id
                       LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_emails` as email
                         ON (c.id = email.contact_id AND email.is_primary = '1')
                       LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_phone` as phone
                         ON (c.id = phone.contact_id AND phone.is_primary = '1')
                       LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_address` as addr
                         ON (c.id = addr.contact_id AND addr.is_primary = '1')
                       WHERE c.id = $contactid";
            $objResult = $objDatabase->Execute($query);

            $objtpl->setVariable(array(
                    'CUSTOMER_NAME'        => contrexx_raw2xhtml($objResult->fields['customer_name']),
                    'CUSTOMER_PHONE'       => contrexx_raw2xhtml($objResult->fields['phone']),
                    'CUSTOMER_EMAIL'       => contrexx_raw2xhtml($objResult->fields['email']),
                    'CUSTOMER_NOOF_CONTACT'=> (int) $contactCount,
                    'CUSTOMER_ADDRESS'     => contrexx_raw2xhtml($objResult->fields['address']),
                    'CUSTOMER_CITY'        => ' '.contrexx_raw2xhtml($objResult->fields['city']),
                    'CUSTOMER_STATE'       => contrexx_raw2xhtml($objResult->fields['state']),
                    'CUSTOMER_POSTCODE'    => ' '.contrexx_raw2xhtml($objResult->fields['zip']),
                    'CRM_CONTACT_COUNTRY'  => contrexx_raw2xhtml($objResult->fields['country']),
                    'CRM_CONTACT_COMMA'    => !empty ($objResult->fields['zip']) || !empty ($objResult->fields['city']) ? ', ' : '',
                    'CRM_CUSTOMER_ID'      => (int) $objResult->fields['id'],
                    'TXT_CRM_CONTACT_TOOLTIP_HEAD' => $_ARRAYLANG['TXT_CRM_CONTACT_TOOLTIP_HEAD'],
                    'CSRF_PARAM'           => \Cx\Core\Csrf\Controller\Csrf::param(),
            ));
        }
        echo $this->_objTpl->get();
        exit();
    }

    /**
     * get contacts to link the customer
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function getLinkContacts()
    {
        global  $objDatabase;

        $searchTerm = (isset($_GET['term'])) ? contrexx_input2raw($_GET['term']) : '';

        $objResult = $objDatabase->Execute("SELECT `id`,
                                                   `customer_name`,
                                                   `contact_familyname`
                                                   FROM `".DBPREFIX."module_{$this->moduleNameLC}_contacts`
                                            WHERE `contact_type` = 2
                                              AND `contact_customer` = 0
                                              AND (contact_familyname like '%$searchTerm%' OR customer_name like '%$searchTerm%')");

        $contacts = array();
        while (!$objResult->EOF) {
            $contacts[] = array(
                    'id'    => (int) $objResult->fields['id'],
                    'label' => html_entity_decode(stripslashes($objResult->fields['contact_familyname'].' '.$objResult->fields['customer_name']), ENT_QUOTES, CONTREXX_CHARSET),
            );
            $objResult->MoveNext();
        }
        echo json_encode($contacts);
        exit();
    }

    /**
     * add new contact
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function addContact()
    {
        global $objDatabase, $_ARRAYLANG;

        $contactId  = (isset ($_GET['id'])) ? (int) $_GET['id'] : 0;
        $customerId = (isset ($_GET['customerid'])) ? (int) $_GET['customerid'] : 0;
        $tpl = isset($_GET['tpl']) ? $_GET['tpl'] : '';

        $this->contact = new \Cx\Modules\Crm\Model\Entity\CrmContact();
        if ($contactId)
            $this->contact->load($contactId);

        isset($_POST['firstname'])  ? $this->contact->customerName = $_POST['firstname'] : '';
        isset($_POST['familyname']) ? $this->contact->family_name  = $_POST['familyname'] : '';
        isset($_POST['language'])   ? $this->contact_language      = $_POST['language'] : '';

        $this->contact->contact_customer = $customerId;
        $this->contact->contactType = 2;
        $this->contact->save();
        switch ($tpl) {
        case 'delete':
            $this->unlinkContact($contactId);
            exit();
        case 'add':
            // insert email
            $objDatabase->Execute("INSERT INTO `".DBPREFIX."module_".$this->moduleNameLC."_customer_contact_emails`
                                        SET `email` = '".contrexx_input2db($_POST['email'])."',
                                            `email_type` = 1, `is_primary` = '1', contact_id = {$this->contact->id}");
            break;
        default:
            break;
        }
        $objTpl = $this->_objTpl;
        $objTpl->loadTemplateFile("module_{$this->moduleNameLC}_add_customer_contact.html");

        if (isset($this->contact->id)) {
            $objTpl->setVariable(array(
                    'CRM_CONTACT_ID'     => $this->contact->id,
                    'CRM_CONTACT_NAME'   => contrexx_raw2xhtml($this->contact->customerName." ".$this->contact->family_name)
            ));
        }

        echo $objTpl->get();
        exit();
    }

    /**
     * get the task of a contact
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function getContactTasks()
    {
        global $objDatabase, $_ARRAYLANG;

        $objTpl = $this->_objTpl;

        $json = array();
        $contactId = (int) $_GET['id'];
        $intPerpage = 50;
        $intPage    = (isset($_GET['page']) ? (int) $_GET['page']-1 : 0) * $intPerpage;
        $objTpl->loadTemplateFile("module_{$this->moduleNameLC}_contact_tasks.html");
        $objTpl->setGlobalVariable(array(
                'MODULE_NAME'        => $this->moduleName,
                'PM_MODULE_NAME'    => $this->pm_moduleName,
                'CSRF_PARAM'            => \Cx\Core\Csrf\Controller\Csrf::param(),
        ));
        $query = "SELECT tt.name,
                         tt.icon,
                         t.task_status,
                         t.id,
                           t.task_id,
                           t.task_title,
                           t.task_type_id,
                           t.added_by,
                           t.assigned_to,
                           c.customer_name,
                           c.contact_familyname,
                           t.due_date,
                           t.description,
                           c.id AS customer_id
                    FROM ".DBPREFIX."module_{$this->moduleNameLC}_task as t
                    LEFT JOIN ".DBPREFIX."module_{$this->moduleNameLC}_task_types as tt
                        ON (t.task_type_id=tt.id)
                    LEFT JOIN ".DBPREFIX."module_{$this->moduleNameLC}_contacts as c
                        ON t.customer_id = c.id WHERE c.id = $contactId ORDER BY t.id DESC LIMIT $intPage, $intPerpage ";

        $objResult = $objDatabase->Execute($query);
        if ($objResult->RecordCount() == 0 && $_GET['ajax'] == true) {
            $json['msg'] = '0';
        }
        ($objResult->RecordCount() > 0) ? $objTpl->hideBlock("noRecords") : $objTpl->touchBlock("noRecords");

        $row = 'row2';
        $now = strtotime('now');
        if ($objResult) {
            if ($objResult->RecordCount() == 0) {
                $objTpl->setVariable(array(
                        'TXT_NO_RECORDS_FOUND'  => $_ARRAYLANG['TXT_CRM_NO_RECORDS_FOUND']
                ));
                $objTpl->touchblock('noRecords');
            } else {
                $objTpl->hideblock('noRecords');
                while (!$objResult->EOF) {
                    list($task_edit_permission, $task_delete_permission) = $this->getTaskPermission((int) $objResult->fields['added_by'], (int) $objResult->fields['assigned_to']);
                    if (!$task_edit_permission) {
                        $objTpl->hideblock('task_edit_block');
                    }
                    if (!$task_delete_permission) {
                        $objTpl->hideblock('task_delete_block');
                    }
                    $objTpl->setVariable(array(
                            'CRM_TASK_ID'           => (int) $objResult->fields['id'],
                            'CRM_TASKTITLE'         => contrexx_raw2xhtml($objResult->fields['task_title']),
                            'CRM_TASKICON'          => !empty ($objResult->fields['icon']) ? \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesCrmWebPath().'/'.contrexx_raw2xhtml($objResult->fields['icon'])."_24X24.thumb" : '/modules/Crm/View/Media/task_default.png',
                            'CRM_TASKTYPE'          => contrexx_raw2xhtml($objResult->fields['task_type_id']),
                            'CRM_CUSTOMERNAME'      => contrexx_raw2xhtml($objResult->fields['customer_name']." ".$objResult->fields['contact_familyname']),
                            'CRM_DUEDATE'           => contrexx_raw2xhtml(date('h:i A Y-m-d', strtotime($objResult->fields['due_date']))),
                            'TXT_STATUS'            => (int) $objResult->fields['task_status'],
                            'CRM_TASK_TYPE_ACTIVE'  => $objResult->fields['task_status'] == 1 ? 'led_green.gif':'led_red.gif',
                            'TXT_ROW'               => $row = ($row == 'row2')? 'row1':'row2',
                            'CRM_ADDEDBY'           => $this->getUserName($objResult->fields['assigned_to']),
                            'CRM_TASK_CUSTOMER_ID'  => (int) $objResult->fields['customer_id'],
                            'TXT_CRM_IMAGE_EDIT'    => $_ARRAYLANG['TXT_CRM_IMAGE_EDIT'],
                            'TXT_CRM_IMAGE_DELETE'  => $_ARRAYLANG['TXT_CRM_IMAGE_DELETE'],
                            'CRM_TASK_EXPIRED_CLASS'=> $objResult->fields['task_status'] == 1 || strtotime($objResult->fields['due_date']) > $now ? '' : 'task_expired',
                            'CRM_REDIRECT_LINK'     => '&redirect='.base64_encode("&act=customers&tpl=showcustdetail&id=$contactId"),
                    ));
                    $objTpl->parse('showTask');
                    $objResult->MoveNext();
                }


            }
        }
        $objTpl->setVariable(array(
                'TXT_CRM_TASK_STATUS'   => $_ARRAYLANG['TXT_CRM_TASK_STATUS'],
                'TXT_CRM_TASK_TITLE'    => $_ARRAYLANG['TXT_CRM_TASK_TITLE'],
                'TXT_CRM_TASK_TYPE'     => $_ARRAYLANG['TXT_CRM_TASK_TYPE'],
                'TXT_CRM_CUSTOMER_NAME' => $_ARRAYLANG['TXT_CRM_CUSTOMER_NAME'],
                'TXT_CRM_TASK_DUE_DATE' => $_ARRAYLANG['TXT_CRM_TASK_DUE_DATE'],
                'TXT_CRM_ASSIGNEDTO'    => $_ARRAYLANG['TXT_CRM_ASSIGNEDTO'],
                'TXT_CRM_TASK'          => $_ARRAYLANG['TXT_CRM_TASK'],
                'TXT_NO_RECORDS_FOUND'  => $_ARRAYLANG['TXT_CRM_NO_RECORDS_FOUND'],
                'TXT_CRM_ADD_TASK'      => $_ARRAYLANG['TXT_CRM_ADD_TASK'],
                'TXT_CRM_TASK_RESPONSIBLE' => $_ARRAYLANG['TXT_CRM_TASK_RESPONSIBLE'],
                'TXT_CRM_FUNCTIONS'        => $_ARRAYLANG['TXT_CRM_FUNCTIONS'],
                'CRM_CUSTOMER_ID'       => $contactId,
        ));
        $this->_objTpl->setGlobalVariable('CRM_REDIRECT_LINK', '&redirect='.base64_encode("&act=customers&tpl=showcustdetail&id={$contactId}"));

        if (isset($_GET['ajax'])) {
            $this->_objTpl->hideBlock("skipAjaxBlock");
            $this->_objTpl->hideBlock("skipAjaxBlock1");
        }
        $json['content'] = $objTpl->get();
        echo $result = json_encode($json);
        exit();
    }

    /**
     * get projects of a contact
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function getContactProjects()
    {
        global $objDatabase, $_ARRAYLANG;

        $objTpl = $this->_objTpl;
        $objTpl->loadTemplateFile("module_{$this->moduleNameLC}_contacts_projects.html");
        $objTpl->setGlobalVariable(array(
                'MODULE_NAME'        => $this->moduleName,
                'PM_MODULE_NAME'     => $this->pm_moduleName
        ));
        $custId     = (int) $_GET['id'];
        $intPerpage = 50;
        $intPage    = (isset($_GET['page']) ? (int) $_GET['page']-1 : 0) * $intPerpage;

        $dbCustomerId[] = $custId;

        $contactType = $objDatabase->getOne("SELECT `contact_type` FROM `".DBPREFIX."module_{$this->moduleNameLC}_contacts` WHERE `id` = '$custId'");

        if ($contactType == 2) {
            $query = "SELECT `contact_customer` AS customerId FROM `".DBPREFIX."module_{$this->moduleNameLC}_contacts` WHERE `id` = '$custId'";
        } elseif ($contactType == 1) {
            $query = "SELECT `id` AS customerId FROM `".DBPREFIX."module_{$this->moduleNameLC}_contacts` WHERE `contact_customer` = '$custId'";
        }
        $objContacts = $objDatabase->Execute($query);

        if ($objContacts) {
            foreach ($objContacts->GetArray() as $contact) {
                $dbCustomerId[] = (int) $contact['customerId'];
            }
        }

        $whereCustomer = '';
        if (!empty($dbCustomerId)) {
            $whereCustomer = " pro.`customer_id` IN (". implode(',', $dbCustomerId).")";
        }
        $projectsResult = 'SELECT pro.`id`,
                                         pro.`name`,
                                         pro.`domain`,
                                         pro.`status`,
                                         pro.`assigned_to`,
                                         pro.`project_type_id`,
                                         cusWeb.`url`,
                                         pro.`quoted_price`,
                                         pro.`target_date`,
                                         pstatus.`name` AS proStatus,
                                         pstatus.`active` AS proActive,
                                         cus.`customer_name`,
                                         cus.`contact_familyname`,
                                         cus.`contact_type`,
                                         curr.`name` AS currency
                                  FROM `'.DBPREFIX.'module_'.$this->pm_moduleName.'_projects` AS pro
                                  LEFT JOIN `'.DBPREFIX.'module_'.$this->pm_moduleName.'_project_status` AS pstatus
                                      ON pro.`status` = pstatus.`projectstatus_id`
                                  LEFT JOIN `'.DBPREFIX.'module_'.$this->moduleNameLC.'_contacts` AS cus
                                      ON pro.`customer_id` = cus.`id`
                                  LEFT JOIN `'.DBPREFIX.'module_'.$this->moduleNameLC.'_currency` AS curr
                                      ON cus.`customer_currency` = curr.`id`
                                  LEFT JOIN `'.DBPREFIX.'module_'.$this->moduleNameLC.'_customer_contact_websites` AS cusWeb
                                      ON pro.`domain` = cusWeb.`id`
                                  WHERE '. $whereCustomer .' ORDER BY pro.`id` DESC LIMIT '.$intPage.','. $intPerpage;
        $json = array();
        $objProjectResult = $objDatabase->Execute($projectsResult);
        if ($objProjectResult->RecordCount() == 0 && $_GET['ajax'] == true) {
            $json['msg'] = '0';
        }

        if ($objProjectResult->RecordCount() == 0) {
            $this->_objTpl->hideBlock('showProjects');
            $this->_objTpl->touchBlock('noProjectEntries');
        } else {
            $this->_objTpl->touchBlock('showProjects');
            $this->_objTpl->hideBlock('noProjectEntries');
        }

        $row = 'row2';
        while (!$objProjectResult->EOF) {
            $contactType = $objProjectResult->fields['contact_type'];
            $company     = contrexx_raw2xhtml($objProjectResult->fields['customer_name']." ".$objProjectResult->fields['contact_familyname']);
            if (($objProjectResult->fields['project_type_id'] == "") || (trim($company) == "") || ($objProjectResult->fields['proStatus'] == "") || ($objProjectResult->fields['proActive'] == 0) || ($objProjectResult->fields['username'] == "")) {
                $active = '<img border="0" src="../core/Core/View/Media/icons/led_red.gif" alt="" title="Inactive" style="margin-top:4px;"/>';
            } else {
                $active = '<img border="0" src="../core/Core/View/Media/icons/led_green.gif" alt="" title="Inactive" style="margin-top:4px;"/>';
            }
            $this->_objTpl->setVariable(array(
                    'CRM_PROJECT_ACTIVE'       => $active,
                    'CRM_PROJECT_ID'           => (int) $objProjectResult->fields['id'],
                    'CRM_PROJECT_NAME'         => "<a href='index.php?cmd=".$this->moduleName."&act=projectdetails&".\Cx\Core\Csrf\Controller\Csrf::param()."&projectid={$objProjectResult->fields['id']}'>".contrexx_raw2xhtml(html_entity_decode($objProjectResult->fields['url'], ENT_QUOTES, CONTREXX_CHARSET))." - ".contrexx_raw2xhtml($objProjectResult->fields['name'])."</a>",
                    'CRM_PROJECT_QUOTED_PRICE' => contrexx_raw2xhtml($objProjectResult->fields['quoted_price']).' '.contrexx_raw2xhtml($objProjectResult->fields['currency']),
                    'CRM_PROJECT_STATUS'       => contrexx_raw2xhtml($objProjectResult->fields['proStatus']),
                    'CRM_PROJECT_RESPONSIBLE'  => $this->getUserName($objProjectResult->fields['assigned_to']),
                    'CRM_PROJECT_TARGET_DATE'  => $objProjectResult->fields['target_date'],
                    'ENTRY_ROWCLASS'           => $row = ($row == 'row1') ? 'row2' : 'row1',
                    'TXT_COMPANY_NAME'         => $company,
            ));
            $this->_objTpl->parse('showProjects');
            $objProjectResult->MoveNext();
        }

        $this->_objTpl->setGlobalVariable(array (
                'TXT_CRM_ACTIVE'                      => $_ARRAYLANG['TXT_CRM_ACTIVE'],
                'TXT_CRM_PROJECT_ID'                  => $_ARRAYLANG['TXT_CRM_PROJECT_ID'],
                'TXT_CRM_PROJECT_NAME'                => $_ARRAYLANG['TXT_CRM_PROJECT_NAME'],
                'TXT_CRM_PROJECT_QUOTED_PRICE'        => $_ARRAYLANG['TXT_CRM_PROJECT_QUOTED_PRICE'],
                'TXT_CRM_CUSTOMER_NAME'               => $_ARRAYLANG['TXT_CRM_CUSTOMER_NAME'],
                'TXT_CRM_PROJECT_STATUS'              => $_ARRAYLANG['TXT_CRM_PROJECT_STATUS'],
                'TXT_CRM_PROJECT_RESPONSIBLE'         => $_ARRAYLANG['TXT_CRM_PROJECT_RESPONSIBLE'],
                'TXT_CRM_PROJECT_TARGET_DATE'         => $_ARRAYLANG['TXT_CRM_PROJECT_TARGET_DATE'],
                'TXT_CRM_NO_RECORDS_FOUND'                => $_ARRAYLANG['TXT_CRM_NO_RECORDS_FOUND'],
                'TXT_CRM_PROJECTS'                    => $_ARRAYLANG['TXT_CRM_PROJECTS'],
                'TXT_CRM_STATUS_SUCCESSFULLY_CHANGED' => $_ARRAYLANG['TXT_CRM_STATUS_SUCCESSFULLY_CHANGED'],
                'TXT_CRM_NOTE_TYPE'                   => $_ARRAYLANG['TXT_CRM_NOTE_TYPE'],
                'TXT_CRM_TITLE_FUNCTIONS'             => $_ARRAYLANG['TXT_CRM_TITLE_FUNCTIONS'],
                'TXT_CRM_ADD_PROJECT'                 => $_ARRAYLANG['TXT_CRM_ADD_PROJECT'],
                'TXT_COMPANY_NAME'                    => $company,
                'CSRF_PARAM'                          => \Cx\Core\Csrf\Controller\Csrf::param(),
                'CRM_CUSTOMER_ID'                     => $custId,

        ));
        $this->_objTpl->setGlobalVariable('CRM_REDIRECT_LINK', "&redirect=".base64_encode("&cmd=".$this->moduleName."&act=customers&tpl=showcustdetail&id={$custId}"));
        if (isset($_GET['ajax'])) {
            $this->_objTpl->hideBlock("skipAjaxBlock");
            $this->_objTpl->hideBlock("skipAjaxBlock1");
        } else {
            $this->_objTpl->touchBlock("skipAjaxBlock");
            $this->_objTpl->touchBlock("skipAjaxBlock1");
        }
        $json['content'] = $objTpl->get();
        echo $result = json_encode($json);
        exit();
    }

    /**
     * get contact deals
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function getContactDeals()
    {
        global $objDatabase, $_ARRAYLANG;

        $objTpl = $this->_objTpl;
        $objTpl->loadTemplateFile("module_{$this->moduleNameLC}_contacts_deals.html");
        $objTpl->setGlobalVariable(array(
                'MODULE_NAME'        => $this->moduleName,
                'PM_MODULE_NAME'    => $this->pm_moduleName
        ));

        $settings = $this->getSettings();
        $allowPm  = $this->isPmInstalled && $settings['allow_pm'];

        $json = array();
        $custId = (int) $_GET['id'];
        $intPerpage = 50;
        $intPage    = (isset($_GET['page']) ? (int) $_GET['page']-1 : 0) * $intPerpage;

        $dealsResult = "SELECT
                               d.id,
                               d.title,
                               d.quoted_price,
                               d.customer,
                               c.customer_name,
                               c.contact_familyname,
                               d.quote_number,
                               d.assigned_to,
                               d.due_date,
                               d.project_id
                            FROM ".DBPREFIX."module_{$this->moduleNameLC}_deals AS d
                                LEFT JOIN ".DBPREFIX."module_{$this->moduleNameLC}_contacts AS c
                            ON d.customer = c.id
                                LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_websites` AS web
                            ON d.website = web.id
                                WHERE d.`customer` = ".(int) $custId ." ORDER BY d.`id` DESC LIMIT $intPage, $intPerpage";

        $objDealsResult = $objDatabase->Execute($dealsResult);
        if ($objDealsResult->RecordCount() == 0 && $_GET['ajax'] == true) {
            $json['msg'] = '0';
        }

        if ($objDealsResult->RecordCount() == 0) {
            $this->_objTpl->hideBlock('showDeals');
            $this->_objTpl->touchBlock('noDealsEntries');
        } else {
            $this->_objTpl->touchBlock('showDeals');
            $this->_objTpl->hideBlock('noDealsEntries');
        }

        $row = 'row2';
        while (!$objDealsResult->EOF) {
            $title = $allowPm ? "<a href='?cmd={$this->pm_moduleName}&act=projectdetails&projectid={$objDealsResult->fields['project_id']}&".\Cx\Core\Csrf\Controller\Csrf::param()."'>".contrexx_raw2xhtml($objDealsResult->fields['title'])."</a>" : contrexx_raw2xhtml($objDealsResult->fields['title']);
            $userName = $allowPm ? "<a href='?cmd={$this->pm_moduleName}&act=resourcedetails&id={$objDealsResult->fields['assigned_to']}&".\Cx\Core\Csrf\Controller\Csrf::param()."'>".contrexx_raw2xhtml($this->getUserName($objDealsResult->fields['assigned_to']))."</a>" : contrexx_raw2xhtml($this->getUserName($objDealsResult->fields['assigned_to']));
            $this->_objTpl->setVariable(array(
                    'ENTRY_ID'              => (int) $objDealsResult->fields['id'],
                    'CRM_DEALS_TITLE'       => $title,
                    'CRM_CONTACT_NAME'      => "<a href='?cmd=".$this->moduleName."&act=customers&tpl=showcustdetail&id={$objDealsResult->fields['customer']}&".\Cx\Core\Csrf\Controller\Csrf::param()."' title='details'>".contrexx_raw2xhtml($objDealsResult->fields['customer_name']." ".$objDealsResult->fields['contact_familyname']).'</a>',
                    'CRM_DEALS_CONTACT_NAME'=> $userName,
                    'CRM_DEALS_DUE_DATE'    => contrexx_raw2xhtml($objDealsResult->fields['due_date']),
                    'ROW_CLASS'             => $row = ($row == "row2") ? "row1" : 'row2',
                    'CSRF_PARAM_CRM'        => \Cx\Core\Csrf\Controller\Csrf::param(),
            ));
            $this->_objTpl->parse('showDeals');
            $objDealsResult->MoveNext();
        }

        $this->_objTpl->setVariable(array (
                'TXT_CRM_DEALS_OVERVIEW'        => $_ARRAYLANG['TXT_CRM_DEALS_OVERVIEW'],
                'TXT_CRM_DEALS_PERCENTAGE'      => $_ARRAYLANG['TXT_CRM_DEALS_PERCENTAGE'],
                'TXT_CRM_DEALS_TITLE'           => $_ARRAYLANG['TXT_CRM_DEALS_TITLE'],
                'TXT_CRM_DEALS_CUSTOMER_NAME'   => $_ARRAYLANG['TXT_CRM_CUSTOMER_NAME'],
                'TXT_CRM_DEALS_DUE_DATE'        => $_ARRAYLANG['TXT_CRM_DUE_DATE'],
                'TXT_CRM_DEALS_RESPONSIBLE'     => $_ARRAYLANG['TXT_CRM_PROJECT_RESPONSIBLE'],
                'TXT_CRM_OF_CONTACTS'           => $_ARRAYLANG['TXT_CRM_OF_CONTACTS'],
                'TXT_CRM_FUNCTIONS'             => $_ARRAYLANG['TXT_CRM_FUNCTIONS'],
                'TXT_CRM_NO_RECORDS_FOUND'          => $_ARRAYLANG['TXT_CRM_NO_RECORDS_FOUND'],
                'CRM_CUSTOMER_ID'               => $custId,
                'TXT_CRM_ADD_OPPURTUNITY'       => $_ARRAYLANG['TXT_CRM_ADD_DEAL_TITLE'],
                'CSRF_PARAM'                    => \Cx\Core\Csrf\Controller\Csrf::param(),
        ));
        $this->_objTpl->setGlobalVariable('CRM_REDIRECT_LINK', '&redirect='.base64_encode("&act=customers&tpl=showcustdetail&id={$custId}"));
        if (isset($_GET['ajax'])) {
            $this->_objTpl->hideBlock("skipAjaxBlock");
            $this->_objTpl->hideBlock("skipAjaxBlock1");
        } else {
            $this->_objTpl->touchBlock("skipAjaxBlock");
            $this->_objTpl->touchBlock("skipAjaxBlock1");
        }
        $json['content'] = $objTpl->get();
        echo $result = json_encode($json);
        exit();
    }

    /**
     * get contact documents
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function getContactDocuments()
    {
        global $objDatabase, $_ARRAYLANG;

        $objTpl = $this->_objTpl;

        $json = array();
        $tpl  = isset($_GET['tpl']) ? $_GET['tpl'] : '';
        if (!empty($tpl)) {
            switch ($tpl) {
            case 'delete':
                    $this->deleteContactDocument();
                break;
            case 'download':
                    $fileName    = $this->getContactFileNameById((int) $_GET['id'], (int) $_GET['customer']);
                    $this->download($fileName);
                break;
            }
            exit();
        }

        $contactId = (int) $_GET['id'];
        $intPerpage = 50;
        $intPage    = (isset($_GET['page']) ? (int) $_GET['page']-1 : 0) * $intPerpage;
        $objTpl->loadTemplateFile("module_{$this->moduleNameLC}_contact_documents.html");
        $objTpl->setGlobalVariable(array(
                'MODULE_NAME'        => $this->moduleName,
                'PM_MODULE_NAME'    => $this->pm_moduleName,
                'CSRF_PARAM'            => \Cx\Core\Csrf\Controller\Csrf::param(),
        ));
        $query = "SELECT doc.id,
                         doc.document_name,
                         doc.uploaded_date,
                         doc.added_by
                    FROM ".DBPREFIX."module_{$this->moduleNameLC}_customer_documents as doc
                    WHERE doc.contact_id = $contactId ORDER BY doc.id DESC LIMIT $intPage, $intPerpage ";

        $objResult = $objDatabase->Execute($query);

        if ($objResult) {
            if ($objResult->RecordCount() == 0 && $_GET['ajax'] == true) {
                $json['msg'] = '0';
            }
            $objTpl->setVariable(array(
                    'TXT_NO_RECORDS_FOUND'  => $_ARRAYLANG['TXT_CRM_NO_RECORDS_FOUND']
            ));

            $objResult->RecordCount() > 0 ? $objTpl->hideBlock("noRecords") : $objTpl->touchBlock("noRecords");

            while (!$objResult->EOF) {
                $ext = pathinfo(\Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteMediaCrmPath().'/'.$objResult->fields['document_name'], PATHINFO_EXTENSION);
                $fileTypeClass = '';
                switch ($ext) {
                case 'jpg':case 'jpeg':case 'png':case 'gif':case 'bmp':
                        $fileTypeClass = 'document-img';
                    break;
                case 'txt':
                        $fileTypeClass = 'document-txt';
                    break;
                case 'csv':
                        $fileTypeClass = 'document-csv';
                    break;
                case 'doc':
                        $fileTypeClass = 'document-doc';
                    break;
                case 'pdf':
                        $fileTypeClass = 'document-pdf';
                    break;
                case 'xls':
                        $fileTypeClass = 'document-xls';
                case 'xlsx':
                        $fileTypeClass = 'document-xls';
                    break;
                case 'docx':
                        $fileTypeClass = 'document-docx';
                    break;
                }
                $objTpl->setVariable(array(
                    'CRM_DOCUMENT_ID'   => (int) $objResult->fields['id'],
                    'CRM_DOCUMENT_NAME' => contrexx_raw2xhtml($objResult->fields['document_name']),
                    'CRM_ADDED_BY'      => contrexx_raw2xhtml($this->getUserName($objResult->fields['added_by'])),
                    'CRM_ADDED_DATE'    => contrexx_raw2xhtml(date('Y-m-d h:i A', strtotime($objResult->fields['uploaded_date']))),
                    'CRM_FILE_TYPE'     => $fileTypeClass
                ));
                $objTpl->parse('contact_documents');
                $objResult->MoveNext();
            }
        }

        $objTpl->setVariable(array(
            'TXT_CRM_DOCUMENTS'     => $_ARRAYLANG['TXT_CRM_DOCUMENTS'],
            'TXT_CRM_DOCUMENT_NAME' => $_ARRAYLANG['TXT_CRM_DOCUMENT_NAME'],
            'TXT_CRM_ADDED_BY'      => $_ARRAYLANG['TXT_CRM_ADDED_BY'],
            'TXT_CRM_ADDED_DATE'    => $_ARRAYLANG['TXT_CRM_ADDED_DATE'],
            'TXT_CRM_FUNCTIONS'     => $_ARRAYLANG['TXT_CRM_FUNCTIONS'],
            'TXT_CRM_ADD_DOCUMENTS' => $_ARRAYLANG['TXT_CRM_ADD_DOCUMENTS']
        ));
        $objTpl->setGlobalVariable(array(
            'CRM_CUSTOMER_ID'        => $contactId,
            'TXT_CRM_IMAGE_DELETE'   => $_ARRAYLANG['TXT_CRM_IMAGE_DELETE'],
            'TXT_CRM_IMAGE_DOWNLOAD' => $_ARRAYLANG['TXT_CRM_IMAGE_DOWNLOAD']
        ));

        if (isset($_GET['ajax'])) {
            $this->_objTpl->hideBlock("skipAjaxBlock");
            $this->_objTpl->hideBlock("skipAjaxBlock1");
        }
        $json['content'] = $objTpl->get();
        echo $result = json_encode($json);
        exit();
    }

    /**
     * To download a file
     *
     * @param string $file
     *
     * @return null
     */
    public function download($file)
    {
        $objHTTPDownload = new \HTTP_Download();
        $objHTTPDownload->setFile(\Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteMediaCrmPath().'/'.$file);
        $objHTTPDownload->setContentDisposition(HTTP_DOWNLOAD_ATTACHMENT, str_replace('"', '\"', $file));
        $objHTTPDownload->setContentType();
        $objHTTPDownload->send('application/force-download');
        exit;
    }

    /**
     * Delete the requested document
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function deleteContactDocument()
    {
        global $objDatabase, $_ARRAYLANG;

        $documentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $customerId = isset($_GET['customer']) ? (int) $_GET['customer'] : 0;
        $json = array();
        if (!empty($customerId) && !empty($documentId)) {
            $fileName = $this->getContactFileNameById($documentId, $customerId);
            unlink(\Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteMediaCrmPath().'/'.$fileName);
            $objDatabase->Execute("DELETE FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_documents` WHERE `id` = $documentId");
            $json['success'] = $_ARRAYLANG['TXT_CRM_DOCUMNET_DELETE_SUCCESS'];
        } else {
            $json['error'] = $_ARRAYLANG['TXT_CRM_ERROR_IN_DELETE_DOCUMENT'];
        }
        echo json_encode($json);
        exit();
    }

    /**
     * return name of the file name
     *
     * @param integer $fileId     file id
     * @param integer $customerId customer id
     *
     * @global object $objDatabase
     *
     * @return file name
     */
    function getContactFileNameById($fileId = 0, $customerId = 0)
    {
        global $objDatabase;

        if (empty($fileId) || empty($customerId))
            return false;

        $fileName = $objDatabase->getOne("SELECT
                                                    `document_name`
                                                FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_documents`
                                                  WHERE `id` = $fileId AND `contact_id` = $customerId");
        return $fileName;
    }

    /**
     * Overview of opportunity
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function dealsOverview()
    {
        global $objDatabase, $_ARRAYLANG;

        \JS::activate("jquery");
        $tpl = isset($_GET['tpl']) ? $_GET['tpl'] : '';
        switch ($tpl) {
        case 'manage':
            $this->_modifyDeal();
            return;
            break;
        default:
            break;
        }

        $settings = $this->getSettings();
        $allowPm  = $this->isPmInstalled && $settings['allow_pm'];

        $objTpl = $this->_objTpl;
        $objTpl->loadTemplateFile("module_{$this->moduleNameLC}_deals_overview.html");
        $this->_pageTitle = $_ARRAYLANG['TXT_CRM_OPPORTUNITY'];

        $objTpl->setGlobalVariable(array(
                'MODULE_NAME'       => $this->moduleName,
                'PM_MODULE_NAME'    => $this->pm_moduleName
        ));

        $action = (isset ($_REQUEST['actionType'])) ? $_REQUEST['actionType'] : '';
        $dealsEntries = (isset($_REQUEST['dealsEntry'])) ? array_map('intval', $_REQUEST['dealsEntry']) : 0;

        switch ($action) {
        case 'delete':
            $this->deleteDeals($dealsEntries, $allowPm);
            break;
        case 'deletedeals':
            $this->deleteDeal($allowPm);
            if (isset($_GET['ajax']))
                exit();
            break;
        default:
            break;
        }

        $mes = isset($_REQUEST['mes']) ? base64_decode($_REQUEST['mes']) : '';
        if (!empty($mes)) {
            switch($mes) {
            case "dealsAdded":
                $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_DEALS_ADDED_SUCCESSFULLY'];
                break;
            case "dealsUpdated":
                $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_DEALS_UPDATED_SUCCESSFULLY'];
                break;
            case "dealsdeleted":
                $this->_strOkMessage = $_ARRAYLANG['TXT_CRM_DEALS_DELETED_SUCCESSFULLY'];
                break;
            }
        }

        $searchLink = '';
        $where      = array();
        if (isset($_GET['term']) && !empty($_GET['term'])) {
            $where[] = " d.title LIKE '%".contrexx_input2raw($_GET['term'])."%' OR c.customer_name LIKE '%".contrexx_input2raw($_GET['term'])."%'";
            $searchLink = "&term={$_GET['term']}";
        }

        //  Join where conditions
        $filter = '';
        if (!empty ($where))
            $filter = " WHERE ".implode(' AND ', $where);

        $sortingFields = array("d.id", "d.title", "d.quoted_price",  "c.customer_name", "u.username", "d.due_date");
        $sorto = (isset ($_GET['sorto'])) ? (((int) $_GET['sorto'] == 0) ? 'DESC' : 'ASC') : 'DESC';
        $sortf = (isset ($_GET['sortf']) && in_array($sortingFields[$_GET['sortf']], $sortingFields)) ? $sortingFields[$_GET['sortf']] : $sortingFields[0];
        $sortLink = isset($_GET['sorto']) && isset($_GET['sortf']) ? "&sorto={$_GET['sorto']}&sortf={$_GET['sortf']}" : '';

        $query = "SELECT
                       d.id,
                       d.title,
                       d.quoted_price,
                       d.customer,
                       c.customer_name,
                       c.contact_familyname,
                       d.quote_number,
                       d.assigned_to,
                       d.due_date,
                       stages.stage AS percent
            FROM ".DBPREFIX."module_{$this->moduleNameLC}_deals AS d
                LEFT JOIN ".DBPREFIX."module_{$this->moduleNameLC}_contacts AS c
            ON d.customer = c.id
                LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_websites` AS web
            ON d.website = web.id
                $filter
                LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_stages` AS stages
            ON d.stage = stages.id
            ORDER BY $sortf $sorto";
        $objResult = $objDatabase->Execute($query);

        /* Start Paging ------------------------------------ */
        $intPos             = (isset($_GET['pos'])) ? intval($_GET['pos']) : 0;
        $intPerPage         = $this->getPagingLimit();
        //$intPerPage         = 5;  //For Testing
        $this->_objTpl->setVariable('ENTRIES_PAGING', getPaging($this->countRecordEntries($query), $intPos, "./index.php?cmd=".$this->moduleName."&act=deals$searchLink$sortLink", false, true, $intPerPage));

        $pageLink           = "&pos=$intPos";
        /* End Paging -------------------------------------- */

        $selectLimit = " LIMIT $intPos, $intPerPage";

        $query = $query. $selectLimit;

        $objResult = $objDatabase->Execute($query);

        if ($objResult) {
            if ($objResult->RecordCount() <= 0)
                $objTpl->touchBlock("dealsNoRecords");
            else
                $objTpl->hideBlock("dealsNoRecords");

            $row = "row2";
            while (!$objResult->EOF) {

                $objTpl->setVariable(array(
                        'ENTRY_ID'              => (int) $objResult->fields['id'],
                        'CRM_DEALS_TITLE'       => contrexx_raw2xhtml($objResult->fields['title']),
                        'CRM_DEALS_PERCENTAGE'  => $objResult->fields['percent'],
                        'CRM_CONTACT_NAME'      => "<a href='?cmd=".$this->moduleName."&act=customers&tpl=showcustdetail&id={$objResult->fields['customer']}' title='details'>".contrexx_raw2xhtml($objResult->fields['customer_name']." ".$objResult->fields['contact_familyname']).'</a>',
                        'CRM_DEALS_CONTACT_NAME'=> contrexx_raw2xhtml($this->getUserName($objResult->fields['assigned_to'])),
                        'CRM_DEALS_DUE_DATE'    => contrexx_raw2xhtml($objResult->fields['due_date']),
                        'CRM_DEALS_QUOTED_PRICE'=> contrexx_raw2xhtml($objResult->fields['quoted_price']),
                        'ROW_CLASS'             => $row = ($row == "row2") ? "row1" : 'row2',
                        'CRM_REDIRECT_LINK'     => '&redirect='.base64_encode("&act=deals{$searchLink}{$sortLink}{$pageLink}"),
                        'TXT_CRM_IMAGE_EDIT'    => $_ARRAYLANG['TXT_EDIT'],
                        'TXT_CRM_IMAGE_DELETE'  => $_ARRAYLANG['TXT_DELETE'],
                ));
                $objTpl->parse("dealsEntries");
                $objResult->MoveNext();
            }
        }

        $sortOrder = isset($_GET['sorto']) && ($_GET['sorto'] == 0) ? 1 : 0;
        $term      = isset($_GET['term']) ? contrexx_input2xhtml($_GET['term']) : '';
        $objTpl->setVariable(array(
                'CRM_NAME_SORT'                 => "&sortf=1&sorto=$sortOrder",
                'CRM_PRICE_SORT'                => "&sortf=2&sorto=$sortOrder",
                'CRM_CUSTOMER_SORT'             => "&sortf=3&sorto=$sortOrder",
                'CRM_RESPONSIBLE_SORT'          => "&sortf=4&sorto=$sortOrder",
                'CRM_DUE_DATE_SORT'             => "&sortf=5&sorto=$sortOrder",
                'CRM_SEARCH_LINK'               => $searchLink,
                'TXT_CRM_SEARCH'                => $_ARRAYLANG['TXT_CRM_SEARCH'],
                'TXT_CRM_DEALS_CREATE'          => $_ARRAYLANG['TXT_CRM_DEALS_CREATE'],
                'TXT_CRM_DEALS_QUOTED_PRICE'    => $_ARRAYLANG['TXT_CRM_PROJECT_QUOTED_PRICE'],
                'TXT_CRM_DEALS_OVERVIEW'        => $_ARRAYLANG['TXT_CRM_DEALS_OVERVIEW'],
                'TXT_CRM_DEALS_PERCENTAGE'      => $_ARRAYLANG['TXT_CRM_DEALS_PERCENTAGE'],
                'TXT_CRM_DEALS_TITLE'           => $_ARRAYLANG['TXT_CRM_DEALS_TITLE'],
                'TXT_CRM_DEALS_CUSTOMER_NAME'   => $_ARRAYLANG['TXT_CRM_CUSTOMER_NAME'],
                'TXT_CRM_FUNCTIONS'             => $_ARRAYLANG['TXT_CRM_FUNCTIONS'],
                'TXT_CRM_DEALS_CONTACT_PERSON'  => $_ARRAYLANG['TXT_CRM_CONTACT_PERSON'],
                'TXT_CRM_DEALS_DUE_DATE'        => $_ARRAYLANG['TXT_CRM_DUE_DATE'],
                'TXT_CRM_SELECT_ACTION'         =>  $_ARRAYLANG['TXT_CRM_SELECT_ACTION'],
                'TXT_CRM_DELETE_SELECTED'       =>  $_ARRAYLANG['TXT_CRM_DELETE_SELECTED'],
                'TXT_CRM_SELECT_ALL'            =>  $_ARRAYLANG['TXT_CRM_SELECT_ALL'],
                'TXT_CRM_REMOVE_SELECTION'      =>  $_ARRAYLANG['TXT_CRM_REMOVE_SELECTION'],
                'TXT_NO_RECORDS_FOUND'          =>  $_ARRAYLANG['TXT_CRM_NO_RECORDS_FOUND'],
                'TXT_SELECT_ENTRIES'            => $_ARRAYLANG['TXT_CRM_NO_OPERATION'],
                'TXT_CRM_FILTERS'               =>  $_ARRAYLANG['TXT_CRM_FILTERS'],
                'TXT_CRM_DEALS_RESPONSIBLE'     =>  $_ARRAYLANG['TXT_CRM_PROJECT_RESPONSIBLE'],
                'CRM_DEALS_SEARCH_TERM'         =>  $term,
                'TXT_CRM_ENTER_SEARCH_TERM'     => $_ARRAYLANG['TXT_CRM_ENTER_SEARCH_TERM'],
                'TXT_CRM_CONFIRM_DELETE_ENTRY'  => $_ARRAYLANG['TXT_CRM_ARE_YOU_SURE_DELETE_ENTRIES'],
                'TXT_CRM_ENTRY_DELETED_SUCCESS' => $_ARRAYLANG['TXT_CRM_ENTRY_DELETED_SUCCESS']
        ));
    }

    /**
     * add /edit of deals
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function _modifyDeal()
    {
        global $objDatabase, $_ARRAYLANG;

        \JS::activate('cx');
        \JS::activate('jqueryui');
        \JS::registerCSS("modules/Crm/View/Style/main.css");
        \JS::registerCSS("modules/Crm/View/Style/contact.css");

        $redirect     = isset($_REQUEST['redirect']) ? $_REQUEST['redirect'] : base64_encode('&act=deals');
        $objTpl = $this->_objTpl;
        $objTpl->loadTemplateFile("module_{$this->moduleNameLC}_deals_modify.html");
        $settings = $this->getSettings();
        $allowPm  = $this->isPmInstalled && $settings['allow_pm'];

        $objTpl->setGlobalVariable(array(
                'MODULE_NAME'       => $this->moduleName,
                'PM_MODULE_NAME'    => $this->pm_moduleName
        ));

        if ($allowPm) {
//            include_once ASCMS_MODULE_PATH . '/pm/lib/pmLib.class.php';
            $objPmLib = new \PmLibrary;
        }


        $id             = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

        $fields = array(
                'title'             => isset($_POST['title']) ? contrexx_input2raw($_POST['title']) : '',
                'website'           => isset($_POST['domain']) ? intval($_POST['domain']) : 0,
                'customer'          => isset($_REQUEST['customer']) ? (int) $_REQUEST['customer'] : 0,
                'customer_contact'  => isset($_POST['customer_contact']) ? (int) $_POST['customer_contact'] : 0,
                'quoted_price'      => isset($_POST['quoted_price']) ? (float) str_replace("'", '', $_POST['quoted_price']) : '',
                'assigned_to'       => isset($_POST['assigned_to']) ? (int) $_POST['assigned_to'] : \FWUser::getFWUserObject()->objUser->getId(),
                'due_date'          => isset($_POST['due_date']) ? contrexx_input2raw($_POST['due_date']) : date("Y-m-d"),
                'description'       => isset($_POST['description']) ? contrexx_input2raw($_POST['description']) : '',
                'stage'             => isset($_POST['dealsStage']) ? contrexx_input2raw($_POST['dealsStage']) : '',
        );

        $projectFileds = array();
        if ($allowPm)
            $projectFileds = array(
                    'invoice_type'      => isset($_POST['invoiceType']) ? (int) $_POST['invoiceType'] : 3,
                    'project_type'      => isset($_POST['project_type']) ? (int) $_POST['project_type'] : 0,
                    'project_status'    => isset($_POST['status']) ? (int) $_POST['status'] : 0,
                    'priority'          => isset($_POST['priority']) ? contrexx_input2raw($_POST['priority']) : 0,
                    'send_invoice'      => isset($_POST['send-partial-invoice']) ? 1 : 0,
                    'hrs_offered'       => isset($_POST['projectDuration']) ? (float) $_POST['projectDuration'] : '',
                    'quote_number'      => isset($_POST['quoteNumber']) ? contrexx_input2raw($_POST['quoteNumber']) : '',
                    'bill_info'         => isset($_POST['billing_info']) ? contrexx_input2raw($_POST['billing_info']) : '',
            );

        if (isset ($_POST['save_deal'])) {
            if (true) {
                $fields['website'] = $this->_getDomainNameId($fields['website'], $fields['customer'], contrexx_input2raw($_POST['domainName']));

                if (!empty($id)) {
                    $query = \SQL::update("module_{$this->moduleNameLC}_deals", $fields, array('escape' => true))." WHERE `id` = $id";
                } else {
                    $query = \SQL::insert("module_{$this->moduleNameLC}_deals", $fields, array('escape' => true));
                }

                //print $query;
                $db = $objDatabase->Execute($query);

                $msg =  empty($id) ? 'dealsAdded' : 'dealsUpdated';

                if (empty($id))
                    $id = $objDatabase->INSERT_ID();

                $projectId = $objDatabase->getOne("SELECT project_id FROM `".DBPREFIX."module_{$this->moduleNameLC}_deals` WHERE id = $id");

                if ($db) {

                    if ($allowPm) {
                        $saveProjects = array(
                                'name'                  => $fields['title'],
                                'quoteNumber'           => $projectFileds['quote_number'],
                                'domain'                => $fields['website'],
                                'customer_id'           => $fields['customer'],
                                'project_type_id'       => $projectFileds['project_type'],
                                'added_by'              => \FWUser::getFWUserObject()->objUser->getId(),
                                'assigned_to'           => $fields['assigned_to'],
                                'status'                => $projectFileds['project_status'],
                                'priority'              => $projectFileds['priority'],
                                'contact_id'            => $fields['customer_contact'],
                                'quoted_price'          => $fields['quoted_price'],
                                'projectDuration'       => $projectFileds['hrs_offered'],
                                'billing_info'          => $projectFileds['bill_info'],
                                'description'           => $fields['description'],
                                'send_partial_invoice'  => $projectFileds['send_invoice'],
                                'internal'              => $projectFileds['invoice_type'],
                                'target_date'           => isset($_POST['dueDate']) ? contrexx_input2raw($_POST['dueDate']) : date("Y-m-d"),
                                'billtype'              => 1
                        );
                        $projectId = $objPmLib->saveOppurtunityProject($saveProjects, $projectId);

                        if (isset($_FILES['documentUpload'])) {
                            $inputName         = 'documentUpload';
                            $date              = date('Y-m-d');
                            $docTitle          = '';
                            $uploadedUserId    = 0;
                            $objPmLib->uploadProjectDocument($inputName, $date, $projectId, $docTitle, $uploadedUserId);
                        }
                        // Update project id to the oppurtunity.
                        $objDatabase->Execute("UPDATE `".DBPREFIX."module_{$this->moduleNameLC}_deals` SET project_id = $projectId WHERE id = $id");
                    }

                    //print base64_decode($redirect);
                    \Cx\Core\Csrf\Controller\Csrf::header("Location:./index.php?cmd=".$this->moduleName."&mes=".base64_encode($msg).base64_decode($redirect));
                    $this->_strOkMessage = "Saved successfully";
                } else {
                    $this->_strErrMessage = "Err in saving";
                }
            } else {
                $this->_strErrMessage = "All fields must be filled out";
            }
        } elseif (!empty($id)) {

            $objResult = $objDatabase->Execute("SELECT d.title,
                                       d.website,
                                       web.url AS siteName,
                                       d.quoted_price,
                                       d.customer,
                                       c.customer_name AS customerName,
                                       c.contact_familyname AS customerFamilyName,
                                       d.customer_contact,
                                       d.quote_number,
                                       d.assigned_to,
                                       d.due_date,
                                       d.description,
                                       d.stage,
                                       d.project_id
                            FROM ".DBPREFIX."module_{$this->moduleNameLC}_deals AS d
                                LEFT JOIN ".DBPREFIX."module_{$this->moduleNameLC}_contacts AS c
                            ON d.customer = c.id
                                LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_websites` AS web
                            ON d.website = web.id
                            WHERE d.id = $id");

            $fields = array(
                    'title'             => $objResult->fields['title'],
                    'website'           => $objResult->fields['website'],
                    'siteName'          => $objResult->fields['siteName'],
                    'customer'          => $objResult->fields['customer'],
                    'customer_name'     => $objResult->fields['customerName']." ".$objResult->fields['customerFamilyName'],
                    'customer_contact'  => $objResult->fields['customer_contact'],
                    'quoted_price'      => $objResult->fields['quoted_price'],
                    'assigned_to'       => $objResult->fields['assigned_to'],
                    'due_date'          => $objResult->fields['due_date'],
                    'description'       => $objResult->fields['description'],
                    'stage'             => $objResult->fields['stage'],
            );

            $projectId = (int) $objResult->fields['project_id'];
            if ($allowPm && !empty($projectId)) {
                $objProjectResult = $objDatabase->Execute("SELECT p.internal,
                                                            p.project_type_id,
                                                            p.status,
                                                            p.priority,
                                                            p.send_partial_invoice,
                                                            p.projectDuration,
                                                            p.quoteNumber,
                                                            p.billing_info
                                                        FROM `".DBPREFIX."module_{$this->pm_moduleName}_projects` AS p
                                                         WHERE id = $projectId");

                $projectFileds = array(
                        'invoice_type'      => $objProjectResult->fields['internal'],
                        'project_type'      => $objProjectResult->fields['project_type_id'],
                        'project_status'    => $objProjectResult->fields['status'],
                        'priority'          => $objProjectResult->fields['priority'],
                        'send_invoice'      => $objProjectResult->fields['send_partial_invoice'],
                        'hrs_offered'       => $objProjectResult->fields['projectDuration'],
                        'quote_number'      => $objProjectResult->fields['quoteNumber'],
                        'bill_info'         => $objProjectResult->fields['billing_info'],
                );

            }
        }

        if (!empty($fields['customer'])) {
            $contactType = $objDatabase->getOne("SELECT contact_type FROM `".DBPREFIX."module_crm_contacts` WHERE id = {$fields['customer']}");
            $contatWhere = $contactType == 1 ? "c.contact_customer" : "c.id";
            $objContactPerson = $objDatabase->Execute("SELECT c.`id`,
                                                          c.`contact_customer`,
                                                          c.`customer_name`,
                                                          c.`contact_familyname`,
                                                          email.`email`
                                                   FROM `".DBPREFIX."module_crm_contacts` AS c
                                                   LEFT JOIN `".DBPREFIX."module_crm_customer_contact_emails` AS email
                                                       ON c.`id` = email.`contact_id` AND email.`is_primary` = '1'
                                                   WHERE $contatWhere = '{$fields['customer']}' AND (status=1 OR c.id ='{$fields['customer_contact']}')");

            while (!$objContactPerson->EOF) {
                $selected = ($fields['customer_contact'] ==  $objContactPerson->fields['id']) ? "selected" : '';
                $contactName = $objContactPerson->fields['customer_name']." ".$objContactPerson->fields['contact_familyname'];

                $this->_objTpl->setVariable(array(
                        'TXT_CONTACT_ID'   =>    (int) $objContactPerson->fields['id'] ,
                        'TXT_CONTACT_NAME' =>   contrexx_raw2xhtml($contactName),
                        'TXT_SELECTED'     =>   $selected));
                $this->_objTpl->parse('Contacts');
                $objContactPerson->MoveNext();
            }

            // Get customer Name
            $objCustomer = $objDatabase->Execute("SELECT customer_name, contact_familyname  FROM `".DBPREFIX."module_crm_contacts` WHERE id = {$fields['customer']}");
            $fields['customer_name'] = $objCustomer->fields['customer_name']." ".$objCustomer->fields['contact_familyname'];
        }

        $this->getDealsStages($fields['stage']);
        $this->_getResourceDropDown('Members', $fields['assigned_to'], $settings['emp_default_user_group']);

        if ($allowPm) {
            $objPmLib->getProjectTypeDropdown($objTpl, $projectFileds['project_type']);
            $objPmLib->getProjectStatusDropdown($objTpl, $projectFileds['project_status']);
            $objPmLib->getProjectPriorityDropdown($objTpl, $projectFileds['priority']);

            $objTpl->setvariable(array(
                    'PROJECT_BILLING_INFO'              => new \Cx\Core\Wysiwyg\Wysiwyg('billing_info', contrexx_raw2xhtml($projectFileds['bill_info'])),
                    'PROJECT_INVOICETYPE_PROJECT'       => ($projectFileds['invoice_type'] == 3) ? 'checked=checked' : '',
                    'PROJECT_INVOICETYPE_COLLECTIVE'    => ($projectFileds['invoice_type'] == 2) ? 'checked=checked' : '',
                    'PROJECT_INVOICETYPE_INTERNAL'      => ($projectFileds['invoice_type'] == 1) ? 'checked=checked' : '',
                    'PM_SEND_PARTIAL_INVOICE_CHECKED'   => !empty($projectFileds['send_invoice']) ? 'checked' : '',
                    'CRM_QUOTATION_NUMBER'              => contrexx_raw2xhtml($projectFileds['quote_number']),
                    'PROJECT_DURATION'                  => number_format($projectFileds['hrs_offered'], 2),

                    'TXT_CRM_ADDITIONAL_INFO'               => $_ARRAYLANG['TXT_CRM_ADDITIONAL_INFO'],
                    'TXT_CRM_INVOICE_TYPE'               => $_ARRAYLANG['TXT_CRM_INVOICE_TYPE'],
                    'TXT_CRM_PROJECT_INVOICETYPE_PROJECT'   => $_ARRAYLANG['TXT_CRM_PROJECT_INVOICETYPE_PROJECT'],
                    'TXT_CRM_PROJECT_INVOICETYPE_COLLECTIVE'=> $_ARRAYLANG['TXT_CRM_PROJECT_INVOICETYPE_COLLECTIVE'],
                    'TXT_CRM_PROJECT_INVOICETYPE_INTERNAL'  => $_ARRAYLANG['TXT_CRM_PROJECT_INVOICETYPE_INTERNAL'],
                    'TXT_CRM_PROJECT_TYPE'                  => $_ARRAYLANG['TXT_CRM_PROJECT_TYPE'],
                    'TXT_CRM_SELECT_PROJECT_TYPE'           => $_ARRAYLANG['TXT_CRM_SELECT_PROJECT_TYPE'],
                    'TXT_CRM_PROJECT_STATUS'                => $_ARRAYLANG['TXT_CRM_PROJECT_STATUS'],
                    'TXT_CRM_PRIORITY'                      => $_ARRAYLANG['TXT_CRM_PRIORITY'],
                    'TXT_CRM_SELECT_PRIORITY'               => $_ARRAYLANG['TXT_CRM_SELECT_PRIORITY'],
                    'TXT_CRM_LOW'                           => $_ARRAYLANG['TXT_CRM_LOW'],
                    'TXT_CRM_MEDIUM'                        => $_ARRAYLANG['TXT_CRM_MEDIUM'],
                    'TXT_CRM_HIGH'                          => $_ARRAYLANG['TXT_CRM_HIGH'],
                    'TXT_CRM_SEND_PARTIAL_INVOICE'       => $_ARRAYLANG['TXT_CRM_SEND_PARTIAL_INVOICE'],
                    'TXT_CRM_PROJECT_DURATION'           => $_ARRAYLANG['TXT_CRM_PROJECT_DURATION'],
                    'TXT_CRM_QUOTE_NUMBER'                  => $_ARRAYLANG['TXT_CRM_QUOTE_NUMBER'],
                    'TXT_CRM_DOCUMENT_UPLOAD'            => $_ARRAYLANG['TXT_CRM_DOCUMENT_UPLOAD'],
                    'TXT_CRM_BILLING_INFORMATION'           => $_ARRAYLANG['TXT_CRM_BILLING_INFORMATION'],
            ));
            if (!empty($id))
                $objTpl->hideBlock("projectDocUpload");
        }

        if (!$allowPm)
            $objTpl->hideBlock("projectEntryBlock");

        $objTpl->setVariable(array(
                'TXT_CRM_DEALS_OVERVIEW'        => $_ARRAYLANG['TXT_CRM_DEALS_OVERVIEW'],
                'TXT_CRM_DEALS_PERCENTAGE'      => $_ARRAYLANG['TXT_CRM_DEALS_PERCENTAGE'],
                'TXT_CRM_DEALS_CUSTOMER_NAME'   => $_ARRAYLANG['TXT_CRM_CUSTOMER_NAME'],
                'TXT_CRM_DEALS_CONTACT_PERSON'  => $_ARRAYLANG['TXT_CRM_CONTACT_PERSON'],
                'TXT_CRM_DEALS_QUOTED_PRICE'    => $_ARRAYLANG['TXT_CRM_PROJECT_QUOTED_PRICE'],
                'TXT_CRM_DEALS_ESTIMATED_HOURS' => $_ARRAYLANG['TXT_CRM_DEALS_ESTIMATED_HOURS'],
                'TXT_CRM_DEALS_QUOTE_NUMBER'    => $_ARRAYLANG['TXT_CRM_DEALS_QUOTE_NUMBER'],
                'TXT_CRM_DEALS_RESPONSIBLE'     => $_ARRAYLANG['TXT_CRM_PROJECT_RESPONSIBLE'],
                'TXT_CRM_DEALS_DUE_DATE'        => $_ARRAYLANG['TXT_CRM_DUE_DATE'],
                'TXT_CRM_DEALS_STAGES'          => $_ARRAYLANG['TXT_CRM_DEALS_STAGES'],
                'TXT_CRM_DEALS_SUCC_RATE'       => $_ARRAYLANG['TXT_CRM_DEALS_SUCC_RATE'],
                'TXT_CRM_SAVE'                  => $_ARRAYLANG['TXT_CRM_SAVE'],
                'TXT_CRM_BACK'                  => $_ARRAYLANG['TXT_CRM_BACK'],
                'CRM_MODIFY_DEAL_TITLE'         => empty($id) ? $_ARRAYLANG['TXT_CRM_ADD_DEAL_TITLE'] : $_ARRAYLANG['TXT_CRM_EDIT_DEAL_TITLE'],

                'CRM_DEALS_TITLE'               => contrexx_raw2xhtml($fields['title']),
                'PM_PROJECT_DOMAIN_ID'          => (int) $fields['website'],
                'PM_PROJECT_DOMAIN_NAME'        => isset($fields['siteName']) ? contrexx_raw2xhtml($fields['siteName']) : '',
                'CRM_DEALS_CUSTOMER'            => (int) $fields['customer'],
                'CRM_DEALS_CUSTOMER_NAME'       => isset($fields['customer_name']) ? contrexx_raw2xhtml($fields['customer_name']) : '',
                'CRM_DEALS_QUOTED_PRICE'        => contrexx_raw2xhtml($fields['quoted_price']),
                'DEALS_DUE_DATE'                => contrexx_raw2xhtml($fields['due_date']),
                'CRM_REDIRECT_LINK'             => $redirect,
                'CRM_BACK_LINK'                 => base64_decode($redirect),
                'CRM_DEALS_DESCRIPTION'         => new \Cx\Core\Wysiwyg\Wysiwyg('description', contrexx_raw2xhtml($fields['description'])),
                'TXT_CRM_DEALS_TITLE'           => $_ARRAYLANG['TXT_CRM_DEALS_TITLE'],
                'TXT_CRM_SELECT_MEMBER_NAME'    => $_ARRAYLANG['TXT_CRM_SELECT_MEMBER_NAME'],
                'CRM_MODIFY_DEAL_DESCRIPTION'   => $_ARRAYLANG['TXT_CRM_DESCRIPTION'],
                'IS_EDIT_TRUE'                  => empty($id) ? 'false' : 'true',
                'TXT_CRM_FIND_COMPANY_BY_NAME'  => $_ARRAYLANG['TXT_CRM_FIND_COMPANY_BY_NAME'],
                'TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT'  => $_ARRAYLANG['TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT'],
                'TXT_CRM_OPPORTUNITY_QUOTE_PRICE_ERROR' => $_ARRAYLANG['TXT_CRM_OPPORTUNITY_QUOTE_PRICE_ERROR']
        ));

        $this->_pageTitle = empty($id) ? $_ARRAYLANG['TXT_CRM_ADD_DEAL_TITLE'] : $_ARRAYLANG['TXT_CRM_EDIT_DEAL_TITLE'];
    }

    /**
     * show settings industry
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function showIndustry()
    {
        global $objDatabase, $_ARRAYLANG, $_LANGID;

        \JS::activate("jquery");

        $fn = isset($_GET['fn']) ? $_GET['fn'] : '';
        if (!empty ($fn)) {
            switch ($fn) {
            case 'modify':
                $this->_modifyIndustry();
                return;
                break;
            }
        }

        $action             = (isset($_REQUEST['actionType'])) ? $_REQUEST['actionType'] : '';
        $indusEntries       = (isset($_REQUEST['indusEntry'])) ? array_map('intval', $_REQUEST['indusEntry']) : 0;
        $indusEntriesorting = (isset($_REQUEST['sorting'])) ? array_map('intval', $_REQUEST['sorting']) : 0;

        if (isset($_SESSION['strOkMessage'])) {
            $strMessage = is_array($_SESSION['strOkMessage']) ? implode("<br>", $_SESSION['strOkMessage']) : $_SESSION['strOkMessage'];
            $this->_strOkMessage = $strMessage;
            unset($_SESSION['strOkMessage']);
        }

        switch ($action) {
        case 'changestatus':
            $this->activateIndustryType((int) $_GET['id']);
            if (isset($_GET['ajax']))
                exit();
        case 'activate':
            $this->activateIndustryType($indusEntries);
            break;
        case 'deactivate':
            $this->activateIndustryType($indusEntries, true);
            break;
        case 'delete':
            $this->deleteIndustryTypes($indusEntries);
            break;
        case 'deleteIndustryType':
            $this->deleteIndustryType();
            if (isset($_GET['ajax']))
                exit();
            break;
        default:
            break;
        }

        if (isset($_POST['save_entries'])) {
            $this->saveSortingIndustryType($indusEntriesorting);
        }

        $objTpl = $this->_objTpl;
        $objTpl->addBlockfile('CRM_SETTINGS_FILE', 'settings_block', 'module_'.$this->moduleNameLC.'_settings_industry.html');
        $this->_pageTitle = $_ARRAYLANG['TXT_CRM_SETTINGS'];
        $objTpl->setGlobalVariable(array(
                'MODULE_NAME' => $this->moduleName,
                'TXT_CRM_IMAGE_EDIT' => $_ARRAYLANG['TXT_CRM_IMAGE_EDIT'],
                'TXT_CRM_IMAGE_DELETE' => $_ARRAYLANG['TXT_CRM_IMAGE_DELETE'],
        ));

        $name     = isset($_POST['name']) ? contrexx_input2raw($_POST['name']) : '';
        $sorting  = isset($_POST['sortingNumber']) ? (int) $_POST['sortingNumber'] : '';
        $status   = isset($_POST['activeStatus']) ? 1 : (empty($_POST) ? 1 : 0);
        $parentId = isset($_POST['parentId']) ? (int) $_POST['parentId'] : 0;

        $industryType = isset($_POST['Inputfield']) ? $_POST['Inputfield'] : array();

        if (isset ($_POST['save_entry'])) {
            $error = false;
            $fields = array(
                    'parent_id'     => $parentId,
                    'sorting'       => $sorting,
                    'status'        => $status
            );

            $field_set = array();
            foreach ($fields as $col => $val) {
                if ($val !== null) {
                    $field_set[] = "`$col` = '".contrexx_input2db($val)."'";
                }
            }
            $field_set = implode(', ', $field_set);

            if (!$error) {
                $query = "INSERT INTO `".DBPREFIX."module_{$this->moduleNameLC}_industry_types` SET
                            $field_set";
                $db = $objDatabase->Execute($query);
                $entryId = !empty($id) ? $id : $objDatabase->INSERT_ID();

                // Insert the name locale
                if ($db) {
                    $objDatabase->Execute("DELETE FROM `".DBPREFIX."module_{$this->moduleNameLC}_industry_type_local` WHERE entry_id = $entryId");
                    foreach ($this->_arrLanguages as $langId => $langValue) {
                        $value = empty($industryType[$langId]) ? contrexx_input2db($industryType[0]) : contrexx_input2db($industryType[$langId]);
                        $objDatabase->Execute("
                            INSERT INTO `".DBPREFIX."module_{$this->moduleNameLC}_industry_type_local` SET
                                `entry_id` = $entryId,
                                `lang_id`   = $langId,
                                `value`    = '$value'
                                ");
                    }
                }

                if ($db) {
                    $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_ENTRY_ADDED_SUCCESS'];
                } else {
                    $this->_strErrMessage = "Error in saving Data";
                }
            }
        }
        $this->listIndustryTypes($objTpl, 1);
        $first = true;
        foreach ($this->_arrLanguages as $langId => $langValue) {

            ($first) ? $objTpl->touchBlock("minimize") : $objTpl->hideBlock("minimize");
            $first = false;

            $objTpl->setVariable(array(
                    'LANG_ID'                 => $langId,
                    'LANG_LONG_NAME'          => $langValue['long'],
                    'LANG_SHORT_NAME'         => $langValue['short'],
                    'CRM_INDUSTRY_NAME_VALUE' => isset($industryType[$langId]) ? contrexx_raw2xhtml($industryType[$langId]) : ''
            ));
            $objTpl->parse("industryTypeNames");
        }

        $objTpl->setGlobalVariable(array(
                'TXT_CRM_MORE'              => $_ARRAYLANG['TXT_CRM_MORE'],
                'TXT_CRM_MINIMIZE'          => $_ARRAYLANG['TXT_CRM_MINIMIZE']
        ));
        $objTpl->setVariable(array(
            'DEFAULT_LANG_ID'               => $_LANGID,
            'LANG_ARRAY'                    => implode(',', array_keys($this->_arrLanguages)),
            'CRM_PARENT_INDUSTRY_DROPDOWN'  => $this->listIndustryTypes($this->_objTpl, 2, $parentId),
            'TXT_CRM_CUSTOMER_INDUSTRY'     => $_ARRAYLANG['TXT_CRM_CUSTOMER_INDUSTRY'],
            'TXT_CRM_OVERVIEW'              => $_ARRAYLANG['TXT_CRM_OVERVIEW'],
            'TXT_CRM_ADD_INDUSTRY'          => $_ARRAYLANG['TXT_CRM_ADD_INDUSTRY'],
            'TXT_STATUS'                    => $_ARRAYLANG['TXT_STATUS'],
            'TXT_CRM_LABEL'                 => $_ARRAYLANG['TXT_CRM_LABEL'],
            'TXT_CRM_ADD_STAGE'             => $_ARRAYLANG['TXT_CRM_ADD_STAGE'],
            'TXT_CRM_SAVE'                  => $_ARRAYLANG['TXT_CRM_SAVE'],
            'TXT_CRM_DEALS_STAGES'          => $_ARRAYLANG['TXT_CRM_DEALS_STAGES'],
            'TXT_CRM_DEALS_STAGE'           => $_ARRAYLANG['TXT_CRM_DEALS_STAGE'],
            'TXT_CRM_SORTING'               => $_ARRAYLANG['TXT_CRM_SORTING'],
            'TXT_CRM_FUNCTIONS'             => $_ARRAYLANG['TXT_CRM_FUNCTIONS'],
            'TXT_CRM_SELECT_ALL'            => $_ARRAYLANG['TXT_CRM_SELECT_ALL'],
            'TXT_CRM_REMOVE_SELECTION'      => $_ARRAYLANG['TXT_CRM_REMOVE_SELECTION'],
            'TXT_CRM_SELECT_ACTION'         => $_ARRAYLANG['TXT_CRM_SELECT_ACTION'],
            'TXT_CRM_ACTIVATESELECTED'      => $_ARRAYLANG['TXT_CRM_ACTIVATESELECTED'],
            'TXT_CRM_DEACTIVATESELECTED'    => $_ARRAYLANG['TXT_CRM_DEACTIVATESELECTED'],
            'TXT_CRM_DELETE_SELECTED'       => $_ARRAYLANG['TXT_CRM_DELETE_SELECTED'],
            'TXT_CRM_CHANGE_STATUS'         => $_ARRAYLANG['TXT_CRM_CHANGE_STATUS'],
            'TXT_CRM_ENTRY_DELETED_SUCCESS' => $_ARRAYLANG['TXT_CRM_ENTRY_DELETED_SUCCESS'],
            'TXT_CRM_OVERVIEW'              => $_ARRAYLANG['TXT_CRM_OVERVIEW'],
            'TXT_CRM_NAME'                  => $_ARRAYLANG['TXT_CRM_TITLE_NAME'],
            'TXT_CRM_TITLEACTIVE'           => $_ARRAYLANG['TXT_CRM_TITLEACTIVE'],
            'TXT_CRM_SORTING_NUMBER'        => $_ARRAYLANG['TXT_CRM_SORTING_NUMBER'],
            'TXT_CRM_SAVE'                  => $_ARRAYLANG['TXT_CRM_SAVE'],
            'TXT_CRM_PARENT_INDUSTRY_TYPE'  => $_ARRAYLANG['TXT_CRM_PARENT_INDUSTRY_TYPE'],
            'TXT_CRM_NEW_INDUSTRY_TYPE'     => $_ARRAYLANG['TXT_CRM_NEW_INDUSTRY_TYPE'],
            'TXT_TITLE_MODIFY_INDUSTRY'     => $_ARRAYLANG['TXT_CRM_ADD_INDUSTRY'],
            'TXT_CRM_NOTHING_SELECTED'      => $_ARRAYLANG['TXT_CRM_NOTHING_SELECTED'],
            'TXT_CRM_ARE_YOU_SURE_DELETE_ENTRIES'           => $_ARRAYLANG['TXT_CRM_ARE_YOU_SURE_DELETE_ENTRIES'],
            'TXT_CRM_ARE_YOU_SURE_DELETE_SELECTED_ENTRIES'  => $_ARRAYLANG['TXT_CRM_ARE_YOU_SURE_DELETE_SELECTED_ENTRIES'],
            'TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT'       => $_ARRAYLANG['TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT'],
        ));

    }

    /**
     * add/ edit industry
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function _modifyIndustry()
    {
        global $objDatabase, $_ARRAYLANG, $_LANGID;

        \JS::activate("jquery");
        $objTpl = $this->_objTpl;
        $objTpl->addBlockfile('CRM_SETTINGS_FILE', 'settings_block', 'module_'.$this->moduleNameLC.'_settings_industry_modify.html');
        $this->_pageTitle = $_ARRAYLANG['TXT_CRM_SETTINGS'];
        $objTpl->setGlobalVariable(array(
                'MODULE_NAME' => $this->moduleName,
                'TXT_CRM_IMAGE_EDIT' => $_ARRAYLANG['TXT_CRM_IMAGE_EDIT'],
                'TXT_CRM_IMAGE_DELETE' => $_ARRAYLANG['TXT_CRM_IMAGE_DELETE'],
        ));

        if (isset($_SESSION['strOkMessage'])) {
            $strMessage = is_array($_SESSION['strOkMessage']) ? implode("<br>", $_SESSION['strOkMessage']) : $_SESSION['strOkMessage'];
            $this->_strOkMessage = $strMessage;
            unset($_SESSION['strOkMessage']);
        }

        $id       = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $name     = isset($_POST['name']) ? contrexx_input2raw($_POST['name']) : '';
        $sorting  = isset($_POST['sortingNumber']) ? (int) $_POST['sortingNumber'] : '';
        $status   = isset($_POST['activeStatus']) ? 1 : (empty($_POST) ? 1 : 0);
        $parentId = isset($_POST['parentId']) ? (int) $_POST['parentId'] : 0;

        $industryType = array();
        foreach ($this->_arrLanguages as $langId => $langValue) {
            if (isset($_POST['Inputfield'][$langId]) && !empty($_POST['Inputfield'][$langId])) {
                $industryType[$langId] = contrexx_input2raw($_POST['Inputfield'][$langId]);
            }
        }
        if (isset ($_POST['save_entry'])) {
            $error = false;
            $fields = array(
                    'parent_id'     => $parentId,
                    'sorting'       => $sorting,
                    'status'        => $status
            );

            $field_set = array();
            foreach ($fields as $col => $val) {
                if ($val !== null) {
                    $field_set[] = "`$col` = '".contrexx_input2db($val)."'";
                }
            }
            $field_set = implode(', ', $field_set);

            if (!empty($id) && ($id == $parentId)) {
                $this->_strErrMessage = "Choose different parent id";
                $error = true;
            }

            if (!$error) {
                if (!empty($id)) {
                    $query = "UPDATE `".DBPREFIX."module_{$this->moduleNameLC}_industry_types` SET
                            $field_set
                      WHERE `id` = $id";
                    $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_ENTRY_UPDATED_SUCCESS'];
                } else {
                    $query = "INSERT INTO `".DBPREFIX."module_{$this->moduleNameLC}_industry_types` SET
                            $field_set";
                    $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_ENTRY_ADDED_SUCCESS'];
                }
                $db = $objDatabase->Execute($query);
                $entryId = !empty($id) ? $id : $objDatabase->INSERT_ID();

                // Insert the name locale
                if ($db) {
                    $objDatabase->Execute("DELETE FROM `".DBPREFIX."module_{$this->moduleNameLC}_industry_type_local` WHERE entry_id = $entryId");
                    foreach ($this->_arrLanguages as $langId => $langValue) {
                        $value = empty($industryType[$langId]) ? contrexx_raw2db($industryType[0]) : contrexx_raw2db($industryType[$langId]);
                        $objDatabase->Execute("
                            INSERT INTO `".DBPREFIX."module_{$this->moduleNameLC}_industry_type_local` SET
                                `entry_id` = $entryId,
                                `lang_id`   = $langId,
                                `value`    = '$value'
                                ");
                    }
                }

                if ($db) {
                    \Cx\Core\Csrf\Controller\Csrf::header("Location:./?cmd=".$this->moduleName."&act=settings&tpl=industry");
                    exit();
                } else {
                    $this->_strErrMessage = "Error in saving Data";
                }
            }
        } elseif (!empty($id)) {
            $objResult = $objDatabase->Execute("SELECT * FROM `".DBPREFIX."module_{$this->moduleNameLC}_industry_types` WHERE id = $id");

            $sorting  = $objResult->fields['sorting'];
            $status   = $objResult->fields['status'];
            $parentId = $objResult->fields['parent_id'];

            $objInputFields = $objDatabase->Execute("SELECT * FROM `".DBPREFIX."module_{$this->moduleNameLC}_industry_type_local` WHERE entry_id = $id");
            while (!$objInputFields->EOF) {
                $industryType[$objInputFields->fields['lang_id']] = $objInputFields->fields['value'];
                $objInputFields->MoveNext();
            }
        }

        $first = true;
        foreach ($this->_arrLanguages as $langId => $langValue) {

            ($first) ? $objTpl->touchBlock("minimize") : $objTpl->hideBlock("minimize");
            $first = false;

            $objTpl->setVariable(array(
                    'LANG_ID'                 => $langId,
                    'LANG_LONG_NAME'          => $langValue['long'],
                    'LANG_SHORT_NAME'         => $langValue['short'],
                    'CRM_INDUSTRY_NAME_VALUE' => isset($industryType[$langId]) ? contrexx_raw2xhtml($industryType[$langId]) : ''
            ));
            $objTpl->parse("industryTypeNames");
        }

        $objTpl->setGlobalVariable(array(
                'MODULE_NAME'               => $this->moduleName,
                'TXT_CRM_MORE'              => $_ARRAYLANG['TXT_CRM_MORE'],
                'TXT_CRM_MINIMIZE'          => $_ARRAYLANG['TXT_CRM_MINIMIZE']
        ));
        $objTpl->setVariable(array(
                'CRM_INDUSTRY_NAME_DEFAULT_VALUE' => isset($industryType[$_LANGID]) ? contrexx_raw2xhtml($industryType[$_LANGID]) : '',
                'CRM_PARENT_INDUSTRY_DROPDOWN'    => $this->listIndustryTypes($this->_objTpl, 2, $parentId),
                'CRM_ACTIVATED_VALUE'             => $status ? "checked='checked'" : '',
                'CRM_SORTINGNUMBER'               => $sorting,
                'DEFAULT_LANG_ID'                 => $_LANGID,
                'LANG_ARRAY'                      => implode(',', array_keys($this->_arrLanguages)),
                'TXT_CRM_CUSTOMER_INDUSTRY'       => $_ARRAYLANG['TXT_CRM_CUSTOMER_INDUSTRY'],
                'TXT_CRM_OVERVIEW'                => $_ARRAYLANG['TXT_CRM_OVERVIEW'],
                'TXT_CRM_NAME'                    => $_ARRAYLANG['TXT_CRM_LABEL'],
                'TXT_CRM_TITLEACTIVE'             => $_ARRAYLANG['TXT_CRM_TITLEACTIVE'],
                'TXT_CRM_SORTING_NUMBER'          => $_ARRAYLANG['TXT_CRM_SORTING_NUMBER'],
                'TXT_CRM_SAVE'                    => $_ARRAYLANG['TXT_CRM_SAVE'],
                'TXT_CRM_PARENT_INDUSTRY_TYPE'    => $_ARRAYLANG['TXT_CRM_PARENT_INDUSTRY_TYPE'],
                'TXT_CRM_NEW_INDUSTRY_TYPE'       => $_ARRAYLANG['TXT_CRM_NEW_INDUSTRY_TYPE'],
                'TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT'       => $_ARRAYLANG['TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT'],
                'TXT_CRM_BACK'                    => $_ARRAYLANG['TXT_CRM_BACK'],
                'TXT_TITLE_MODIFY_INDUSTRY'       => (!empty ($id)) ? $_ARRAYLANG['TXT_CRM_EDIT_INDUSTRY'] : $_ARRAYLANG['TXT_CRM_ADD_INDUSTRY'],
                'CSRF_PARAM'                      => \Cx\Core\Csrf\Controller\Csrf::param(),
        ));
    }

    /**
     * show membership menu
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function showMembership()
    {
        global $objDatabase,$_ARRAYLANG, $_LANGID;

        \JS::activate("jquery");

        $tpl = isset($_GET['subTpl']) ? $_GET['subTpl'] : '';
        if (!empty ($tpl)) {
            switch ($tpl) {
            case 'modify':
                $this->_modifyMembership();
                break;
            }
            return;
        }

        $action              = (isset($_REQUEST['actionType'])) ? $_REQUEST['actionType'] : '';
        $memberEntries       = (isset($_REQUEST['memberEntry'])) ? array_map('intval', $_REQUEST['memberEntry']) : 0;
        $memberEntriesorting = (isset($_REQUEST['sorting'])) ? array_map('intval', $_REQUEST['sorting']) : 0;

        if (isset($_SESSION['strOkMessage'])) {
            $strMessage = is_array($_SESSION['strOkMessage']) ? implode("<br>", $_SESSION['strOkMessage']) : $_SESSION['strOkMessage'];
            $this->_strOkMessage = $strMessage;
            unset($_SESSION['strOkMessage']);
        }

        switch ($action) {
        case 'changestatus':
            $this->activateMembership((int) $_GET['id']);
            if (isset($_GET['ajax']))
                exit();
        case 'activate':
            $this->activateMembership($memberEntries);
            break;
        case 'deactivate':
            $this->activateMembership($memberEntries, true);
            break;
        case 'delete':
            $this->deleteMemberships($memberEntries);
            break;
        case 'deleteMembership':
            $this->deleteMembership();
            if (isset($_GET['ajax']))
                exit();
            break;
        default:
            break;
        }
        if (!empty ($action) || isset($_POST['save_entries'])) {
            $this->saveSortingMembership($memberEntriesorting);
        }

        $objTpl = $this->_objTpl;
        $objTpl->addBlockfile('CRM_SETTINGS_FILE', 'settings_block', 'module_'.$this->moduleNameLC.'_settings_membership.html');
        $this->_pageTitle = $_ARRAYLANG['TXT_CRM_SETTINGS'];
        $objTpl->setGlobalVariable(array(
                'MODULE_NAME' => $this->moduleName,
                'TXT_CRM_IMAGE_EDIT' => $_ARRAYLANG['TXT_CRM_IMAGE_EDIT'],
                'TXT_CRM_IMAGE_DELETE' => $_ARRAYLANG['TXT_CRM_IMAGE_DELETE'],
        ));


        // tab 2
        $id      = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $name    = isset($_POST['name']) ? contrexx_input2raw($_POST['name']) : '';
        $sorting = isset($_POST['sortingNumber']) ? (int) $_POST['sortingNumber'] : '';
        $status  = isset($_POST['activeStatus']) ? 1 : (empty($_POST) ? 1 : 0);

        $inputField = isset($_POST['Inputfield']) ? $_POST['Inputfield'] : array();
        if (isset ($_POST['save_entry'])) {
            $fields = array(
                    'sorting'       => $sorting,
                    'status'        => $status
            );

            $field_set = array();
            foreach ($fields as $col => $val) {
                if ($val !== null) {
                    $field_set[] = "`$col` = '".contrexx_input2db($val)."'";
                }
            }
            $field_set = implode(', ', $field_set);

            if (!empty($id)) {
                $query = "UPDATE `".DBPREFIX."module_{$this->moduleNameLC}_memberships` SET
                        $field_set
                  WHERE `id` = $id";
                $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_ENTRY_UPDATED_SUCCESS'];
            } else {
                $query = "INSERT INTO `".DBPREFIX."module_{$this->moduleNameLC}_memberships` SET
                        $field_set";

            }
            $db = $objDatabase->Execute($query);
            $entryId = !empty($id) ? $id : $objDatabase->INSERT_ID();

            // Insert the name locale
            if ($db) {
                $objDatabase->Execute("DELETE FROM `".DBPREFIX."module_{$this->moduleNameLC}_membership_local` WHERE entry_id = $entryId");
                foreach ($this->_arrLanguages as $langId => $langValue) {
                    $value = empty($inputField[$langId]) ? contrexx_input2db($inputField[0]) : contrexx_input2db($inputField[$langId]);
                    $objDatabase->Execute("
                        INSERT INTO `".DBPREFIX."module_{$this->moduleNameLC}_membership_local` SET
                            `entry_id` = $entryId,
                            `lang_id`   = $langId,
                            `value`    = '$value'
                            ");
                }
            }

            if ($db) {
                $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_ENTRY_ADDED_SUCCESS'];
            } else {
                $this->_strErrMessage = "Error in saving Data";
            }
        }

        $first = true;
        foreach ($this->_arrLanguages as $langId => $langValue) {

            ($first) ? $objTpl->touchBlock("minimize") : $objTpl->hideBlock("minimize");
            $first = false;

            $objTpl->setVariable(array(
                    'LANG_ID'                 => $langId,
                    'LANG_LONG_NAME'          => $langValue['long'],
                    'LANG_SHORT_NAME'         => $langValue['short'],
                    'CRM_SETTINGS_VALUE'      => isset($inputField[$langId]) ? contrexx_raw2xhtml($inputField[$langId]) : ''
            ));
            $objTpl->parse("settingsNames");
        }

        //show all records
        $query = "SELECT membership.*,
                         memberLoc.value,
                         (SELECT COUNT(1) FROM
                            `".DBPREFIX."module_{$this->moduleNameLC}_customer_membership` as m
                            WHERE m.membership_id = membership.id)
                         as cusCount
                     FROM `".DBPREFIX."module_{$this->moduleNameLC}_memberships` AS membership
                     LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_membership_local` AS memberLoc
                        ON membership.id = memberLoc.entry_id
                     WHERE memberLoc.lang_id = ".$_LANGID." ORDER BY sorting ASC ";
        $objResult = $objDatabase->Execute($query);

        if ($objResult && $objResult->RecordCount() == 0) {
            $objTpl->setVariable(array(
                    'TXT_NO_RECORDS_FOUND'  =>  $_ARRAYLANG['TXT_CRM_NO_RECORDS_FOUND']
            ));
        }
        while (!$objResult->EOF) {
            $activeImage = ($objResult->fields['status']) ? '../core/Core/View/Media/icons/led_green.gif' : '../core/Core/View/Media/icons/led_red.gif';
            $objTpl->setVariable(array(
                    'ENTRY_ID'          => $objResult->fields['id'],
                    'CRM_SORTING'       => (int) $objResult->fields['sorting'],
                    'CRM_SUCCESS_STATUS' => $activeImage,
                    'CRM_CUSTOMER_COUNT' => (int) $objResult->fields['cusCount'],
                    'CRM_INDUSTRY_NAME' => contrexx_raw2xhtml($objResult->fields['value'])
            ));
            $objTpl->parse("membershipEntries");
            $objResult->MoveNext();
        }

        $objTpl->setGlobalVariable(array(
                'TXT_CRM_MORE'              => $_ARRAYLANG['TXT_CRM_MORE'],
                'TXT_CRM_MINIMIZE'          => $_ARRAYLANG['TXT_CRM_MINIMIZE']
        ));
        $objTpl->setVariable(array(
            'DEFAULT_LANG_ID'               => $_LANGID,
            'LANG_ARRAY'                    => implode(',', array_keys($this->_arrLanguages)),
            'CSRF_PARAM'                    => \Cx\Core\Csrf\Controller\Csrf::param(),
            'TXT_CRM_CUSTOMER_MEMBERSHIP'   => $_ARRAYLANG['TXT_CRM_CUSTOMER_MEMBERSHIP'],
            'TXT_CRM_ADD_MEMBERSHIP'        => $_ARRAYLANG['TXT_CRM_ADD_MEMBERSHIP'],
            'TXT_STATUS'                    => $_ARRAYLANG['TXT_STATUS'],
            'TXT_CRM_LABEL'                 => $_ARRAYLANG['TXT_CRM_LABEL'],
            'TXT_CRM_SAVE'                  => $_ARRAYLANG['TXT_CRM_SAVE'],
            'TXT_CRM_SORTING'               => $_ARRAYLANG['TXT_CRM_SORTING'],
            'TXT_CRM_FUNCTIONS'             => $_ARRAYLANG['TXT_CRM_FUNCTIONS'],
            'TXT_CRM_SELECT_ALL'            => $_ARRAYLANG['TXT_CRM_SELECT_ALL'],
            'TXT_CRM_REMOVE_SELECTION'      => $_ARRAYLANG['TXT_CRM_REMOVE_SELECTION'],
            'TXT_CRM_SELECT_ACTION'         => $_ARRAYLANG['TXT_CRM_SELECT_ACTION'],
            'TXT_CRM_ACTIVATESELECTED'      => $_ARRAYLANG['TXT_CRM_ACTIVATESELECTED'],
            'TXT_CRM_DEACTIVATESELECTED'    => $_ARRAYLANG['TXT_CRM_DEACTIVATESELECTED'],
            'TXT_CRM_DELETE_SELECTED'       => $_ARRAYLANG['TXT_CRM_DELETE_SELECTED'],
            'TXT_CRM_CHANGE_STATUS'         => $_ARRAYLANG['TXT_CRM_CHANGE_STATUS'],
            'TXT_CRM_ENTRY_DELETED_SUCCESS' => $_ARRAYLANG['TXT_CRM_ENTRY_DELETED_SUCCESS'],
            'TXT_CRM_NOTHING_SELECTED'      => $_ARRAYLANG['TXT_CRM_NOTHING_SELECTED'],
            'TXT_CRM_NAME'                  => $_ARRAYLANG['TXT_CRM_LABEL'],
            'TXT_CRM_TITLEACTIVE'           => $_ARRAYLANG['TXT_CRM_TITLEACTIVE'],
            'TXT_CRM_SORTING_NUMBER'        => $_ARRAYLANG['TXT_CRM_SORTING_NUMBER'],
            'TXT_CRM_ARE_YOU_SURE_DELETE_ENTRIES'           => $_ARRAYLANG['TXT_CRM_ARE_YOU_SURE_DELETE_ENTRIES'],
            'TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT'       => $_ARRAYLANG['TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT'],
            'TXT_CRM_ARE_YOU_SURE_DELETE_SELECTED_ENTRIES'  => $_ARRAYLANG['TXT_CRM_ARE_YOU_SURE_DELETE_SELECTED_ENTRIES']
        ));

    }

    /**
     * add/ edit membership
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function _modifyMembership()
    {
        global $objDatabase, $_ARRAYLANG, $_LANGID;

        \JS::activate("jquery");
        $objTpl = $this->_objTpl;
        $objTpl->addBlockfile('CRM_SETTINGS_FILE', 'settings_block', 'module_'.$this->moduleNameLC.'_settings_membership_modify.html');
        $objTpl->setGlobalVariable(array(
                'MODULE_NAME' => $this->moduleName,
                'TXT_CRM_IMAGE_EDIT' => $_ARRAYLANG['TXT_CRM_IMAGE_EDIT'],
                'TXT_CRM_IMAGE_DELETE' => $_ARRAYLANG['TXT_CRM_IMAGE_DELETE'],
        ));

        $id      = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $name    = isset($_POST['name']) ? contrexx_input2raw($_POST['name']) : '';
        $sorting = isset($_POST['sortingNumber']) ? (int) $_POST['sortingNumber'] : '';
        $status  = isset($_POST['activeStatus']) ? 1 : (empty($_POST) ? 1 : 0);

        $this->_pageTitle = $_ARRAYLANG['TXT_CRM_SETTINGS'];

        $inputField = isset($_POST['Inputfield']) ? $_POST['Inputfield'] : array();
        if (isset ($_POST['save_entry'])) {
            $fields = array(
                    'sorting'       => $sorting,
                    'status'        => $status
            );

            $field_set = array();
            foreach ($fields as $col => $val) {
                if ($val !== null) {
                    $field_set[] = "`$col` = '".contrexx_input2db($val)."'";
                }
            }
            $field_set = implode(', ', $field_set);

            if (!empty($id)) {
                $query = "UPDATE `".DBPREFIX."module_{$this->moduleNameLC}_memberships` SET
                        $field_set
                  WHERE `id` = $id";
                $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_ENTRY_UPDATED_SUCCESS'];
            } else {
                $query = "INSERT INTO `".DBPREFIX."module_{$this->moduleNameLC}_memberships` SET
                        $field_set";
                $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_ENTRY_ADDED_SUCCESS'];
            }
            $db = $objDatabase->Execute($query);
            $entryId = !empty($id) ? $id : $objDatabase->INSERT_ID();

            // Insert the name locale
            if ($db) {
                $objDatabase->Execute("DELETE FROM `".DBPREFIX."module_{$this->moduleNameLC}_membership_local` WHERE entry_id = $entryId");
                foreach ($this->_arrLanguages as $langId => $langValue) {
                    $value = empty($inputField[$langId]) ? contrexx_input2db($inputField[0]) : contrexx_input2db($inputField[$langId]);
                    $objDatabase->Execute("
                        INSERT INTO `".DBPREFIX."module_{$this->moduleNameLC}_membership_local` SET
                            `entry_id` = $entryId,
                            `lang_id`   = $langId,
                            `value`    = '$value'
                            ");
                }
            }

            if ($db) {
                \Cx\Core\Csrf\Controller\Csrf::header("Location:./?cmd=".$this->moduleName."&act=settings&tpl=membership");
                exit();
            } else {
                $this->_strErrMessage = "Error in saving Data";
            }
        } elseif (!empty($id)) {
            $objResult = $objDatabase->Execute("SELECT * FROM `".DBPREFIX."module_{$this->moduleNameLC}_memberships` WHERE id = $id");

            $sorting = $objResult->fields['sorting'];
            $status  = $objResult->fields['status'];

            $objInputFields = $objDatabase->Execute("SELECT * FROM `".DBPREFIX."module_{$this->moduleNameLC}_membership_local` WHERE entry_id = $id");
            while (!$objInputFields->EOF) {
                $inputField[$objInputFields->fields['lang_id']] = $objInputFields->fields['value'];
                $objInputFields->MoveNext();
            }
        }

        $first = true;
        foreach ($this->_arrLanguages as $langId => $langValue) {

            ($first) ? $objTpl->touchBlock("minimize") : $objTpl->hideBlock("minimize");
            $first = false;

            $objTpl->setVariable(array(
                    'LANG_ID'                 => $langId,
                    'LANG_LONG_NAME'          => $langValue['long'],
                    'LANG_SHORT_NAME'         => $langValue['short'],
                    'CRM_SETTINGS_VALUE'      => isset($inputField[$langId]) ? contrexx_raw2xhtml($inputField[$langId]) : ''
            ));
            $objTpl->parse("settingsNames");
        }

        $objTpl->setGlobalVariable(array(
                'TXT_CRM_MORE'              => $_ARRAYLANG['TXT_CRM_MORE'],
                'TXT_CRM_MINIMIZE'          => $_ARRAYLANG['TXT_CRM_MINIMIZE']
        ));
        $objTpl->setVariable(array(
                'CRM_SETTINGS_NAME_DEFAULT_VALUE' => isset($inputField[$_LANGID]) ? contrexx_raw2xhtml($inputField[$_LANGID]) : '',
                'CRM_ACTIVATED_VALUE'             => $status ? "checked='checked'" : '',
                'CRM_SORTINGNUMBER'               => $sorting,
                'DEFAULT_LANG_ID'                 => $_LANGID,
                'LANG_ARRAY'                      => implode(',', array_keys($this->_arrLanguages)),
                'TXT_CRM_OVERVIEW'                => $_ARRAYLANG['TXT_CRM_OVERVIEW'],
                'TXT_CRM_NAME'                    => $_ARRAYLANG['TXT_CRM_LABEL'],
                'TXT_CRM_TITLEACTIVE'             => $_ARRAYLANG['TXT_CRM_TITLEACTIVE'],
                'TXT_CRM_SORTING_NUMBER'          => $_ARRAYLANG['TXT_CRM_SORTING_NUMBER'],
                'TXT_CRM_SAVE'                    => $_ARRAYLANG['TXT_CRM_SAVE'],
                'TXT_CRM_BACK'                    => $_ARRAYLANG['TXT_CRM_BACK'],
                'TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT'  => $_ARRAYLANG['TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT'],
                'TXT_TITLE_MODIFY_INDUSTRY'       => (!empty ($id)) ? $_ARRAYLANG['TXT_CRM_EDIT_MEMBERSHIP'] : $_ARRAYLANG['TXT_CRM_ADD_MEMBERSHIP'],
        ));
    }

    /**
     * get customer search result
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return json result
     */
    function getCustomerSearch()
    {
        global $objDatabase, $_LANGID;

        $where = array();

        $searchContactTypeFilter = isset($_REQUEST['contactSearch']) ? (array) $_REQUEST['contactSearch'] : array(1,2);
        $searchContactTypeFilter = array_map('intval', $searchContactTypeFilter);
        $where[] = " c.contact_type IN (".implode(',', $searchContactTypeFilter).")";

        if (isset($_REQUEST['advanced-search'])) {
            if (isset($_REQUEST['s_name']) && !empty($_REQUEST['s_name'])) {
                $where[] = " (c.customer_name LIKE '".contrexx_input2db($_REQUEST['s_name'])."%' OR c.contact_familyname LIKE '".contrexx_input2db($_REQUEST['s_name'])."%')";
            }
            if (isset($_REQUEST['s_email']) && !empty($_REQUEST['s_email'])) {
                $where[] = " (email.email LIKE '".contrexx_input2db($_REQUEST['s_email'])."%')";
            }
            if (isset($_REQUEST['s_address']) && !empty($_REQUEST['s_address'])) {
                $where[] = " (addr.address LIKE '".contrexx_input2db($_REQUEST['s_address'])."%')";
            }
            if (isset($_REQUEST['s_city']) && !empty($_REQUEST['s_city'])) {
                $where[] = " (addr.city LIKE '".contrexx_input2db($_REQUEST['s_city'])."%')";
            }
            if (isset($_REQUEST['s_postal_code']) && !empty($_REQUEST['s_postal_code'])) {
                $where[] = " (addr.zip LIKE '".contrexx_input2db($_REQUEST['s_postal_code'])."%')";
            }
            if (isset($_REQUEST['s_notes']) && !empty($_REQUEST['s_notes'])) {
                $where[] = " (c.notes LIKE '".contrexx_input2db($_REQUEST['s_notes'])."%')";
            }
        }
        if (isset($_REQUEST['customer_type']) && !empty($_REQUEST['customer_type'])) {
            $where[] = " (c.customer_type = '".intval($_REQUEST['customer_type'])."')";
        }
        if (isset($_REQUEST['filter_membership']) && !empty($_REQUEST['filter_membership'])) {
            $where[] = " mem.membership_id IN(" . 
                implode(
                    ',', 
                    contrexx_input2int($_REQUEST['filter_membership'])
                ) . ")";
        }

        if (isset($_REQUEST['term']) && !empty($_REQUEST['term'])) {
            if (in_array(2, $searchContactTypeFilter)) {
                $fullTextContact[]  =  'c.customer_name, c.contact_familyname';
            }
            if (in_array(1, $searchContactTypeFilter)) {
                $fullTextContact[]  = 'c.customer_name';
            }
            $where[] = " MATCH (".implode(',', $fullTextContact).") AGAINST ('".contrexx_input2raw($_REQUEST['term'])."*' IN BOOLEAN MODE)";
        }

        //  Join where conditions
        $filter = '';
        if (!empty ($where)) {
            $filter = " WHERE ".implode(' AND ', $where);
        }

        $sorto = 'DESC';
        $sortf = 'c.id';

        $query = "SELECT
                       DISTINCT c.id,
                       c.customer_id,
                       c.customer_type,
                       c.customer_name,
                       c.contact_familyname,
                       c.contact_type,
                       c.contact_customer AS contactCustomerId,
                       c.status,
                       c.added_date,
                       con.customer_name AS contactCustomer,
                       email.email,
                       phone.phone,
                       t.label AS cType,
                       Inloc.value AS industryType
                   FROM `".DBPREFIX."module_{$this->moduleNameLC}_contacts` AS c
                   LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_contacts` AS con
                     ON c.contact_customer =con.id
                   LEFT JOIN ".DBPREFIX."module_{$this->moduleNameLC}_customer_types AS t
                     ON c.customer_type = t.id
                   LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_emails` as email
                     ON (c.id = email.contact_id AND email.is_primary = '1')
                   LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_phone` as phone
                     ON (c.id = phone.contact_id AND phone.is_primary = '1')
                   LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_address` as addr
                     ON (c.id = addr.contact_id AND addr.is_primary = '1')
                   LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_customer_membership` as mem
                     ON (c.id = mem.contact_id)
                   LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_industry_types` AS Intype
                     ON c.industry_type = Intype.id
                   LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_industry_type_local` AS Inloc
                     ON Intype.id = Inloc.entry_id AND Inloc.lang_id = ".$_LANGID."
            $filter
                   ORDER BY $sortf $sorto";

        $objResult = $objDatabase->Execute($query);

        $result = array();
        if ($objResult) {
            while (!$objResult->EOF) {
                if ($objResult->fields['contact_type'] == 1) {
                    $contactName = $objResult->fields['customer_name'];
                } else {
                    $contactName = $objResult->fields['customer_name']." ".$objResult->fields['contact_familyname'];
                }
                $result[] = array(
                    'id'    => (int) $objResult->fields['id'],
                    'label' => html_entity_decode(stripslashes($contactName), ENT_QUOTES, CONTREXX_CHARSET),
                    'value' => html_entity_decode(stripslashes($contactName), ENT_QUOTES, CONTREXX_CHARSET),
                );
                $objResult->MoveNext();
            }
        }
        echo json_encode($result);
        exit();
    }

    /**
     * Chek the user already exists in crm and user admin
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return boolean
     */
    public function checkUserAvailablity()
    {
        global $objDatabase, $_ARRAYLANG;

        $json = array();

        $customerId = isset($_GET['id']) ?  intval($_GET['id']) : 0;
        $term       = isset($_GET['term']) ?  contrexx_input2raw($_GET['term']) : '';
        $userId     = 0;
        if (!empty($term)) {
            if ($customerId) {
                $userId = $objDatabase->getOne("SELECT `user_account` FROM `". DBPREFIX ."module_{$this->moduleNameLC}_contacts` WHERE `id` = $customerId");
            }

            $jsonError = $this->isUniqueUsername($term, $userId);

            if ($jsonError) {
                $json['error']   = $jsonError;
            } else {
                $json['success'] = 'Available';

            }
        }

        echo json_encode($json);
        exit();
    }

    /**
     * get customers domain result
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return json result
     */
    function getCustomerDomains()
    {
        global $objDatabase;

        $term       = contrexx_input2db($_GET['term']);
        $customerId = isset($_GET['customer']) ? intval($_GET['customer']) : 0;

        if (!empty($customerId)) {
            $searchCustomer = " AND cus.`id` = $customerId";
        }
        $query = "SELECT web.`id`,
                         web.`url`,
                         web.`contact_id`,
                         cus.`customer_name`
                     FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_websites` AS web
                        LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_contacts` AS cus
                       ON web.`contact_id` = cus.`id`
                     WHERE web.`url` LIKE '%$term%' $searchCustomer ORDER BY web.`url` ASC";
        $objResult = $objDatabase->Execute($query);

        $website = array();

        while (!$objResult->EOF) {
            $website[] = array(
                    'id'         => $objResult->fields['id'],
                    'label'      => html_entity_decode(contrexx_raw2xhtml($objResult->fields['url']), ENT_QUOTES, CONTREXX_CHARSET),
                    'value'      => html_entity_decode(contrexx_raw2xhtml($objResult->fields['url']), ENT_QUOTES, CONTREXX_CHARSET),
                    'company'    => $objResult->fields['customer_name'],
                    'companyId'    => $objResult->fields['contact_id'],
            );
            $objResult->MoveNext();
        }
        echo json_encode($website);
        exit();

    }

    /**
     * Default PM Customer Suggetion box functionality
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return json result
     */
    function autoSuggest()
    {
        global $_ARRAYLANG,$objDatabase;

        $id = intval($_GET['id']);

        $q = "SELECT
                cust.id,
                cust.customer_name,
                cust.contact_familyname,
                cust.customer_id,
                cur.name AS cur_name,
                cust.`contact_type`
             FROM ".DBPREFIX."module_crm_contacts AS cust
             LEFT JOIN ".DBPREFIX."module_crm_currency AS cur
                ON cust.customer_currency = cur.id
             WHERE cust.id ='".$id."' AND status='1'";


        $objResult = $objDatabase->Execute($q);
        $customer = array();

        $contatWhere = $objResult->fields['contact_type'] == 1 ? "c.contact_customer" : "c.id";
        $contactPerson = $objDatabase->Execute(
            "SELECT c.`id`,
                    c.`contact_customer`,
                    c.`customer_name`,
                    c.`contact_familyname`,
                    email.`email`
               FROM `".DBPREFIX."module_crm_contacts` AS c
               LEFT JOIN `".DBPREFIX."module_crm_customer_contact_emails` AS email
                   ON c.`id` = email.`contact_id` AND email.`is_primary` = '1'
               WHERE $contatWhere = '$id' AND `status` = 1"
        );

        $customer['id']         = intval($objResult->fields['id']);
        $customer['company']    = $objResult->fields['contact_type'] == 1 ? stripslashes($objResult->fields['customer_name']) : stripslashes($objResult->fields['customer_name']." ".$objResult->fields['contact_familyname']);// Reply array list for given query
        $customer['cust_input'] = stripslashes($objResult->fields['customer_id']);
        $customer['cur_name']   = stripslashes($objResult->fields['cur_name']);
        $customer['customer'] = array();
        $row = 0;
        while (!$contactPerson->EOF) {
            $customer['customer'][$row] = array();
            $customer['customer'][$row]['name'] = stripslashes($contactPerson->fields['customer_name']." ".$contactPerson->fields['contact_familyname']);
            $customer['customer'][$row]['email'] = stripslashes($contactPerson->fields['email']);
            $customer['customer'][$row]['id'] = intval($contactPerson->fields['id']);
            $contactPerson->MoveNext();
            $row++;
        }

        $rcustomer = json_encode($customer);
        header("Content-Type: application/json");
        echo $rcustomer;

        exit();
    }

    /**
     * get customer search results as json result
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return json result
     */
    function getCustomers()
    {
        global $objDatabase;

        $term = strtolower(contrexx_input2db($_GET['term']));
        // Customers without contacts

        $q = "SELECT   c.id,
                       c.customer_name,
                       c.contact_familyname,
                       c.contact_type
                   FROM `".DBPREFIX."module_{$this->moduleNameLC}_contacts` AS c
                   WHERE LOWER(c.customer_name) LIKE '$term%'";
        $objResult = $objDatabase->Execute($q);

        $customer = array();
        while (!$objResult->EOF) {
            $customerName   = $objResult->fields['contact_type'] == 1 ? contrexx_addslashes($objResult->fields['customer_name']) : contrexx_addslashes($objResult->fields['customer_name']." ".$objResult->fields['contact_familyname']);// Reply array list for given query
            $customer[] = array(
                    'id'    => (int) $objResult->fields['id'],
                    'label' => html_entity_decode(stripslashes($customerName), ENT_QUOTES, CONTREXX_CHARSET),
                    'value' => html_entity_decode(stripslashes($customerName), ENT_QUOTES, CONTREXX_CHARSET),
            );
            $objResult->MoveNext();
        }
        echo json_encode($customer);
        exit();
    }

    /**
     * the upload is finished
     * rewrite the names
     * write the uploaded files to the database
     *
     * @param string     $tempPath    the temporary file path
     * @param string     $tempWebPath the temporary file path which is accessable by web browser
     * @param array      $data        the data which are attached by uploader init method
     * @param integer    $uploadId    the upload id
     * @param array      $fileInfos   the file infos
     * @param String     $response    the respose
     *
     * @return array the target paths
     */
    public static function uploadFinished($tempPath, $tempWebPath, $data, $uploadId, $fileInfos, $response)
    {

        //target folder
        $depositionTarget = \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteMediaCrmPath() . '/';
        $h = opendir($tempPath);
        if (!$h) {
            return array($tempPath, $tempWebPath);
        }

        while (false != ($file = readdir($h))) {

            //skip . and ..
            if ($file == '.' || $file == '..') {
                continue;
            }

            //delete unwanted files
            $sizeLimit = 10485760;
            $size = filesize($tempPath . '/' . $file);
            if ($size > $sizeLimit) {
                $response->addMessage(
                    \Cx\Core_Modules\Uploader\Controller\UploadResponse::STATUS_ERROR,
                    "Server error. Increase post_max_size and upload_max_filesize to $size."
                );
                \Cx\Lib\FileSystem\FileSystem::delete_file($tempPath . '/' . $file);
                continue;
            }

            $info = pathinfo($file);
            if (!in_array(strtolower($info['extension']), array('csv'))) {
                $response->addMessage(
                    \Cx\Core_Modules\Uploader\Controller\UploadResponse::STATUS_ERROR,
                    'Please choose a csv to upload'
                );
                \Cx\Lib\FileSystem\FileSystem::delete_file($tempPath . '/' . $file);
                continue;
            }

            //do not overwrite existing files.
            $prefix = '';
            while (file_exists($depositionTarget . $prefix . $file)) {
                if (empty($prefix)) {
                    $prefix = 0;
                }
                $prefix++;
            }

            // move file
            try {
                $objFile = new \Cx\Lib\FileSystem\File($tempPath . '/' . $file);
                $objFile->move($depositionTarget . $prefix . $file, false);
                $_SESSION['importFilename'] = $prefix . $file;
            } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                \DBG::msg($e->getMessage());
            }
        }
        closedir($h);

        return array($tempPath, $tempWebPath);
    }


    /**
     * the upload is finished
     * rewrite the names
     * write the uploaded files to the database
     *
     * @param string     $tempPath    the temporary file path
     * @param string     $tempWebPath the temporary file path which is accessable by web browser
     * @param array      $data        the data which are attached by uploader init method
     * @param integer    $uploadId    the upload id
     * @param array      $fileInfos   the file infos
     *
     * @return array the target paths
     */
    public static function docUploadFinished($tempPath, $tempWebPath, $data, $uploadId, $fileInfos, $response)
    {

        global $objDatabase;

        $objFWUser = \FWUser::getFWUserObject();
        $depositionTarget = \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteMediaCrmPath().'/'; //target folder
        $h = opendir($tempPath);
        if ($h) {

            while (false != ($file = readdir($h))) {

                $info = pathinfo($file);

                //skip . and ..
                if ($file == '.' || $file == '..') {
                    continue;
                }

                if ($file != '..' && $file != '.') {
                    //do not overwrite existing files.
                    $prefix = '';
                    while (file_exists($depositionTarget.$prefix.$file)) {
                        if (empty($prefix)) {
                            $prefix = 0;
                        }
                        $prefix ++;
                    }

                    // move file
                    try {
                        $objFile = new \Cx\Lib\FileSystem\File($tempPath.'/'.$file);
                        $objFile->move($depositionTarget.$prefix.$file, false);
                        // write the uploaded files into database
                        $fields = array(
                            'document_name' => trim($prefix.$file),
                            'added_by'      => $objFWUser->objUser->getId(),
                            'uploaded_date' => date('Y-m-d H:i:s'),
                            'contact_id'    => $data[0]
                        );
                        $sql = \SQL::insert("module_crm_customer_documents", $fields, array('escape' => true));
                        $objDatabase->Execute($sql);
                    } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                        \DBG::msg($e->getMessage());
                    }
                }

                $arrFiles[] = $file;
            }
            closedir($h);
        }

        // return web- and filesystem path. files will be moved there.
        return array($tempPath, $tempWebPath);
    }

    /**
     * the upload is finished
     * rewrite the names
     * write the uploaded files to the database
     *
     * @param string     $tempPath    the temporary file path
     * @param string     $tempWebPath the temporary file path which is accessable by web browser
     * @param array      $data        the data which are attached by uploader init method
     * @param integer    $uploadId    the upload id
     * @param array      $fileInfos   the file infos
     * @param String     $response    the respose
     *
     * @return array the target paths
     */
    public static function proPhotoUploadFinished($tempPath, $tempWebPath, $data, $uploadId, $fileInfos, $response)
    {

        global $objDatabase;
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $depositionTarget = $cx->getWebsiteImagesCrmProfilePath().'/'; //target folder
        $h = opendir($tempPath);
        if ($h) {

            while (false != ($file = readdir($h))) {

                $info = pathinfo($file);

                //skip . and ..
                if ($file == '.' || $file == '..') {
                    continue;
                }

                if ($file != '..' && $file != '.') {
                    //do not overwrite existing files.
                    $prefix = '';
                    while (file_exists($depositionTarget.$prefix.$file)) {
                        if (empty($prefix)) {
                            $prefix = 0;
                        }
                        $prefix ++;
                    }

                    // move file
                    try {
                        $objFile = new \Cx\Lib\FileSystem\File($tempPath.'/'.$file);
                        $objFile->move($depositionTarget.$prefix.$file, false);

                        // create thumbnail
                        if (empty($objImage)) {
                            $objImage = new \ImageManager();
                        }
                        $imageName = trim($prefix.$file);
                        $objImage->_createThumbWhq(
                            $cx->getWebsiteImagesCrmProfilePath().'/',
                            $cx->getWebsiteImagesCrmProfileWebPath().'/',
                            $imageName,
                            40,
                            40,
                            70,
                            '_40X40.thumb'
                        );

                        $objImage->_createThumbWhq(
                            $cx->getWebsiteImagesCrmProfilePath().'/',
                            $cx->getWebsiteImagesCrmProfileWebPath().'/',
                            $imageName,
                            121,
                            160,
                            70
                        );

                        // write the uploaded files into database
                        $fields = array(
                            'profile_picture' => $imageName
                        );
                        $sql = \SQL::update("module_crm_contacts", $fields, array('escape' => true))." WHERE `id` = {$data[0]}";
                        $objDatabase->Execute($sql);
                        $accountId = $objDatabase->getOne("SELECT user_account FROM `".DBPREFIX."module_crm_contacts` WHERE id = {$data[0]}");
                        if (!empty ($accountId) && !empty ($imageName)) {
                            $objFWUser = \FWUser::getFWUserObject();
                            $objUser  = $objFWUser->objUser->getUser($accountId);
                            if (!file_exists($cx->getWebsiteImagesAccessProfilePath().'/'.$imageName)) {
                                $file = $cx->getWebsiteImagesCrmProfilePath().'/';
                                if (($imageName = self::moveUploadedImageInToPlace($objUser, $file.$imageName, $imageName, true)) == true) {
                                    // create thumbnail
                                    $objImage = new \ImageManager();
                                    $objImage->_createThumbWhq(
                                        $cx->getWebsiteImagesAccessProfilePath().'/',
                                        $cx->getWebsiteImagesAccessProfileWebPath().'/',
                                        $imageName,
                                        80,
                                        60,
                                        90
                                    );
                                    $objUser->setProfile(array('picture' => array(0 => $imageName)));
                                    $objUser->store();
                                }
                            }
                        }
                    } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                        \DBG::msg($e->getMessage());
                    }
                }

                $arrFiles[] = $file;
            }
            closedir($h);
        }

        // return web- and filesystem path. files will be moved there.
        return array($tempPath, $tempWebPath);
    }

    /**
     * the upload is finished
     * rewrite the names
     * write the uploaded files to the database
     *
     * @param string     $tempPath    the temporary file path
     * @param string     $tempWebPath the temporary file path which is accessable by web browser
     * @param array      $data        the data which are attached by uploader init method
     * @param integer    $uploadId    the upload id
     * @param array      $fileInfos   the file infos
     * @param String     $response    the respose
     *
     * @return array the target paths
     */
    public static function taskUploadFinished($tempPath, $tempWebPath, $data, $uploadId, $fileInfos, $response)
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $depositionTarget = $cx->getWebsiteImagesCrmPath() . '/'; //target folder
        $h = opendir($tempPath);
        if ($h) {

            while (false != ($file = readdir($h))) {

                $info = pathinfo($file);

                //skip . and ..
                if ($file == '.' || $file == '..') {
                    continue;
                }

                if ($file != '..' && $file != '.') {
                    //do not overwrite existing files.
                    $prefix = '';
                    while (file_exists($depositionTarget.$prefix.$file)) {
                        if (empty($prefix)) {
                            $prefix = 0;
                        }
                        $prefix ++;
                    }

                    // move file
                    try {
                        $objFile = new \Cx\Lib\FileSystem\File($tempPath.'/'.$file);
                        $objFile->move($depositionTarget.$prefix.$file, false);

                        // create thumbnail
                        if (empty($objImage)) {
                            $objImage = new \ImageManager();
                        }
                        $imageName = trim($prefix.$file);
                        $objImage->_createThumbWhq(
                            $cx->getWebsiteImagesCrmPath().'/',
                            $cx->getWebsiteImagesCrmWebPath().'/',
                            $imageName,
                            24,
                            24,
                            70,
                            '_24X24.thumb'
                        );
                        $_SESSION['importFilename'] = $imageName;
                    } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                        \DBG::msg($e->getMessage());
                    }
                }

                $arrFiles[] = $file;
            }
            closedir($h);
        }

        // return web- and filesystem path. files will be moved there.
        return array($tempPath, $tempWebPath);
    }

    /**
     * the upload is finished
     * rewrite the names
     * write the uploaded files to the database
     *
     * @param string     $tempPath    the temporary file path
     * @param string     $tempWebPath the temporary file path which is accessable by web browser
     * @param array      $data        the data which are attached by uploader init method
     * @param integer    $uploadId    the upload id
     * @param array      $fileInfos   the file infos
     * @param String     $response    the respose
     *
     * @return array the target paths
     */
    public static function notesUploadFinished($tempPath, $tempWebPath, $data, $uploadId, $fileInfos, $response)
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $depositionTarget = $cx->getWebsiteImagesCrmPath().'/'; //target folder
        $h = opendir($tempPath);
        if ($h) {

            while (false != ($file = readdir($h))) {

                $info = pathinfo($file);

                //skip . and ..
                if ($file == '.' || $file == '..') {
                    continue;
                }

                if ($file != '..' && $file != '.') {
                    //do not overwrite existing files.
                    $prefix = '';
                    while (file_exists($depositionTarget.$prefix.$file)) {
                        if (empty($prefix)) {
                            $prefix = 0;
                        }
                        $prefix ++;
                    }

                    // move file
                    try {
                        $objFile = new \Cx\Lib\FileSystem\File($tempPath.'/'.$file);
                        $objFile->move($depositionTarget.$prefix.$file, false);

                        // create thumbnail
                        if (empty($objImage)) {
                            $objImage = new \ImageManager();
                        }
                        $imageName = trim($prefix.$file);
                        $objImage->_createThumbWhq(
                            $cx->getWebsiteImagesCrmPath().'/',
                            $cx->getWebsiteImagesCrmWebPath().'/',
                            $imageName,
                            16,
                            16,
                            90,
                            '_16X16.thumb'
                        );
                        $_SESSION['importFilename'] = $imageName;
                    } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                        \DBG::msg($e->getMessage());
                    }
                }

                $arrFiles[] = $file;
            }
            closedir($h);
        }

        // return web- and filesystem path. files will be moved there.
        return array($tempPath, $tempWebPath);
    }

    /**
     * check the account id
     *
     * @return json
     */
    function checkAccountId()
    {
        $accountId    = isset ($_GET['id']) ? (int) $_GET['id'] : '';
        $accountEmail = isset ($_GET['email']) ? trim($_GET['email']) : '';
        $show         = !empty ($accountId) || !empty ($accountEmail) ? true : false;

        if (!empty($accountId)) {
            $objFWUser = \FWUser::getFWUserObject();
            $objUsers  = $objFWUser->objUser->getUsers($filter = array('id' => intval($accountId)));
            if ($objUsers) {
                $email = $objUsers->getEmail();
            }
        }

        if (empty($accountId) && !empty ($accountEmail) && \FWValidator::isEmail($accountEmail)) {
            $objFWUser = \FWUser::getFWUserObject();
            $objUsers  = $objFWUser->objUser->getUsers($filter = array('email' => addslashes($accountEmail)));
            if ($objUsers) {
                $id             = $objUsers->getId();
                $email          = $objUsers->getEmail();
                $company        = trim($objUsers->getProfileAttribute('company'));
                $lastname       = trim($objUsers->getProfileAttribute('lastname'));
                $firstname      = trim($objUsers->getProfileAttribute('firstname'));
                $defaultUser    = !empty ($company) ? trim($company.', '.$firstname.' '.$lastname) : trim($firstname.' '.$lastname);
                $setDefaultUser = !empty ($defaultUser) ? $defaultUser : 'unknown';
            } else {
                $sendLoginCheck = true;
                $email          = $accountEmail;
            }
        } else {
            $email          = $accountEmail;
        }
        $json[] = array(
            'show'              => $show,
            'id'                => $id,
            'email'             => $email,
            'sendLoginCheck'    => $sendLoginCheck,
            'setDefaultUser'    => $setDefaultUser
        );
        echo json_encode($json);
        exit();
    }
    /**
     * get the imported file name
     *
     * @global ADOConnection $objDatabase
     *
     * @return null
     */
    function getImportFilename()
    {
        global $objDatabase;

        if (isset ($_SESSION['importFilename'])) {
            $fileName = $_SESSION['importFilename'];
            unset ($_SESSION['importFilename']);
        }

        if (isset ($_REQUEST['custId']) && !empty($_REQUEST['custId'])) {
            $id = (int) $_REQUEST['custId'];
            $fileName = $objDatabase->getOne("SELECT profile_picture FROM `".DBPREFIX."module_{$this->moduleNameLC}_contacts` WHERE id = '".$id."'");
        }

        if (!empty ($fileName)) {
            $result[] = array('fileName' => $fileName);
        }
        echo json_encode($result);
        exit();
    }
}
