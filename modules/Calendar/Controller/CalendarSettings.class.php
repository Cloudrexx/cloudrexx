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
 * Calendar
 *
 * @package    cloudrexx
 * @subpackage module_calendar
 * @author     Cloudrexx <info@cloudrexx.com>
 * @copyright  CLOUDREXX CMS - CLOUDREXX AG
 * @version    1.00
 */

namespace Cx\Modules\Calendar\Controller;
/**
 * Calendar Class Settings
 *
 * @package    cloudrexx
 * @subpackage module_calendar
 * @author     Cloudrexx <info@cloudrexx.com>
 * @copyright  CLOUDREXX CMS - CLOUDREXX AG
 * @version    1.00
 */
class CalendarSettings extends CalendarLibrary
{
    /**
     * Status message
     *
     * @access public
     * @var string
     */
    public $okMessage;

    /**
     * Error message
     *
     * @access public
     * @var string
     */
    public $errMessage;

    /**
     * Yellowpay settigns
     *
     * @access private
     * @var array
     */
    private $yellowPaySettings = array();

    /**
     * Constructor
     *
     * @see getFrontendLanguages();
     */
    function __construct()
    {
        $this->getFrontendLanguages();
        parent::__construct();
    }

    /**
     * General settings block
     *
     * @param object $objTpl Template object
     *
     * @return null
     */
    function general($objTpl)
    {
        global $objDatabase, $_ARRAYLANG, $_CORELANG;

        $objTpl->addBlockfile($this->moduleLangVar.'_SETTINGS_CONTENT', 'settings_content', 'module_calendar_settings_general.html');

        if(isset($_POST['submitSettingsGeneral'])){
           $this->_saveSettings();
        }

        $this->_getSettingElements($objTpl, 1);
    }

    /**
     * Date settings block
     *
     * @param object $objTpl Template object
     *
     * @return null
     */
    function dateDisplay($objTpl)
    {
        global $objDatabase, $_ARRAYLANG, $_CORELANG;

        $objTpl->addBlockfile($this->moduleLangVar.'_SETTINGS_CONTENT', 'settings_content', 'module_calendar_settings_date.html');

        if(isset($_POST['submitSettingsDate'])){
           $this->_saveSettings();
        }

        $this->_getDateSeparators();
        $objTpl->setVariable(array(
            $this->moduleLangVar."_SEPARATOR_DATE_LIST"           => json_encode($this->getDateSeparatorByName('separatorDateList')),
            $this->moduleLangVar."_SEPARATOR_TIME_LIST"           => json_encode($this->getDateSeparatorByName('separatorTimeList')),
            $this->moduleLangVar."_SEPARATOR_DATE_TIME_LIST"      => json_encode($this->getDateSeparatorByName('separatorDateTimeList')),
            $this->moduleLangVar."_SEPARATOR_SEVERAL_DAYS_LIST"   => json_encode($this->getDateSeparatorByName('separatorSeveralDaysList')),

            $this->moduleLangVar."_SEPARATOR_DATE_DETAIL"         => json_encode($this->getDateSeparatorByName('separatorDateDetail')),
            $this->moduleLangVar."_SEPARATOR_TIME_DETAIL"         => json_encode($this->getDateSeparatorByName('separatorTimeDetail')),
            $this->moduleLangVar."_SEPARATOR_DATE_TIME_DETAIL"    => json_encode($this->getDateSeparatorByName('separatorDateTimeDetail')),
            $this->moduleLangVar."_SEPARATOR_SEVERAL_DAYS_DETAIL" => json_encode($this->getDateSeparatorByName('separatorSeveralDaysDetail')),

            "TXT_{$this->moduleLangVar}_OCLOCK"                   => $_ARRAYLANG['TXT_CALENDAR_OCLOCK']
        ));

        $this->_getSettingElements($objTpl, 14);
    }

    /**
     * Initialize the date seprators for parsing
     *
     * @return null
     */
    function _getDateSeparators()
    {
        global $_ARRAYLANG, $objDatabase;

        $arrDateSettings =  array(
                            'separatorDateList','separatorDateTimeList', 'separatorSeveralDaysList', 'separatorTimeList',
                            'separatorDateDetail','separatorDateTimeDetail', 'separatorSeveralDaysDetail', 'separatorTimeDetail',
                            );

        $where = " WHERE `name` IN (". implode(',', array_map(function($val) { return "'$val'"; }, $arrDateSettings)).")" ;

        $this->arrSeparatorValue = array();
        $objSettings = $objDatabase->Execute("SELECT name,value,options, type FROM  ".DBPREFIX."module_".$this->moduleTablePrefix."_settings $where ORDER BY name ASC");
        if ($objSettings !== false) {
            while (!$objSettings->EOF) {
                $strOptions = $objSettings->fields['options'];
                $arrOptions = explode(',', $strOptions );

                $this->arrSeparatorValue[$objSettings->fields['name']] = array_map(function($val){
                    global $_ARRAYLANG;

                    return $_ARRAYLANG["{$val}_VALUE"];
                }, $arrOptions);

                $objSettings->MoveNext();
            }
        }
    }

    /**
     * Return date seprator by name
     *
     * @param string $settingName setting name
     * @return array date seperators list by its name
     */
    function getDateSeparatorByName($settingName)
    {
        if (isset($this->arrSeparatorValue[$settingName])) {
            return $this->arrSeparatorValue[$settingName];
        }

        return array();
    }

    /**
     * Loads the payment settings
     *
     * @param object $objTpl Template object
     *
     * @return null
     */
    function payment($objTpl)
    {
        global $objDatabase, $_ARRAYLANG, $_CORELANG;

        $objTpl->addBlockfile($this->moduleLangVar.'_SETTINGS_CONTENT', 'settings_content', 'module_calendar_settings_payment.html');

        if(isset($_POST['submitSettingsPayment'])){
           $this->_saveSettings();
        }

        $this->_getSettingElements($objTpl, 9);

    }

