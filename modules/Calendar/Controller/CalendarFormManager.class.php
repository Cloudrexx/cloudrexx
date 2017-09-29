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
 * Calendar Class Form manager
 *
 * @package    cloudrexx
 * @subpackage module_calendar
 * @author     Cloudrexx <info@cloudrexx.com>
 * @copyright  CLOUDREXX CMS - CLOUDREXX AG
 * @version    1.00
 */
class CalendarFormManager extends CalendarLibrary
{
    /**
     * Form list
     *
     * @access public
     * @var array
     */
    public $formList = array();

    /**
     * Input fields type
     *
     * @access private
     * @var array
     */
    private $arrInputfieldTypes = array(
        'inputtext',
        'textarea',
        'select',
        'radio',
        'checkbox',
        'fieldset'
    );

    private $arrRegistrationFields = array(
        'mail',
        'seating',
        'agb',
        'salutation',
        'firstname',
        'lastname',
        //'selectBillingAddress'
    );

    /**
     * Input fields affiliations
     *
     * @access private
     * @var array
     */
    private $arrInputfieldAffiliations = array(
        1  => 'form',
        2  => 'contact',
        3  => 'billing',
    );

    /**
     * only Active
     *
     * @access private
     * @var boolean
     */
    private $onlyActive;

    /**
     * Form field Template
     *
     * @var string
     */
    const frontendFieldTemplate = '<div class="row">
                <label>{TXT_CALENDAR_FIELD_NAME}</label>
                {CALENDAR_FIELD_INPUT}
            </div>';

    /**
     * Instance of Event
     *
     * @var CalendarEvent
     */
    protected $event = null;

    /**
     * Form manager constructor
     *
     * @param boolean $onlyActive get only active forms
     */
    function __construct($onlyActive=false){
        $this->onlyActive = $onlyActive;
    }

    /**
     * Get the forms list
     *
     * Loads the forms from the database into $this->formList array
     *
     * @return null
     */
    function getFormList() {
        global $objDatabase;

        $where = array();
        if ($this->onlyActive) {
            $where[] = 'status = 1';
        }
        if ($this->event) {
            $where[] = 'id = '. contrexx_input2int($this->event->registrationForm);
        }
        $whereCondition = '';
        if (!empty($where)) {
            $whereCondition = 'WHERE '. implode(' AND ', $where);
        }

        $query = '
            SELECT
                `id` AS id
            FROM
                `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration_form`
                ' . $whereCondition . '
            ORDER BY `order`
        ';
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            return;
        }

