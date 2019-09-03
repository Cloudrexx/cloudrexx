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
 * Calendar Class Registration manager
 *
 * @package    cloudrexx
 * @subpackage module_calendar
 * @author     Cloudrexx <info@cloudrexx.com>
 * @copyright  CLOUDREXX CMS - CLOUDREXX AG
 * @version    1.00
 */
class CalendarRegistrationManager extends CalendarLibrary
{
    /**
     * Event id
     *
     * @access private
     * @var integer
     */
    private $eventId;

    /**
     * Form id
     *
     * @access private
     * @var integer
     */
    private $formId;

    /**
     * Get Registration
     *
     * @access private
     * @var boolean
     */
    private $getRegistrations;

    /**
     * Get deregistration
     *
     * @access private
     * @var boolean
     */
    private $getDeregistrations;

    /**
     * Get waitlist
     *
     * @access private
     * @var boolean
     */
    private $getWaitlist;

    /**
     * Registration list
     *
     * @access public
     * @var array
     */
    public $registrationList = array();

    /**
     * Startdate filter
     *
     * @var integer
     */
    public $startDate;

    /**
     * Enddate filter
     *
     * @var integer
     */
    public $endDate;

    /**
     * Set true when registration manager is loaded to parse the current view
     *
     * @var boolean
     */
    public $defaultView;

    /**
     * Calendar Event instance
     *
     * @var \Cx\Modules\Calendar\Controller\CalendarEvent
     */
    public $event;

    /**
     * Registration manager constructor
     *
     * Loads the form object by loading the calendarEvent object
     *
     * @param \Cx\Modules\Calendar\Controller\CalendarEvent     $event                Event id
     * @param boolean                                           $getRegistrations     condition to check whether we need the
     *                                                                                  registrations
     * @param boolean                                           $getDeregistrations   condition to check whether we need the
     *                                                                                  deregistrations
     * @param boolean                                           $getWaitlist          condition to check whether we need the
     *                                                                                  waitlist
     * @param integer                                           $startDate            Startdate filter
     * @param integer                                           $endDate              Enddate filter
     * @param boolean                                           $isDefaultView        Is registration manager is loaded to parse the current view
     */
    function __construct(CalendarEvent $event, $getRegistrations = true, $getDeregistrations = false, $getWaitlist = false, $startDate = null, $endDate = null, $isDefaultView = false)
    {
        $this->event              = $event;
        $this->eventId            = intval($event->id);
        $this->getRegistrations   = $getRegistrations;
        $this->getDeregistrations = $getDeregistrations;
        $this->getWaitlist        = $getWaitlist;
        $this->startDate          = $startDate;
        $this->endDate            = $endDate;
        $this->defaultView        = $isDefaultView;
        $this->formId             = $this->event->registrationForm;
    }