    /**
     * Loads the form settings
     *
     * @param object $objTpl Template object
     *
     * @return null
     */
    function forms($objTpl)
    {
        global $objDatabase, $_ARRAYLANG, $_CORELANG;

        $objTpl->addBlockfile($this->moduleLangVar.'_SETTINGS_CONTENT', 'settings_content', 'module_calendar_settings_forms.html');

        if(isset($_POST['submitModifyForm'])){
            $objForm = new \Cx\Modules\Calendar\Controller\CalendarForm(intval($_POST['formId']));

            if($objForm->save($_POST)) {
                \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->deleteComponentFiles('Calendar');
                if(intval($_POST['formId']) == 0 || intval($_POST['copy']) == 1) {
                    $this->okMessage = $_ARRAYLANG['TXT_CALENDAR_FORM_SUCCESSFULLY_ADDED'];
                } else {
                    $this->okMessage = $_ARRAYLANG['TXT_CALENDAR_FORM_SUCCESSFULLY_EDITED'];
                }
            } else {
                if(intval($_POST['formId']) == 0) {
                    $this->errMessage = $_ARRAYLANG['TXT_CALENDAR_FORM_CORRUPT_ADDED'];
                } else {
                    $this->errMessage = $_ARRAYLANG['TXT_CALENDAR_FORM_CORRUPT_EDITED'];
                }
            }
        }

        if(isset($_GET['switch_status'])) {
            $objForm = new \Cx\Modules\Calendar\Controller\CalendarForm(intval($_GET['switch_status']));
            if($objForm->switchStatus()) {
                \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->deleteComponentFiles('Calendar');
                $this->okMessage = $_ARRAYLANG['TXT_CALENDAR_FORM_SUCCESSFULLY_EDITED'];
            } else {
                $this->errMessage = $_ARRAYLANG['TXT_CALENDAR_FORM_CORRUPT_EDITED'];
            }
        }

        if(isset($_GET['delete'])) {
            $objForm = new \Cx\Modules\Calendar\Controller\CalendarForm(intval($_GET['delete']));
            if($objForm->delete()) {
                \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->deleteComponentFiles('Calendar');
                $this->okMessage = $_ARRAYLANG['TXT_CALENDAR_FORM_SUCCESSFULLY_DELETED'];
            } else {
                $this->errMessage = $_ARRAYLANG['TXT_CALENDAR_FORM_CORRUPT_DELETED'];
            }
        }

        if(isset($_POST['submitSettingsForms'])) {
            $status = true;
            foreach($_POST['formOrder'] as $formId => $order) {
                $objForm = new \Cx\Modules\Calendar\Controller\CalendarForm(intval($formId));
                if(!$objForm->saveOrder(intval($order))) {
                    $status = false;
                }
            }

            if($status) {
                \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->deleteComponentFiles('Calendar');
                $this->okMessage = $_ARRAYLANG['TXT_CALENDAR_FORM_SUCCESSFULLY_EDITED'];
            } else {
                 $this->errMessage = $_ARRAYLANG['TXT_CALENDAR_FORM_CORRUPT_EDITED'];
            }
        }


        $objTpl->setGlobalVariable(array(
            'TXT_'.$this->moduleLangVar.'_REGISTRATION_FORMS'       => $_ARRAYLANG['TXT_CALENDAR_REGISTRATION_FORMS'],
            'TXT_'.$this->moduleLangVar.'_NEW_REGISTRATION_FORM'    => $_ARRAYLANG['TXT_CALENDAR_NEW_REGISTRATION_FORM'],
            'TXT_'.$this->moduleLangVar.'_STATUS'                   => $_CORELANG['TXT_STATUS'],
            'TXT_'.$this->moduleLangVar.'_TITLE'                    => $_ARRAYLANG['TXT_CALENDAR_TITLE'],
            'TXT_'.$this->moduleLangVar.'_ACTION'                   => $_CORELANG['TXT_HISTORY_ACTION'],
            'TXT_'.$this->moduleLangVar.'_CONFIRM_DELETE_DATA'      => $_ARRAYLANG['TXT_CALENDAR_CONFIRM_DELETE_DATA'],
            'TXT_'.$this->moduleLangVar.'_ACTION_IS_IRREVERSIBLE'   => $_ARRAYLANG['TXT_CALENDAR_ACTION_IS_IRREVERSIBLE'],
            'TXT_'.$this->moduleLangVar.'_EDIT'                     => $_ARRAYLANG['TXT_CALENDAR_EDIT'],
            'TXT_'.$this->moduleLangVar.'_DELETE'                   => $_ARRAYLANG['TXT_CALENDAR_DELETE'],
            'TXT_'.$this->moduleLangVar.'_COPY'                      => $_ARRAYLANG['TXT_CALENDAR_COPY'],
            'TXT_'.$this->moduleLangVar.'_SORTING'                   => $_ARRAYLANG['TXT_CALENDAR_SORTING'],
        ));

        $objFormManager = new \Cx\Modules\Calendar\Controller\CalendarFormManager();
        $objFormManager->getFormList();
        $objFormManager->showFormList($objTpl);
    }

