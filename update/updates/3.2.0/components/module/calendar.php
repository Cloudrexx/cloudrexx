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


function _calendarUpdate()
{
    global $objDatabase;

    try {
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX . 'module_calendar', array(
                'id' => array('type' => 'INT(11)', 'notnull' => true, 'primary' => true, 'auto_increment' => true),
                'active' => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '1', 'after' => 'id'),
                'catid' => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'active'),
                'startdate' => array('type' => 'INT(14)', 'notnull' => false, 'after' => 'catid'),
                'enddate' => array('type' => 'INT(14)', 'notnull' => false, 'after' => 'startdate'),
                'priority' => array('type' => 'INT(1)', 'notnull' => true, 'default' => '3', 'after' => 'enddate'),
                'access' => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'priority'),
                'name' => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => '', 'after' => 'access'),
                'comment' => array('type' => 'text', 'after' => 'name'),
                'placeName' => array('type' => 'VARCHAR(255)', 'after' => 'comment'),
                'link' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => 'http://', 'after' => 'placeName'),
                'pic' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'link'),
                'attachment' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'pic'),
                'placeStreet' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'attachment'),
                'placeZip' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'placeStreet'),
                'placeCity' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'placeZip'),
                'placeLink' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'placeCity'),
                'placeMap' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'placeLink'),
                'organizerName' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'placeMap'),
                'organizerStreet' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'organizerName'),
                'organizerZip' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'organizerStreet'),
                'organizerPlace' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'organizerZip'),
                'organizerMail' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'organizerPlace'),
                'organizerLink' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'organizerMail'),
                'key' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'organizerLink'),
                'num' => array('type' => 'INT(5)', 'notnull' => true, 'default' => '0', 'after' => 'key'),
                'mailTitle' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'num'),
                'mailContent' => array('type' => 'text', 'after' => 'mailTitle'),
                'registration' => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'mailContent'),
                'groups' => array('type' => 'text', 'after' => 'registration'),
                'all_groups' => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'groups'),
                'public' => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'all_groups'),
                'notification' => array('type' => 'INT(1)', 'after' => 'public'),
                'notification_address' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'notification'),
                'series_status' => array('type' => 'TINYINT(4)', 'after' => 'notification_address'),
                'series_type' => array('type' => 'INT(11)', 'after' => 'series_status'),
                'series_pattern_count' => array('type' => 'INT(11)', 'after' => 'series_type'),
                'series_pattern_weekday' => array('type' => 'VARCHAR(7)', 'after' => 'series_pattern_count'),
                'series_pattern_day' => array('type' => 'INT(11)', 'after' => 'series_pattern_weekday'),
                'series_pattern_week' => array('type' => 'INT(11)', 'after' => 'series_pattern_day'),
                'series_pattern_month' => array('type' => 'INT(11)', 'after' => 'series_pattern_week'),
                'series_pattern_type' => array('type' => 'INT(11)', 'after' => 'series_pattern_month'),
                'series_pattern_dourance_type' => array('type' => 'INT(11)', 'after' => 'series_pattern_type'),
                'series_pattern_end' => array('type' => 'INT(11)', 'after' => 'series_pattern_dourance_type'),
                'series_pattern_begin' => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_pattern_end'),
                'series_pattern_exceptions' => array('type' => 'longtext', 'after' => 'series_pattern_begin')
            ),
            array(
                'name' => array('fields' => array('name', 'comment', 'placeName'), 'type' => 'FULLTEXT')
            )
        );
    } catch (\Cx\Lib\UpdateException $e) {
        // we COULD do something else here..
        DBG::trace();
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }


    //2.1.1


    $query = "SELECT status FROM " . DBPREFIX . "modules WHERE id='21'";
    $objResultCheck = $objDatabase->SelectLimit($query, 1);

    if ($objResultCheck !== false) {
        if ($objResultCheck->fields['status'] == 'y') {
            $calendarStatus = true;
        } else {
            $calendarStatus = false;
        }
    } else {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }

    if ($calendarStatus) {
        $arrContentSites = array();

        $arrContentSites[0]['module'] = 'calendar';
        $arrContentSites[0]['cmd'] = '';
        $arrContentSites[1]['module'] = 'calendar';
        $arrContentSites[1]['cmd'] = 'eventlist';
        $arrContentSites[2]['module'] = 'calendar';
        $arrContentSites[2]['cmd'] = 'boxes';


        //insert new link placeholder in content, if module is active
        foreach ($arrContentSites as $key => $siteArray) {

            $module = $siteArray['module'];
            $cmd = $siteArray['cmd'];

            try {
                \Cx\Lib\UpdateUtil::migrateContentPage(
                    $module,
                    $cmd,
                    '<a href="index.php?section=calendar&amp;cmd=event&amp;id={CALENDAR_ID}">{CALENDAR_TITLE}</a>',
                    '{CALENDAR_DETAIL_LINK}',
                    '3.0.0'
                );
            } catch (\Cx\Lib\UpdateException $e) {
                return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
            }
        }


        try {
            \Cx\Lib\UpdateUtil::migrateContentPage(
                'calendar',
                'sign',
                '<input type="hidden" name="id" value="{CALENDAR_NOTE_ID}" />',
                '<input type="hidden" name="id" value="{CALENDAR_NOTE_ID}" /><input type="hidden" name="date" value="{CALENDAR_NOTE_DATE}" />',
                '3.0.0'
            );
            \Cx\Lib\UpdateUtil::migrateContentPage(
                'calendar',
                'sign',
                '<a href="index.php?section=calendar&amp;id={CALENDAR_NOTE_ID}">{TXT_CALENDAR_BACK}</a>',
                '{CALENDAR_LINK_BACK}',
                '3.0.0'
            );
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
    }

    try {
        // delete obsolete table  contrexx_module_calendar_access
        \Cx\Lib\UpdateUtil::drop_table(DBPREFIX . 'module_calendar_access');

        \Cx\Lib\UpdateUtil::table(
            DBPREFIX . 'module_calendar_form_data', array(
                'reg_id' => array('type' => 'INT(10)', 'notnull' => true, 'default' => '0'),
                'field_id' => array('type' => 'INT(10)', 'notnull' => true, 'default' => '0'),
                'data' => array('type' => 'TEXT', 'notnull' => true)
            )
        );

        \Cx\Lib\UpdateUtil::table(
            DBPREFIX . 'module_calendar_form_fields', array(
                'id' => array('type' => 'INT(7)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'note_id' => array('type' => 'INT(10)', 'notnull' => true, 'default' => '0'),
                'name' => array('type' => 'TEXT'),
                'type' => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0'),
                'required' => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0'),
                'order' => array('type' => 'INT(3)', 'notnull' => true, 'default' => '0'),
                'key' => array('type' => 'INT(7)', 'notnull' => true, 'default' => '0')
            )
        );

        \Cx\Lib\UpdateUtil::table(
            DBPREFIX . 'module_calendar_registrations', array(
                'id' => array('type' => 'INT(7)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'note_id' => array('type' => 'INT(7)', 'notnull' => true, 'default' => '0'),
                'note_date' => array('type' => 'INT(11)', 'notnull' => true, 'after' => 'note_id'),
                'time' => array('type' => 'INT(14)', 'notnull' => true, 'default' => '0'),
                'host' => array('type' => 'VARCHAR(255)'),
                'ip_address' => array('type' => 'VARCHAR(15)'),
                'type' => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0')
            )
        );
    } catch (\Cx\Lib\UpdateException $e) {
        // we COULD do something else here..
        DBG::trace();
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    global $objUpdate;
    if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.1.0')) {
        $CalendarUpdate31 = new CalendarUpdate31();
        $calendarUpdateFeedback = $CalendarUpdate31->run();
        if ($calendarUpdateFeedback !== true) {
            return $calendarUpdateFeedback;
        }
    }

    if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.2.0')) {
        try {
            \Cx\Lib\UpdateUtil::table(
                DBPREFIX.'module_calendar_event',
                array(
                    'id'                                 => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                    'type'                               => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'id'),
                    'startdate'                          => array('type' => 'INT(14)', 'notnull' => false, 'after' => 'type'),
                    'enddate'                            => array('type' => 'INT(14)', 'notnull' => false, 'after' => 'startdate'),
                    'startdate_timestamp'                => array('type' => 'timestamp', 'notnull' => true, 'default' => '0000-00-00 00:00:00', 'after' => 'enddate'),
                    'enddate_timestamp'                  => array('type' => 'timestamp', 'notnull' => true, 'default' => '0000-00-00 00:00:00', 'after' => 'startdate_timestamp'),
                    'use_custom_date_display'            => array('type' => 'TINYINT(1)', 'after' => 'enddate_timestamp'),
                    'showStartDateList'                  => array('type' => 'INT(1)', 'after' => 'use_custom_date_display'),
                    'showEndDateList'                    => array('type' => 'INT(1)', 'after' => 'showStartDateList'),
                    'showStartTimeList'                  => array('type' => 'INT(1)', 'after' => 'showEndDateList'),
                    'showEndTimeList'                    => array('type' => 'INT(1)', 'after' => 'showStartTimeList'),
                    'showTimeTypeList'                   => array('type' => 'INT(1)', 'after' => 'showEndTimeList'),
                    'showStartDateDetail'                => array('type' => 'INT(1)', 'after' => 'showTimeTypeList'),
                    'showEndDateDetail'                  => array('type' => 'INT(1)', 'after' => 'showStartDateDetail'),
                    'showStartTimeDetail'                => array('type' => 'INT(1)', 'after' => 'showEndDateDetail'),
                    'showEndTimeDetail'                  => array('type' => 'INT(1)', 'after' => 'showStartTimeDetail'),
                    'showTimeTypeDetail'                 => array('type' => 'INT(1)', 'after' => 'showEndTimeDetail'),
                    'google'                             => array('type' => 'INT(11)', 'after' => 'showTimeTypeDetail'),
                    'access'                             => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'google'),
                    'priority'                           => array('type' => 'INT(1)', 'notnull' => true, 'default' => '3', 'after' => 'access'),
                    'price'                              => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'priority'),
                    'link'                               => array('type' => 'VARCHAR(255)', 'after' => 'price'),
                    'pic'                                => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'link'),
                    'attach'                             => array('type' => 'VARCHAR(255)', 'after' => 'pic'),
                    'place_mediadir_id'                  => array('type' => 'INT(11)', 'after' => 'attach'),
                    'catid'                              => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'place_mediadir_id'),
                    'show_in'                            => array('type' => 'VARCHAR(255)', 'after' => 'catid'),
                    'invited_groups'                     => array('type' => 'VARCHAR(45)', 'notnull' => false, 'after' => 'show_in'),
                    'invited_mails'                      => array('type' => 'mediumtext', 'notnull' => false, 'after' => 'invited_groups'),
                    'invitation_sent'                    => array('type' => 'INT(1)', 'after' => 'invited_mails'),
                    'invitation_email_template'          => array('type' => 'VARCHAR(255)', 'after' => 'invitation_sent'),
                    'registration'                       => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'invitation_email_template'),
                    'registration_form'                  => array('type' => 'INT(11)', 'after' => 'registration'),
                    'registration_num'                   => array('type' => 'VARCHAR(45)', 'notnull' => false, 'after' => 'registration_form'),
                    'registration_notification'          => array('type' => 'VARCHAR(1024)', 'notnull' => false, 'after' => 'registration_num'),
                    'email_template'                     => array('type' => 'VARCHAR(255)', 'after' => 'registration_notification'),
                    'ticket_sales'                       => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'email_template'),
                    'num_seating'                        => array('type' => 'text', 'after' => 'ticket_sales'),
                    'series_status'                      => array('type' => 'TINYINT(4)', 'notnull' => true, 'default' => '0', 'after' => 'num_seating'),
                    'series_type'                        => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_status'),
                    'series_pattern_count'               => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_type'),
                    'series_pattern_weekday'             => array('type' => 'VARCHAR(7)', 'after' => 'series_pattern_count'),
                    'series_pattern_day'                 => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_pattern_weekday'),
                    'series_pattern_week'                => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_pattern_day'),
                    'series_pattern_month'               => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_pattern_week'),
                    'series_pattern_type'                => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_pattern_month'),
                    'series_pattern_dourance_type'       => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_pattern_type'),
                    'series_pattern_end'                 => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_pattern_dourance_type'),
                    'series_pattern_end_date'            => array('type' => 'timestamp', 'notnull' => true, 'default' => '0000-00-00 00:00:00', 'after' => 'series_pattern_end'),
                    'series_pattern_begin'               => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_pattern_end_date'),
                    'series_pattern_exceptions'          => array('type' => 'longtext', 'after' => 'series_pattern_begin'),
                    'status'                             => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '1', 'after' => 'series_pattern_exceptions'),
                    'confirmed'                          => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '1', 'after' => 'status'),
                    'author'                             => array('type' => 'VARCHAR(255)', 'after' => 'confirmed'),
                    'all_day'                            => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'author'),
                    'location_type'                      => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '1', 'after' => 'all_day'),
                    'place'                              => array('type' => 'VARCHAR(255)', 'after' => 'location_type'),
                    'place_id'                           => array('type' => 'INT(11)', 'after' => 'place'),
                    'place_street'                       => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'place_id'),
                    'place_zip'                          => array('type' => 'VARCHAR(10)', 'notnull' => false, 'after' => 'place_street'),
                    'place_city'                         => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'place_zip'),
                    'place_country'                      => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'place_city'),
                    'place_link'                         => array('type' => 'VARCHAR(255)', 'after' => 'place_country'),
                    'place_map'                          => array('type' => 'VARCHAR(255)', 'after' => 'place_link'),
                    'host_type'                          => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '1', 'after' => 'place_map'),
                    'org_name'                           => array('type' => 'VARCHAR(255)', 'after' => 'host_type'),
                    'org_street'                         => array('type' => 'VARCHAR(255)', 'after' => 'org_name'),
                    'org_zip'                            => array('type' => 'VARCHAR(10)', 'after' => 'org_street'),
                    'org_city'                           => array('type' => 'VARCHAR(255)', 'after' => 'org_zip'),
                    'org_country'                        => array('type' => 'VARCHAR(255)', 'after' => 'org_city'),
                    'org_link'                           => array('type' => 'VARCHAR(255)', 'after' => 'org_country'),
                    'org_email'                          => array('type' => 'VARCHAR(255)', 'after' => 'org_link'),
                    'host_mediadir_id'                   => array('type' => 'INT(11)', 'after' => 'org_email')
                ),
                array(
                    'fk_contrexx_module_calendar_notes_contrexx_module_calendar_ca1' => array('fields' => array('catid'))
                )
            );
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }

        try {
            \Cx\Lib\UpdateUtil::sql('UPDATE `'.DBPREFIX.'module_calendar_event` SET `startdate_timestamp` = FROM_UNIXTIME(`startdate`), `enddate_timestamp` = FROM_UNIXTIME(`enddate`)');
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }

        try {
            \Cx\Lib\UpdateUtil::table(
                DBPREFIX.'module_calendar_event',
                array(
                    'id'                                 => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                    'type'                               => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'id'),
                    'startdate'                          => array('type' => 'timestamp', 'notnull' => true, 'default' => '0000-00-00 00:00:00', 'after' => 'type'),
                    'enddate'                            => array('type' => 'timestamp', 'notnull' => true, 'default' => '0000-00-00 00:00:00', 'after' => 'startdate'),
                    'startdate_timestamp'                => array('type' => 'timestamp', 'notnull' => true, 'default' => '0000-00-00 00:00:00', 'after' => 'enddate'),
                    'enddate_timestamp'                  => array('type' => 'timestamp', 'notnull' => true, 'default' => '0000-00-00 00:00:00', 'after' => 'startdate_timestamp'),
                    'use_custom_date_display'            => array('type' => 'TINYINT(1)', 'after' => 'enddate_timestamp'),
                    'showStartDateList'                  => array('type' => 'INT(1)', 'after' => 'use_custom_date_display'),
                    'showEndDateList'                    => array('type' => 'INT(1)', 'after' => 'showStartDateList'),
                    'showStartTimeList'                  => array('type' => 'INT(1)', 'after' => 'showEndDateList'),
                    'showEndTimeList'                    => array('type' => 'INT(1)', 'after' => 'showStartTimeList'),
                    'showTimeTypeList'                   => array('type' => 'INT(1)', 'after' => 'showEndTimeList'),
                    'showStartDateDetail'                => array('type' => 'INT(1)', 'after' => 'showTimeTypeList'),
                    'showEndDateDetail'                  => array('type' => 'INT(1)', 'after' => 'showStartDateDetail'),
                    'showStartTimeDetail'                => array('type' => 'INT(1)', 'after' => 'showEndDateDetail'),
                    'showEndTimeDetail'                  => array('type' => 'INT(1)', 'after' => 'showStartTimeDetail'),
                    'showTimeTypeDetail'                 => array('type' => 'INT(1)', 'after' => 'showEndTimeDetail'),
                    'google'                             => array('type' => 'INT(11)', 'after' => 'showTimeTypeDetail'),
                    'access'                             => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'google'),
                    'priority'                           => array('type' => 'INT(1)', 'notnull' => true, 'default' => '3', 'after' => 'access'),
                    'price'                              => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'priority'),
                    'link'                               => array('type' => 'VARCHAR(255)', 'after' => 'price'),
                    'pic'                                => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'link'),
                    'attach'                             => array('type' => 'VARCHAR(255)', 'after' => 'pic'),
                    'place_mediadir_id'                  => array('type' => 'INT(11)', 'after' => 'attach'),
                    'catid'                              => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'place_mediadir_id'),
                    'show_in'                            => array('type' => 'VARCHAR(255)', 'after' => 'catid'),
                    'invited_groups'                     => array('type' => 'VARCHAR(45)', 'notnull' => false, 'after' => 'show_in'),
                    'invited_mails'                      => array('type' => 'mediumtext', 'notnull' => false, 'after' => 'invited_groups'),
                    'invitation_sent'                    => array('type' => 'INT(1)', 'after' => 'invited_mails'),
                    'invitation_email_template'          => array('type' => 'VARCHAR(255)', 'after' => 'invitation_sent'),
                    'registration'                       => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'invitation_email_template'),
                    'registration_form'                  => array('type' => 'INT(11)', 'after' => 'registration'),
                    'registration_num'                   => array('type' => 'VARCHAR(45)', 'notnull' => false, 'after' => 'registration_form'),
                    'registration_notification'          => array('type' => 'VARCHAR(1024)', 'notnull' => false, 'after' => 'registration_num'),
                    'email_template'                     => array('type' => 'VARCHAR(255)', 'after' => 'registration_notification'),
                    'ticket_sales'                       => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'email_template'),
                    'num_seating'                        => array('type' => 'text', 'after' => 'ticket_sales'),
                    'series_status'                      => array('type' => 'TINYINT(4)', 'notnull' => true, 'default' => '0', 'after' => 'num_seating'),
                    'series_type'                        => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_status'),
                    'series_pattern_count'               => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_type'),
                    'series_pattern_weekday'             => array('type' => 'VARCHAR(7)', 'after' => 'series_pattern_count'),
                    'series_pattern_day'                 => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_pattern_weekday'),
                    'series_pattern_week'                => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_pattern_day'),
                    'series_pattern_month'               => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_pattern_week'),
                    'series_pattern_type'                => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_pattern_month'),
                    'series_pattern_dourance_type'       => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_pattern_type'),
                    'series_pattern_end'                 => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_pattern_dourance_type'),
                    'series_pattern_end_date'            => array('type' => 'timestamp', 'notnull' => true, 'default' => '0000-00-00 00:00:00', 'after' => 'series_pattern_end'),
                    'series_pattern_begin'               => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_pattern_end_date'),
                    'series_pattern_exceptions'          => array('type' => 'longtext', 'after' => 'series_pattern_begin'),
                    'status'                             => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '1', 'after' => 'series_pattern_exceptions'),
                    'confirmed'                          => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '1', 'after' => 'status'),
                    'author'                             => array('type' => 'VARCHAR(255)', 'after' => 'confirmed'),
                    'all_day'                            => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'author'),
                    'location_type'                      => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '1', 'after' => 'all_day'),
                    'place'                              => array('type' => 'VARCHAR(255)', 'after' => 'location_type'),
                    'place_id'                           => array('type' => 'INT(11)', 'after' => 'place'),
                    'place_street'                       => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'place_id'),
                    'place_zip'                          => array('type' => 'VARCHAR(10)', 'notnull' => false, 'after' => 'place_street'),
                    'place_city'                         => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'place_zip'),
                    'place_country'                      => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'place_city'),
                    'place_link'                         => array('type' => 'VARCHAR(255)', 'after' => 'place_country'),
                    'place_map'                          => array('type' => 'VARCHAR(255)', 'after' => 'place_link'),
                    'host_type'                          => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '1', 'after' => 'place_map'),
                    'org_name'                           => array('type' => 'VARCHAR(255)', 'after' => 'host_type'),
                    'org_street'                         => array('type' => 'VARCHAR(255)', 'after' => 'org_name'),
                    'org_zip'                            => array('type' => 'VARCHAR(10)', 'after' => 'org_street'),
                    'org_city'                           => array('type' => 'VARCHAR(255)', 'after' => 'org_zip'),
                    'org_country'                        => array('type' => 'VARCHAR(255)', 'after' => 'org_city'),
                    'org_link'                           => array('type' => 'VARCHAR(255)', 'after' => 'org_country'),
                    'org_email'                          => array('type' => 'VARCHAR(255)', 'after' => 'org_link'),
                    'host_mediadir_id'                   => array('type' => 'INT(11)', 'after' => 'org_email')
                ),
                array(
                    'fk_contrexx_module_calendar_notes_contrexx_module_calendar_ca1' => array('fields' => array('catid'))
                )
            );
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }

        try {
            \Cx\Lib\UpdateUtil::sql('UPDATE `'.DBPREFIX.'module_calendar_event` SET `startdate` = `startdate_timestamp`, `enddate` = `enddate_timestamp`');
            if (\Cx\Lib\UpdateUtil::column_exist(DBPREFIX.'module_calendar_event', 'startdate_timestamp')) {
                \Cx\Lib\UpdateUtil::sql('ALTER TABLE `'.DBPREFIX.'module_calendar_event` DROP COLUMN `startdate_timestamp`');
            }
            if (\Cx\Lib\UpdateUtil::column_exist(DBPREFIX.'module_calendar_event', 'enddate_timestamp')) {
                \Cx\Lib\UpdateUtil::sql('ALTER TABLE `'.DBPREFIX.'module_calendar_event` DROP COLUMN `enddate_timestamp`');
            }
            \Cx\Lib\UpdateUtil::sql('UPDATE `'.DBPREFIX.'module_calendar_event` SET `series_pattern_end_date` = FROM_UNIXTIME(`series_pattern_end`)');
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
    }


    // update calendar data to version 3.2.0
    if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.2.0')) {
        $languages = FWLanguage::getLanguageArray();

        try {
            $result = \Cx\Lib\UpdateUtil::sql('SELECT `id`, `invitation_email_template`, `email_template` FROM `'.DBPREFIX.'module_calendar_event`');
            if ($result && $result->RecordCount() > 0) {
                while (!$result->EOF) {
                    // if the event has been already migrated, continue
                    if (intval($result->fields['invitation_email_template']) != $result->fields['invitation_email_template']) {
                        $result->MoveNext();
                        continue;
                    }
                    $invitationEmailTemplate = array();
                    $emailTemplate = array();
                    foreach ($languages as $langId => $langData) {
                        $invitationEmailTemplate[$langId] = $result->fields['invitation_email_template'];
                        $emailTemplate[$langId] = $result->fields['email_template'];
                    }
                    $invitationEmailTemplate = json_encode($invitationEmailTemplate);
                    $emailTemplate = json_encode($emailTemplate);
                    \Cx\Lib\UpdateUtil::sql('UPDATE `'.DBPREFIX.'module_calendar_event` SET
                                            `invitation_email_template`=\''.contrexx_raw2db($invitationEmailTemplate).'\',
                                            `email_template`=\''.contrexx_raw2db($emailTemplate).'\' WHERE `id`='.intval($result->fields['id']));
                    $result->MoveNext();
                }
            }
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
    }


    return true;
}