        while (!$objResult->EOF) {
            $objForm = new \Cx\Modules\Calendar\Controller\CalendarForm(intval($objResult->fields['id']));
            $this->formList[] = $objForm;
            $objResult->MoveNext();
        }
    }

    /**
     * Sets the form list placeholders to the template
     *
     * @param object $objTpl Template object
     *
     * @return null
     */
    function showFormList($objTpl)
    {
        global $objDatabase, $_ARRAYLANG;

        $i=0;
        foreach ($this->formList as $key => $objForm) {
            $objTpl->setVariable(array(
                $this->moduleLangVar.'_FORM_ROW'           => $i%2==0 ? 'row1' : 'row2',
                $this->moduleLangVar.'_FORM_ID'            => $objForm->id,
                $this->moduleLangVar.'_FORM_STATUS'        => $objForm->status==0 ? 'red' : 'green',
                $this->moduleLangVar.'_FORM_TITLE'         => $objForm->title,
                $this->moduleLangVar.'_FORM_SORT'          => $objForm->sort,
            ));

            $i++;
            $objTpl->parse('formList');
        }

        if(count($this->formList) == 0) {
            $objTpl->hideBlock('formList');

            $objTpl->setVariable(array(
                'TXT_CALENDAR_NO_FORMS_FOUND' => $_ARRAYLANG['TXT_CALENDAR_NO_FORMS_FOUND'],
            ));

            $objTpl->parse('emptyFormList');
        }
    }

    /**
     * Returns the form list drop down
     *
     * @param integer $selectedId selected option in the form
     *
     * @return string HTML drop down menu
     */
    function getFormDorpdown($selectedId=null) {
        global $_ARRAYLANG;

        $this->getSettings();
        $arrOptions = array();

        foreach ($this->formList as $key => $objForm) {
            $arrOptions[$objForm->id] = $objForm->title;
        }
        return \HTML::getOptions($arrOptions, $selectedId);
    }

    /**
     * Sets placeholders for the form view.
     *
     * @param object $objTpl         Template object
     * @param integer $formId        Form id
     * @param integer $intView       request mode frontend or backend
     * @param integer $arrNumSeating number of seating
     *
     * @return null
     */
    function showForm($objTpl, $formId, $intView, $ticketSales=false, $invite = null) {
        global $_ARRAYLANG, $_LANGID;

        $objForm = new \Cx\Modules\Calendar\Controller\CalendarForm(intval($formId));
        if (!empty($formId)) {
            $this->formList[$formId] = $objForm;
        }

        switch($intView) {
            // backend
            case 1:
                $this->getFrontendLanguages();

                $objTpl->setGlobalVariable(array(
                    $this->moduleLangVar.'_FORM_ID'    => !empty($formId) ? $objForm->id : '',
                    $this->moduleLangVar.'_FORM_TITLE' => !empty($formId) ? $objForm->title : '',
                ));

                $i          = 0;
                $formFields = array();
                if (!empty($formId)) {
                    $defaultLangId = $_LANGID;
                    if (!in_array($defaultLangId, \FWLanguage::getIdArray())) {
                        $defaultLangId = \FWLanguage::getDefaultLangId();
                    }
                    foreach ($objForm->inputfields as $key => $arrInputfield) {
                        $i++;

                        $fieldValue = array();
                        $defaultFieldValue = array();
                        foreach ($this->arrFrontendLanguages as $key => $arrLang) {
                            $fieldValue[$arrLang['id']]        =  isset($arrInputfield['name'][$arrLang['id']])
                                                                 ? $arrInputfield['name'][$arrLang['id']] : '';
                            $defaultFieldValue[$arrLang['id']] =  isset($arrInputfield['default_value'][$arrLang['id']])
                                                                 ? $arrInputfield['default_value'][$arrLang['id']] : '';
                        }
                        $formFields[] = array(
                            'type'                 => $arrInputfield['type'],
                            'id'                   => $arrInputfield['id'],
                            'row'                  => $i%2 == 0 ? 'row2' : 'row1',
                            'order'                => $arrInputfield['order'],
                            'name_master'          => $fieldValue[$defaultLangId],
                            'default_value_master' => $defaultFieldValue[$defaultLangId],
                            'required'             => $arrInputfield['required'],
                            'affiliation'          => $arrInputfield['affiliation'],
                            'field_value'          => json_encode($fieldValue),
                            'default_field_value'  => json_encode($defaultFieldValue)
                        );
                    }
                }

                foreach ($this->arrFrontendLanguages as $key => $arrLang) {
                    $objTpl->setVariable(array(
                        $this->moduleLangVar.'_INPUTFIELD_LANG_ID'       => $arrLang['id'],
                        $this->moduleLangVar.'_INPUTFIELD_LANG_NAME'     => $arrLang['name'],
                        $this->moduleLangVar.'_INPUTFIELD_LANG_SHORTCUT' => $arrLang['lang'],
                    ));
                    $objTpl->parse('inputfieldNameList');
                    $objTpl->setVariable(array(
                        $this->moduleLangVar.'_INPUTFIELD_LANG_ID'       => $arrLang['id'],
                        $this->moduleLangVar.'_INPUTFIELD_LANG_NAME'     => $arrLang['name'],
                        $this->moduleLangVar.'_INPUTFIELD_LANG_SHORTCUT' => $arrLang['lang'],
                    ));
                    $objTpl->parse('inputfieldDefaultValueList');
                    $objTpl->setVariable(array(
                        $this->moduleLangVar.'_INPUTFIELD_LANG_NAME' => $arrLang['name'],
                    ));
                    $objTpl->parse('inputfieldLanguagesList');
                }

                foreach ($this->arrInputfieldTypes as $fieldType) {
                    $objTpl->setVariable(array(
                       $this->moduleLangVar.'_FORM_FIELD_TYPE'        =>  $fieldType,
                       'TXT_'.$this->moduleLangVar.'_FORM_FIELD_TYPE' =>  $_ARRAYLANG['TXT_CALENDAR_FORM_FIELD_'.strtoupper($fieldType)]
                    ));
                    $objTpl->parse('inputfieldTypes');
                }
                foreach ($this->arrRegistrationFields as $fieldType) {
                    $objTpl->setVariable(array(
                       $this->moduleLangVar.'_FORM_FIELD_TYPE'        =>  $fieldType,
                       'TXT_'.$this->moduleLangVar.'_FORM_FIELD_TYPE' =>  $_ARRAYLANG['TXT_CALENDAR_FORM_FIELD_'.strtoupper($fieldType)]
                    ));
                    $objTpl->parse('inputRegfieldTypes');
                }
                /* foreach ($this->arrInputfieldAffiliations as $strAffiliation) {
                    $objTpl->setVariable(array(
                        $this->moduleLangVar.'_FORM_FIELD_TYPE'        =>  $strAffiliation,
                        'TXT_'.$this->moduleLangVar.'_FORM_FIELD_TYPE' =>  $_ARRAYLANG['TXT_CALENDAR_FORM_FIELD_AFFILIATION_'.strtoupper($strAffiliation)],
                    ));
                    $objTpl->parse('fieldAfflications');
                }*/

                $objTpl->setVariable(array(
                    $this->moduleLangVar.'_FORM_DATA'           => json_encode($formFields),
                    $this->moduleLangVar.'_FRONTEND_LANG_COUNT' => count($this->arrFrontendLanguages),
                    $this->moduleLangVar.'_INPUTFIELD_LAST_ID'  => $objForm->getLastInputfieldId(),
                    $this->moduleLangVar.'_INPUTFIELD_LAST_ROW' => $i%2 == 0 ? "'row2'" : "'row1'",
                    $this->moduleLangVar.'_DISPLAY_EXPAND'      => count($this->arrFrontendLanguages) > 1 ? "block" : "none",
                ));

            break;

        // frontend
        case 2:
            // $selectBillingAddressStatus = false;

            $invitee = false;
            $inviteeMail = '';
            $inviteeFirstname = '';
            $inviteeLastname = '';
            $registration = null;

            if ($invite) {
                if ($invite->getRegistration()) {
                    // load data of previously submitted form data
                    $registration = $invite->getRegistration();

                    // add registration-Id to submission form
                    $objTpl->setVariable($this->moduleLangVar.'_REGISTRATION_FIELD', \Html::getHidden('regid', $registration->getId()));
                    $objTpl->parse('calendarRegistrationField');
                }

                // add invitation-Id to submission form
                $objTpl->setVariable($this->moduleLangVar.'_REGISTRATION_FIELD', \Html::getHidden(\CX\Modules\Calendar\Model\Entity\Invite::HTTP_REQUEST_PARAM_ID, $invite->getId()));
                $objTpl->parse('calendarRegistrationField');

                // add invitation-Id to submission form
                $objTpl->setVariable($this->moduleLangVar.'_REGISTRATION_FIELD', \Html::getHidden(\CX\Modules\Calendar\Model\Entity\Invite::HTTP_REQUEST_PARAM_ID, $invite->getId()));
                $objTpl->parse('calendarRegistrationField');

                // add invitation-token to submission form
                $objTpl->setVariable($this->moduleLangVar.'_REGISTRATION_FIELD', \Html::getHidden(\CX\Modules\Calendar\Model\Entity\Invite::HTTP_REQUEST_PARAM_TOKEN, $invite->getToken()));
                $objTpl->parse('calendarRegistrationField');

                switch ($invite->getInviteeType()) {
                    case MailRecipient::RECIPIENT_TYPE_ACCESS_USER:
                        $objUser = \FWUser::getFWUserObject()->objUser->getUser($invite->getInviteeId());
                        if (!$objUser) {
                            break;
                        }

                        $invitee = true;
                        $inviteeMail = $objUser->getEmail();
                        $inviteeFirstname = $objUser->getProfileAttribute('firstname');
                        $inviteeLastname = $objUser->getProfileAttribute('lastname');
                        break;

                    case MailRecipient::RECIPIENT_TYPE_CRM_CONTACT:
                        $crmContact = new \Cx\Modules\Crm\Model\Entity\CrmContact();
                        if (!$crmContact->load($invite->getInviteeId())) {
                            break;
                        }

                        $invitee = true;
                        $inviteeMail = $crmContact->email;
                        $inviteeFirstname = $crmContact->customerName;
                        $inviteeLastname = $crmContact->family_name;
                        break;

                    default:
                        break;
                }
            } elseif (\FWUser::getFWUserObject()->objUser->login()) {
                $invitee = true;
                $inviteeMail = \FWUser::getFWUserObject()->objUser->getEmail();
                $inviteeFirstname = \FWUser::getFWUserObject()->objUser->getProfileAttribute('firstname');
                $inviteeLastname = \FWUser::getFWUserObject()->objUser->getProfileAttribute('lastname');
            }

            // parse registration type dropdown
            $objFieldTemplate = new \Cx\Core\Html\Sigma('.');
            $objFieldTemplate->setTemplate(self::frontendFieldTemplate, true, true);

            $registrationTypeOptions = array(
                1 => $_ARRAYLANG['TXT_CALENDAR_REG_REGISTRATION'],
                0 => $_ARRAYLANG['TXT_CALENDAR_REG_SIGNOFF'],
            );

            // set registration type
            if ($registration) {
                $registrationType = $registration->getType();
            } else {
                $registrationType = key($registrationTypeOptions);
            }

            $registrationTypeSelect = \Html::getSelect('registrationType', $registrationTypeOptions, $registrationType, false, '', 'style="calendarSelect affiliateForm"');
            $objFieldTemplate->setVariable(array(
                'TXT_'.$this->moduleLangVar.'_FIELD_NAME'   => $_ARRAYLANG['TXT_CALENDAR_TYPE'].'<font class="calendarRequired"> *</font>',
                $this->moduleLangVar.'_FIELD_INPUT'         => $registrationTypeSelect,
                $this->moduleLangVar.'_FIELD_CLASS'         => 'affiliationForm',
            ));
            $objTpl->setVariable($this->moduleLangVar.'_REGISTRATION_FIELD', $objFieldTemplate->get());
            $objTpl->parse('calendarRegistrationField');


            foreach ($objForm->inputfields as $key => $arrInputfield) {
                $objFieldTemplate->setTemplate(self::frontendFieldTemplate, true, true);
                $options = array();
                $options = explode(',', $arrInputfield['default_value'][$_LANGID]);
                $inputfield = null;
                $hide = false;
                $optionSelect = true;
                $availableSeat = 0;
                $checkSeating  = false;

                if(isset($_POST['registrationField'][$arrInputfield['id']])) {
                    $value = $_POST['registrationField'][$arrInputfield['id']];
                } elseif ($registration) {
                    $formFieldValue = $registration->getRegistrationFormFieldValueByFieldId($arrInputfield['id']);
                    if ($formFieldValue) {
                        $value = $formFieldValue->getValue();
                    }
                } elseif (
                     $invitee &&
                     in_array($arrInputfield['type'], array('mail', 'firstname', 'lastname'))
                ) {
                    $value = '';
                    switch ($arrInputfield['type']) {
                        case 'mail':
                            $value = $inviteeMail;
                            break;
                        case 'firstname':
                            $value = $inviteeFirstname;
                            break;
                        case 'lastname':
                            $value = $inviteeLastname;
                            break;
                        default :
                            $value = $arrInputfield['default_value'][$_LANGID];
                            break;
                    }
                } else {
                    $value = $arrInputfield['default_value'][$_LANGID];
                }

                $affiliationClass = 'affiliation'.ucfirst($arrInputfield['affiliation']);

                switch($arrInputfield['type']) {
                    case 'inputtext':
                    case 'mail':
                    case 'firstname':
                    case 'lastname':
                        $inputfield = '<input type="text" class="calendarInputText" name="registrationField['.$arrInputfield['id'].']" value="'.$value.'" /> ';
                        break;
                    case 'textarea':
                        $inputfield = '<textarea class="calendarTextarea" name="registrationField['.$arrInputfield['id'].']">'.$value.'</textarea>';
                        break;
                    case 'seating':
                        if (!$ticketSales) {
                            $hide = true;
                        }
                        $optionSelect = false;

                        if ($this->event) {
                            $checkSeating  = $this->event->registration && $this->event->numSubscriber;
                            $availableSeat = $this->event->getFreePlaces();
                        }
                    case 'select':
                    case 'salutation':
                        $inputfield = '<select class="calendarSelect" name="registrationField['.$arrInputfield['id'].']">';
                        $selected =  empty($_POST) ? 'selected="selected"' : '';
                        $inputfield .= $optionSelect ? '<option value="" '.$selected.'>'.$_ARRAYLANG['TXT_CALENDAR_PLEASE_CHOOSE'].'</option>' : '';

                        foreach ($options as $key => $name) {
                            // filter out any seating options that would cause
                            // an overbooking
                            if (
                                // skip filtering selected option of loaded registration
                                $key + 1 != $value &&
                                // only filter in case the event has set an invitee limit
                                $checkSeating &&
                                // skip if option would cause an overbooking of the event
                                contrexx_input2int($name) > $availableSeat
                            ) {
                                continue;
                            }
                            $selected    = ($key + 1 == $value) ? 'selected="selected"' : '';
                            $inputfield .= '<option value="' . intval($key + 1) . '" ' . $selected . '>' . $name . '</option>';
                        }

                        $inputfield .= '</select>';
                        break;
                     case 'radio':
                        $inputfield .= '<div>';
                        foreach($options as $key => $name)  {
                            $checked =  ($key+1 == $value) || (empty($_POST) && $key == 0) ? 'checked="checked"' : '';

                            $textValue = (isset($_POST["registrationFieldAdditional"][$arrInputfield['id']][$key]) ? $_POST["registrationFieldAdditional"][$arrInputfield['id']][$key] : '');
                            $textfield = '<input type="text" class="calendarInputCheckboxAdditional" name="registrationFieldAdditional['.$arrInputfield['id'].']['.$key.']" value="'. contrexx_input2xhtml($textValue) .'" />';
                            $name = str_replace('[[INPUT]]', $textfield, $name);

                            $inputfield .= '<input type="radio" class="calendarInputCheckbox" name="registrationField['.$arrInputfield['id'].']" value="'.intval($key+1).'" '.$checked.'/>&nbsp;'.$name.'<br />';
                        }
                        $inputfield .= '</div>';
                        break;
                     case 'checkbox':
                        foreach($options as $key => $name)  {
                            $textValue = (isset($_POST["registrationFieldAdditional"][$arrInputfield['id']][$key]) ? $_POST["registrationFieldAdditional"][$arrInputfield['id']][$key] : '');
                            $textfield = '<input type="text" class="calendarInputCheckboxAdditional" name="registrationFieldAdditional['.$arrInputfield['id'].']['.$key.']" value="'. contrexx_input2xhtml($textValue) .'" />';
                            $name = str_replace('[[INPUT]]', $textfield, $name);

                            $checked =  (in_array($key+1, $_POST['registrationField'][$arrInputfield['id']]))  ? 'checked="checked"' : '';
                            $inputfield .= '<input '.$checked.' type="checkbox" class="calendarInputCheckbox" name="registrationField['.$arrInputfield['id'].'][]" value="'.intval($key+1).'" />&nbsp;'.$name.'<br />';
                        }
                        break;
                    case 'agb':
                        $inputfield = '<input class="calendarInputCheckbox" type="checkbox" name="registrationField['.$arrInputfield['id'].'][]" value="1" />&nbsp;'.$_ARRAYLANG['TXT_CALENDAR_AGB'].'<br />';
                        break;
                    /* case 'selectBillingAddress':
                        if(!$selectBillingAddressStatus) {
                            if($_REQUEST['registrationField'][$arrInputfield['id']] == 'deviatesFromContact') {
                                $selectDeviatesFromContact = 'selected="selected"';
                            } else {
                                $selectDeviatesFromContact = '';
                            }

                            $inputfield = '<select id="calendarSelectBillingAddress" class="calendarSelect" name="registrationField['.$arrInputfield['id'].']">';
                            $inputfield .= '<option value="sameAsContact">'.$_ARRAYLANG['TXT_CALENDAR_SAME_AS_CONTACT'].'</option>';
                            $inputfield .= '<option value="deviatesFromContact" '.$selectDeviatesFromContact.'>'.$_ARRAYLANG['TXT_CALENDAR_DEVIATES_FROM_CONTACT'].'</option>';
                            $inputfield .= '</select>';
                            $selectBillingAddressStatus = true;
                        }
                        break; */
                    case 'fieldset':
                        $inputfield = null;
                        break;
                }

                $field = '';
                if($arrInputfield['type'] == 'fieldset') {
                    $field = '</fieldset><fieldset><legend>'.$arrInputfield['name'][$_LANGID].'</legend>';
                    $hide = true;
                } else {
                    $required = $arrInputfield['required'] == 1 ? '<font class="calendarRequired"> *</font>' : '';
                    $label    = $arrInputfield['name'][$_LANGID].$required;
                }

                if(!$hide) {
                    $objFieldTemplate->setVariable(array(
                        'TXT_'.$this->moduleLangVar.'_FIELD_NAME' => $label,
                        $this->moduleLangVar.'_FIELD_INPUT'       => $inputfield,
                        $this->moduleLangVar.'_FIELD_CLASS'       => $affiliationClass,
                    ));
                    $field = $objFieldTemplate->get();
                }
                $objTpl->setVariable($this->moduleLangVar.'_REGISTRATION_FIELD', $field);

                $objTpl->parse('calendarRegistrationField');
            }
            break;
        }
    }

    /**
     * Returns the CalendarEvent instance if set, null otherwise
     *
     * @return CalendarEvent
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set the Event
     *
     * @param \Cx\Modules\Calendar\Controller\CalendarEvent $event
     */
    public function setEvent(CalendarEvent $event)
    {
        $this->event = $event;
    }
}