    /**
     * Add / edit of settings -> form
     *
     * @param object  $objTpl Template object
     * @param integer $formId FormId
     *
     * @return null
     */
    function modifyForm($objTpl,$formId)
    {
        global $_ARRAYLANG, $_CORELANG;

        $objTpl->addBlockfile($this->moduleLangVar.'_SETTINGS_CONTENT', 'settings_content', 'module_calendar_settings_modify_form.html');

        if($formId != 0) {
            $this->_pageTitle = $_ARRAYLANG['TXT_CALENDAR_REGISTRATION_FORM']." ".$_ARRAYLANG['TXT_CALENDAR_EDIT'];
        } else {
            if(intval($_GET['copy']) != 0) {
                $objForm = new \Cx\Modules\Calendar\Controller\CalendarForm($_GET['copy']);
                $formId = $objForm->copy();

                if(intval($formId) == 0) {
                    $this->errMessage = $_ARRAYLANG['TXT_CALENDAR_FORM_CORRUPT_ADDED'];
                }

                $this->_pageTitle = $_ARRAYLANG['TXT_CALENDAR_REGISTRATION_FORM']." ".$_ARRAYLANG['TXT_CALENDAR_COPY'];
            } else{
                $this->_pageTitle = $_ARRAYLANG['TXT_CALENDAR_INSERT_REGISTRATION_FORM'];
            }
        }

        $objTpl->setGlobalVariable(array(
            'TXT_'.$this->moduleLangVar.'_REGISTRATION_FORM_TITLE'  =>  $this->_pageTitle,
            'TXT_'.$this->moduleLangVar.'_REGISTRATION_FORM'        => $_ARRAYLANG['TXT_CALENDAR_REGISTRATION_FORM'],
            'TXT_'.$this->moduleLangVar.'_ACTION'                   => $_CORELANG['TXT_HISTORY_ACTION'],
            'TXT_'.$this->moduleLangVar.'_TITLE'                    => $_ARRAYLANG['TXT_CALENDAR_TITLE'],
            'TXT_'.$this->moduleLangVar.'_CONFIRM_DELETE_DATA'      => $_ARRAYLANG['TXT_CALENDAR_CONFIRM_DELETE_DATA'],
            'TXT_'.$this->moduleLangVar.'_ACTION_IS_IRREVERSIBLE'   => $_ARRAYLANG['TXT_CALENDAR_ACTION_IS_IRREVERSIBLE'],
            'TXT_'.$this->moduleLangVar.'_SORT'                     => $_ARRAYLANG['TXT_CALENDAR_SORTING'],
            'TXT_'.$this->moduleLangVar.'_FIELD_NAME'               => $_ARRAYLANG['TXT_CALENDAR_FIELD_NAME'],
            'TXT_'.$this->moduleLangVar.'_FIELD_TYPE'               => $_ARRAYLANG['TXT_CALENDAR_FIELD_TYPE'],
            'TXT_'.$this->moduleLangVar.'_DEFAULT_VALUES'           => $_ARRAYLANG['TXT_CALENDAR_DEFAULT_VALUES'],
            'TXT_'.$this->moduleLangVar.'_FIELD_REQUIRED'           => $_ARRAYLANG['TXT_CALENDAR_FIELD_REQUIRED'],
            'TXT_'.$this->moduleLangVar.'_FIELD_AFFILIATION'        => $_ARRAYLANG['TXT_CALENDAR_FIELD_AFFILIATION'],
            'TXT_'.$this->moduleLangVar.'_ACTIONS'                  => $_CORELANG['TXT_HISTORY_ACTION'],
            'TXT_'.$this->moduleLangVar.'_DELETE'                   => $_ARRAYLANG['TXT_CALENDAR_DELETE'],
            'TXT_'.$this->moduleLangVar.'_NEW_INPUTFIELD'           => $_ARRAYLANG['TXT_CALENDAR_NEW_INPUTFIELD'],
            'TXT_'.$this->moduleLangVar.'_EXPAND'                   => $_ARRAYLANG['TXT_CALENDAR_EXPAND'],
            'TXT_'.$this->moduleLangVar.'_MINIMIZE'                 => $_ARRAYLANG['TXT_CALENDAR_MINIMIZE'],
            'TXT_'.$this->moduleLangVar.'_FIELDS'                   => $_ARRAYLANG['TXT_CALENDAR_FIELDS'],
            'TXT_'.$this->moduleLangVar.'_REGISTRATION_DATA'        => $_ARRAYLANG['TXT_CALENDAR_REGISTRATION_DATA'],
        ));

        $objFormManager = new \Cx\Modules\Calendar\Controller\CalendarFormManager();
        $objFormManager->showForm($objTpl, intval($formId), 1);
    }