class CalendarUpdate31
{
    protected $db;
    protected $categoryLanguages;

    public function run()
    {
        global $objDatabase, $objUpdate;
        $this->db = $objDatabase;

        // set constant for all table names

        // old tables (contrexx version < 3.1)
        define('CALENDAR_OLD_CATEGORY_TABLE', DBPREFIX . 'module_calendar_categories');
        define('CALENDAR_OLD_SETTINGS_TABLE', DBPREFIX . 'module_calendar_settings');
        define('CALENDAR_OLD_EVENT_TABLE', DBPREFIX . 'module_calendar');
        define('CALENDAR_OLD_REGISTRATIONS_TABLE', DBPREFIX . 'module_calendar_registrations');
        define('CALENDAR_OLD_FORM_FIELD_TABLE', DBPREFIX . 'module_calendar_form_fields');
        define('CALENDAR_OLD_FORM_DATA_TABLE', DBPREFIX . 'module_calendar_form_data');


        // new tables (contrexx version >= 3.1)
        define('CALENDAR_NEW_CATEGORY_TABLE', DBPREFIX . 'module_calendar_category');
        define('CALENDAR_NEW_CATEGORY_NAME_TABLE', DBPREFIX . 'module_calendar_category_name');

        define('CALENDAR_NEW_HOST_TABLE', DBPREFIX . 'module_calendar_host');
        define('CALENDAR_NEW_REL_HOST_EVENT_TABLE', DBPREFIX . 'module_calendar_rel_event_host');

        define('CALENDAR_NEW_SETTINGS_TABLE', DBPREFIX . 'module_calendar_settings');
        define('CALENDAR_NEW_SETTINGS_SECTION_TABLE', DBPREFIX . 'module_calendar_settings_section');

        define('CALENDAR_NEW_MAIL_TABLE', DBPREFIX . 'module_calendar_mail');
        define('CALENDAR_NEW_MAIL_ACTION_TABLE', DBPREFIX . 'module_calendar_mail_action');

        define('CALENDAR_NEW_EVENT_TABLE', DBPREFIX . 'module_calendar_event');
        define('CALENDAR_NEW_EVENT_FIELD_TABLE', DBPREFIX . 'module_calendar_event_field');

        define('CALENDAR_NEW_REGISTRATION_TABLE', DBPREFIX . 'module_calendar_registration');
        define('CALENDAR_NEW_REGISTRATION_FORM_TABLE', DBPREFIX . 'module_calendar_registration_form');
        define('CALENDAR_NEW_REGISTRATION_FORM_FIELD_TABLE', DBPREFIX . 'module_calendar_registration_form_field');
        define('CALENDAR_NEW_REGISTRATION_FORM_FIELD_NAME_TABLE', DBPREFIX . 'module_calendar_registration_form_field_name');
        define('CALENDAR_NEW_REGISTRATION_FORM_FIELD_VALUE_TABLE', DBPREFIX . 'module_calendar_registration_form_field_value');

        if (!isset($_SESSION['contrexx_update']['calendar'])) {
            $_SESSION['contrexx_update']['calendar'] = array();
        }
        // create new tables
        if (empty($_SESSION['contrexx_update']['calendar']['tables_created'])) {
            $createTablesState = $this->createNewTables();
            if ($createTablesState !== true || !checkMemoryLimit() || !checkTimeoutLimit()) {
                return ($createTablesState === true ? 'timeout' : $createTablesState);
            }
            $_SESSION['contrexx_update']['calendar']['tables_created'] = true;
        }

        // insert demo data to settings tables
        if (empty($_SESSION['contrexx_update']['calendar']['settings_demo_data'])) {
            $settingsDemoData = $this->insertSettingsDemoData();
            if ($settingsDemoData !== true || !checkMemoryLimit() || !checkTimeoutLimit()) {
                return ($settingsDemoData === true ? 'timeout' : $settingsDemoData);
            }
            $_SESSION['contrexx_update']['calendar']['settings_demo_data'] = true;
        }

        // insert demo data for mail tables
        if (empty($_SESSION['contrexx_update']['calendar']['mail_demo_data'])) {
            $mailDemoData = $this->insertMailDemoData();
            if ($mailDemoData !== true || !checkMemoryLimit() || !checkTimeoutLimit()) {
                return ($mailDemoData === true ? 'timeout' : $mailDemoData);
            }
            $_SESSION['contrexx_update']['calendar']['mail_demo_data'] = true;
        }

        // migrate categories
        if (empty($_SESSION['contrexx_update']['calendar']['categories']) || $_SESSION['contrexx_update']['calendar']['categories'] !== true) {
            $migrateCategories = $this->migrateCategories();
            if ($migrateCategories !== true || !checkMemoryLimit() || !checkTimeoutLimit()) {
                return ($migrateCategories === true ? 'timeout' : $migrateCategories);
            }
            $_SESSION['contrexx_update']['calendar']['categories'] = true;
        }

        // write language ids in relation to category id in an array
        // this array is used for the events, so we know in which language events of these categories
        // are available
        $this->categoryLanguages = $this->getCategoryLanguages();

        // migrate events
        if (empty($_SESSION['contrexx_update']['calendar']['events']) || $_SESSION['contrexx_update']['calendar']['events'] !== true) {
            $migrateEvents = $this->migrateEvents();
            if ($migrateEvents !== true || !checkMemoryLimit() || !checkTimeoutLimit()) {
                return ($migrateEvents === true ? 'timeout' : $migrateEvents);
            }
            $_SESSION['contrexx_update']['calendar']['events'] = true;
        }

        // drop old tables
        if (empty($_SESSION['contrexx_update']['calendar']['migration_completed']) == true) {
            $dropTables = $this->dropOldTables();
            if ($dropTables !== true || !checkMemoryLimit() || !checkTimeoutLimit()) {
                return ($dropTables === true ? 'timeout' : $migrateCategories);
            }
            $_SESSION['contrexx_update']['calendar']['migration_completed'] = true;
        }

        // add access to access ids 164/165/166/167 for user groups which had access to access id 16
        try {
            $result = \Cx\Lib\UpdateUtil::sql("SELECT `group_id` FROM `" . DBPREFIX . "access_group_static_ids` WHERE access_id = 16 GROUP BY group_id");
            if ($result !== false) {
                while (!$result->EOF) {
                    \Cx\Lib\UpdateUtil::sql("INSERT IGNORE INTO `" . DBPREFIX . "access_group_static_ids` (`access_id`, `group_id`)
                                                VALUES
                                                (165, " . intval($result->fields['group_id']) . "),
                                                (180, " . intval($result->fields['group_id']) . "),
                                                (181, " . intval($result->fields['group_id']) . "),
                                                (182, " . intval($result->fields['group_id']) . ")
                                            ");
                    $result->MoveNext();
                }
            }
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }

        // migrate old content pages to new ones
        try {
            $this->migrateContentPages();
        } catch (\Exception $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }

        return true;
    }


    /**
     * create new tables
     * @return string|boolean
     */
    protected function createNewTables()
    {
        try {
            // host table
            if (!\Cx\Lib\UpdateUtil::table_exist(CALENDAR_NEW_HOST_TABLE)) {
                \Cx\Lib\UpdateUtil::table(
                    CALENDAR_NEW_HOST_TABLE,
                    array(
                        'id' => array('type' => 'INT(1)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                        'title' => array('type' => 'VARCHAR(255)', 'after' => 'id'),
                        'uri' => array('type' => 'mediumtext', 'after' => 'title'),
                        'cat_id' => array('type' => 'INT(11)', 'after' => 'uri'),
                        'key' => array('type' => 'VARCHAR(40)', 'after' => 'cat_id'),
                        'confirmed' => array('type' => 'INT(11)', 'after' => 'key'),
                        'status' => array('type' => 'INT(1)', 'after' => 'confirmed')
                    ),
                    array(
                        'fk_contrexx_module_calendar_shared_hosts_contrexx_module_cale1' => array('fields' => array('cat_id'))
                    )
                );
            }

            if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                return 'timeout';
            }

            // relation table for host - event
            if (!\Cx\Lib\UpdateUtil::table_exist(CALENDAR_NEW_REL_HOST_EVENT_TABLE)) {
                \Cx\Lib\UpdateUtil::table(
                    CALENDAR_NEW_REL_HOST_EVENT_TABLE,
                    array(
                        'host_id' => array('type' => 'INT(11)'),
                        'event_id' => array('type' => 'INT(11)', 'notnull' => true, 'after' => 'host_id')
                    )
                );
            }

            if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                return 'timeout';
            }

            // settings table
            if (!\Cx\Lib\UpdateUtil::table_empty(CALENDAR_OLD_SETTINGS_TABLE)) {
                // remove old settings data
                \Cx\Lib\UpdateUtil::drop_table(CALENDAR_NEW_SETTINGS_TABLE);
            }
            \Cx\Lib\UpdateUtil::table(
                CALENDAR_NEW_SETTINGS_TABLE,
                array(
                    'id' => array('type' => 'INT(7)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                    'section_id' => array('type' => 'INT(11)', 'after' => 'id'),
                    'name' => array('type' => 'VARCHAR(255)', 'after' => 'section_id'),
                    'title' => array('type' => 'VARCHAR(255)', 'after' => 'name'),
                    'value' => array('type' => 'mediumtext', 'after' => 'title'),
                    'info' => array('type' => 'mediumtext', 'after' => 'value'),
                    'type' => array('type' => 'INT(11)', 'after' => 'info'),
                    'options' => array('type' => 'mediumtext', 'after' => 'type'),
                    'special' => array('type' => 'VARCHAR(255)', 'after' => 'options'),
                    'order' => array('type' => 'INT(11)', 'after' => 'special')
                )
            );

            if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                return 'timeout';
            }

            // settings section table
            if (!\Cx\Lib\UpdateUtil::table_exist(CALENDAR_NEW_SETTINGS_SECTION_TABLE)) {
                \Cx\Lib\UpdateUtil::table(
                    CALENDAR_NEW_SETTINGS_SECTION_TABLE,
                    array(
                        'id' => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                        'parent' => array('type' => 'INT(11)', 'after' => 'id'),
                        'order' => array('type' => 'INT(11)', 'after' => 'parent'),
                        'name' => array('type' => 'VARCHAR(255)', 'after' => 'order'),
                        'title' => array('type' => 'VARCHAR(255)', 'after' => 'name')
                    )
                );
            }

            if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                return 'timeout';
            }

            // registration table
            if (!\Cx\Lib\UpdateUtil::table_exist(CALENDAR_NEW_REGISTRATION_TABLE)) {
                \Cx\Lib\UpdateUtil::table(
                    CALENDAR_NEW_REGISTRATION_TABLE,
                    array(
                        'id' => array('type' => 'INT(7)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                        'event_id' => array('type' => 'INT(7)', 'after' => 'id'),
                        'date' => array('type' => 'INT(15)', 'after' => 'event_id'),
                        'host_name' => array('type' => 'VARCHAR(255)', 'after' => 'date'),
                        'ip_address' => array('type' => 'VARCHAR(15)', 'after' => 'host_name'),
                        'type' => array('type' => 'INT(1)', 'after' => 'ip_address'),
                        'key' => array('type' => 'VARCHAR(45)', 'after' => 'type'),
                        'user_id' => array('type' => 'INT(7)', 'after' => 'key'),
                        'lang_id' => array('type' => 'INT(11)', 'after' => 'user_id'),
                        'export' => array('type' => 'INT(11)', 'after' => 'lang_id'),
                        'payment_method' => array('type' => 'INT(11)', 'after' => 'export'),
                        'paid' => array('type' => 'INT(11)', 'after' => 'payment_method')
                    )
                );
            }

            if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                return 'timeout';
            }

            // registration form table
            if (!\Cx\Lib\UpdateUtil::table_exist(CALENDAR_NEW_REGISTRATION_FORM_TABLE)) {
                \Cx\Lib\UpdateUtil::table(
                    CALENDAR_NEW_REGISTRATION_FORM_TABLE,
                    array(
                        'id' => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                        'status' => array('type' => 'INT(11)', 'after' => 'id'),
                        'order' => array('type' => 'INT(11)', 'after' => 'status'),
                        'title' => array('type' => 'VARCHAR(255)', 'after' => 'order')
                    )
                );
            }

            if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                return 'timeout';
            }

