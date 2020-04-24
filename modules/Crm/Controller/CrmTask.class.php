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
 * This is the crmTask class file for handling the all functionalities under task menu.
 *
 * PHP version 5.3 or >
 *
 * @category   CrmTask
 * @package    cloudrexx
 * @subpackage module_crm
 * @author     ss4ugroup <ss4ugroup@softsolutions4u.com>
 * @license    BSD Licence
 * @version    1.0.0
 * @link       www.cloudrexx.com
 */

namespace Cx\Modules\Crm\Controller;

/**
 * This is the crmTask class file for handling the all functionalities under task menu.
 *
 * @category   CrmTask
 * @package    cloudrexx
 * @subpackage module_crm
 * @author     ss4ugroup <ss4ugroup@softsolutions4u.com>
 * @license    BSD Licence
 * @version    1.0.0
 * @link       www.cloudrexx.com
 */

class CrmTask extends CrmLibrary
{
    /**
     * Template object
     *
     * @param object
     */
    public $_objTpl;

    /**
     * sort fields
     *
     * @access protected
     * @var array
     */
    protected $_sortFields = array(
        array(
          'name'   => 'TXT_CRM_TASK_RECENTLY_ADDED',
          'column' => 'taskId'
        ),
        array(
          'name'   => 'TXT_CRM_TASK_TITLE',
          'column' => 'task_title'
        ),
        array(
          'name'   => 'TXT_CRM_TASK_DUE_DATE',
          'column' => 'due_date'
        ),
        array(
          'name'   => 'TXT_CRM_CUSTOMER_NAME',
          'column' => 'customer_name'
        ),
        array(
          'name'   => 'TXT_CRM_TASK_RESPONSIBLE',
          'column' => 'assigned_to'
        ),
    );

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
     * get task overview page
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    public function overview()
    {
        global $_ARRAYLANG, $objDatabase;

        \JS::registerJS('lib/javascript/jquery.tmpl.min.js');

        $objtpl = $this->_objTpl;
        $_SESSION['pageTitle'] = $_ARRAYLANG['TXT_CRM_TASK_OVERVIEW'];
        $objtpl->loadTemplateFile("module_{$this->moduleNameLC}_tasks_overview.html");
        $objtpl->setGlobalVariable("MODULE_NAME", $this->moduleName);

        $msg = isset($_GET['mes']) ? base64_decode($_GET['mes']) : '';
        if ($msg) {
            switch ($msg) {
            case 'taskDeleted':
                $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_TASK_DELETE_MESSAGE'];
                break;
            }
        }

        $filterTaskType  = isset($_GET['searchType'])? intval($_GET['searchType']) : 0 ;
        $filterTaskStatus= !empty($_GET['status']) && is_array($_GET['status']) ? contrexx_input2int($_GET['status']) : array(0);
        $filterTaskTitle = isset($_GET['searchTitle'])? contrexx_input2raw($_GET['searchTitle']) : '';
        $sortField       = isset($_GET['sort_by']) && array_key_exists($_GET['sort_by'], $this->_sortFields) ? (int) $_GET['sort_by'] : 2;
        $sortOrder       = (isset($_GET['sort_order']) && $_GET['sort_order'] == 1)  ? 1 : 0 ;

        $filter     = array();
        $filterLink = '';

        if (!empty($filterTaskType)) {
            $filter[]    = " t.task_type_id = '$filterTaskType'";
            $filterLink .= "&searchType=$filterTaskType";
        }
        if (!empty($filterTaskTitle)) {
            $filter[]    = " t.task_title LIKE '%$filterTaskTitle%' OR c.customer_name LIKE '%$filterTaskTitle%'";
            $filterLink .= "&searchTitle=$filterTaskTitle";
        }

        if (!empty($filterTaskStatus)) {
            $filter[]    = ' t.task_status IN ('. implode(" ,", $filterTaskStatus) .')';
            $filterLink .= '&status[]='. implode('&status[]=', $filterTaskStatus);
        }

        $filterCondition = !empty($filter) ? " WHERE ". implode(" AND", $filter) : '';

        $query = "SELECT tt.name,
                               tt.icon,
                               t.task_status,
                               t.id AS taskId,
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
                            ON (t.customer_id = c.id) $filterCondition";

        /* Start Paging ------------------------------------ */
        $intPos             = (isset($_GET['pos'])) ? intval($_GET['pos']) : 0;
        $intPerPage         = $this->getPagingLimit();
        $strPagingSource    = getPaging($this->countRecordEntries($query), $intPos, "&amp;cmd=".$this->moduleName."&amp;act=task$filterLink", false, '', $intPerPage);
        $this->_objTpl->setVariable('ENTRIES_PAGING', $strPagingSource);

        /* End Paging -------------------------------------- */

        $start  = $intPos ? $intPos : 0;

        $sorto  = $sortOrder ? 'DESC' : 'ASC';
        $query .= " ORDER BY {$this->_sortFields[$sortField]['column']} $sorto LIMIT $start, $intPerPage";

        $objResult  = $objDatabase->Execute($query);

        $row = 'row2';
        $now = strtotime('now');
        if ($objResult) {
            if ($objResult->RecordCount() == 0) {
                $objtpl->setVariable(array(
                        'TXT_NO_RECORDS_FOUND'  => $_ARRAYLANG['TXT_CRM_NO_RECORDS_FOUND']
                ));
                $objtpl->touchblock('noRecords');
            } else {
                $objtpl->hideblock('noRecords');
                while (!$objResult->EOF) {
                    list($task_edit_permission, $task_delete_permission, $task_status_update_permission) = $this->getTaskPermission((int) $objResult->fields['added_by'], (int) $objResult->fields['assigned_to']);
                    if (!$task_edit_permission) {
                        $objtpl->hideblock('task_edit_block');
                    }
                    if (!$task_delete_permission) {
                        $objtpl->hideblock('task_delete_block');
                    }
                    if (!$task_status_update_permission) {
                        $objtpl->hideblock('task_status_block');
                    } else {
                        $objtpl->hideblock('task_image_block');
                    }
                    $objtpl->setVariable(array(
                            'CRM_TASK_ID'           => (int) $objResult->fields['taskId'],
                            'CRM_TASKTITLE'         => contrexx_raw2xhtml($objResult->fields['task_title']),
                            'CRM_TASKICON'          => !empty ($objResult->fields['icon']) ? \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesCrmWebPath().'/'.contrexx_raw2xhtml($objResult->fields['icon'])."_24X24.thumb" : '../modules/Crm/View/Media/task_default.png',
                            'CRM_TASKTYPE'          => contrexx_raw2xhtml($objResult->fields['task_type_id']),
                            'CRM_CUSTOMERNAME'      => contrexx_raw2xhtml($objResult->fields['customer_name']." ".$objResult->fields['contact_familyname']),
                            'CRM_DUEDATE'           => contrexx_raw2xhtml(date('h:i A Y-m-d', strtotime($objResult->fields['due_date']))),
                            'TXT_STATUS'            => (int) $objResult->fields['task_status'],
                            'CRM_TASK_TYPE_ACTIVE'  => $objResult->fields['task_status'] == 1 ? 'led_green.gif':'led_red.gif',
                            'TXT_ROW'               => $row = ($row == 'row2')? 'row1':'row2',
                            'CRM_ADDEDBY'           => contrexx_raw2xhtml($this->getUserName($objResult->fields['assigned_to'])),
                            'CRM_TASK_CUSTOMER_ID'  => (int) $objResult->fields['customer_id'],
                            'TXT_CRM_IMAGE_EDIT'    => $_ARRAYLANG['TXT_CRM_IMAGE_EDIT'],
                            'TXT_CRM_IMAGE_DELETE'  => $_ARRAYLANG['TXT_CRM_IMAGE_DELETE'],
                            'TXT_CRM_DELETE_CONFIRM'=> $_ARRAYLANG['TXT_CRM_DELETE_CONFIRM'],
                            'CRM_TASK_EXPIRED_CLASS'=> $objResult->fields['task_status'] == 1 || strtotime($objResult->fields['due_date']) > $now ? '' : 'task_expired',

                    ));
                    $objtpl->parse('showTask');
                    $objResult->MoveNext();
                }
            }
        }

        $objType = $objDatabase->Execute("SELECT id,name FROM ".DBPREFIX."module_{$this->moduleNameLC}_task_types ORDER BY sorting");
        if ($objType) {
            while (!$objType->EOF) {
                $selected = ($objType->fields['id'] == $filterTaskType) ? 'selected="selected"' : '';
                $objtpl->setVariable(array(
                        'CRM_TASK_ID'       => (int) $objType->fields['id'],
                        'TXT_TASK_NAME'     => contrexx_raw2xhtml($objType->fields['name']),
                        'TXT_TASK_SELECTED' => $selected,
                ));
                $objtpl->parse('tastType');
                $objType->MoveNext();
            }
        }

        foreach ($this->_sortFields as $key => $value) {
            $selected = $key == $sortField ? "selected='selected'" : '';
            $objtpl->setVariable(array(
                'CRM_FILTER_SORT_ID'             => $key,
                'CRM_FILTER_SORT_FIELD'          => $_ARRAYLANG[$value['name']],
                'CRM_FILTER_SORT_FIELD_SELECTED' => $selected,
            ));
            $objtpl->parse('sort_fields');
        }

        $sortIcons = array('../modules/Crm/View/Media/Actions-view-sort-ascending-icon.png', '../modules/Crm/View/Media/Actions-view-sort-descending-icon.png');
        $objtpl->setGlobalVariable(array(
                'TXT_SEARCH_VALUE'              => contrexx_raw2xhtml($filterTaskTitle),
                'CRM_TASK_SORT_ORDER'           => $sortOrder,
                'CRM_TASK_SORT_ORDER_ICON'      => $sortIcons[$sortOrder],
                'CRM_TASK_SORT_ORDER_TITLE'     => $sortOrder ? 'descending' : 'ascending',
                'CRM_TASK_SORT_ORDER_ICONS'     => json_encode($sortIcons),
                'CRM_REDIRECT_LINK'             => '&redirect='.base64_encode("&act=task$filterLink&pos=$intPos"),
                'CRM_TASK_STATUS_OPEN'          => !empty($filterTaskStatus) && in_array(0, $filterTaskStatus) ? 'checked="checked"' : '',
                'CRM_TASK_STATUS_COMPLETED'     => !empty($filterTaskStatus) && in_array(1, $filterTaskStatus) ? 'checked="checked"' : '',
                'TXT_CRM_OVERVIEW'              => $_ARRAYLANG['TXT_CRM_OVERVIEW'],
                'TXT_CRM_ADD_TASK'              => $_ARRAYLANG['TXT_CRM_ADD_TASK'],
                'TXT_CRM_ADD_IMPORT'            => $_ARRAYLANG['TXT_CRM_ADD_IMPORT'],
                'TXT_CRM_ADD_EXPORT'            => $_ARRAYLANG['TXT_CRM_ADD_EXPORT'],
                "TXT_CRM_FUNCTIONS"             => $_ARRAYLANG['TXT_CRM_FUNCTIONS'],
                'TXT_CRM_TASK_TYPE_DESCRIPTION' => $_ARRAYLANG['TXT_CRM_TASK_TYPE_DESCRIPTION'],
                'TXT_CRM_TASK_RESPONSIBLE'      => $_ARRAYLANG['TXT_CRM_TASK_RESPONSIBLE'],
                'TXT_CRM_TASK_DUE_DATE'         => $_ARRAYLANG['TXT_CRM_TASK_DUE_DATE'],
                'TXT_CRM_CUSTOMER_NAME'         => $_ARRAYLANG['TXT_CRM_CUSTOMER_NAME'],
                'TXT_CRM_TASK_TYPE'             => $_ARRAYLANG['TXT_CRM_TASK_TYPE'],
                'TXT_CRM_TASK_TITLE'            => $_ARRAYLANG['TXT_CRM_TASK_TITLE'],
                'TXT_CRM_TASK_ID'               => $_ARRAYLANG['TXT_CRM_TASK_ID'],
                'TXT_CRM_TASK_STATUS'           => $_ARRAYLANG['TXT_CRM_TASK_STATUS'],
                'TXT_CRM_TASK'                  => $_ARRAYLANG['TXT_CRM_TASK'],
                'TXT_CRM_SELECT_ALL'            => $_ARRAYLANG['TXT_CRM_SELECT_ALL'],
                'TXT_CRM_DESELECT_ALL'          => $_ARRAYLANG['TXT_CRM_REMOVE_SELECTION'],
                'TXT_CRM_SELECT_ACTION'         => $_ARRAYLANG['TXT_CRM_SELECT_ACTION'],
                'TXT_CRM_NO_OPERATION'          => $_ARRAYLANG['TXT_CRM_NO_OPERATION'],
                'TXT_CRM_ACTIVATESELECTED'      => $_ARRAYLANG['TXT_CRM_ACTIVATESELECTED'],
                'TXT_CRM_DEACTIVATESELECTED'    => $_ARRAYLANG['TXT_CRM_DEACTIVATESELECTED'],
                'TXT_CRM_DELETE_SELECTED'       => $_ARRAYLANG['TXT_CRM_DELETE_SELECTED'],
                'TXT_CRM_DELETE_CONFIRM'        => $_ARRAYLANG['TXT_CRM_DELETE_CONFIRM'],
                'TXT_CRM_FILTERS'               => $_ARRAYLANG['TXT_CRM_FILTERS'],
                'TXT_CRM_SEARCH'                => $_ARRAYLANG['TXT_CRM_SEARCH'],
                'TXT_CRM_ENTER_SEARCH_TERM'     => $_ARRAYLANG['TXT_CRM_ENTER_SEARCH_TERM'],
                'TXT_CRM_FILTER_TASK_TYPE'      => $_ARRAYLANG['TXT_CRM_FILTER_TASK_TYPE'],
                'TXT_CRM_TASK_OPEN'             => $_ARRAYLANG['TXT_CRM_TASK_OPEN'],
                'TXT_CRM_TASK_COMPLETED'        => $_ARRAYLANG['TXT_CRM_TASK_COMPLETED'],
                'TXT_CRM_FILTER_SORT_BY'        => $_ARRAYLANG['TXT_CRM_FILTER_SORT_BY'],
                'TXT_CRM_FILTER_STATUS'         => $_ARRAYLANG['TXT_CRM_FILTER_STATUS'],
                'TXT_CRM_TASK_STATUS_OPEN'      => $_ARRAYLANG['TXT_CRM_TASK_STATUS_OPEN'],
                'TXT_CRM_TASK_STATUS_COMPLETED' => $_ARRAYLANG['TXT_CRM_TASK_STATUS_COMPLETED'],
        ));
    }