    /**
     * Loads the mail settings
     *
     * @param object $objTpl Template object
     *
     * @return null
     */
    function mails($objTpl)
    {
        global $objDatabase, $_ARRAYLANG, $_CORELANG;

        $objTpl->addBlockfile($this->moduleLangVar.'_SETTINGS_CONTENT', 'settings_content', 'module_calendar_settings_mails.html');

        if(isset($_GET['switch_status'])) {
            $objMail = new \Cx\Modules\Calendar\Controller\CalendarMail(intval($_GET['switch_status']));
            if($objMail->switchStatus()) {
                $this->okMessage = $_ARRAYLANG['TXT_CALENDAR_MAIL_SUCCESSFULLY_EDITED'];
            } else {
                $this->errMessage = $_ARRAYLANG['TXT_CALENDAR_MAIL_CORRUPT_EDITED'];
            }
        }

        if(isset($_GET['delete'])) {
            $objMail = new \Cx\Modules\Calendar\Controller\CalendarMail(intval($_GET['delete']));
            if($objMail->delete()) {
                $this->okMessage = $_ARRAYLANG['TXT_CALENDAR_MAIL_SUCCESSFULLY_DELETED'];
            } else {
                $this->errMessage = $_ARRAYLANG['TXT_CALENDAR_MAIL_CORRUPT_DELETED'];
            }
        }

        if(isset($_POST['submitModifyMail'])) {
            $objMail = new \Cx\Modules\Calendar\Controller\CalendarMail(intval($_POST['id']));
            if($objMail->save($_POST)) {
                $this->okMessage = intval($_POST['id']) == 0 ? $_ARRAYLANG['TXT_CALENDAR_MAIL_SUCCESSFULLY_ADDED'] : $_ARRAYLANG['TXT_CALENDAR_MAIL_SUCCESSFULLY_EDITED'];
            } else {
                $this->errMessage = intval($_POST['id']) == 0 ? $_ARRAYLANG['TXT_CALENDAR_MAIL_CORRUPT_ADDED'] : $_ARRAYLANG['TXT_CALENDAR_MAIL_CORRUPT_EDITED'];
            }
        }

        if(isset($_POST['submitSettingsMail'])) {
            foreach ($_POST as $key => $mailId) {
                if(substr($key, 0, 10) == 'isDefault_') {
                    $objMail = new \Cx\Modules\Calendar\Controller\CalendarMail(intval($mailId));
                    if($objMail->setAsDefault()) {
                        $this->okMessage = $_ARRAYLANG['TXT_CALENDAR_MAIL_SUCCESSFULLY_EDITED'];
                    } else {
                        $this->errMessage = $_ARRAYLANG['TXT_CALENDAR_MAIL_CORRUPT_EDITED'];
                    }
                }
            }
        }

        $objTpl->setVariable(array(
            'TXT_'.$this->moduleLangVar.'_MAIL_TEMPLATES'           => $_ARRAYLANG['TXT_CALENDAR_MAIL_TEMPLATES'],
            'TXT_'.$this->moduleLangVar.'_NEW_MAIL_TEMPLATE'        => $_ARRAYLANG['TXT_CALENDAR_NEW_MAIL_TEMPLATE'],
            'TXT_'.$this->moduleLangVar.'_STATUS'                   => $_CORELANG['TXT_STATUS'],
            'TXT_'.$this->moduleLangVar.'_TITLE'                    => $_ARRAYLANG['TXT_CALENDAR_TITLE'],
            'TXT_'.$this->moduleLangVar.'_ACTION'                   => $_CORELANG['TXT_HISTORY_ACTION'],
            'TXT_'.$this->moduleLangVar.'_LANG'                     => $_CORELANG['TXT_ACCESS_LANGUAGE'],
            'TXT_'.$this->moduleLangVar.'_DEFAULT'                  => $_CORELANG['TXT_STANDARD'],
            'TXT_'.$this->moduleLangVar.'_CONFIRM_DELETE_DATA'      => $_ARRAYLANG['TXT_CALENDAR_CONFIRM_DELETE_DATA'],
            'TXT_'.$this->moduleLangVar.'_ACTION_IS_IRREVERSIBLE'   => $_ARRAYLANG['TXT_CALENDAR_ACTION_IS_IRREVERSIBLE'],
            'TXT_'.$this->moduleLangVar.'_EDIT'                     => $_ARRAYLANG['TXT_CALENDAR_EDIT'],
            'TXT_'.$this->moduleLangVar.'_DELETE'                   => $_ARRAYLANG['TXT_CALENDAR_DELETE'],
        ));

        $objMailManager = new \Cx\Modules\Calendar\Controller\CalendarMailManager();
        $objMailManager->getMailList();
        $objMailManager->showMailList($objTpl);
    }