            // registration form field table
            if (!\Cx\Lib\UpdateUtil::table_exist(CALENDAR_NEW_REGISTRATION_FORM_FIELD_TABLE)) {
                \Cx\Lib\UpdateUtil::table(
                    DBPREFIX . 'module_calendar_registration_form_field',
                    array(
                        'id' => array('type' => 'INT(7)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                        'form' => array('type' => 'INT(11)', 'after' => 'id'),
                        'type' => array('type' => 'ENUM(\'inputtext\',\'textarea\',\'select\',\'radio\',\'checkbox\',\'mail\',\'seating\',\'agb\',\'salutation\',\'firstname\',\'lastname\',\'selectBillingAddress\',\'fieldset\')', 'after' => 'form'),
                        'required' => array('type' => 'INT(1)', 'after' => 'type'),
                        'order' => array('type' => 'INT(3)', 'after' => 'required'),
                        'affiliation' => array('type' => 'VARCHAR(45)', 'after' => 'order')
                    )
                );
            }

            if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                return 'timeout';
            }

            // registration form field name table
            if (!\Cx\Lib\UpdateUtil::table_exist(CALENDAR_NEW_REGISTRATION_FORM_FIELD_NAME_TABLE)) {
                \Cx\Lib\UpdateUtil::table(
                    CALENDAR_NEW_REGISTRATION_FORM_FIELD_NAME_TABLE,
                    array(
                        'field_id' => array('type' => 'INT(7)'),
                        'form_id' => array('type' => 'INT(11)', 'after' => 'field_id'),
                        'lang_id' => array('type' => 'INT(1)', 'after' => 'form_id'),
                        'name' => array('type' => 'VARCHAR(255)', 'after' => 'lang_id'),
                        'default' => array('type' => 'mediumtext', 'notnull' => true, 'after' => 'name')
                    )
                );
            }