    /**
     * Initialize the registration list
     *
     * @return null
     */
    function getRegistrationList()
    {
        global $objDatabase;

        $blnFirst = true;
        $arrWhere = array();
        if ($this->getRegistrations)   { $arrWhere[] = 1; }
        if ($this->getDeregistrations) { $arrWhere[] = 0; }
        if ($this->getWaitlist)        { $arrWhere[] = 2; }
        $strWhere = ' AND (';
        foreach ($arrWhere as $value) {
            $strWhere .=  $blnFirst ? '`type` = '.$value : ' OR `type` = '.$value;
            $blnFirst = false;
        }
        $strWhere .= ')';

        if ($this->startDate && $this->endDate) {
            $strWhere .= ' AND (date >= '. contrexx_input2int($this->startDate) .' AND date <= '. contrexx_input2int($this->endDate) .')';
        } elseif ($this->startDate) {
            $strWhere .= ' AND (date >= '. contrexx_input2int($this->startDate) .')';
        } elseif ($this->endDate) {
            $strWhere .= ' AND (date <= '. contrexx_input2int($this->startDate) .')';
        }

        $query = '
            SELECT `id`
            FROM `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration`
            WHERE `event_id` = '.$this->eventId.'
            '.$strWhere.'
            ORDER BY `id` DESC'
        ;
        $objResult = $objDatabase->Execute($query);

        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $objRegistration = new \Cx\Modules\Calendar\Controller\CalendarRegistration($this->formId, intval($objResult->fields['id']));
                $this->registrationList[$objResult->fields['id']] = $objRegistration;
                $objResult->MoveNext();
            }
        }
    }

    /**
     * Set the registration list place holder to the template
     *
     * @param object $objTpl Template object
     * @param string tpl     Template type
     *
     * @return null
     */
    function showRegistrationList($objTpl, $tpl)
    {
        global $objDatabase, $_ARRAYLANG;

        $objResult = $objDatabase->Execute('SELECT count(DISTINCT `field_id`) AS `count_form_fields` FROM `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration_form_field_name` WHERE `form_id` = '.$this->formId);
        $objTpl->setVariable($this->moduleLangVar.'_COUNT_FORM_FIELDS', $objResult->fields['count_form_fields'] + 4);

        $query = '
            SELECT
                `formField`.`id`,
                (
                    SELECT `fieldName`.`name`
                    FROM `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration_form_field_name` AS `fieldName`
                    WHERE `fieldName`.`field_id` = `formField`.`id` AND `fieldName`.`form_id` = `formField`.`form`
                    ORDER BY CASE `fieldName`.`lang_id`
                                WHEN '.FRONTEND_LANG_ID.' THEN 1
                                ELSE 2
                                END
                    LIMIT 1
                ) AS `name`,
                (
                    SELECT `fieldDefault`.`default`
                    FROM `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration_form_field_name` AS `fieldDefault`
                    WHERE `fieldDefault`.`field_id` = `formField`.`id` AND `fieldDefault`.`form_id` = `formField`.`form`
                    ORDER BY CASE `fieldDefault`.`lang_id`
                                WHEN '.FRONTEND_LANG_ID.' THEN 1
                                ELSE 2
                                END
                    LIMIT 1
                ) AS `default`,
                `formField`.`type`
            FROM `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration_form_field` AS `formField`
            WHERE `formField`.`form` = '.$this->formId.'
            ORDER BY `formField`.`order`
        ';
        $objResult = $objDatabase->Execute($query);

        if ($objResult !== false) {
            $objTpl->setVariable($this->moduleLangVar.'_REGISTRATION_NAME', '#');
            $objTpl->parse('eventRegistrationName');

            $dateFilterTpl = new \Cx\Core\Html\Sigma(
                    \Cx\Core\Core\Controller\Cx::instanciate()->getCodeBaseModulePath() . '/' . $this->moduleName . '/View/Template/Backend'
            );
            $dateFilterTpl->loadTemplateFile('module_calendar_registration_date_filter.html');
            $dateFilterTpl->setVariable('TXT_CALENDAR_DATE', $_ARRAYLANG['TXT_CALENDAR_DATE']);
            $eventStats         = $this->getEventStats();
            $selectedDateFilter = $this->defaultView && isset($_GET['date']) ? contrexx_input2raw($_GET['date']) : '';
            $this->parseEventRegistrationStats($dateFilterTpl, $eventStats, $selectedDateFilter);

            $objTpl->setVariable($this->moduleLangVar.'_REGISTRATION_NAME', $dateFilterTpl->get());
            $objTpl->parse('eventRegistrationName');
            
            //display the registration submission date header
            $objTpl->setVariable($this->moduleLangVar.'_REGISTRATION_NAME', $_ARRAYLANG['TXT_CALENDAR_EVENT_REGISTRATION_SUBMISSION']);
            $objTpl->parse('eventRegistrationName');

            $arrFieldColumns = array();
            $arrDefaults = array();
            while (!$objResult->EOF) {
                if (!in_array($objResult->fields['type'], array('agb', 'fieldset'))) {
                    $arrFieldColumns[] = $objResult->fields['id'];
                    $arrDefaults[$objResult->fields['id']] = !empty($objResult->fields['default']) ? explode(',', $objResult->fields['default']) : array();
                    $objTpl->setVariable($this->moduleLangVar.'_REGISTRATION_NAME', contrexx_raw2xhtml($objResult->fields['name']));
                    $objTpl->parse('eventRegistrationName');
                }
                $objResult->MoveNext();
            }

            //$objTpl->setVariable($this->moduleLangVar.'_REGISTRATION_NAME', $_ARRAYLANG['TXT_CALENDAR_PAYMENT_METHOD']);
            $objTpl->setVariable(array(
                $this->moduleLangVar.'_REGISTRATION_NAME'  => $_ARRAYLANG['TXT_CALENDAR_ACTION'],
                $this->moduleLangVar.'_REG_COL_ATTRIBUTES' => "style='text-align:right;'",
            ));
            $objTpl->parse('eventRegistrationName');
        }

        $query = '
            SELECT `v`.`reg_id`, `v`.`field_id`, `v`.`value`, `f`.`type`
            FROM `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration_form_field_value` AS `v`
            INNER JOIN `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration_form_field` AS `f`
            ON `v`.`field_id` = `f`.`id`
            WHERE `f`.`form` = '.$this->formId.'
            ORDER BY `f`.`order`
        ';
        $objResult = $objDatabase->Execute($query);

        $arrValues = array();
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                if (!in_array($objResult->fields['type'], array('agb', 'fieldset'))) {
                    $options = $arrDefaults[$objResult->fields['field_id']];
                    $value   = '';

                    switch ($objResult->fields['type']) {
                        case 'firstname':
                        case 'lastname':
                        case 'inputtext':
                        case 'textarea':
                        case 'mail':
                        // case 'selectBillingAddress':
                            $value = $objResult->fields['value'];
                            break;
                        case 'salutation':
                        case 'seating':
                        case 'select':
                            $optionIdx = $objResult->fields['value'] - 1;
                            if (isset($options[$optionIdx])) {
                                $value = $options[$optionIdx];
                            }
                            break;
                        case 'radio':
                        case 'checkbox':
                            $output   = array();
                            $input    = '';
                            foreach (explode(',', $objResult->fields['value']) as $value) {
                                $input = '';

                                // extract data from input field (in case the magic [[INPUT]] function was used
                                if (preg_match('/^([^\[]*)(?:\[\[([^\]]*)\]\])?$/', $value, $match)) {
                                    $value = $match[1];
                                    if (isset($match[2])) {
                                        $input = $match[2];
                                    }
                                }

                                // fetch label of selected option
                                $optionIdx = $value - 1;
                                $label = '';
                                if (isset($options[$optionIdx])) {
                                    $label = current(explode('[[', $options[$optionIdx]));
                                }

                                // fetch value of selected option (based on selection and magic [[INPUT]] field)
                                if (!empty($input)) {
                                    $output[]  = $label . ': ' . $input;
                                } else {
                                    if ($label == '') {
                                        $label = $value == 1 ? $_ARRAYLANG['TXT_CALENDAR_YES'] : $_ARRAYLANG['TXT_CALENDAR_NO'];
                                    }

                                    $output[] = $label;
                                }
                                $value = implode(', ', $output);
                            }
                            break;
                    }

                    $arrValues[$objResult->fields['reg_id']][$objResult->fields['field_id']] = $value;
                }
                $objResult->MoveNext();
            }
        }

        $i = 0;

        //$paymentMethods = explode(',', $_ARRAYLANG["TXT_PAYMENT_METHODS"]);
        if (empty($this->registrationList)) {
            $objTpl->touchBlock("emptyEventRegistrationList");
        } else {
            $objTpl->hideBlock("emptyEventRegistrationList");
        }
        foreach ($this->registrationList as $objRegistration) {
            $checkbox = '<input type="checkbox" name="selectedRegistrationId[]" class="selectedRegistrationId" value="'.$objRegistration->id.'" />';
            $objTpl->setVariable($this->moduleLangVar.'_REGISTRATION_VALUE', $checkbox);
            $objTpl->parse('eventRegistrationValue');

            $objTpl->setVariable($this->moduleLangVar.'_REGISTRATION_VALUE', date("d.m.Y", $objRegistration->eventDate));
            $objTpl->parse('eventRegistrationValue');

            //display the registration submission date value
            $objTpl->setVariable(
                $this->moduleLangVar.'_REGISTRATION_VALUE',
                (($objRegistration->submissionDate instanceof \DateTime)
                    ? $this->format2userDateTime($objRegistration->submissionDate)
                    : ''
                )
            );
            $objTpl->parse('eventRegistrationValue');

            foreach ($arrFieldColumns as $fieldId) {
                $objTpl->setVariable($this->moduleLangVar.'_REGISTRATION_VALUE', isset($arrValues[$objRegistration->id][$fieldId]) ? contrexx_raw2xhtml($arrValues[$objRegistration->id][$fieldId]) : '');
                $objTpl->parse('eventRegistrationValue');
            }

            /*unset($paymentMethod);
            switch ($objRegistration->paymentMethod) {
                case 1:
                    $paymentMethod = $paymentMethods[1];
                    break;
                case 2:
                    $paymentMethod = $paymentMethods[2];
                    break;
                default:
                    $paymentMethod = $paymentMethods[0];
                    break;
            }*/

            //$objTpl->setVariable($this->moduleLangVar.'_REGISTRATION_VALUE', $paymentMethod . " (" . ($objRegistration->paid ? $_ARRAYLANG["TXT_PAYMENT_COMPLETED"] : $_ARRAYLANG["TXT_PAYMENT_INCOMPLETED"]) . ")");
            //$objTpl->parse('eventRegistrationValue');

            $links = '
                <a style="float: right;" class="delete_registration" href="index.php?cmd='. $this->moduleName .'&amp;act=event_registrations&amp;tpl='.$tpl.'&amp;id='.$this->eventId.'&amp;delete='.$objRegistration->id.'" title="'.$_ARRAYLANG['TXT_CALENDAR_DELETE'].'"><img src="../core/Core/View/Media/icons/delete.gif" width="17" height="17" border="0" alt="'.$_ARRAYLANG['TXT_CALENDAR_DELETE'].'" /></a>
                <a style="float: right;" href="index.php?cmd='.$this->moduleName.'&amp;act=modify_registration&amp;tpl='.$tpl.'&amp;event_id='.$this->eventId.'&amp;rid='.$objRegistration->id.'" title="'.$_ARRAYLANG['TXT_CALENDAR_EDIT'].'"><img src="../core/Core/View/Media/icons/edit.gif" width="16" height="16" border="0" alt="'.$_ARRAYLANG['TXT_CALENDAR_EDIT'].'" /></a>
            ';
            $objTpl->setVariable($this->moduleLangVar.'_REGISTRATION_VALUE', $links);
            $objTpl->parse('eventRegistrationValue');

            $objTpl->setVariable($this->moduleLangVar.'_REGISTRATION_ROW', $i % 2 == 0 ? 'row1' : 'row2');
            $objTpl->parse('eventRegistrationList');
            $i++;
        }
    }

    /**
     * Parse the Event registration stats dropdown to filter the registration
     *
     * @param \Cx\Core\Html\Sigma   $objTpl      Template instance
     * @param array                 $eventStats  Array of event stats
     * @param string                $selected    Selected option
     * @param string                $parent      Parent level
     * @param integer               $level       Current level of parsing,
     *                                           It is used to identify the value of parsing
     *                                           Level 1: Year value
     *                                                 2: Month
     *                                                 3: Day
     * @param string                $blockName   Block name to parse
     */
    public function parseEventRegistrationStats(
        \Cx\Core\Html\Sigma $objTpl,
        $eventStats = array(),
        $selected = '',
        $parent = '',
        $level = 1,
        $blockName = 'calendar_registration_date'
    ) {
        global $_CORELANG;

        $arrMonthTxts = explode(',', $_CORELANG['TXT_MONTH_ARRAY']);
        foreach ($eventStats as $key => $value) {
            $optionValue = empty($parent) ? str_pad($key, 2, '0', STR_PAD_LEFT) : $parent . '-' . str_pad($key, 2, '0', STR_PAD_LEFT);
            $optionName  = '';
            switch ($level) {
                case 1: // year value
                    $optionName = $key;
                    break;
                case 2: // month value
                    $optionName = $arrMonthTxts[$key-1];
                    break;
                case 3: // day value
                    $optionName = $key .' ('. $value .')';
                default:
                    break;
            }

            $objTpl->setVariable(array(
                $this->moduleLangVar . '_REGISTRATION_FILTER_DATE_INTENT'   => str_repeat('&nbsp;', ($level - 1) * 2),
                $this->moduleLangVar . '_REGISTRATION_FILTER_DATE_NAME'     => $optionName,
                $this->moduleLangVar . '_REGISTRATION_FILTER_DATE_KEY'      => $optionValue,
                $this->moduleLangVar . '_REGISTRATION_FILTER_DATE_SELECTED' => ($selected == $optionValue) ? 'selected="selected"' : '',
            ));
            $objTpl->parse($blockName);

            if (is_array($value)) {
                $this->parseEventRegistrationStats($objTpl, $value, $selected, $optionValue, $level + 1, $blockName);
            }
        }
    }

    /**
     * Get the event registration count by given filter
     * output array format
     *  array(
     *     year => array(
     *      month => array(
     *          day1 => registration_count
     *          day2 => registration_count
     *      )
     *     )
     *  )
     *
     * @return array
     */
    public function getEventStats()
    {
        global $objDatabase;

        $blnFirst = true;
        $arrWhere = array();
        if ($this->getRegistrations)   { $arrWhere[] = 1; }
        if ($this->getDeregistrations) { $arrWhere[] = 0; }
        if ($this->getWaitlist)        { $arrWhere[] = 2; }
        $strWhere = ' AND (';
        foreach ($arrWhere as $value) {
            $strWhere .=  $blnFirst ? '`type` = '.$value : ' OR `type` = '.$value;
            $blnFirst  = false;
        }
        $strWhere .= ')';
        $query = 'SELECT
                    COUNT(1) AS `count`,
                    `date` FROM
                  `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_registration`
                WHERE
                    `event_id` = '. $this->eventId .'
                    '. $strWhere .'
                GROUP BY `date`';
        $registration = $objDatabase->Execute($query);
        if (!$registration) {
            return array();
        }

        $stats = array();
        while (!$registration->EOF) {
            $dateTime = new \DateTime();
            $dateTime->setTimestamp($registration->fields['date']);
            $year  = $dateTime->format('Y');
            $month = $dateTime->format('n');
            $day   = $dateTime->format('d');
            if (!isset($stats[$year])) {
                $stats[$year] = array();
            }
            if (!isset($stats[$year][$month])) {
                $stats[$year][$month] = array();
            }
            $stats[$year][$month][$day] = $registration->fields['count'];

            $registration->MoveNext();
        }

        return $stats;
    }

    /**
     * Set the registration fields placeholders to the template
     *
     * @param \Cx\Core\Html\Sigma   $objTpl Template instance
     * @param integer               $regId  Registration id
     */
    function showRegistrationInputfields(\Cx\Core\Html\Sigma $objTpl, $regId = null)
    {
        global $_ARRAYLANG;

        $i = 0;
        $objForm         = new \Cx\Modules\Calendar\Controller\CalendarForm($this->formId);
        $objRegistration = new \Cx\Modules\Calendar\Controller\CalendarRegistration($this->formId, $regId);

        // parse the registration type for the add/edit subscription
        $regType      = isset($_POST['registrationType']) ? (int) $_POST['registrationType'] : (!empty($regId) ? $objRegistration->type : 1);
        $regTypeField = '<select style="width: 208px;" class="calendarSelect" name="registrationType">
                            <option value="1" '. ($regType == 1 ? "selected='selected'" : '') .' />'.$_ARRAYLANG['TXT_CALENDAR_REG_REGISTRATION'].'</option>
                            <option value="0" '. ($regType == 0 ? "selected='selected'" : '') .' />'.$_ARRAYLANG['TXT_CALENDAR_REG_SIGNOFF'].'</option>
                            <option value="2" '. ($regType == 2 ? "selected='selected'" : '') .' />'.$_ARRAYLANG['TXT_CALENDAR_REG_WAITLIST'].'</option>
                        </select>';
        $objTpl->setVariable(array(
            $this->moduleLangVar.'_ROW'                             => $i % 2 == 0 ? 'row1' : 'row2',
            $this->moduleLangVar.'_REGISTRATION_INPUTFIELD_NAME'    => $_ARRAYLANG['TXT_CALENDAR_TYPE'],
            $this->moduleLangVar.'_REGISTRATION_INPUTFIELD_VALUE'   => $regTypeField,
        ));
        $objTpl->parse('calendar_registration_inputfield');
        $i++;

        if ($this->event && $this->event->seriesStatus && $this->event->independentSeries) {
            $endDate = new \DateTime();
            $endDate->modify('+10 years');

            $eventManager = new CalendarEventManager(null, $endDate);
            $objEvent     = new \Cx\Modules\Calendar\Controller\CalendarEvent($this->event->id);
            if ($eventManager->_addToEventList($objEvent)) {
                $eventManager->eventList[] = $objEvent;
            }
            $additionalRecurrences = $objEvent->seriesData['seriesAdditionalRecurrences'];
            $eventManager->_setNextSeriesElement($objEvent, $additionalRecurrences);

            $regEventDateField = '<select style="width: 208px;" class="calendarSelect" name="registrationEventDate">';
            foreach ($eventManager->eventList as $event) {
                $selectedDate       = $objRegistration->eventDate == $event->startDate->getTimestamp() ? 'selected="selected"' : '';
                $regEventDateField .= '<option value="' . $event->startDate->getTimestamp() . '" ' . $selectedDate . ' />' . $this->format2userDate($event->startDate) . '</option>';
            }
            $regEventDateField .= '</select>';

            $objTpl->setVariable(array(
                $this->moduleLangVar.'_ROW'                             => $i % 2 == 0 ? 'row1' : 'row2',
                $this->moduleLangVar.'_REGISTRATION_INPUTFIELD_NAME'    => $_ARRAYLANG['TXT_CALENDAR_DATE_OF_THE_EVENT'],
                $this->moduleLangVar.'_REGISTRATION_INPUTFIELD_VALUE'   => $regEventDateField,
            ));
            $objTpl->parse('calendar_registration_inputfield');
            $i++;
        }

        foreach ($objForm->inputfields as $arrInputfield) {
            $inputfield = '';
            $options = explode(',', $arrInputfield['default_value'][FRONTEND_LANG_ID]);
            $optionSelect = true;

            if(isset($_POST['registrationField'][$arrInputfield['id']])) {
                $value = $_POST['registrationField'][$arrInputfield['id']];
            } else {
                $value = $regId != null ? $objRegistration->fields[$arrInputfield['id']]['value'] : '';
            }

            switch ($arrInputfield['type']) {
                case 'inputtext':
                case 'mail':
                case 'firstname':
                case 'lastname':
                    $inputfield = '<input style="width: 200px;" type="text" class="calendarInputText" name="registrationField['.$arrInputfield['id'].']" value="'.$value.'" />';
                    break;
                case 'textarea':
                    $inputfield = '<textarea style="width: 196px;" class="calendarTextarea" name="registrationField['.$arrInputfield['id'].']">'.$value.'</textarea>';
                    break ;
                case 'seating':
                    $optionSelect = false;
                case 'select':
                case 'salutation':
                    $inputfield = '<select style="width: 208px;" class="calendarSelect" name="registrationField['.$arrInputfield['id'].']">';
                    $selected =  empty($_POST) ? 'selected="selected"' : '';
                    $inputfield .= $optionSelect ? '<option value="" '.$selected.'>'.$_ARRAYLANG['TXT_CALENDAR_PLEASE_CHOOSE'].'</option>' : '';
                    foreach ($options as $key => $name)  {
                        $selected =  ($key+1 == $value)  ? 'selected="selected"' : '';
                        $inputfield .= '<option value="'.intval($key+1).'" '.$selected.'>'.$name.'</option>';
                    }
                    $inputfield .= '</select>';
                    break;
                 case 'radio':
                    $arrValue = explode('[[', $value);
                    $value    = $arrValue[0];
                    $input    = str_replace(']]','', $arrValue[1]);
                    foreach ($options as $key => $name)  {
                        $checked =  ($key+1 == $value) || (in_array($key+1, $_POST['registrationField'][$arrInputfield['id']])) ? 'checked="checked"' : '';
                        $textfield = '<input type="text" class="calendarInputCheckboxAdditional" name="registrationFieldAdditional['.$arrInputfield['id'].']['.$key.']" value="'. ($checked ? $input : '') .'" />';
                        $name = str_replace('[[INPUT]]', $textfield, $name);
                        $inputfield .= '<input type="radio" class="calendarInputCheckbox" name="registrationField['.$arrInputfield['id'].']" value="'.intval($key+1).'" '.$checked.'/>&nbsp;'.$name.'<br />';
                    }
                    break;
                 case 'checkbox':
                    $results = explode(',', $value);
                    foreach ($results as $result) {
                        list ($value, $input) = explode('[[', $result);
                        $value = !empty($value) ? $value : 0;
                        $input = str_replace(']]','', $input);
                        $newResult[$value] = $input;
                    }

                    foreach ($options as $key => $name)  {
                        $checked = array_key_exists($key+1, $newResult) || (in_array($key+1, $_POST['registrationField'][$arrInputfield['id']]))  ? 'checked="checked"' : '';
                        $textfield = '<input type="text" class="calendarInputCheckboxAdditional" name="registrationFieldAdditional['.$arrInputfield['id'].']['.$key.']" value="'. ($checked ? $newResult[$key+1] : '') .'" />';
                        $name = str_replace('[[INPUT]]', $textfield, $name);
                        $inputfield .= '<input '.$checked.' type="checkbox" class="calendarInputCheckbox" name="registrationField['.$arrInputfield['id'].'][]" value="'.intval($key+1).'" />&nbsp;'.$name.'<br />';
                    }
                    break;
                 case 'agb':
                     $checked = $value ? "checked='checked'" : '';
                     $inputfield = '<input '. $checked .' class="calendarInputCheckbox" type="checkbox" name="registrationField['.$arrInputfield['id'].'][]" value="1" />&nbsp;'.$_ARRAYLANG['TXT_CALENDAR_AGB'].'<br />';
                     break;
            }

            if ($arrInputfield['type'] != 'fieldset') {
                $objTpl->setVariable(array(
                    $this->moduleLangVar.'_ROW'                              => $i % 2 == 0 ? 'row1' : 'row2',
                    $this->moduleLangVar.'_REGISTRATION_INPUTFIELD_NAME'     => $arrInputfield['name'][FRONTEND_LANG_ID],
                    $this->moduleLangVar.'_REGISTRATION_INPUTFIELD_REQUIRED' => $arrInputfield['required'] == 1 ? '<font class="calendarRequired"> *</font>' : '',
                    $this->moduleLangVar.'_REGISTRATION_INPUTFIELD_VALUE'    => $inputfield,
                ));
                $objTpl->parse('calendar_registration_inputfield');
                $i++;
            }
        }
    }

    /**
     * Count returns the number of escort data for the event
     *
     * @return int number of escort data
     */
    function getEscortData()
    {
        global $objDatabase;

        $query = "SELECT
                    `n`.`field_id`
                  FROM
                    `".DBPREFIX."module_{$this->moduleTablePrefix}_registration_form_field_name` AS `n`
                  INNER JOIN
                    `".DBPREFIX."module_{$this->moduleTablePrefix}_registration_form_field` AS `f`
                  ON
                    `n`.`field_id` = `f`.`id`
                  WHERE
                    `n`.`form_id` = '{$this->formId}'
                  AND
                    `n`.`lang_id` = '" . FRONTEND_LANG_ID . "'
                  AND
                    `f`.`type` = 'seating'
                ";
        $seatingFieldId = $objDatabase->getOne($query);

        if (empty($seatingFieldId))
            return (int) count($this->registrationList);

        // Limit search to date of the event. This is critical for series!
        $startDateBackup = $this->startDate;
        $endDateBackup = $this->endDate;
        $this->startDate = $this->event->startDate->format('U');
        $this->endDate = $this->event->endDate->format('U');
        $this->getRegistrationList();
        $this->startDate = $startDateBackup;
        $this->endDate = $endDateBackup;

        $countSeating = 0;
        foreach ($this->registrationList as $registration) {
            $arrOptions    = explode(',', $registration->fields[$seatingFieldId]['default']);
            $optionIdx = $registration->fields[$seatingFieldId]['value'] - 1;
            if (isset($arrOptions[$optionIdx])) {
                $countSeating += (int) $arrOptions[$optionIdx];
            } else {
                $countSeating += 1;
            }
        }

        return $countSeating;
    }
}