    /**
     * Add / edit of Mail template
     *
     * @param object  $objTpl Template object
     * @param integer $mailId Mail id
     *
     * @return null
     */
    function modifyMail($objTpl, $mailId)
    {
        global $objDatabase, $_ARRAYLANG, $_CORELANG;

        $objTpl->addBlockfile($this->moduleLangVar.'_SETTINGS_CONTENT', 'settings_content', 'module_calendar_settings_modify_mail.html');

        if($mailId != 0) {
            $this->_pageTitle = $_ARRAYLANG['TXT_CALENDAR_MAIL_TEMPLATE']." ".$_ARRAYLANG['TXT_CALENDAR_EDIT'];
        } else {
            $this->_pageTitle = $_ARRAYLANG['TXT_CALENDAR_NEW_MAIL_TEMPLATE'];
        }

        $objTpl->setVariable(array(
            'TXT_'.$this->moduleLangVar.'_MAIL_TEMPLATE_TITLE'      =>  $this->_pageTitle,
            'TXT_'.$this->moduleLangVar.'_MAIL_TEMPLATE'            =>  $_ARRAYLANG['TXT_CALENDAR_MAIL_TEMPLATE'],
            'TXT_'.$this->moduleLangVar.'_CONTENT'                  =>  $_ARRAYLANG['TXT_CALENDAR_MAIL_CONTENT'],
            'TXT_'.$this->moduleLangVar.'_MAIL_CONTENT_HTML'        =>  $_ARRAYLANG['TXT_CALENDAR_MAIL_CONTENT_HTML'],
            'TXT_'.$this->moduleLangVar.'_MAIL_HTML'                =>  $_ARRAYLANG['TXT_CALENDAR_MAIL_HTML'],
            'TXT_'.$this->moduleLangVar.'_MAIL_CONTENT_TEXT'        =>  $_ARRAYLANG['TXT_CALENDAR_MAIL_CONTENT_TEXT'],
            'TXT_'.$this->moduleLangVar.'_MAIL_TITLE'               =>  $_ARRAYLANG['TXT_CALENDAR_TITLE'],
            'TXT_'.$this->moduleLangVar.'_MAIL_TEXT'                =>  $_ARRAYLANG['TXT_CALENDAR_MAIL_TEXT'],
            'TXT_'.$this->moduleLangVar.'_PLACEHOLDER_DIRECTORY'    =>  $_ARRAYLANG['TXT_CALENDAR_PLACEHOLDER_DIRECTORY'],
            'TXT_'.$this->moduleLangVar.'_MAIL_PLACEHOLDER'         =>  $_ARRAYLANG['TXT_CALENDAR_PLACEHOLDER'],
            'TXT_'.$this->moduleLangVar.'_MAIL_ACTION'              =>  $_ARRAYLANG['TXT_CALENDAR_MAIL_ACTION'],
            'TXT_'.$this->moduleLangVar.'_MAIL_LANG'                =>  $_CORELANG['TXT_ACCESS_LANGUAGE'],
            'TXT_'.$this->moduleLangVar.'_MAIL_RECIPIENTS'          =>  $_ARRAYLANG['TXT_CALENDAR_RECIPIENTS'],
            'TXT_'.$this->moduleLangVar.'_TITLE'                    =>  $_ARRAYLANG['TXT_CALENDAR_PLACEHOLDER_TITLE'],
            'TXT_'.$this->moduleLangVar.'_START_DATE'               =>  $_ARRAYLANG['TXT_CALENDAR_PLACEHOLDER_START_DATE'],
            'TXT_'.$this->moduleLangVar.'_END_DATE'                 =>  $_ARRAYLANG['TXT_CALENDAR_PLACEHOLDER_END_DATE'],
            'TXT_'.$this->moduleLangVar.'_LINK_ATTACHMENT'          =>  $_ARRAYLANG['TXT_CALENDAR_PLACEHOLDER_LINK_ATTACHMENT'],
            'TXT_'.$this->moduleLangVar.'_LINK_EVENT'               =>  $_ARRAYLANG['TXT_CALENDAR_PLACEHOLDER_LINK_EVENT'],
            'TXT_'.$this->moduleLangVar.'_LINK_REGISTRATION'        =>  $_ARRAYLANG['TXT_CALENDAR_PLACEHOLDER_LINK_REGISTRATION'],
            'TXT_'.$this->moduleLangVar.'_USERNAME'                 =>  $_ARRAYLANG['TXT_CALENDAR_PLACEHOLDER_USERNAME'],
            'TXT_'.$this->moduleLangVar.'_FIRSTNAME'                =>  $_ARRAYLANG['TXT_CALENDAR_PLACEHOLDER_FIRSTNAME'],
            'TXT_'.$this->moduleLangVar.'_LASTNAME'                 =>  $_ARRAYLANG['TXT_CALENDAR_PLACEHOLDER_LASTNAME'],
            'TXT_'.$this->moduleLangVar.'_SALUTATION'               =>  $_ARRAYLANG['TXT_CALENDAR_PLACEHOLDER_SALUTATION'],
            'TXT_'.$this->moduleLangVar.'_URL'                      =>  $_ARRAYLANG['TXT_CALENDAR_PLACEHOLDER_URL'],
            'TXT_'.$this->moduleLangVar.'_DATE'                     =>  $_ARRAYLANG['TXT_CALENDAR_PLACEHOLDER_DATE'],
            'TXT_'.$this->moduleLangVar.'_REGISTRATION_TYPE'        =>  $_ARRAYLANG['TXT_CALENDAR_PLACEHOLDER_REGISTRATION_TYPE'],
            'TXT_'.$this->moduleLangVar.'_REGISTRATION_SALUTATION'  =>  $_ARRAYLANG['TXT_CALENDAR_PLACEHOLDER_REGISTRATION_SALUTATION'],
            'TXT_'.$this->moduleLangVar.'_REGISTRATION_FIRSTNAME'   =>  $_ARRAYLANG['TXT_CALENDAR_PLACEHOLDER_REGISTRATION_FIRSTNAME'],
            'TXT_'.$this->moduleLangVar.'_REGISTRATION_LASTNAME'    =>  $_ARRAYLANG['TXT_CALENDAR_PLACEHOLDER_REGISTRATION_LASTNAME'],
            'TXT_'.$this->moduleLangVar.'_REGISTRATION_EMAIL'       =>  $_ARRAYLANG['TXT_CALENDAR_PLACEHOLDER_REGISTRATION_EMAIL'],
            'TXT_'.$this->moduleLangVar.'_REGISTRATION_DATA'        =>  $_ARRAYLANG['TXT_CALENDAR_PLACEHOLDER_REGISTRATION_DATA'],
            'TXT_'.$this->moduleLangVar.'_COMMENT'                  =>  $_ARRAYLANG['TXT_CALENDAR_COMMENT'],
        ));

        $objMailManager = new \Cx\Modules\Calendar\Controller\CalendarMailManager();
        if($mailId != 0) {
            $objMailManager->showMail($objTpl, $mailId);
            $objMail = $objMailManager->mailList[$mailId];
        }

        foreach ($objMailManager->getMailActions() as $id => $name) {
            $checked = $id == $objMail->action_id ? 'selected="selected"' : '';
            $action .= '<option value="'.$id.'" '.$checked.'>'.$name.'</option>';
        }

        foreach ($this->arrFrontendLanguages as $key => $arrLang) {
            $checked = $arrLang['id'] == $objMail->lang_id ? 'selected="selected"' : '';
            $lang .= '<option value="'.intval($arrLang['id']).'" '.$checked.'>'.$arrLang['name'].'</option>';
        }

        $objTpl->setVariable(array(
            $this->moduleLangVar.'_TEMPLATE_ACTION'          =>  $action,
            $this->moduleLangVar.'_TEMPLATE_LANG'            =>  $lang,
            $this->moduleLangVar.'_TEMPLATE_CONTENT_HTML'    =>  new \Cx\Core\Wysiwyg\Wysiwyg('content_html', contrexx_raw2xhtml($objMail->content_html), 'fullpage'),
        ));
    }