            if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                return 'timeout';
            }

            // registration form field value table
            if (!\Cx\Lib\UpdateUtil::table_exist(CALENDAR_NEW_REGISTRATION_FORM_FIELD_VALUE_TABLE)) {
                \Cx\Lib\UpdateUtil::table(
                    CALENDAR_NEW_REGISTRATION_FORM_FIELD_VALUE_TABLE,
                    array(
                        'reg_id' => array('type' => 'INT(7)'),
                        'field_id' => array('type' => 'INT(7)', 'after' => 'reg_id'),
                        'value' => array('type' => 'mediumtext', 'notnull' => true, 'after' => 'field_id')
                    )
                );
            }

            if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                return 'timeout';
            }

            // category table
            if (!\Cx\Lib\UpdateUtil::table_exist(CALENDAR_NEW_CATEGORY_TABLE)) {
                \Cx\Lib\UpdateUtil::table(
                    CALENDAR_NEW_CATEGORY_TABLE,
                    array(
                        'id' => array('type' => 'INT(5)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                        'pos' => array('type' => 'INT(5)', 'notnull' => false, 'after' => 'id'),
                        'status' => array('type' => 'INT(1)', 'notnull' => false, 'after' => 'pos')
                    )
                );
            }

            if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                return 'timeout';
            }

            // category name table
            if (!\Cx\Lib\UpdateUtil::table_exist(CALENDAR_NEW_CATEGORY_NAME_TABLE)) {
                \Cx\Lib\UpdateUtil::table(
                    CALENDAR_NEW_CATEGORY_NAME_TABLE,
                    array(
                        'cat_id' => array('type' => 'INT(11)'),
                        'lang_id' => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'cat_id'),
                        'name' => array('type' => 'VARCHAR(225)', 'notnull' => false, 'after' => 'lang_id')
                    ),
                    array(
                        'fk_contrexx_module_calendar_category_names_contrexx_module_ca1' => array('fields' => array('cat_id'))
                    )
                );
            }

            if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                return 'timeout';
            }

            // mail table
            if (!\Cx\Lib\UpdateUtil::table_exist(CALENDAR_NEW_MAIL_TABLE)) {
                \Cx\Lib\UpdateUtil::table(
                    CALENDAR_NEW_MAIL_TABLE,
                    array(
                        'id' => array('type' => 'INT(7)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                        'title' => array('type' => 'VARCHAR(255)', 'after' => 'id'),
                        'content_text' => array('type' => 'longtext', 'after' => 'title'),
                        'content_html' => array('type' => 'longtext', 'after' => 'content_text'),
                        'recipients' => array('type' => 'mediumtext', 'after' => 'content_html'),
                        'lang_id' => array('type' => 'INT(1)', 'after' => 'recipients'),
                        'action_id' => array('type' => 'INT(1)', 'after' => 'lang_id'),
                        'is_default' => array('type' => 'INT(1)', 'after' => 'action_id'),
                        'status' => array('type' => 'INT(1)', 'after' => 'is_default')
                    )
                );
            }

            if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                return 'timeout';
            }

            // mail action table
            if (!\Cx\Lib\UpdateUtil::table_exist(CALENDAR_NEW_MAIL_ACTION_TABLE)) {
                \Cx\Lib\UpdateUtil::table(
                    CALENDAR_NEW_MAIL_ACTION_TABLE,
                    array(
                        'id' => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                        'name' => array('type' => 'VARCHAR(255)', 'after' => 'id'),
                        'default_recipient' => array('type' => 'ENUM(\'empty\',\'admin\',\'author\')', 'after' => 'name'),
                        'need_auth' => array('type' => 'INT(11)', 'after' => 'default_recipient')
                    )
                );
            }

            if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                return 'timeout';
            }

            // event table
            if (!\Cx\Lib\UpdateUtil::table_exist(CALENDAR_NEW_EVENT_TABLE)) {
                \Cx\Lib\UpdateUtil::table(
                    DBPREFIX.'module_calendar_event',
                    array(
                        'id'                                 => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                        'type'                               => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'id'),
                        'startdate'                          => array('type' => 'INT(14)', 'notnull' => false, 'after' => 'type'),
                        'enddate'                            => array('type' => 'INT(14)', 'notnull' => false, 'after' => 'startdate'),
                        'use_custom_date_display'            => array('type' => 'TINYINT(1)', 'after' => 'enddate'),
                        'showStartDateList'                  => array('type' => 'INT(1)', 'after' => 'use_custom_date_display'),
                        'showEndDateList'                    => array('type' => 'INT(1)', 'after' => 'showStartDateList'),
                        'showStartTimeList'                  => array('type' => 'INT(1)', 'after' => 'showEndDateList'),
                        'showEndTimeList'                    => array('type' => 'INT(1)', 'after' => 'showStartTimeList'),
                        'showTimeTypeList'                   => array('type' => 'INT(1)', 'after' => 'showEndTimeList'),
                        'showStartDateDetail'                => array('type' => 'INT(1)', 'after' => 'showTimeTypeList'),
                        'showEndDateDetail'                  => array('type' => 'INT(1)', 'after' => 'showStartDateDetail'),
                        'showStartTimeDetail'                => array('type' => 'INT(1)', 'after' => 'showEndDateDetail'),
                        'showEndTimeDetail'                  => array('type' => 'INT(1)', 'after' => 'showStartTimeDetail'),
                        'showTimeTypeDetail'                 => array('type' => 'INT(1)', 'after' => 'showEndTimeDetail'),
                        'google'                             => array('type' => 'INT(11)', 'after' => 'showTimeTypeDetail'),
                        'access'                             => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'google'),
                        'priority'                           => array('type' => 'INT(1)', 'notnull' => true, 'default' => '3', 'after' => 'access'),
                        'price'                              => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'priority'),
                        'link'                               => array('type' => 'VARCHAR(255)', 'after' => 'price'),
                        'pic'                                => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'link'),
                        'attach'                             => array('type' => 'VARCHAR(255)', 'after' => 'pic'),
                        'place_mediadir_id'                  => array('type' => 'INT(11)', 'after' => 'attach'),
                        'catid'                              => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'place_mediadir_id'),
                        'show_in'                            => array('type' => 'VARCHAR(255)', 'after' => 'catid'),
                        'invited_groups'                     => array('type' => 'VARCHAR(45)', 'notnull' => false, 'after' => 'show_in'),
                        'invited_mails'                      => array('type' => 'mediumtext', 'notnull' => false, 'after' => 'invited_groups'),
                        'invitation_sent'                    => array('type' => 'INT(1)', 'after' => 'invited_mails'),
                        'invitation_email_template'          => array('type' => 'VARCHAR(255)', 'after' => 'invitation_sent'),
                        'registration'                       => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'invitation_email_template'),
                        'registration_form'                  => array('type' => 'INT(11)', 'after' => 'registration'),
                        'registration_num'                   => array('type' => 'VARCHAR(45)', 'notnull' => false, 'after' => 'registration_form'),
                        'registration_notification'          => array('type' => 'VARCHAR(1024)', 'notnull' => false, 'after' => 'registration_num'),
                        'email_template'                     => array('type' => 'INT(11)', 'after' => 'registration_notification'),
                        'ticket_sales'                       => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'email_template'),
                        'num_seating'                        => array('type' => 'text', 'after' => 'ticket_sales'),
                        'series_status'                      => array('type' => 'TINYINT(4)', 'notnull' => true, 'default' => '0', 'after' => 'num_seating'),
                        'series_type'                        => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_status'),
                        'series_pattern_count'               => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_type'),
                        'series_pattern_weekday'             => array('type' => 'VARCHAR(7)', 'after' => 'series_pattern_count'),
                        'series_pattern_day'                 => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_pattern_weekday'),
                        'series_pattern_week'                => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_pattern_day'),
                        'series_pattern_month'               => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_pattern_week'),
                        'series_pattern_type'                => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_pattern_month'),
                        'series_pattern_dourance_type'       => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_pattern_type'),
                        'series_pattern_end'                 => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_pattern_dourance_type'),
                        'series_pattern_begin'               => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'series_pattern_end'),
                        'series_pattern_exceptions'          => array('type' => 'longtext', 'after' => 'series_pattern_begin'),
                        'status'                             => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '1', 'after' => 'series_pattern_exceptions'),
                        'confirmed'                          => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '1', 'after' => 'status'),
                        'author'                             => array('type' => 'VARCHAR(255)', 'after' => 'confirmed'),
                        'all_day'                            => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'author'),
                        'place'                              => array('type' => 'VARCHAR(255)', 'after' => 'all_day'),
                        'place_id'                           => array('type' => 'INT(11)', 'after' => 'place'),
                        'place_street'                       => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'place_id'),
                        'place_zip'                          => array('type' => 'VARCHAR(10)', 'notnull' => false, 'after' => 'place_street'),
                        'place_city'                         => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'place_zip'),
                        'place_country'                      => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'place_city'),
                        'place_link'                         => array('type' => 'VARCHAR(255)', 'after' => 'place_country'),
                        'place_map'                          => array('type' => 'VARCHAR(255)', 'after' => 'place_link'),
                        'org_name'                           => array('type' => 'VARCHAR(255)', 'after' => 'place_map'),
                        'org_street'                         => array('type' => 'VARCHAR(255)', 'after' => 'org_name'),
                        'org_zip'                            => array('type' => 'VARCHAR(10)', 'after' => 'org_street'),
                        'org_city'                           => array('type' => 'VARCHAR(255)', 'after' => 'org_zip'),
                        'org_link'                           => array('type' => 'VARCHAR(255)', 'after' => 'org_city'),
                        'org_email'                          => array('type' => 'VARCHAR(255)', 'after' => 'org_link'),
                        'host_mediadir_id'                   => array('type' => 'INT(11)', 'after' => 'org_email')
                    ),
                    array(
                        'fk_contrexx_module_calendar_notes_contrexx_module_calendar_ca1' => array('fields' => array('catid'))
                    )
                );
            }

            if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                return 'timeout';
            }

            // event field table
            if (!\Cx\Lib\UpdateUtil::table_exist(CALENDAR_NEW_EVENT_FIELD_TABLE)) {
                \Cx\Lib\UpdateUtil::table(
                    CALENDAR_NEW_EVENT_FIELD_TABLE,
                    array(
                        'event_id' => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0'),
                        'lang_id' => array('type' => 'VARCHAR(225)', 'notnull' => false, 'after' => 'event_id'),
                        'title' => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'lang_id'),
                        'description' => array('type' => 'mediumtext', 'notnull' => false, 'after' => 'title'),
                        'redirect' => array('type' => 'VARCHAR(255)', 'after' => 'description')
                    ),
                    array(
                        'lang_field' => array('fields' => array('title')),
                        'fk_contrexx_module_calendar_note_field_contrexx_module_calend1' => array('fields' => array('event_id')),
                        'eventIndex' => array('fields' => array('title', 'description'), 'type' => 'FULLTEXT')
                    )
                );
            }
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
        return true;

        // the style table hasn't been modified
    }

    /**
     * insert demo data for settings tables
     * @return bool|string
     */
    protected function insertSettingsDemoData()
    {
        global $_CONFIG;
        try {
            if (\Cx\Lib\UpdateUtil::table_empty(CALENDAR_NEW_SETTINGS_TABLE)) {
                $headlinesActivated = $_CONFIG['calendarheadlines'];
                $headlinesCategory = $_CONFIG['calendarheadlinescat'];
                $headlinesCount = $_CONFIG['calendarheadlinescount'];
                $defaultCount = $_CONFIG['calendardefaultcount'];
                \Cx\Lib\UpdateUtil::sql("
                    INSERT IGNORE INTO `" . CALENDAR_NEW_SETTINGS_TABLE . "` (`id`, `section_id`, `name`, `title`, `value`, `info`, `type`, `options`, `special`, `order`)
                    VALUES
                        (8, 5, 'numPaging', 'TXT_CALENDAR_NUM_PAGING', '" . $defaultCount . "', '', 1, '', '', 1),
                        (9, 5, 'numEntrance', 'TXT_CALENDAR_NUM_EVENTS_ENTRANCE', '" . $defaultCount . "', '', 1, '', '', 2),
                        (10, 6, 'headlinesStatus', 'TXT_CALENDAR_HEADLINES_STATUS', '" . $headlinesActivated . "', '', 3, 'TXT_CALENDAR_ACTIVATE,TXT_CALENDAR_DEACTIVATE', '', 1),
                        (11, 6, 'headlinesCategory', 'TXT_CALENDAR_HEADLINES_CATEGORY', '" . $headlinesCategory . "', '', 5, '', 'getCategoryDorpdown', 3),
                        (12, 6, 'headlinesNum', 'TXT_CALENDAR_HEADLINES_NUM', '" . $headlinesCount . "', '', 1, '', '', 2),
                        (14, 7, 'publicationStatus', 'TXT_CALENDAR_PUBLICATION_STATUS', '2', 'TXT_CALENDAR_PUBLICATION_STATUS_INFO', 3, 'TXT_CALENDAR_ACTIVATE,TXT_CALENDAR_DEACTIVATE', '', 0),
                        (15, 15, 'dateFormat', 'TXT_CALENDAR_DATE_FORMAT', '0', 'TXT_CALENDAR_DATE_FORMAT_INFO', 5, 'TXT_CALENDAR_DATE_FORMAT_DD.MM.YYYY,TXT_CALENDAR_DATE_FORMAT_DD/MM/YYYY,TXT_CALENDAR_DATE_FORMAT_YYYY.MM.DD,TXT_CALENDAR_DATE_FORMAT_MM/DD/YYYY,TXT_CALENDAR_DATE_FORMAT_YYYY-MM-DD', '', 3),
                        (16, 8, 'countCategoryEntries', 'TXT_CALENDAR_CATEGORY_COUNT_ENTRIES', '2', '', 3, 'TXT_CALENDAR_ACTIVATE,TXT_CALENDAR_DEACTIVATE', '', 0),
                        (18, 18, 'addEventsFrontend', 'TXT_CALENDAR_ADD_EVENTS_FRONTEND', '0', '', 5, 'TXT_CALENDAR_DEACTIVATE,TXT_CALENDAR_ACTIVATE_ALL,TXT_CALENDAR_ACTIVATE_ONLY_COMMUNITY', '', 5),
                        (19, 18, 'confirmFrontendEvents', 'TXT_CALENDAR_CONFIRM_FRONTEND_EVENTS', '2', '', 3, 'TXT_CALENDAR_ACTIVATE,TXT_CALENDAR_DEACTIVATE', '', 6),
                        (22, 10, 'paymentStatus', 'TXT_CALENDAR_PAYMENT_STATUS', '2', '', 3, 'TXT_CALENDAR_ACTIVATE,TXT_CALENDAR_DEACTIVATE', '', 1),
                        (23, 10, 'paymentCurrency', 'TXT_CALENDAR_PAYMENT_CURRENCY', 'CHF', '', 1, '', '', 2),
                        (24, 10, 'paymentVatRate', 'TXT_CALENDAR_PAYMENT_VAT_RATE', '8', '', 1, '', '', 3),
                        (25, 11, 'paymentBillStatus', 'TXT_CALENDAR_PAYMENT_BILL_STATUS', '2', '', 3, 'TXT_CALENDAR_ACTIVATE,TXT_CALENDAR_DEACTIVATE', '', 1),
                        (26, 11, 'paymentlBillGrace', 'TXT_CALENDAR_PAYMENT_BILL_GRACE', '30', '', 1, '', '', 2),
                        (27, 12, 'paymentYellowpayStatus', 'TXT_CALENDAR_PAYMENT_YELLOWPAY_STATUS', '2', '', 3, 'TXT_CALENDAR_ACTIVATE,TXT_CALENDAR_DEACTIVATE', '', 1),
                        (28, 12, 'paymentYellowpayPspid', 'TXT_CALENDAR_PAYMENT_YELLOWPAY_PSPID', '', '', 1, '', '', 2),
                        (29, 12, 'paymentYellowpayShaIn', 'TXT_CALENDAR_PAYMENT_YELLOWPAY_SHA_IN', '', '', 1, '', '', 3),
                        (30, 12, 'paymentYellowpayShaOut', 'TXT_CALENDAR_PAYMENT_YELLOWPAY_SHA_OUT', '', '', 1, '', '', 4),
                        (31, 12, 'paymentYellowpayAuthorization', 'TXT_CALENDAR_PAYMENT_YELLOWPAY_AUTHORIZATION', '0', '', 5, 'TXT_CALENDAR_PAYMENT_YELLOWPAY_AUTHORIZATION_SALE,TXT_CALENDAR_PAYMENT_YELLOWPAY_AUTHORIZATION', '', 5),
                        (32, 12, 'paymentTestserver', 'TXT_CALENDAR_PAYMENT_YELLOWPAY_TESTSERVER', '2', '', 3, 'TXT_CALENDAR_YES,TXT_CALENDAR_NO', '', 7),
                        (33, 12, 'paymentYellowpayMethods', 'TXT_CALENDAR_PAYMENT_YELLOWPAY_METHODS', '0', '', 4, 'TXT_CALENDAR_PAYMENT_YELLOWPAY_POSTFINANCE,TXT_CALENDAR_PAYMENT_YELLOWPAY_POSTFINANCE_EFINANCE,TXT_CALENDAR_PAYMENT_YELLOWPAY_MASTERCARD,TXT_CALENDAR_PAYMENT_YELLOWPAY_VISA,TXT_CALENDAR_PAYMENT_YELLOWPAY_AMEX,TXT_CALENDAR_PAYMENT_YELLOWPAY_DINERS', '', 6),
                        (34, 10, 'paymentVatNumber', 'TXT_CALENDAR_PAYMENT_VAT_NUMBER', '', '', 1, '', '', 4),
                        (35, 13, 'paymentBank', 'TXT_CALENDAR_PAYMENT_BANK', '', '', 1, '', '', 1),
                        (36, 13, 'paymentBankAccount', 'TXT_CALENDAR_PAYMENT_BANK_ACCOUNT', '', '', 1, '', '', 2),
                        (37, 13, 'paymentBankIBAN', 'TXT_CALENDAR_PAYMENT_BANK_IBAN', '', '', 1, '', '', 3),
                        (38, 13, 'paymentBankCN', 'TXT_CALENDAR_PAYMENT_BANK_CN', '', '', 1, '', '', 4),
                        (39, 13, 'paymentBankSC', 'TXT_CALENDAR_PAYMENT_BANK_SC', '', '', 1, '', '', 5),
                        (40, 17, 'separatorDateDetail', 'TXT_CALENDAR_SEPARATOR_DATE', '1', 'TXT_CALENDAR_SEPARATOR_DATE_INFO', 5, 'TXT_CALENDAR_SEPARATOR_SPACE,TXT_CALENDAR_SEPARATOR_HYPHEN,TXT_CALENDAR_SEPARATOR_COLON,TXT_CALENDAR_SEPARATOR_TO', '', 1),
                        (41, 17, 'separatorTimeDetail', 'TXT_CALENDAR_SEPARATOR_TIME', '3', 'TXT_CALENDAR_SEPARATOR_TIME_INFO', 5, 'TXT_CALENDAR_SEPARATOR_SPACE,TXT_CALENDAR_SEPARATOR_HYPHEN,TXT_CALENDAR_SEPARATOR_COLON,TXT_CALENDAR_SEPARATOR_TO', '', 2),
                        (42, 17, 'separatorDateTimeDetail', 'TXT_CALENDAR_SEPARATOR_DATE_TIME', '3', 'TXT_CALENDAR_SEPARATOR_DATE_TIME_INFO', 5, 'TXT_CALENDAR_SEPARATOR_NOTHING,TXT_CALENDAR_SEPARATOR_SPACE,TXT_CALENDAR_SEPARATOR_BREAK,TXT_CALENDAR_SEPARATOR_HYPHEN,TXT_CALENDAR_SEPARATOR_COLON', '', 3),
                        (43, 17, 'separatorSeveralDaysDetail', 'TXT_CALENDAR_SEPARATOR_SEVERAL_DAYS', '2', 'TXT_CALENDAR_SEPARATOR_SEVERAL_DAYS_INFO', 5, 'TXT_CALENDAR_SEPARATOR_SPACE,TXT_CALENDAR_SEPARATOR_HYPHEN,TXT_CALENDAR_SEPARATOR_TO,TXT_CALENDAR_SEPARATOR_BREAK', '', 4),
                        (44, 17, 'showClockDetail', 'TXT_CALENDAR_SHOW_CLOCK', '1', 'TXT_CALENDAR_SEPARATOR_SHOW_CLOCK_INFO', 3, 'TXT_CALENDAR_ACTIVATE,TXT_CALENDAR_DEACTIVATE', '', 5),
                        (45, 17, 'showStartDateDetail', 'TXT_CALENDAR_SHOW_START_DATE', '1', 'TXT_CALENDAR_SHOW_START_DATE_INFO', 3, 'TXT_CALENDAR_ACTIVATE,TXT_CALENDAR_DEACTIVATE', '', 6),
                        (46, 17, 'showEndDateDetail', 'TXT_CALENDAR_SHOW_END_DATE', '1', 'TXT_CALENDAR_SHOW_END_DATE_INFO', 3, 'TXT_CALENDAR_ACTIVATE,TXT_CALENDAR_DEACTIVATE', '', 7),
                        (47, 17, 'showStartTimeDetail', 'TXT_CALENDAR_SHOW_START_TIME', '1', 'TXT_CALENDAR_SHOW_START_TIME_INFO', 3, 'TXT_CALENDAR_ACTIVATE,TXT_CALENDAR_DEACTIVATE', '', 9),
                        (48, 17, 'showEndTimeDetail', 'TXT_CALENDAR_SHOW_END_TIME', '1', 'TXT_CALENDAR_SHOW_END_TIME_INFO', 3, 'TXT_CALENDAR_ACTIVATE,TXT_CALENDAR_DEACTIVATE', '', 10),
                        (49, 16, 'separatorDateList', 'TXT_CALENDAR_SEPARATOR_DATE', '1', 'TXT_CALENDAR_SEPARATOR_DATE_INFO', 5, 'TXT_CALENDAR_SEPARATOR_SPACE,TXT_CALENDAR_SEPARATOR_HYPHEN,TXT_CALENDAR_SEPARATOR_COLON,TXT_CALENDAR_SEPARATOR_TO', '', 1),
                        (50, 16, 'separatorTimeList', 'TXT_CALENDAR_SEPARATOR_TIME', '1', 'TXT_CALENDAR_SEPARATOR_TIME_INFO', 5, 'TXT_CALENDAR_SEPARATOR_SPACE,TXT_CALENDAR_SEPARATOR_HYPHEN,TXT_CALENDAR_SEPARATOR_COLON,TXT_CALENDAR_SEPARATOR_TO', '', 2),
                        (51, 16, 'separatorDateTimeList', 'TXT_CALENDAR_SEPARATOR_DATE_TIME', '1', 'TXT_CALENDAR_SEPARATOR_DATE_TIME_INFO', 5, 'TXT_CALENDAR_SEPARATOR_NOTHING,TXT_CALENDAR_SEPARATOR_SPACE,TXT_CALENDAR_SEPARATOR_BREAK,TXT_CALENDAR_SEPARATOR_HYPHEN,TXT_CALENDAR_SEPARATOR_COLON', '', 3),
                        (52, 16, 'separatorSeveralDaysList', 'TXT_CALENDAR_SEPARATOR_SEVERAL_DAYS', '2', 'TXT_CALENDAR_SEPARATOR_SEVERAL_DAYS_INFO', 5, 'TXT_CALENDAR_SEPARATOR_SPACE,TXT_CALENDAR_SEPARATOR_HYPHEN,TXT_CALENDAR_SEPARATOR_TO,TXT_CALENDAR_SEPARATOR_BREAK', '', 4),
                        (53, 16, 'showClockList', 'TXT_CALENDAR_SHOW_CLOCK', '1', 'TXT_CALENDAR_SEPARATOR_SHOW_CLOCK_INFO', 3, 'TXT_CALENDAR_ACTIVATE,TXT_CALENDAR_DEACTIVATE', '', 5),
                        (54, 16, 'showStartDateList', 'TXT_CALENDAR_SHOW_START_DATE', '1', 'TXT_CALENDAR_SHOW_START_DATE_INFO', 3, 'TXT_CALENDAR_ACTIVATE,TXT_CALENDAR_DEACTIVATE', '', 6),
                        (55, 16, 'showEndDateList', 'TXT_CALENDAR_SHOW_END_DATE', '2', 'TXT_CALENDAR_SHOW_END_DATE_INFO', 3, 'TXT_CALENDAR_ACTIVATE,TXT_CALENDAR_DEACTIVATE', '', 7),
                        (56, 16, 'showStartTimeList', 'TXT_CALENDAR_SHOW_START_TIME', '2', 'TXT_CALENDAR_SHOW_START_TIME_INFO', 3, 'TXT_CALENDAR_ACTIVATE,TXT_CALENDAR_DEACTIVATE', '', 9),
                        (57, 16, 'showEndTimeList', 'TXT_CALENDAR_SHOW_END_TIME', '2', 'TXT_CALENDAR_SHOW_END_TIME_INFO', 3, 'TXT_CALENDAR_ACTIVATE,TXT_CALENDAR_DEACTIVATE', '', 10),
                        (58, 5, 'maxSeriesEndsYear', 'TXT_CALENDAR_MAX_SERIES_ENDS_YEAR', '0', 'TXT_CALENDAR_MAX_SERIES_ENDS_YEAR_INFO', 5, 'TXT_CALENDAR_MAX_SERIES_ENDS_YEAR_1_YEARS,TXT_CALENDAR_MAX_SERIES_ENDS_YEAR_2_YEARS,TXT_CALENDAR_MAX_SERIES_ENDS_YEAR_3_YEARS,TXT_CALENDAR_MAX_SERIES_ENDS_YEAR_4_YEARS,TXT_CALENDAR_MAX_SERIES_ENDS_YEAR_5_YEARS', '', 9),
                        (59, 5, 'showEventsOnlyInActiveLanguage', 'TXT_CALENDAR_SHOW_EVENTS_ONLY_IN_ACTIVE_LANGUAGE', '1', '', 3, 'TXT_CALENDAR_ACTIVATE,TXT_CALENDAR_DEACTIVATE', '', 10),
                        (60, 16, 'listViewPreview', 'TXT_CALENDAR_SHOW_PREVIEW', '0', '', 7, '', 'listPreview', 10),
                        (61, 17, 'detailViewPreview', 'TXT_CALENDAR_SHOW_PREVIEW', '0', '', 7, '', 'detailPreview', 10),
                        (20,19,'placeData','TXT_CALENDAR_PLACE_DATA','1','TXT_CALENDAR_PLACE_DATA_STATUS_INFO',3,'TXT_CALENDAR_PLACE_DATA_DEFAULT,TXT_CALENDAR_PLACE_DATA_FROM_MEDIADIR,TXT_CALENDAR_PLACE_DATA_FROM_BOTH','',7),
                        (62,19,'placeDataForm','','0','',5,'','getPlaceDataDorpdown',8),
                        (63,19,'placeDataHost','TXT_CALENDAR_PLACE_DATA_HOST','1','TXT_CALENDAR_PLACE_DATA_STATUS_INFO',3,'TXT_CALENDAR_PLACE_DATA_DEFAULT,TXT_CALENDAR_PLACE_DATA_FROM_MEDIADIR,TXT_CALENDAR_PLACE_DATA_FROM_BOTH','',9),
                        (64,19,'placeDataHostForm','','0','',5,'','getPlaceDataDorpdown',10)
                ");
            }

            if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                return 'timeout';
            }

            if (\Cx\Lib\UpdateUtil::table_empty(CALENDAR_NEW_SETTINGS_SECTION_TABLE)) {
                \Cx\Lib\UpdateUtil::sql("
                    INSERT IGNORE INTO `" . CALENDAR_NEW_SETTINGS_SECTION_TABLE . "` (`id`, `parent`, `order`, `name`, `title`)
                        VALUES
                            (5, 1, 0, 'global', 'TXT_CALENDAR_GLOBAL'),
                            (6, 1, 1, 'headlines', 'TXT_CALENDAR_HEADLINES'),
                            (1, 0, 0, 'global', ''),
                            (2, 0, 1, 'form', ''),
                            (3, 0, 2, 'mails', ''),
                            (4, 0, 3, 'hosts', ''),
                            (7, 4, 0, 'publication', 'TXT_CALENDAR_PUBLICATION'),
                            (8, 1, 2, 'categories', 'TXT_CALENDAR_CATEGORIES'),
                            (9, 0, 4, 'payment', ''),
                            (10, 9, 0, 'payment', 'TXT_CALENDAR_PAYMENT'),
                            (11, 9, 1, 'paymentBill', 'TXT_CALENDAR_PAYMENT_BILL'),
                            (12, 9, 2, 'paymentYellowpay', 'TXT_CALENDAR_PAYMENT_YELLOWPAY'),
                            (13, 9, 1, 'paymentBank', 'TXT_CALENDAR_PAYMENT_BANK'),
                            (14, 0, 5, 'dateDisplay', 'TXT_CALENDAR_DATE_DISPLAY'),
                            (15, 14, 0, 'dateGlobal', 'TXT_CALENDAR_GLOBAL'),
                            (16, 14, 1, 'dateDisplayList', 'TXT_CALENDAR_DATE_DISPLAY_LIST'),
                            (17, 14, 2, 'dateDisplayDetail', 'TXT_CALENDAR_DATE_DISPLAY_DETAIL'),
                            (18, 1, 3, 'frontend_submission', 'TXT_CALENDAR_FRONTEND_SUBMISSION'),
                            (19, 1, 4, 'location_host', 'TXT_CALENDAR_EVENT_LOCATION')
                ");
            }
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
        return true;
    }

    /**
     * insert demo data to mail tables
     * @return bool|string
     */
    protected function insertMailDemoData()
    {
        try {
            if (\Cx\Lib\UpdateUtil::table_empty(CALENDAR_NEW_MAIL_TABLE)) {
                \Cx\Lib\UpdateUtil::sql("
                    INSERT IGNORE INTO `" . CALENDAR_NEW_MAIL_TABLE . "` (`id`, `title`, `content_text`, `content_html`, `recipients`, `lang_id`, `action_id`, `is_default`, `status`)
                        VALUES
                            (1, '[[URL]] - Einladung zu [[TITLE]]', 'Hallo [[FIRSTNAME]] [[LASTNAME]] \r\n\r\nSie wurden auf [[URL]] zum Event \"[[TITLE]]\" eingeladen.\r\nDetails: [[LINK_EVENT]]\r\n\r\nFolgen Sie dem unten stehenden Link um sich f&uuml;r diesen Event an- oder abzumelden.\r\nHinweis: Sollte der Link nicht funktionieren, kopieren Sie die komplette Adresse ohne Zeilenumbr&uuml;che in die Adresszeile Ihres Browsers und dr&uuml;cken Sie anschliessend \"Enter\".\r\n\r\n[[LINK_REGISTRATION]]\r\n\r\n\r\n--\r\nDiese Nachricht wurde automatisch generiert\r\n[[DATE]]', 'Hallo [[FIRSTNAME]] [[LASTNAME]]<br />\r\n<br />\r\nSie wurden auf <a href=\"http://[[URL]]\" title=\"[[URL]]\">[[URL]]</a> zum Event <a href=\"[[LINK_EVENT]]\" title=\"Event Details\">&quot;[[TITLE]]&quot;</a> eingeladen. <br />\r\nKlicken Sie <a href=\"[[LINK_REGISTRATION]]\" title=\"Anmeldung\">hier</a>, um sich an diesem Event an- oder abzumelden.<br />\r\n<br />\r\n<br />\r\n--<br />\r\n<em>Diese Nachricht wurde automatisch generiert</em><br />\r\n<em>[[DATE]]</em>', '', 1, 1, 1, 1),
                            (15, '[[URL]] - Neue [[REGISTRATION_TYPE]] f&uuml;r [[TITLE]]', 'Hallo\r\n\r\nAuf [[URL]] wurde eine neue [[REGISTRATION_TYPE]] f&uuml;r den Termin \"[[TITLE]]\" eingetragen.\r\n\r\nInformationen zur [[REGISTRATION_TYPE]]\r\n[[REGISTRATION_DATA]]\r\n\r\n-- \r\nDiese Nachricht wurde automatisch generiert [[DATE]]', 'Hallo<br />\r\n<br />\r\nAuf [[URL]] wurde eine neue [[REGISTRATION_TYPE]] f&uuml;r den Termin &quot;[[TITLE]]&quot; eingetragen.<br />\r\n<br />\r\n<h2>Informationen zur [[REGISTRATION_TYPE]]</h2>\r\n[[REGISTRATION_DATA]] <br />\r\n<br />\r\n-- <br />\r\nDiese Nachricht wurde automatisch generiert [[DATE]]', '', 1, 3, 1, 1),
                            (14, '[[URL]] - Erfolgreiche [[REGISTRATION_TYPE]]', 'Hallo [[FIRSTNAME]] [[LASTNAME]]\r\n\r\nIhre [[REGISTRATION_TYPE]] zum Event \"[[TITLE]]\" vom [[START_DATE]] wurde erfolgreich in unserem System eingetragen.\r\n\r\n\r\n--\r\nDiese Nachricht wurde automatisch generiert\r\n[[DATE]]', 'Hallo [[FIRSTNAME]] [[LASTNAME]]<br />\r\n<br />\r\nIhre [[REGISTRATION_TYPE]] zum Event <a title=\"[[TITLE]]\" href=\"[[LINK_EVENT]]\">[[TITLE]]</a> vom [[START_DATE]] wurde erfolgreich in unserem System eingetragen.<br />\r\n<br />\r\n--<br />\r\n<em>Diese Nachricht wurde automatisch generiert<br />\r\n[[DATE]]</em>', '', 1, 2, 1, 1),
                            (16, '[[URL]] - Neuer Termin: [[TITLE]]', 'Hallo [[FIRSTNAME]] [[LASTNAME]] \r\n\r\nUnter [[URL]] finden Sie den neuen Event \"[[TITLE]]\".\r\nDetails: [[LINK_EVENT]]\r\n\r\n\r\n--\r\nDiese Nachricht wurde automatisch generiert\r\n[[DATE]]', 'Hallo [[FIRSTNAME]] [[LASTNAME]]<br />\r\n<br />\r\nUnter <a title=\"[[URL]]\" href=\"http://[[URL]]\">[[URL]]</a> finden Sie den neuen Event <a title=\"Event Details\" href=\"[[LINK_EVENT]]\">&quot;[[TITLE]]&quot;</a>. <br />\r\n<br />\r\n<br />\r\n--<br />\r\n<em>Diese Nachricht wurde automatisch generiert</em><br />\r\n<em>[[DATE]]</em>', '', 1, 4, 1, 1)
                ");
            }

            if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                return 'timeout';
            }

            if (\Cx\Lib\UpdateUtil::table_empty(CALENDAR_NEW_MAIL_ACTION_TABLE)) {
                \Cx\Lib\UpdateUtil::sql("
                    INSERT IGNORE INTO `" . CALENDAR_NEW_MAIL_ACTION_TABLE . "`
                        VALUES
                            (1, 'invitationTemplate', 'empty', 0),
                            (2, 'confirmationRegistration', 'author', 0),
                            (3, 'notificationRegistration', 'empty', 0),
                            (4, 'notificationNewEntryFE', 'admin', 0)
                ");
            }
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
        return true;
    }

    /**
     * migrate old categories to new category table
     */
    protected function migrateCategories()
    {
        try {
            $where = '';
            if (!empty($_SESSION['contrexx_update']['calendar']['categories'])) {
                $where = ' WHERE `id` > ' . $_SESSION['contrexx_update']['calendar']['categories'];
            }
            $result = \Cx\Lib\UpdateUtil::sql("SELECT `id`, `name`, `status`, `pos`, `lang` FROM `" . CALENDAR_OLD_CATEGORY_TABLE . "`" . $where . " ORDER BY `id`");
            $languages = \FWLanguage::getLanguageArray();
            while (!$result->EOF) {
                \Cx\Lib\UpdateUtil::sql(
                    "INSERT IGNORE INTO `" . CALENDAR_NEW_CATEGORY_TABLE . "` (`id`, `pos`,`status`)
                        VALUES (
                            " . intval($result->fields['id']) . ",
                            " . intval($result->fields['pos']) . ",
                            " . intval($result->fields['status']) . "
                        )"
                );
                foreach ($languages as $id => $languageData) {
                    \Cx\Lib\UpdateUtil::sql(
                        "INSERT IGNORE INTO `" . CALENDAR_NEW_CATEGORY_NAME_TABLE . "` (`cat_id`,`lang_id`,`name`)
                            VALUES (
                                " . intval($result->fields['id']) . ",
                                " . intval($id) . ",
                                '" . contrexx_raw2db($result->fields['name']) . "'
                            )"
                    );
                }

                $_SESSION['contrexx_update']['calendar']['categories'] = intval($result->fields['id']);

                if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                    return 'timeout';
                }

                $result->MoveNext();
            }
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
        return true;
    }

    /**
     * get the language ids of each category
     */
    protected function getCategoryLanguages()
    {
        $languageIds = array();
        try {
            $result = \Cx\Lib\UpdateUtil::sql("SELECT `id`, `lang` FROM `" . CALENDAR_OLD_CATEGORY_TABLE . "`");
            while (!$result->EOF) {
                $languageIds[$result->fields['id']] = $result->fields['lang'];
                $result->MoveNext();
            }
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
        return $languageIds;
    }

    /**
     * migrate old events to new events table
     */
    protected function migrateEvents()
    {
        $eventId = null;
        $mailTemplateId = null;
        try {
            // migration old events to new event table
            // migrate entries
            $where = '';
            if (!empty($_SESSION['contrexx_update']['calendar']['events'])) {
                $where = ' WHERE `id` > ' . $_SESSION['contrexx_update']['calendar']['events'];
            }
            $result = \Cx\Lib\UpdateUtil::sql("SELECT * FROM `" . CALENDAR_OLD_EVENT_TABLE . "`" . $where . " ORDER BY `id`");
            while (!$result->EOF) {
                $langId = null;
                $registrationFormId = null;
                $mailTemplateId = null;
                $eventId = null;

                $langId = $this->categoryLanguages[$result->fields['catid']];
                $name = $result->fields['name'];

                // added event name to mail title
                $mailTemplateId = $this->addMailTemplate($result->fields['mailTitle'] . ' (' . $name . ')', $result->fields['mailContent'], $langId);

                // insert event
                \Cx\Lib\UpdateUtil::sql("
                    INSERT IGNORE INTO `" . CALENDAR_NEW_EVENT_TABLE . "` (
                        `status`,
                        `catid`,
                        `startdate`,
                        `enddate`,
                        `priority`,
                        `access`,
                        `place`,
                        `link`,
                        `pic`,
                        `attach`,
                        `place_street`,
                        `place_zip`,
                        `place_city`,
                        `place_link`,
                        `place_map`,
                        `org_name`,
                        `org_street`,
                        `org_zip`,
                        `org_city`,
                        `org_email`,
                        `org_link`,
                        `registration_num`,
                        `registration`,
                        `invited_groups`,
                        `registration_notification`,
                        `invited_mails`,
                        `invitation_email_template`,
                        `series_status`,
                        `series_type`,
                        `series_pattern_count`,
                        `series_pattern_weekday`,
                        `series_pattern_day`,
                        `series_pattern_week`,
                        `series_pattern_month`,
                        `series_pattern_type`,
                        `series_pattern_dourance_type`,
                        `series_pattern_end`,
                        `series_pattern_begin`,
                        `series_pattern_exceptions`,

                        `use_custom_date_display`,
                        `showStartDateList`,
                        `showEndDateList`,
                        `showStartTimeList`,
                        `showEndTimeList`,
                        `showTimeTypeList`,
                        `showStartDateDetail`,
                        `showEndDateDetail`,
                        `showStartTimeDetail`,
                        `showEndTimeDetail`,
                        `showTimeTypeDetail`,
                        `google`,
                        `price`,
                        `place_mediadir_id`,
                        `show_in`,
                        `invitation_sent`,
                        `ticket_sales`,
                        `num_seating`,
                        `confirmed`,
                        `author`,
                        `all_day`,
                        `place_id`,
                        `place_country`,
                        `registration_form`,
                        `email_template`
                    ) VALUES (
                        '" . contrexx_raw2db($result->fields['active']) . "',
                        '" . contrexx_raw2db($result->fields['catid']) . "',
                        '" . contrexx_raw2db($result->fields['startdate']) . "',
                        '" . contrexx_raw2db($result->fields['enddate']) . "',
                        '" . contrexx_raw2db($result->fields['priority']) . "',
                        '" . contrexx_raw2db($result->fields['access']) . "',
                        '" . contrexx_raw2db($result->fields['placeName']) . "',
                        '" . contrexx_raw2db($result->fields['link']) . "',
                        '" . contrexx_raw2db($result->fields['pic']) . "',
                        '" . contrexx_raw2db($result->fields['attachment']) . "',
                        '" . contrexx_raw2db($result->fields['placeStreet']) . "',
                        '" . contrexx_raw2db($result->fields['placeZip']) . "',
                        '" . contrexx_raw2db($result->fields['placeCity']) . "',
                        '" . contrexx_raw2db($result->fields['placeLink']) . "',
                        '" . contrexx_raw2db($result->fields['placeMap']) . "',
                        '" . contrexx_raw2db($result->fields['organizerName']) . "',
                        '" . contrexx_raw2db($result->fields['organizerStreet']) . "',
                        '" . contrexx_raw2db($result->fields['organizerZip']) . "',
                        '" . contrexx_raw2db($result->fields['organizerPlace']) . "',
                        '" . contrexx_raw2db($result->fields['organizerMail']) . "',
                        '" . contrexx_raw2db($result->fields['organizerLink']) . "',
                        '" . contrexx_raw2db($result->fields['num']) . "',
                        '" . contrexx_raw2db($result->fields['registration']) . "',
                        '" . contrexx_raw2db($result->fields['groups']) . "',
                        " . ($result->fields['notification'] ? "'" . contrexx_raw2db($result->fields['notification_address']) . "'" : "''") . ",
                        '',
                        " . $mailTemplateId . ",
                        '" . contrexx_raw2db($result->fields['series_status']) . "',
                        '" . contrexx_raw2db($result->fields['series_type']) . "',
                        '" . contrexx_raw2db($result->fields['series_pattern_count']) . "',
                        '" . contrexx_raw2db($result->fields['series_pattern_weekday']) . "',
                        '" . contrexx_raw2db($result->fields['series_pattern_day']) . "',
                        '" . contrexx_raw2db($result->fields['series_pattern_week']) . "',
                        '" . contrexx_raw2db($result->fields['series_pattern_month']) . "',
                        '" . contrexx_raw2db($result->fields['series_pattern_type']) . "',
                        '" . contrexx_raw2db($result->fields['series_pattern_dourance_type']) . "',
                        '" . contrexx_raw2db($result->fields['series_pattern_end']) . "',
                        '" . contrexx_raw2db($result->fields['series_pattern_begin']) . "',
                        '" . contrexx_raw2db($result->fields['series_pattern_exceptions']) . "',
                        0,
                        1,
                        0,
                        0,
                        0,
                        0,
                        1,
                        1,
                        1,
                        1,
                        1,
                        0,
                        0,
                        0,
                        " . $langId . ",
                        1,
                        0,
                        '',
                        1,
                        " . $_SESSION['contrexx_update']['user_id'] . ",
                        0,
                        0,
                        '',
                        0,
                        0
                    )
                ");

                $eventId = $this->db->Insert_ID();

                // add language fields for event
                \Cx\Lib\UpdateUtil::sql("
                    INSERT IGNORE INTO `" . CALENDAR_NEW_EVENT_FIELD_TABLE . "` (`event_id`, `lang_id`, `title`, `description`, `redirect`)
                    VALUES (
                        " . $eventId . ",
                        " . $langId . ",
                        '" . contrexx_raw2db($name) . "',
                        '" . contrexx_raw2db($result->fields['comment']) . "',
                        ''
                    )
                ");

                // add registration form fields
                $resultFormFields = \Cx\Lib\UpdateUtil::sql("
                    SELECT `id` FROM `" . CALENDAR_OLD_FORM_FIELD_TABLE . "`
                        WHERE `note_id` = " . $result->fields['id'] . "
                ");
                if ($resultFormFields->RecordCount() > 0) {
                    // add registration form
                    $registrationFormId = $this->addRegistrationFormForEvent($name);
                    $formFieldsMap = $this->addRegistrationFormFields($registrationFormId, $result->fields['id'], $langId);
                    \Cx\Lib\UpdateUtil::sql("UPDATE `" . CALENDAR_NEW_EVENT_TABLE . "` SET `registration_form` = " . $registrationFormId . " WHERE `id` = " . $eventId);
                    // add registration data
                    $this->addRegistrationData($result->fields['id'], $eventId, $langId, $formFieldsMap);
                }

                $_SESSION['contrexx_update']['calendar']['events'] = intval($result->fields['id']);

                if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                    return 'timeout';
                }

                // take next event
                $result->MoveNext();
            }
        } catch (\Cx\Lib\UpdateException $e) {
            // remove already inserted data from failed event
            if ($eventId) {
                \Cx\Lib\UpdateUtil::sql("DELETE FROM `" . CALENDAR_NEW_EVENT_TABLE . "` WHERE `id` = " . $eventId);
                \Cx\Lib\UpdateUtil::sql("DELETE FROM `" . CALENDAR_NEW_EVENT_FIELD_TABLE . "` WHERE `event_id` = " . $eventId);
            }
            if ($mailTemplateId) {
                \Cx\Lib\UpdateUtil::sql("DELETE FROM `" . CALENDAR_NEW_MAIL_TABLE . "` WHERE `id` = " . $mailTemplateId);
            }
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
        return true;
    }

    /**
     * insert registration form
     * @param string $name the name of the event
     * @return bool|void
     */
    protected function addRegistrationFormForEvent($name)
    {
        \Cx\Lib\UpdateUtil::sql("
                INSERT IGNORE INTO `" . CALENDAR_NEW_REGISTRATION_FORM_TABLE . "` (
                    `status`,
                    `order`,
                    `title`
                ) VALUES (
                    1,
                    99,
                    '" . contrexx_raw2db($name) . "'
                )
            ");
        return $this->db->Insert_ID();
    }

    /**
     * @param string $title the subject for the mail template
     * @param string $content the content for the mail template
     * @param int $langId the language id
     * @return bool|void
     */
    protected function addMailTemplate($title, $content, $langId)
    {
        // replace old placeholders with new placeholders
        $title = str_replace('[[REG_LINK]]', '[[LINK_REGISTRATION]]', $title);
        $content = str_replace('[[REG_LINK]]', '[[LINK_REGISTRATION]]', $content);
        \Cx\Lib\UpdateUtil::sql("
                INSERT IGNORE INTO `" . CALENDAR_NEW_MAIL_TABLE . "` (
                    `title`,
                    `content_text`,
                    `content_html`,
                    `recipients`,
                    `lang_id`,
                    `action_id`,
                    `is_default`,
                    `status`
                ) VALUES (
                    '" . contrexx_raw2db($title) . "',
                    '" . contrexx_raw2db($content) . "',
                    '" . contrexx_raw2db($content) . "',
                    '', " . $langId . ", 1, 0, 1)
            ");
        return $this->db->Insert_ID();
    }

    /**
     * @param int $registrationFormId the registration form id
     * @param int $eventId the event id
     * @param int $langId the language id
     * @return array|bool|void
     */
    protected function addRegistrationFormFields($registrationFormId, $eventId, $langId)
    {
        $formFieldIdMap = array();
        $resultFormFields = \Cx\Lib\UpdateUtil::sql("
                SELECT `id`, `name`, `type`, `required`, `order`, `key` FROM `" . CALENDAR_OLD_FORM_FIELD_TABLE . "`
                    WHERE `note_id` = " . $eventId . "
            ");
        while (!$resultFormFields->EOF) {
            $default = '';

            if ($resultFormFields->fields['key'] == 13) {
                // person number field
                // this value should be able to set in frontend
                $resultMaxSeatingNumber = \Cx\Lib\UpdateUtil::sql("
                    SELECT MAX(CONVERT(`data`, UNSIGNED)) AS `maxSeatingNumber`
                        FROM `" . CALENDAR_OLD_FORM_DATA_TABLE . "`
                        WHERE `field_id` = " . $resultFormFields->fields['id']
                );
                $resultFormFields->fields['type'] = 'seating';
                $default = implode(',',
                    range(1, $resultMaxSeatingNumber->fields['maxSeatingNumber'] + 1)
                );
            }

            if ($resultFormFields->fields['type'] == 3) { // checkbox
                $resultFormFields->fields['type'] = 'checkbox';
            }
            \Cx\Lib\UpdateUtil::sql("
                    INSERT IGNORE INTO `" . CALENDAR_NEW_REGISTRATION_FORM_FIELD_TABLE . "` (`form`, `type`, `required`, `order`, `affiliation`)
                    VALUES (
                        " . $registrationFormId . ",
                        '" . contrexx_raw2db($resultFormFields->fields['type']) . "',
                        '" . contrexx_raw2db($resultFormFields->fields['required']) . "',
                        '" . contrexx_raw2db($resultFormFields->fields['order']) . "',
                        '" . contrexx_raw2db($resultFormFields->fields['key']) . "'
                    )
                ");

            $formFieldId = $this->db->Insert_ID();
            $formFieldIdMap[$resultFormFields->fields['id']] = $formFieldId;

            \Cx\Lib\UpdateUtil::sql("
                    INSERT IGNORE INTO `" . CALENDAR_NEW_REGISTRATION_FORM_FIELD_NAME_TABLE . "` (`field_id`, `form_id`, `lang_id`, `name`, `default`)
                    VALUES (
                        " . $formFieldId . ",
                        " . $registrationFormId . ",
                        " . $langId . ",
                        '" . contrexx_raw2db($resultFormFields->fields['name']) . "',
                        '" . contrexx_raw2db($default) . "'
                    )
                ");

            $resultFormFields->MoveNext();
        }
        return $formFieldIdMap;
    }

    /**
     * @param int $oldEventId
     * @param int $newEventId
     * @param int $langId
     * @param array $formFieldMap
     */
    protected function addRegistrationData($oldEventId, $newEventId, $langId, $formFieldMap)
    {
        $resultRegistrations = \Cx\Lib\UpdateUtil::sql("
                SELECT `id`, `note_date`, `time`, `host`, `ip_address`, `type` FROM `" . CALENDAR_OLD_REGISTRATIONS_TABLE . "`
                    WHERE `note_id` = " . $oldEventId . "
            ");
        while (!$resultRegistrations->EOF) {
            $key = $this->generateRandomKey();
            \Cx\Lib\UpdateUtil::sql("
                    INSERT IGNORE INTO `" . CALENDAR_NEW_REGISTRATION_TABLE . "`
                        (`event_id`, `date`, `host_name`, `ip_address`, `type`, `key`, `user_id`, `lang_id`, `export`, `payment_method`, `paid`)
                    VALUES (
                        " . $newEventId . ",
                        '" . contrexx_raw2db($resultRegistrations->fields['time']) . "',
                        '" . contrexx_raw2db($resultRegistrations->fields['host']) . "',
                        '" . contrexx_raw2db($resultRegistrations->fields['ip_address']) . "',
                        '" . contrexx_raw2db($resultRegistrations->fields['type']) . "',
                        '" . contrexx_raw2db($key) . "',
                        0,
                        " . $langId . ",
                        0,
                        0,
                        0
                    )
                ");
            $registrationId = $this->db->Insert_ID();

            $resultRegistrationData = \Cx\Lib\UpdateUtil::sql("
                    SELECT `field_id`, `data` FROM `" . CALENDAR_OLD_FORM_DATA_TABLE . "`
                    WHERE `reg_id` = " . $resultRegistrations->fields['id'] . "
                ");
            while (!$resultRegistrationData->EOF) {

                // if a seating number field exists, add this number to data
                $resultNewFieldType = \Cx\Lib\UpdateUtil::sql("
                    SELECT `type` FROM `" . CALENDAR_NEW_REGISTRATION_FORM_FIELD_TABLE . "`
                        WHERE `id` = " . $formFieldMap[$resultRegistrationData->fields['field_id']]
                );
                if ($resultNewFieldType->fields['type'] == 'seating') {
                    $resultRegistrationData->fields['data'] = intval($resultRegistrationData->fields['data']) + 1;
                }

                \Cx\Lib\UpdateUtil::sql("
                        INSERT IGNORE INTO `" . CALENDAR_NEW_REGISTRATION_FORM_FIELD_VALUE_TABLE . "` (
                            `reg_id`,
                            `field_id`,
                            `value`
                        ) VALUES (
                            '" . $registrationId . "',
                            " . $formFieldMap[$resultRegistrationData->fields['field_id']] . ",
                            '" . contrexx_raw2db($resultRegistrationData->fields['data']) . "'
                        )
                    ");
                $resultRegistrationData->MoveNext();
            }

            $resultRegistrations->MoveNext();
        }
    }

    /**
     * Generate a random key
     * @see \CalendarLibrary
     * @return string
     */
    protected function generateRandomKey()
    {
        $arrRandom = array();
        $arrChars = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');
        $arrNumerics = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9);

        for ($i = 0; $i <= rand(15, 40); $i++) {
            $charOrNum = rand(0, 1);
            if ($charOrNum == 1) {
                $posChar = rand(0, 25);
                $upOrLow = rand(0, 1);

                if ($upOrLow == 0) {
                    $arrRandom[$i] = strtoupper($arrChars[$posChar]);
                } else {
                    $arrRandom[$i] = strtolower($arrChars[$posChar]);
                }
            } else {
                $posNum = rand(0, 9);
                $arrRandom[$i] = $arrNumerics[$posNum];
            }
        }

        $key = join('',$arrRandom);

        return $key;
    }

    /**
     * drop old tables
     * @return bool|void
     */
    protected function dropOldTables()
    {
        try {
            \Cx\Lib\UpdateUtil::drop_table(CALENDAR_OLD_EVENT_TABLE);
            \Cx\Lib\UpdateUtil::drop_table(CALENDAR_OLD_CATEGORY_TABLE);
            \Cx\Lib\UpdateUtil::drop_table(CALENDAR_OLD_FORM_DATA_TABLE);
            \Cx\Lib\UpdateUtil::drop_table(CALENDAR_OLD_FORM_FIELD_TABLE);
            \Cx\Lib\UpdateUtil::drop_table(CALENDAR_OLD_REGISTRATIONS_TABLE);
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
        return true;
    }

    /**
     * Updates the content pages of the calendar module
     * @return bool
     */
    public function migrateContentPages()
    {
        $em = \Env::get('em');
        $pageRepo = $em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');

        // all cmd changes of all calendar pages
        // array key = old cmd, value = new cmd
        $cmdMigrations = array(
            'eventlist' => 'list',
            'event' => 'detail',
            'sign' => 'register',
        );

        foreach($cmdMigrations as $oldCmd => $newCmd) {
            $calendarPages = $pageRepo->findBy(
                array(
                    'module' => 'calendar',
                    'cmd' => $oldCmd,
                )
            );
            foreach ($calendarPages as $page) {
                $page->setCmd($newCmd);
                $em->persist($page);
                $em->flush($page);
            }
        }
        return true;
    }
}
