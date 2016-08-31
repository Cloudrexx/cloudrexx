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


function _crmUpdate() {
    global $objUpdate, $_CONFIG;
    try {
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_crm_contacts',
            array(
                'id'                     => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'customer_id'            => array('type' => 'VARCHAR(256)', 'notnull' => false, 'after' => 'id'),
                'customer_type'          => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'customer_id'),
                'customer_name'          => array('type' => 'VARCHAR(256)', 'notnull' => false, 'after' => 'customer_type'),
                'customer_website'       => array('type' => 'VARCHAR(256)', 'notnull' => false, 'after' => 'customer_name'),
                'customer_addedby'       => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'customer_website'),
                'customer_currency'      => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'customer_addedby'),
                'contact_familyname'     => array('type' => 'VARCHAR(256)', 'notnull' => false, 'after' => 'customer_currency'),
                'contact_role'           => array('type' => 'VARCHAR(256)', 'notnull' => false, 'after' => 'contact_familyname'),
                'contact_customer'       => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'contact_role'),
                'contact_language'       => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'contact_customer'),
                'gender'                 => array('type' => 'TINYINT(2)', 'after' => 'contact_language'),
                'notes'                  => array('type' => 'text','notnull' => false, 'after' => 'gender'),
                'industry_type'          => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'notes'),
                'contact_type'           => array('type' => 'TINYINT(2)', 'notnull' => false, 'after' => 'industry_type'),
                'user_account'           => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'contact_type'),
                'datasource'             => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'user_account'),
                'profile_picture'        => array('type' => 'VARCHAR(256)', 'after' => 'datasource'),
                'status'                 => array('type' => 'TINYINT(2)', 'notnull' => true, 'default' => '1', 'after' => 'profile_picture'),
                'added_date'             => array('type' => 'date', 'after' => 'status')
            ),
            array(
                'contact_customer'       => array('fields' => array('contact_customer')),
                'customer_id'            => array('fields' => array('customer_id')),
                'customer_name'          => array('fields' => array('customer_name')),
                'contact_familyname'     => array('fields' => array('contact_familyname')),
                'contact_role'           => array('fields' => array('contact_role')),
                'customer_id_2'          => array('fields' => array('customer_id','customer_name','contact_familyname','contact_role','notes'), 'type' => 'FULLTEXT')
            )
        );
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_crm_currency',
            array(
                'id'                     => array('type' => 'INT(10)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'name'                   => array('type' => 'VARCHAR(400)', 'after' => 'id'),
                'active'                 => array('type' => 'INT(1)', 'notnull' => true, 'default' => '1', 'after' => 'name'),
                'pos'                    => array('type' => 'INT(5)', 'notnull' => true, 'default' => '0', 'after' => 'active'),
                'hourly_rate'            => array('type' => 'text', 'after' => 'pos'),
                'default_currency'       => array('type' => 'TINYINT(1)', 'after' => 'hourly_rate')
            ),
            array(
                'name'                   => array('fields' => array('name' => 333)),
                'name_2'                 => array('fields' => array('name'), 'type' => 'FULLTEXT')
            )
        );
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_crm_customer_comment',
            array(
                'id'                 => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'customer_id'        => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'id'),
                'notes_type_id'      => array('type' => 'INT(1)', 'after' => 'customer_id'),
                'user_id'            => array('type' => 'INT(11)', 'after' => 'notes_type_id'),
                'date'               => array('type' => 'date', 'after' => 'user_id'),
                'comment'            => array('type' => 'text', 'notnull' => false, 'after' => 'date'),
                'added_date'         => array('type' => 'datetime', 'notnull' => false, 'after' => 'comment'),
                'updated_by'         => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'added_date'),
                'updated_on'         => array('type' => 'datetime', 'notnull' => false, 'after' => 'updated_by')
            ),
            array(
                'customer_id'        => array('fields' => array('customer_id')),
                'comment'            => array('fields' => array('comment'), 'type' => 'FULLTEXT')
            )
        );
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_crm_customer_contact_address',
            array(
                'id'                 => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'address'            => array('type' => 'VARCHAR(256)', 'after' => 'id'),
                'city'               => array('type' => 'VARCHAR(256)', 'after' => 'address'),
                'state'              => array('type' => 'VARCHAR(256)', 'after' => 'city'),
                'zip'                => array('type' => 'VARCHAR(256)', 'after' => 'state'),
                'country'            => array('type' => 'VARCHAR(256)', 'after' => 'zip'),
                'Address_Type'       => array('type' => 'TINYINT(4)', 'after' => 'country'),
                'is_primary'         => array('type' => 'ENUM(\'0\',\'1\')', 'after' => 'Address_Type'),
                'contact_id'         => array('type' => 'INT(11)', 'after' => 'is_primary')
            ),
            array(
                'contact_id'         => array('fields' => array('contact_id')),
                'address'            => array('fields' => array('address')),
                'city'               => array('fields' => array('city')),
                'state'              => array('fields' => array('state')),
                'zip'                => array('fields' => array('zip')),
                'country'            => array('fields' => array('country')),
                'address_2'          => array('fields' => array('address','city','state','zip','country'), 'type' => 'FULLTEXT')
            )
        );
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_crm_customer_contact_emails',
            array(
                'id'             => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'email'          => array('type' => 'VARCHAR(256)', 'after' => 'id'),
                'email_type'     => array('type' => 'TINYINT(4)', 'after' => 'email'),
                'is_primary'     => array('type' => 'ENUM(\'0\',\'1\')', 'notnull' => false, 'default' => '0', 'after' => 'email_type'),
                'contact_id'     => array('type' => 'INT(11)', 'after' => 'is_primary')
            ),
            array(
                'contact_id'     => array('fields' => array('contact_id')),
                'email'          => array('fields' => array('email')),
                'email_2'        => array('fields' => array('email'), 'type' => 'FULLTEXT')
            )
        );
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_crm_customer_contact_phone',
            array(
                'id'             => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'phone'          => array('type' => 'VARCHAR(256)', 'after' => 'id'),
                'phone_type'     => array('type' => 'TINYINT(4)', 'after' => 'phone'),
                'is_primary'     => array('type' => 'ENUM(\'0\',\'1\')', 'notnull' => false, 'default' => '0', 'after' => 'phone_type'),
                'contact_id'     => array('type' => 'INT(11)', 'after' => 'is_primary')
            ),
            array(
                'contact_id'     => array('fields' => array('contact_id')),
                'phone'          => array('fields' => array('phone')),
                'phone_2'        => array('fields' => array('phone'), 'type' => 'FULLTEXT')
            )
        );
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_crm_customer_contact_social_network',
            array(
                'id'             => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'url'            => array('type' => 'VARCHAR(256)', 'after' => 'id'),
                'url_profile'    => array('type' => 'TINYINT(4)', 'after' => 'url'),
                'is_primary'     => array('type' => 'ENUM(\'0\',\'1\')', 'notnull' => false, 'default' => '0', 'after' => 'url_profile'),
                'contact_id'     => array('type' => 'INT(11)', 'after' => 'is_primary')
            ),
            array(
                'contact_id'     => array('fields' => array('contact_id')),
                'url'            => array('fields' => array('url')),
                'url_2'          => array('fields' => array('url'), 'type' => 'FULLTEXT')
            )
        );
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_crm_customer_contact_websites',
            array(
                'id'             => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'url'            => array('type' => 'VARCHAR(256)', 'after' => 'id'),
                'url_type'       => array('type' => 'TINYINT(4)', 'after' => 'url'),
                'url_profile'    => array('type' => 'TINYINT(4)', 'after' => 'url_type'),
                'is_primary'     => array('type' => 'ENUM(\'0\',\'1\')', 'notnull' => false, 'default' => '0', 'after' => 'url_profile'),
                'contact_id'     => array('type' => 'INT(11)', 'after' => 'is_primary')
            ),
            array(
                'contact_id'     => array('fields' => array('contact_id')),
                'url'            => array('fields' => array('url')),
                'url_2'          => array('fields' => array('url'), 'type' => 'FULLTEXT')
            )
        );
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_crm_customer_types',
            array(
                'id'             => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'label'          => array('type' => 'VARCHAR(250)', 'after' => 'id'),
                'hourly_rate'    => array('type' => 'VARCHAR(256)', 'after' => 'label'),
                'active'         => array('type' => 'INT(1)', 'after' => 'hourly_rate'),
                'pos'            => array('type' => 'INT(10)', 'notnull' => true, 'default' => '0', 'after' => 'active'),
                'default'        => array('type' => 'TINYINT(2)', 'notnull' => true, 'default' => '0', 'after' => 'pos')
            ),
            array(
                'label'          => array('fields' => array('label')),
                'label_2'        => array('fields' => array('label'), 'type' => 'FULLTEXT')
            )
        );
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_crm_industry_type_local',
            array(
                'entry_id'       => array('type' => 'INT(11)'),
                'lang_id'        => array('type' => 'INT(11)', 'after' => 'entry_id'),
                'value'          => array('type' => 'VARCHAR(256)', 'after' => 'lang_id')
            ),
            array(
                'entry_id'       => array('fields' => array('entry_id')),
                'value'          => array('fields' => array('value')),
                'value_2'        => array('fields' => array('value'), 'type' => 'FULLTEXT')
            )
        );
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_crm_membership_local',
            array(
                'entry_id'       => array('type' => 'INT(11)'),
                'lang_id'        => array('type' => 'INT(11)', 'after' => 'entry_id'),
                'value'          => array('type' => 'VARCHAR(256)', 'after' => 'lang_id')
            ),
            array(
                'entry_id'       => array('fields' => array('entry_id')),
                'value'          => array('fields' => array('value')),
                'value_2'        => array('fields' => array('value'), 'type' => 'FULLTEXT')
            )
        );
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_crm_notes',
            array(
                'id'                 => array('type' => 'INT(1)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'name'               => array('type' => 'VARCHAR(255)', 'after' => 'id'),
                'status'             => array('type' => 'TINYINT(1)', 'after' => 'name'),
                'icon'               => array('type' => 'VARCHAR(255)', 'after' => 'status'),
                'pos'                => array('type' => 'INT(1)', 'after' => 'icon'),
                'system_defined'     => array('type' => 'TINYINT(2)', 'notnull' => true, 'default' => '0', 'after' => 'pos')
            ),
            array(
                'name'               => array('fields' => array('name')),
                'name_2'             => array('fields' => array('name'), 'type' => 'FULLTEXT')
            )
        );
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_crm_task_types',
            array(
                'id'                 => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'name'               => array('type' => 'VARCHAR(256)', 'after' => 'id'),
                'status'             => array('type' => 'TINYINT(1)', 'after' => 'name'),
                'sorting'            => array('type' => 'INT(11)', 'after' => 'status'),
                'description'        => array('type' => 'text', 'after' => 'sorting'),
                'icon'               => array('type' => 'VARCHAR(255)', 'after' => 'description'),
                'system_defined'     => array('type' => 'TINYINT(4)', 'after' => 'icon')
            ),
            array(
                'name'               => array('fields' => array('name')),
                'name_2'             => array('fields' => array('name'), 'type' => 'FULLTEXT')
            )
        );
    } catch (\Cx\Lib\UpdateException $e) {
        // we COULD do something else here..
        DBG::trace();
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.2.0')) {
        try {
            $result = \Cx\Lib\UpdateUtil::sql('SELECT `id` FROM `'.DBPREFIX.'core_text` WHERE `section` = \'crm\'');
            if ($result && $result->RecordCount() > 0) {
                // emails have been already migrated, stop
                return true;
            }

            // migrate mail templates
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(12, 1, \'crm\', \'core_mail_template_bcc\', \'\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(12, 1, \'crm\', \'core_mail_template_cc\', \'\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(12, 1, \'crm\', \'core_mail_template_from\', \'info@example.com\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(12, 1, \'crm\', \'core_mail_template_message\', \'Guten Tag,\r\n\r\nNachfolgend erhalten Sie Ihre persönlichen Zugangsdaten zur Website http://www.example.com/\r\n\r\nBenutzername: [CRM_CONTACT_USERNAME]\r\nKennwort: [CRM_CONTACT_PASSWORD]\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(12, 1, \'crm\', \'core_mail_template_message_html\', \'<div>Guten Tag,<br />\r\n<br />\r\nNachfolgend erhalten Sie Ihre pers&ouml;nlichen Zugangsdaten zur Website <a href="http://www.example.com/">http://www.example.com/</a><br />\r\n<br />\r\nBenutzername: [CRM_CONTACT_USERNAME]<br />\r\nKennwort: [CRM_CONTACT_PASSWORD]</div>\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(12, 1, \'crm\', \'core_mail_template_name\', \'Benachrichtigung über Benutzerkonto\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(12, 1, \'crm\', \'core_mail_template_reply\', \'info@example.com\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(12, 1, \'crm\', \'core_mail_template_sender\', \'Ihr Firmenname\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(12, 1, \'crm\', \'core_mail_template_subject\', \'Ihr persönlischer Zugang\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(12, 1, \'crm\', \'core_mail_template_to\', \'[CRM_CONTACT_EMAIL]\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(13, 1, \'crm\', \'core_mail_template_bcc\', \'\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(13, 1, \'crm\', \'core_mail_template_cc\', \'\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(13, 1, \'crm\', \'core_mail_template_from\', \'info@example.com\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(13, 1, \'crm\', \'core_mail_template_message\', \'Der Mitarbeiter [CRM_TASK_CREATED_USER] hat eine neue Aufgabe erstellt und Ihnen zugewiesen: [CRM_TASK_URL]\r\n\r\nBeschreibung: [CRM_TASK_DESCRIPTION_TEXT_VERSION]\r\n\r\nFällig am: [CRM_TASK_DUE_DATE]\r\n\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(13, 1, \'crm\', \'core_mail_template_message_html\', \'<div style="padding:0px; margin:0px; font-family:Tahoma, sans-serif; font-size:14px; width:620px; color: #333;">\r\n<div style="padding: 0px 20px; border:1px solid #e0e0e0; margin-bottom: 10px; width:618px;">\r\n<h1 style="background-color: #e0e0e0;color: #3d4a6b;font-size: 18px;font-weight: normal;padding: 15px 20px;margin-top: 0 !important;margin-bottom: 0 !important;margin-left: -20px !important;margin-right: -20px !important;-webkit-margin-before: 0 !important;-webkit-margin-after: 0 !important;-webkit-margin-start: -20px !important;-webkit-margin-end: -20px !important;">Neue Aufgabe wurde Ihnen zugewiesen</h1>\r\n\r\n<p style="margin-top: 20px;word-wrap: break-word !important;">Der Mitarbeiter [CRM_TASK_CREATED_USER] hat eine neue Aufgabe erstellt und Ihnen zugewiesen: [CRM_TASK_LINK]</p>\r\n\r\n<p style="margin-top: 20px;word-wrap: break-word !important;">Beschreibung: [CRM_TASK_DESCRIPTION_HTML_VERSION]<br />\r\nF&auml;llig am: [CRM_TASK_DUE_DATE]</p>\r\n</div>\r\n</div>\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(13, 1, \'crm\', \'core_mail_template_name\', \'Neue Aufgabe\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(13, 1, \'crm\', \'core_mail_template_reply\', \'info@example.com\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(13, 1, \'crm\', \'core_mail_template_sender\', \'Ihr Firmenname\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(13, 1, \'crm\', \'core_mail_template_subject\', \'Neue Aufgabe : [CRM_TASK_NAME]\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(13, 1, \'crm\', \'core_mail_template_to\', \'[CRM_ASSIGNED_USER_EMAIL]\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(14, 1, \'crm\', \'core_mail_template_bcc\', \'\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(14, 1, \'crm\', \'core_mail_template_cc\', \'\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(14, 1, \'crm\', \'core_mail_template_from\', \'info@example.com\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(14, 1, \'crm\', \'core_mail_template_message\', \'Im CRM wurde ein neuer Kontakt erfasst: [CRM_CONTACT_DETAILS_URL]\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(14, 1, \'crm\', \'core_mail_template_message_html\', \'<div style="padding:0px; margin:0px; font-family:Tahoma, sans-serif; font-size:14px; width:620px; color: #333;">\r\n<div style="padding: 0px 20px; border:1px solid #e0e0e0; margin-bottom: 10px; width:618px;">\r\n<h1 style="background-color: #e0e0e0;color: #3d4a6b;font-size: 18px;font-weight: normal;padding: 15px 20px;margin-top: 0 !important;margin-bottom: 0 !important;margin-left: -20px !important;margin-right: -20px !important;-webkit-margin-before: 0 !important;-webkit-margin-after: 0 !important;-webkit-margin-start: -20px !important;-webkit-margin-end: -20px !important;">Neuer Kontakt im CRM</h1>\r\n\r\n<p style="margin-top: 20px;word-wrap: break-word !important;">Neuer Kontakt: [CRM_CONTACT_DETAILS_LINK].</p>\r\n</div>\r\n</div>\r\n\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(14, 1, \'crm\', \'core_mail_template_name\', \'Benachrichtigung an Mitarbeiter über neue Kontakte\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(14, 1, \'crm\', \'core_mail_template_reply\', \'info@example.com\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(14, 1, \'crm\', \'core_mail_template_sender\', \'Ihr Firmenname\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(14, 1, \'crm\', \'core_mail_template_subject\', \'Neuer Kontakt erfasst\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(14, 1, \'crm\', \'core_mail_template_to\', \'[CRM_ASSIGNED_USER_EMAIL]\')');

            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_mail_template` (`key`, `section`, `text_id`, `html`, `protected`) VALUES(\'crm_user_account_created\', \'crm\', 12, 1, 1)');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_mail_template` (`key`, `section`, `text_id`, `html`, `protected`) VALUES(\'crm_notify_staff_on_contact_added\', \'crm\', 14, 1, 1)');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_mail_template` (`key`, `section`, `text_id`, `html`, `protected`) VALUES(\'crm_task_assigned\', \'crm\', 13, 1, 1)');

            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_setting` (`section`, `name`, `group`, `type`, `value`, `values`, `ord`) VALUES (\'crm\',\'numof_mailtemplate_per_page_backend\',\'config\',\'text\',\'25\',\'\',1001)');
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
    }
    return true;
}