    /**
     * Loads the Host settings
     *
     * @param object $objTpl Html template object
     *
     * @return null
     */
    function hosts($objTpl)
    {
        global $_ARRAYLANG, $_CORELANG;

        $objTpl->addBlockfile($this->moduleLangVar.'_SETTINGS_CONTENT', 'settings_content', 'module_calendar_settings_hosts.html');

        if(isset($_POST['submitSettingsHosts'])){
           $this->_saveSettings();
        }

        if(isset($_POST['submitModifyHost'])){
            $objHost = new \Cx\Modules\Calendar\Controller\CalendarHost(intval($_POST['id']));
            if($objHost->save($_POST)) {
                $this->okMessage = intval($_POST['id']) == 0 ? $_ARRAYLANG['TXT_CALENDAR_HOST_SUCCESSFULLY_ADDED'] : $_ARRAYLANG['TXT_CALENDAR_HOST_SUCCESSFULLY_EDITED'];
            } else {
                $this->errMessage = intval($_POST['id']) == 0 ? $_ARRAYLANG['TXT_CALENDAR_HOST_CORRUPT_ADDED'] : $_ARRAYLANG['TXT_CALENDAR_HOST_CORRUPT_EDITED'];
            }
        }

        if(isset($_GET['switch_status'])) {
            $objHost = new \Cx\Modules\Calendar\Controller\CalendarHost(intval($_GET['switch_status']));
            if($objHost->switchStatus()) {
                $this->okMessage = $_ARRAYLANG['TXT_CALENDAR_HOST_SUCCESSFULLY_EDITED'];
            } else {
                $this->errMessage = $_ARRAYLANG['TXT_CALENDAR_HOST_CORRUPT_EDITED'];
            }
        }

        if(isset($_GET['delete'])) {
            $objHost = new \Cx\Modules\Calendar\Controller\CalendarHost(intval($_GET['delete']));
            if($objHost->delete()) {
                $this->okMessage = $_ARRAYLANG['TXT_CALENDAR_HOST_SUCCESSFULLY_DELETED'];
            } else {
                $this->errMessage = $_ARRAYLANG['TXT_CALENDAR_HOST_CORRUPT_DELETED'];
            }
        }

        if(isset($_GET['multi'])) {
            $status = true;
            $messageVar = 'EDITED';

            foreach($_POST['selectedHostId'] as $key => $hostId) {
                $objHost = new \Cx\Modules\Calendar\Controller\CalendarHost(intval($hostId));

                switch($_GET['multi']) {
                    case 'delete':
                        $status = $objHost->delete() ? true : false;
                        $messageVar = 'DELETED';
                        break;
                    case 'activate':
                        $objHost->status = 0;
                        $status = $objHost->switchStatus() ? true : false;
                        $messageVar = 'EDITED';
                        break;
                    case 'deactivate':
                        $objHost->status = 1;
                        $status = $objHost->switchStatus() ? true : false;
                        $messageVar = 'EDITED';
                        break;
                }
            }

            if($status) {
                $this->okMessage = $_ARRAYLANG['TXT_CALENDAR_HOST_SUCCESSFULLY_'.$messageVar];
            } else {
                 $this->errMessage = $_ARRAYLANG['TXT_CALENDAR_HOST_CORRUPT_'.$messageVar];
            }
        }

        $this->_getSettingElements($objTpl, 4);

        $objTpl->setVariable(array(
            'TXT_'.$this->moduleLangVar.'_ADDED_HOSTS'              => $_ARRAYLANG['TXT_CALENDAR_ADDED_HOSTS'],
            'TXT_'.$this->moduleLangVar.'_TITLE'                    => $_ARRAYLANG['TXT_CALENDAR_TITLE'],
            'TXT_'.$this->moduleLangVar.'_URI'                      => $_ARRAYLANG['TXT_CALENDAR_URI'],
            'TXT_'.$this->moduleLangVar.'_KEY'                      => $_ARRAYLANG['TXT_CALENDAR_KEY'],
            'TXT_'.$this->moduleLangVar.'_CATEGORY'                 => $_ARRAYLANG['TXT_CALENDAR_CATEGORY'],
            'TXT_'.$this->moduleLangVar.'_ACTIONS'                  => $_ARRAYLANG['TXT_HISTORY_ACTION'],
            'TXT_'.$this->moduleLangVar.'_STATUS'                   => $_ARRAYLANG['TXT_STATUS'],
            'TXT_'.$this->moduleLangVar.'_CONFIRM_DELETE_DATA'      => $_ARRAYLANG['TXT_CALENDAR_CONFIRM_DELETE_DATA'],
            'TXT_'.$this->moduleLangVar.'_ACTION_IS_IRREVERSIBLE'   => $_ARRAYLANG['TXT_CALENDAR_ACTION_IS_IRREVERSIBLE'],
            'TXT_'.$this->moduleLangVar.'_EDIT'                     => $_ARRAYLANG['TXT_CALENDAR_EDIT'],
            'TXT_'.$this->moduleLangVar.'_DELETE'                   => $_ARRAYLANG['TXT_CALENDAR_DELETE'],
            'TXT_SELECT_ALL'                                        => $_ARRAYLANG['TXT_CALENDAR_MARK_ALL'],
            'TXT_DESELECT_ALL'                                      => $_ARRAYLANG['TXT_CALENDAR_REMOVE_CHOICE'],
            'TXT_SUBMIT_SELECT'                                     => $_ARRAYLANG['TXT_SUBMIT_SELECT'],
            'TXT_SUBMIT_ACTIVATE'                                   => $_ARRAYLANG['TXT_SUBMIT_ACTIVATE'],
            'TXT_SUBMIT_DEACTIVATE'                                 => $_ARRAYLANG['TXT_SUBMIT_DEACTIVATE'],
            'TXT_SUBMIT_DELETE'                                     => $_ARRAYLANG['TXT_SUBMIT_DELETE'],
            'TXT_'.$this->moduleLangVar.'_INSERT_HOST'              => $_ARRAYLANG['TXT_CALENDAR_INSERT_HOST'],
            'TXT_'.$this->moduleLangVar.'_MAKE_SELECTION'           => $_ARRAYLANG['TXT_CALENDAR_MAKE_SELECTION']
        ));

        $objHostManager = new \Cx\Modules\Calendar\Controller\CalendarHostManager();
        $objHostManager->getHostList();
        $objHostManager->showHostList($objTpl);
    }