    /**
     * add /edit task
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    public function _modifyTask()
    {
        global $_ARRAYLANG,$objDatabase,$objJs,$objFWUser;

        \JS::registerCSS("modules/Crm/View/Style/contact.css");
        if ( gettype($objFWUser) === 'NULL') {
            $objFWUser = \FWUser::getFWUserObject();
        }
        $objtpl = $this->_objTpl;
        $_SESSION['pageTitle'] = empty($_GET['id']) ? $_ARRAYLANG['TXT_CRM_ADDTASK'] : $_ARRAYLANG['TXT_CRM_EDITTASK'];

        $this->_objTpl->loadTemplateFile('module_'.$this->moduleNameLC.'_addtasks.html');
        $objtpl->setGlobalVariable("MODULE_NAME", $this->moduleName);

        $settings    = $this->getSettings();

        $id          = isset($_REQUEST['id'])? (int) $_REQUEST['id']:'';
        $date        = date('Y-m-d H:i:s');
        $title       = isset($_POST['taskTitle']) ? contrexx_input2raw($_POST['taskTitle']) : '';
        $type        = isset($_POST['taskType']) ? (int) $_POST['taskType'] : 0;
        $customer    = isset($_REQUEST['customerId']) ? (int) $_REQUEST['customerId'] : '';
        $duedate     = isset($_POST['date']) ? $_POST['date'] : $date;
        $assignedto  = isset($_POST['assignedto']) ? intval($_POST['assignedto']) : 0;
        $description = isset($_POST['description']) ? contrexx_input2raw($_POST['description']) : '';
        $notify      = isset($_POST['notify']);

        $taskId      = isset($_REQUEST['searchType'])? intval($_REQUEST['searchType']) : 0 ;
        $taskTitle   = isset($_REQUEST['searchTitle'])? contrexx_input2raw($_REQUEST['searchTitle']) : '';

        $redirect     = isset($_REQUEST['redirect']) ? $_REQUEST['redirect'] : base64_encode('&act=task');

        // check permission
        if (!empty($id)) {
            $objResult = $objDatabase->Execute("SELECT `added_by`,
                                                       `assigned_to`
                                                    FROM `".DBPREFIX."module_{$this->moduleNameLC}_task`
                                                 WHERE `id` = '$id'
                                               ");
            $added_user    = (int) $objResult->fields['added_by'];
            $assigned_user = (int) $objResult->fields['assigned_to'];
            if ($objResult) {
                list($task_edit_permission) = $this->getTaskPermission($added_user, $assigned_user);
                if (!$task_edit_permission) {
                    \Permission::noAccess();
                }
            }
        }

        if (isset($_POST['addtask'])) {
            if (!empty($id)) {
                if (
                    $objFWUser->objUser->getAdminStatus() ||
                    $added_user == $objFWUser->objUser->getId() ||
                    $assigned_user == $assignedto
                ) {
                        $fields    = array(
                            'task_title'        => $title,
                            'task_type_id'      => $type,
                            'customer_id'       => $customer,
                            'due_date'          => $duedate,
                            'assigned_to'       => $assignedto,
                            'description'       => $description
                        );
                        $query = \SQL::update("module_{$this->moduleNameLC}_task", $fields, array('escape' => true))." WHERE `id` = {$id}";
                        $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_TASK_UPDATE_MESSAGE'];
                } else {
                        $_SESSION['strErrMessage'] = $_ARRAYLANG['TXT_CRM_TASK_RESPONSIBLE_ERR'];
                    }
            } else {
                $addedDate = date('Y-m-d H:i:s');
                $fields    = array(
                    'task_title'        => $title,
                    'task_type_id'      => $type,
                    'customer_id'       => $customer,
                    'due_date'          => $duedate,
                    'assigned_to'       => $assignedto,
                    'added_by'          => $objFWUser->objUser->getId(),
                    'added_date_time'   => $addedDate,
                    'task_status'       => '0',
                    'description'       => $description
                );
                $query = \SQL::insert("module_{$this->moduleNameLC}_task", $fields, array('escape' => true));
                $_SESSION['strOkMessage'] = $_ARRAYLANG['TXT_CRM_TASK_OK_MESSAGE'];
            }
            $db = $objDatabase->Execute($query);
            if ($db) {
                if ($notify) {
                    $cx = \Cx\Core\Core\Controller\Cx::instanciate();
                    $id = (!empty($id)) ? $id : $objDatabase->INSERT_ID();
                    $info['substitution'] = array(
                            'CRM_ASSIGNED_USER_NAME'            => contrexx_raw2xhtml(\FWUser::getParsedUserTitle($assignedto)),
                            'CRM_ASSIGNED_USER_EMAIL'           => $objFWUser->objUser->getUser($assignedto)->getEmail(),
                            'CRM_DOMAIN'                        => ASCMS_PROTOCOL."://{$_SERVER['HTTP_HOST']}". $cx->getCodeBaseOffsetPath(),
                            'CRM_TASK_NAME'                     => $title,
                            'CRM_TASK_LINK'                     => "<a href='". ASCMS_PROTOCOL."://{$_SERVER['HTTP_HOST']}". $cx->getCodeBaseOffsetPath(). $cx->getBackendFolderName() ."/index.php?cmd=".$this->moduleName."&act=task&tpl=modify&id=$id'>$title</a>",
                            'CRM_TASK_URL'                      => ASCMS_PROTOCOL."://{$_SERVER['HTTP_HOST']}". $cx->getCodeBaseOffsetPath(). $cx->getBackendFolderName() ."/index.php?cmd=".$this->moduleName."&act=task&tpl=modify&id=$id",
                            'CRM_TASK_DUE_DATE'                 => $duedate,
                            'CRM_TASK_CREATED_USER'             => contrexx_raw2xhtml(\FWUser::getParsedUserTitle($objFWUser->objUser->getId())),
                            'CRM_TASK_DESCRIPTION_TEXT_VERSION' => contrexx_html2plaintext($description),
                            'CRM_TASK_DESCRIPTION_HTML_VERSION' => $description
                    );
                    //setting email template lang id
                    $availableMailTempLangAry = $this->getActiveEmailTemLangId('Crm', CRM_EVENT_ON_TASK_CREATED);
                    $availableLangId          = $this->getEmailTempLang($availableMailTempLangAry, $objFWUser->objUser->getUser($assignedto)->getEmail());
                    $info['lang_id']          = $availableLangId;

                    $dispatcher = CrmEventDispatcher::getInstance();
                    $dispatcher->triggerEvent(CRM_EVENT_ON_TASK_CREATED, null, $info);
                }

                \Cx\Core\Csrf\Controller\Csrf::header("Location:./index.php?cmd=".$this->moduleName.base64_decode($redirect));
                exit();
            }
        } elseif (!empty($id)) {
            $objValue       = $objDatabase->Execute("SELECT task_id,
                                                            task_title,
                                                            task_type_id,
                                                            due_date,
                                                            assigned_to,
                                                            description,
                                                            c.id,
                                                            c.customer_name,
                                                            c.contact_familyname
                                                       FROM `".DBPREFIX."module_{$this->moduleNameLC}_task` AS t
                                                       LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_contacts` AS c
                                                            ON t.customer_id = c.id
                                                       WHERE t.id='$id'");

            $title      = $objValue->fields['task_title'];
            $type       = $objValue->fields['task_type_id'];
            $customer   = $objValue->fields['id'];
            $customerName = !empty($objValue->fields['customer_name']) ? $objValue->fields['customer_name']." ".$objValue->fields['contact_familyname'] : '';
            $duedate    = $objValue->fields['due_date'];
            $assignedto = $objValue->fields['assigned_to'];
            $description= $objValue->fields['description'];
            $taskAutoId = $objValue->fields['task_id'];
        }
        $this->_getResourceDropDown('Members', $assignedto, $settings['emp_default_user_group']);
        $this->taskTypeDropDown($objtpl, $type);

        if (!empty($customer)) {
            // Get customer Name
            $objCustomer = $objDatabase->Execute("SELECT customer_name, contact_familyname  FROM `".DBPREFIX."module_crm_contacts` WHERE id = {$customer}");
            $customerName = $objCustomer->fields['customer_name']." ".$objCustomer->fields['contact_familyname'];
        }
        $objtpl->setVariable(array(
                'CRM_LOGGED_USER_ID'    => $objFWUser->objUser->getId(),
                'CRM_TASK_AUTOID'       => !empty($taskAutoId) ? contrexx_raw2xhtml($taskAutoId) : '',
                'CRM_TASK_ID'           => (int) $id,
                'CRM_TASKTITLE'         => contrexx_raw2xhtml($title),
                'CRM_DUE_DATE'          => contrexx_raw2xhtml($duedate),
                'CRM_CUSTOMER_ID'       => intval($customer),
                'CRM_CUSTOMER_NAME'     => !empty($customerName) ? contrexx_raw2xhtml($customerName) : '',
                'CRM_TASK_DESC'         => new \Cx\Core\Wysiwyg\Wysiwyg('description', contrexx_raw2xhtml($description)),
                'CRM_BACK_LINK'         => base64_decode($redirect),

                'TXT_CRM_ADD_TASK'             => empty($id)? $_ARRAYLANG['TXT_CRM_ADD_TASK'] : $_ARRAYLANG['TXT_CRM_EDITTASK'],
                'TXT_CRM_TASK_ID'              => $_ARRAYLANG['TXT_CRM_TASK_ID'],
                'TXT_CRM_TASK_TITLE'           => $_ARRAYLANG['TXT_CRM_TASK_TITLE'],
                'TXT_CRM_TASK_TYPE'            => $_ARRAYLANG['TXT_CRM_TASK_TYPE'],
                'TXT_CRM_SELECT_TASK_TYPE'     => $_ARRAYLANG['TXT_CRM_SELECT_TASK_TYPE'],
                'TXT_CRM_CUSTOMER_NAME'        => $_ARRAYLANG['TXT_CRM_CUSTOMER_NAME'],
                'TXT_CRM_TASK_DUE_DATE'        => $_ARRAYLANG['TXT_CRM_TASK_DUE_DATE'],
                'TXT_CRM_TASK_RESPONSIBLE'     => $_ARRAYLANG['TXT_CRM_TASK_RESPONSIBLE'],
                'TXT_CRM_SELECT_MEMBER_NAME'   => $_ARRAYLANG['TXT_CRM_SELECT_MEMBER_NAME'],
                'TXT_CRM_OVERVIEW'             => $_ARRAYLANG['TXT_CRM_OVERVIEW'],
                'TXT_CRM_TASK_DESCRIPTION'     => $_ARRAYLANG['TXT_CRM_TASK_DESCRIPTION'],
                'TXT_CRM_FIND_COMPANY_BY_NAME' => $_ARRAYLANG['TXT_CRM_FIND_COMPANY_BY_NAME'],
                'TXT_CRM_SAVE'                 => $_ARRAYLANG['TXT_CRM_SAVE'],
                'TXT_CRM_BACK'                 => $_ARRAYLANG['TXT_CRM_BACK'],
                'TXT_CRM_NOTIFY'               => $_ARRAYLANG['TXT_CRM_NOTIFY'],
                'TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT' => $_ARRAYLANG['TXT_CRM_MANDATORY_FIELDS_NOT_FILLED_OUT'],

        ));
    }

    /**
     * used to change task status if valid
     *
     * @return json status about the task
     */
    public function changeTaskStatus()
    {
        global $_ARRAYLANG, $objDatabase;
        $json = array();

        $id     = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $status = isset($_POST['status']) ? (int) $_POST['status'] : 0;
        $status = ($status == 1) ? 0 : 1;
        // check permission
        if (!empty($id)) {
            $objResult = $objDatabase->Execute("SELECT `added_by`,
                                                       `assigned_to`
                                                    FROM `".DBPREFIX."module_{$this->moduleNameLC}_task`
                                                 WHERE `id` = '$id'
                                               ");
            if ($objResult) {
                list($task_edit_permission, $task_delete_permission, $task_status_update_permission) = $this->getTaskPermission((int) $objResult->fields['added_by'], (int) $objResult->fields['assigned_to']);
                if (!$task_status_update_permission) {
                    $json['error'] = $_ARRAYLANG['TXT_CRM_NO_ACCESS_TO_STATUS'];
                } else {
                    $query = $objDatabase->Execute("UPDATE ".DBPREFIX."module_".$this->moduleNameLC."_task
                                                        SET task_status  = '$status'
                                                       WHERE id      = '$id'");
                    $json['success'] = 1;
                    $json['status_img'] = $status ? '../core/Core/View/Media/icons/led_green.gif' : '../core/Core/View/Media/icons/led_red.gif';
                    $json['status_msg'] = sprintf($_ARRAYLANG['TXT_CRM_TASK_STATUS_CHANGED'], ($status ? $_ARRAYLANG['TXT_CRM_TASK_COMPLETED'] : $_ARRAYLANG['TXT_CRM_TASK_OPEN']));

                    $query = "SELECT tt.name,
                               tt.icon,
                               t.task_status,
                               t.id AS taskId,
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
                            ON (t.customer_id = c.id) WHERE t.id = $id";

                    $objResult = $objDatabase->Execute($query);

                    $now = strtotime('now');
                    if ($objResult) {
                        $json['task'] = array(
                            'id'           => (int) $objResult->fields['taskId'],
                            'activeImg'    => $objResult->fields['task_status'] == 1 ? 'led_green.gif':'led_red.gif',
                            'taskType'     => (int) $objResult->fields['task_type_id'],
                            'taskTypeIcon' => !empty ($objResult->fields['icon']) ? \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteImagesCrmWebPath().'/'.contrexx_raw2xhtml($objResult->fields['icon'])."_24X24.thumb" : '../modules/Crm/View/Media/task_default.png',
                            'taskTitle'    => contrexx_raw2xhtml($objResult->fields['task_title']),
                            'dueDate'      => contrexx_raw2xhtml(date('h:i A Y-m-d', strtotime($objResult->fields['due_date']))),
                            'customerId'   => (int) $objResult->fields['customer_id'],
                            'taskStatus'   => (int) $objResult->fields['task_status'],
                            'customerName' => contrexx_raw2xhtml($objResult->fields['customer_name']." ".$objResult->fields['contact_familyname']),
                            'addedBy'      => contrexx_raw2xhtml($this->getUserName($objResult->fields['assigned_to'])),
                            'taskClass'    => $objResult->fields['task_status'] == 1 || strtotime($objResult->fields['due_date']) > $now ? '' : 'task_expired',
                            'Update'       => $task_status_update_permission,
                            'Edit'         => $task_edit_permission,
                            'Delete'       => $task_delete_permission
                        );
                    }
                }
            }
        }

        echo json_encode($json);
    }

    /**
     * delete the task
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @return true
     */
    function deleteTask()
    {
        global $objDatabase;

        $id       = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $redirect = isset($_REQUEST['redirect']) ? $_REQUEST['redirect'] : base64_encode('&act=task');

        if (!empty($id)) {
            $objResult = $objDatabase->Execute("SELECT `added_by`,
                                                       `assigned_to`
                                                    FROM `".DBPREFIX."module_{$this->moduleNameLC}_task`
                                                 WHERE `id` = '$id'
                                               ");
            if ($objResult) {
                list($task_edit_permission, $task_delete_permission) = $this->getTaskPermission((int) $objResult->fields['added_by'], (int) $objResult->fields['assigned_to']);
                if (!$task_delete_permission) {
                    \Permission::noAccess();
                }
            }
        }

        if (!empty($id)) {
            $objResult = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_{$this->moduleNameLC}_task WHERE id = '$id'");

            \Cx\Core\Csrf\Controller\Csrf::header("Location:index.php?cmd=".$this->moduleName.base64_decode($redirect)."&mes=".  base64_encode('taskDeleted'));
        }
    }
}