function _crmInstall() {

    try {
        $result = \Cx\Lib\UpdateUtil::sql('SELECT `id` FROM `'.DBPREFIX.'core_text` WHERE `section` = \'crm\'');
        if (!$result || $result->RecordCount() == 0) {
            // add core mail template table because it is possible it doesn't yet exist
            \Cx\Lib\UpdateUtil::table(
                DBPREFIX.'core_mail_template',
                array(
                    'key'            => array('type' => 'tinytext'),
                    'section'        => array('type' => 'tinytext', 'after' => 'key'),
                    'text_id'        => array('type' => 'INT(10)', 'unsigned' => true, 'after' => 'section'),
                    'html'           => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'text_id'),
                    'protected'      => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'html')
                ),
                null,
                'MyISAM',
                'cx3upgrade'
            );
            Cx\Lib\UpdateUtil::sql("
                ALTER TABLE `".DBPREFIX."core_mail_template`
                ADD PRIMARY KEY (`key` (32), `section` (32))
            ");

            // migrate mail templates
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(12, 1, \'crm\', \'core_mail_template_bcc\', \'\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(12, 1, \'crm\', \'core_mail_template_cc\', \'\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(12, 1, \'crm\', \'core_mail_template_from\', \'info@example.com\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(12, 1, \'crm\', \'core_mail_template_message\', \'Guten Tag,\r\n\r\nNachfolgend erhalten Sie Ihre persönlichen Zugangsdaten zur Website http://www.example.com/\r\n\r\nBenutzername: [CRM_CONTACT_USERNAME]\r\nKennwort: [CRM_CONTACT_PASSWORD]\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(12, 1, \'crm\', \'core_mail_template_message_html\', \'<div>Guten Tag,<br />\r\n<br />\r\nNachfolgend erhalten Sie Ihre pers&ouml;nlichen Zugangsdaten zur Website <a href="http://www.example.com/">http://www.example.com/</a><br />\r\n<br />\r\nBenutzername: [CRM_CONTACT_USERNAME]<br />\r\nKennwort: [CRM_CONTACT_PASSWORD]</div>\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(12, 1, \'crm\', \'core_mail_template_name\', \'Benachrichtigung über Benutzerkonto\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(12, 1, \'crm\', \'core_mail_template_reply\', \'info@example.com\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(12, 1, \'crm\', \'core_mail_template_sender\', \'Ihr Firmenname\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(12, 1, \'crm\', \'core_mail_template_subject\', \'Ihr persönlischer Zugang\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(12, 1, \'crm\', \'core_mail_template_to\', \'[CRM_CONTACT_EMAIL]\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(13, 1, \'crm\', \'core_mail_template_bcc\', \'\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(13, 1, \'crm\', \'core_mail_template_cc\', \'\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(13, 1, \'crm\', \'core_mail_template_from\', \'info@example.com\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(13, 1, \'crm\', \'core_mail_template_message\', \'Der Mitarbeiter [CRM_TASK_CREATED_USER] hat eine neue Aufgabe erstellt und Ihnen zugewiesen: [CRM_TASK_URL]\r\n\r\nBeschreibung: [CRM_TASK_DESCRIPTION_TEXT_VERSION]\r\n\r\nFällig am: [CRM_TASK_DUE_DATE]\r\n\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(13, 1, \'crm\', \'core_mail_template_message_html\', \'<div style="padding:0px; margin:0px; font-family:Tahoma, sans-serif; font-size:14px; width:620px; color: #333;">\r\n<div style="padding: 0px 20px; border:1px solid #e0e0e0; margin-bottom: 10px; width:618px;">\r\n<h1 style="background-color: #e0e0e0;color: #3d4a6b;font-size: 18px;font-weight: normal;padding: 15px 20px;margin-top: 0 !important;margin-bottom: 0 !important;margin-left: -20px !important;margin-right: -20px !important;-webkit-margin-before: 0 !important;-webkit-margin-after: 0 !important;-webkit-margin-start: -20px !important;-webkit-margin-end: -20px !important;">Neue Aufgabe wurde Ihnen zugewiesen</h1>\r\n\r\n<p style="margin-top: 20px;word-wrap: break-word !important;">Der Mitarbeiter [CRM_TASK_CREATED_USER] hat eine neue Aufgabe erstellt und Ihnen zugewiesen: [CRM_TASK_LINK]</p>\r\n\r\n<p style="margin-top: 20px;word-wrap: break-word !important;">Beschreibung: [CRM_TASK_DESCRIPTION_HTML_VERSION]<br />\r\nF&auml;llig am: [CRM_TASK_DUE_DATE]</p>\r\n</div>\r\n</div>\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(13, 1, \'crm\', \'core_mail_template_name\', \'Neue Aufgabe\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(13, 1, \'crm\', \'core_mail_template_reply\', \'info@example.com\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(13, 1, \'crm\', \'core_mail_template_sender\', \'Ihr Firmenname\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(13, 1, \'crm\', \'core_mail_template_subject\', \'Neue Aufgabe : [CRM_TASK_NAME]\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(13, 1, \'crm\', \'core_mail_template_to\', \'[CRM_ASSIGNED_USER_EMAIL]\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(14, 1, \'crm\', \'core_mail_template_bcc\', \'\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(14, 1, \'crm\', \'core_mail_template_cc\', \'\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(14, 1, \'crm\', \'core_mail_template_from\', \'info@example.com\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(14, 1, \'crm\', \'core_mail_template_message\', \'Im CRM wurde ein neuer Kontakt erfasst: [CRM_CONTACT_DETAILS_URL]\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(14, 1, \'crm\', \'core_mail_template_message_html\', \'<div style="padding:0px; margin:0px; font-family:Tahoma, sans-serif; font-size:14px; width:620px; color: #333;">\r\n<div style="padding: 0px 20px; border:1px solid #e0e0e0; margin-bottom: 10px; width:618px;">\r\n<h1 style="background-color: #e0e0e0;color: #3d4a6b;font-size: 18px;font-weight: normal;padding: 15px 20px;margin-top: 0 !important;margin-bottom: 0 !important;margin-left: -20px !important;margin-right: -20px !important;-webkit-margin-before: 0 !important;-webkit-margin-after: 0 !important;-webkit-margin-start: -20px !important;-webkit-margin-end: -20px !important;">Neuer Kontakt im CRM</h1>\r\n\r\n<p style="margin-top: 20px;word-wrap: break-word !important;">Neuer Kontakt: [CRM_CONTACT_DETAILS_LINK].</p>\r\n</div>\r\n</div>\r\n\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(14, 1, \'crm\', \'core_mail_template_name\', \'Benachrichtigung an Mitarbeiter über neue Kontakte\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(14, 1, \'crm\', \'core_mail_template_reply\', \'info@example.com\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(14, 1, \'crm\', \'core_mail_template_sender\', \'Ihr Firmenname\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(14, 1, \'crm\', \'core_mail_template_subject\', \'Neuer Kontakt erfasst\')');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES(14, 1, \'crm\', \'core_mail_template_to\', \'[CRM_ASSIGNED_USER_EMAIL]\')');

            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_mail_template` (`key`, `section`, `text_id`, `html`, `protected`) VALUES(\'crm_user_account_created\', \'crm\', 12, 1, 1)');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_mail_template` (`key`, `section`, `text_id`, `html`, `protected`) VALUES(\'crm_notify_staff_on_contact_added\', \'crm\', 14, 1, 1)');
            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_mail_template` (`key`, `section`, `text_id`, `html`, `protected`) VALUES(\'crm_task_assigned\', \'crm\', 13, 1, 1)');

            \Cx\Lib\UpdateUtil::sql('INSERT IGNORE INTO `'.DBPREFIX.'core_setting` (`section`, `name`, `group`, `type`, `value`, `values`, `ord`) VALUES (\'crm\',\'numof_mailtemplate_per_page_backend\',\'config\',\'text\',\'25\',\'\',1001)');
        }
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }
}