    /**
     * Add / edit of Host settings
     *
     * @param object  $objTpl Template object
     * @param integer $hostId Host id
     *
     * @return null
     */
    function modifyHost($objTpl, $hostId)
    {
        global $_ARRAYLANG, $_CORELANG;

        $objTpl->addBlockfile('CALENDAR_SETTINGS_CONTENT', 'settings_content', 'module_calendar_settings_modify_host.html');

        if($hostId != 0) {
            $this->_pageTitle = $_ARRAYLANG['TXT_CALENDAR_HOST']." ".$_ARRAYLANG['TXT_CALENDAR_EDIT'];
        } else {
            $this->_pageTitle = $_ARRAYLANG['TXT_CALENDAR_INSERT_HOST'];
        }

        $objTpl->setVariable(array(
            'TXT_'.$this->moduleLangVar.'_HOST'                         => $this->_pageTitle,
            'TXT_'.$this->moduleLangVar.'_HOST_TITLE'                   => $_ARRAYLANG['TXT_CALENDAR_TITLE'],
            'TXT_'.$this->moduleLangVar.'_HOST_URI'                     => $_ARRAYLANG['TXT_CALENDAR_URI'],
            'TXT_'.$this->moduleLangVar.'_HOST_KEY'                     => $_ARRAYLANG['TXT_CALENDAR_KEY'],
            'TXT_'.$this->moduleLangVar.'_HOST_STATUS'                  => $_ARRAYLANG['TXT_STATUS'],
            'TXT_'.$this->moduleLangVar.'_HOST_KEY_AUTOGEN_IF_EMPTY'    => $_ARRAYLANG['TXT_CALENDAR_HOST_KEY_AUTOGEN_IF_EMPTY'],
            'TXT_'.$this->moduleLangVar.'_HOST_CATEGORY'                => $_ARRAYLANG['TXT_CALENDAR_CATEGORY'],
        ));
        if($hostId != 0) {
            $objHostManager = new \Cx\Modules\Calendar\Controller\CalendarHostManager();
            $objHostManager->showHost($objTpl, $hostId);
            $objHost = $objHostManager->hostList[$hostId];
        }
        $objCategoryManager = new \Cx\Modules\Calendar\Controller\CalendarCategoryManager(true);
        $objCategoryManager->getCategoryList();
        $category = '<select style="width: 252px;" name="category" >';
        $category .= $objCategoryManager->getCategoryDropdown(
            array($objHost->catId => null),
            CalendarCategoryManager::DROPDOWN_TYPE_ASSIGN);
        $category .= '</select>';
        $objTpl->setVariable(array(
            $this->moduleLangVar.'_HOST_CATEGORY'    => $category
        ));
    }

    /**
     * Global save function for saving the settings into database
     *
     * @return null
     */
    function _saveSettings()
    {
        global $_ARRAYLANG, $objDatabase;

        foreach ($_POST['settings'] as $name => $value) {
            if(is_array($value)) {
                $value = implode(',',$value);
            }
            $query = "UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_settings
                         SET value = '".contrexx_addslashes($value)."'
                       WHERE name = '".contrexx_addslashes($name)."'";

            $objResult = $objDatabase->Execute($query);
        }

        if (isset($_POST['settings']['headlinesStatus'])) {
            \Cx\Core\Setting\Controller\Setting::init('Config', 'component','Yaml');
            $headLinesStatusIntval = intval($_POST['settings']['headlinesStatus']);
            if (!\Cx\Core\Setting\Controller\Setting::isDefined('calendarheadlines')) {
                \Cx\Core\Setting\Controller\Setting::add('calendarheadlines', $headLinesStatusIntval, 1, \Cx\Core\Setting\Controller\Setting::TYPE_RADIO, '1:TXT_ACTIVATED,0:TXT_DEACTIVATED', 'component');
            } else {
                \Cx\Core\Setting\Controller\Setting::set('calendarheadlines', $headLinesStatusIntval);
                \Cx\Core\Setting\Controller\Setting::update('calendarheadlines');
            }
        }

        if ($objResult !== false) {
            //Clear cache
            $this->triggerEvent('clearEsiCache');
            \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->deleteComponentFiles('Calendar');
            $this->okMessage = $_ARRAYLANG['TXT_CALENDAR_SETTINGS_SUCCESSFULLY_EDITED'];
        } else {
            $this->errMessage = $_ARRAYLANG['TXT_CALENDAR_SETTINGS_CORRUPT_EDITED'];
       }
    }

    /**
     * Return's the settings Elements by its section id
     *
     * @param object  $objTpl  Template object
     * @param integer $section section id
     *
     * @return null
     */
    function _getSettingElements($objTpl, $section)
    {
        global $_ARRAYLANG, $objDatabase;

        $this->getSettings();

        $query = "SELECT id,title
                    FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_settings_section
                   WHERE parent='".intval($section)."'
                ORDER BY `order` ASC";

        $objResult = $objDatabase->Execute($query);
        if ($objResult !== false) {
            $infoboxJS = '';
            while (!$objResult->EOF) {
                $objTpl->setVariable(array(
                    'TXT_CALENDAR_SECTION_NAME' => $_ARRAYLANG[$objResult->fields['title']],
                ));

                $query = "SELECT  id,name,title,value,info,type,options,special
                            FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_settings
                           WHERE section_id='".intval($objResult->fields['id'])."'
                        ORDER BY `order` ASC";

                $objResultSetting = $objDatabase->Execute($query);

                if ($objResultSetting !== false) {
                    $i=0;
                    while (!$objResultSetting->EOF) {
                        $arrSetting = array();

                        $arrSetting = $this->_getSettingProperties($objResultSetting->fields['id'],$objResultSetting->fields['name'],$objResultSetting->fields['title'],$objResultSetting->fields['value'],$objResultSetting->fields['info'],$objResultSetting->fields['type'],$objResultSetting->fields['options'],$objResultSetting->fields['special']);

                        $objTpl->setVariable(array(
                            $this->moduleLangVar.'_SETTING_ROW'             => $i%2==0 ? 'row1' : 'row2',
                            $this->moduleLangVar.'_SETTING_NAME'            => $objResultSetting->fields['name'],
                            'TXT_'.$this->moduleLangVar.'_SETTING_NAME'     => isset($_ARRAYLANG[$objResultSetting->fields['title']]) ? $_ARRAYLANG[$objResultSetting->fields['title']] : '',
                            $this->moduleLangVar.'_SETTING_VALUE'           => $arrSetting['output'],
                            $this->moduleLangVar.'_SETTING_INFO'            => $arrSetting['infobox'],
                        ));

                        if (isset($arrSetting['infoboxJS'])) {
                            $infoboxJS .= $arrSetting['infoboxJS'];
                        }

                        $i++;
                        $objTpl->parse('settingsList');
                        $objResultSetting->MoveNext();
                    }
                }

                $objTpl->parse('sectionList');
                $objResult->MoveNext();
            }

            $objTpl->setVariable(array(
                $this->moduleLangVar.'_SETTING_INFO_JS' => $infoboxJS
            ));
        }
    }

    /**
     * Return's settings element html by given properties
     *
     * @param integer $id      Field id
     * @param string  $name    Html input name of the field
     * @param string  $title   Name/Title of the field
     * @param string  $value   Value of the field
     * @param string  $info    Info about the field
     * @param integer $type    Type of the settings field
     * @param string  $options options array
     * @param string  $special integer value of special field
     *
     * @return string Html of the setting field
     */
    function _getSettingProperties($id,$name,$title,$value,$info,$type,$options,$special)
    {
        global $_ARRAYLANG, $_CORELANG;

        $arrSetting = array();
        $output = '';

        switch (intval($type)) {
            case 1:
                //input text
                $output = '<input type="text" style="width: 250px;" name="settings['.$name.']" value="'.$value.'" />';
                break;
            case 2:
                //textarea
                $output = '<textarea style="width: 250px; height: 60px;" name="settings['.$name.']">'.$value.'"</textarea>';
                break;
            case 3:
                //radio
                switch ($name) {
                    case 'placeData':
                    case 'placeDataHost':
                        $addBreak = true;
                        break;
                    default:
                        $addBreak = false;
                        break;
                }

                $arrOptions = array();
                if(!empty($options)) {
                    $arrOptions = explode(",",$options);
                    $first = true;
                    foreach ($arrOptions as $key => $label) {
                        $checked = ($key+1)==$value ? 'checked="checked"' : '';
                        $output .= !$first && $addBreak ? "<br />" : '';
                        $output .= '<label><input type="radio" '.$checked.' value="'.($key+1).'" name="settings['.$name.']" />&nbsp;'.$_ARRAYLANG[$label].'</label>';
                        $first   = false;
                    }
                }
                break;
            case 4:
                //checkbox
                $arrOptions = array();
                if(!empty($options)) {
                    $arrOptions = explode(",",$options);
                    foreach ($arrOptions as $key => $label) {
                        $checked = $key==$value ? 'checked="checked"' : '';
                        $output .= '<label><input type="checkbox" '.$checked.' value="'.$key.'" name="settings['.$name.']" />&nbsp;'.$_ARRAYLANG[$label].'</label>';
                    }
                } else {
                    $checked = $value=='1' ? 'checked="checked"' : '';
                    $value = '<input type="checkbox" '.$checked.' value="1" name="settings['.$name.']" />';
                }
                break;
            case 5:
                //dropdown
                if(!empty($options)) {
                    $options = explode(",",$options);
                    $output = '<select style="width: 252px;" name="settings['.$name.']" >';
                                foreach ($options as $key => $title) {
                                    $checked = $key==$value ? 'selected="selected"' : '';
                                    $output .= '<option '.$checked.' value="'.$key.'" />'.$_ARRAYLANG[$title].'</option>';
                                }
                    $output .= '</select>';
                }
                if(!empty($special)) {
                    switch ($special) {
                        case 'getCategoryDorpdown':
                            $objCategoryManager = new \Cx\Modules\Calendar\Controller\CalendarCategoryManager(true);
                            $objCategoryManager->getCategoryList();
                            $output = '<select style="width: 252px;" name="settings['.$name.']" >';
                            $output .= $objCategoryManager->getCategoryDropdown(
                                array(intval($value) => null),
                                CalendarCategoryManager::DROPDOWN_TYPE_FILTER);
                            $output .= '</select>';
                            break;
                        case 'getPlaceDataDorpdown':
                            $objMediadirForms = new \Cx\Modules\MediaDir\Controller\MediaDirectoryForm(null, 'MediaDir');
                            $objMediadirForms->getForms();
                            $objMediadirForms->listForms(null,4);

                            $output  = $_ARRAYLANG['TXT_CALENDAR_SELECT_FORM_MEDIADIR'].": <br />";
                            $output .= '<select style="width: 252px;" name="settings['.$name.']" >';
                            $output .= $objMediadirForms->listForms(null,4,intval($value));
                            $output .= '</select>';
                            break;
                    }
                }
                break;
            case 6:
                //checkbox multi-select
                $arrOptions = array();
                if(!empty($options)) {
                    $arrOptions = explode(",",$options);
                    $arrValue = explode(',', $value);
                    foreach ($arrOptions as $key => $label) {
                        $checked = in_array($key, $arrValue) ? 'checked="checked"' : '';
                        $output .= '<label><input type="checkbox" '.$checked.' value="'.$key.'" name="settings['.$name.'][]" />&nbsp;'.$_ARRAYLANG[$label].'</label>';
                    }
                } else {
                    $checked = $value=='1' ? 'checked="checked"' : '';
                    $value = '<input type="checkbox" '.$checked.' value="1" name="settings['.$name.'][]" />';
                }
                break;
            case 7:
                if ($special == 'listPreview') {
                    $output = "<div id='listPreview'></div>";
                } elseif ($special == 'detailPreview') {
                    $output = "<div id='detailPreview'></div>";
                }
                break;
    }

        if(!empty($info)) {
            $infobox = '&nbsp;<span class="icon-info tooltip-trigger"></span><span class="tooltip-message">' . $_ARRAYLANG[$info] . '</span>';
        } else {
            $infobox = '';
        }

        $arrSetting['output'] = $output;
        $arrSetting['infobox'] = $infobox;


        return $arrSetting;
    }

    /**
     * Return's the yellowpay settings
     *
     * @return array yellowpay settings
     */
    function getYellowpaySettings() {
        global $objDatabase;
        if(!$this->yellowPaySettings) {
            $query = "SELECT  id,name,title,value,info,type,options,special
                                        FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_settings
                                       WHERE `name` LIKE '%yellowpay%' OR `name` LIKE '%payment%'
                                    ORDER BY `order` ASC";

            $objResultSetting = $objDatabase->Execute($query);

            while(!$objResultSetting->EOF) {
                $this->yellowPaySettings[$objResultSetting->fields["name"]] = $objResultSetting->fields["value"];

                $objResultSetting->moveNext();
            }
        }

        return $this->yellowPaySettings;

    }

}
