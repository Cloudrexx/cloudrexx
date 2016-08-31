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
 * This file updates a RC1 or RC2 installation to a stable installation
 * To update your installation perform the following:
 * 1. Copy all your files to a backup folder
 * 2. Create a backup of your database
 * 3. Copy all stable files to your folder
 * 4. Copy /config/configuration.php, /config/settings.php and /.htaccess back
 * 5. Copy your customized files back
 * 6. Execute this script
 */

$documentRoot = ASCMS_PATH . ASCMS_PATH_OFFSET;

$objResultRc1 = \Cx\Lib\UpdateUtil::sql('SELECT `target` FROM `'.DBPREFIX.'backend_areas` WHERE `area_id` = 186');
$objResultRc2 = \Cx\Lib\UpdateUtil::sql('SELECT `order_id` FROM `'.DBPREFIX.'backend_areas` WHERE `area_id` = 2');
if (!$objResultRc1 || !$objResultRc2) {
    die('ERROR: Could not execute query.');
}
if ($objResultRc1->fields['target'] != '_blank') {
    $version = 'rc1';
} elseif ($objResultRc2->fields['order_id'] != 6 && $_CONFIG['coreCmsVersion'] == '3.0.0') {
    $version = 'rc2';
} elseif ($_CONFIG['coreCmsVersion'] == '3.0.0') {
    $version = 'stable';
} elseif ($_CONFIG['coreCmsVersion'] == '3.0.0.1') {
    $version = 'hotfix';
} elseif ($_CONFIG['coreCmsVersion'] == '3.0.1') {
    $version = 'sp1';
} elseif ($_CONFIG['coreCmsVersion'] == '3.0.2') {
    $version = 'sp2';
} elseif ($_CONFIG['coreCmsVersion'] == '3.0.3') {
    $version = 'sp3';
} elseif ($_CONFIG['coreCmsVersion'] == '3.0.4') {
    $version = 'sp4';
} elseif ($_CONFIG['coreCmsVersion'] == '3.1.0') {
    $version = '310';
} elseif ($_CONFIG['coreCmsVersion'] == '3.1.1') {
    $version = '311';
} else {
    // nothing to do
    return true;
}

$objResult = \Cx\Lib\UpdateUtil::sql('SELECT 1 FROM `'.DBPREFIX.'module_filesharing_mail_template` WHERE (`id` = 1 OR `id` = 2)');
if (!$objResult) {
    die('ERROR: Could not execute query.');
}
if ($objResult->RecordCount() == 0) {
    $rc1UpdateFilesharing[] = '
        INSERT INTO `'.DBPREFIX.'module_filesharing_mail_template` (`id`, `lang_id`, `subject`, `content`) VALUES (1, 1, "Jemand teilt eine Datei mit Ihnen", "Guten Tag,\r\n\r\nJemand hat auf [[DOMAIN]] eine Datei mit Ihnen geteilt.\r\n\r\n<!-- BEGIN filesharing_file -->\r\nDownload-Link: [[FILE_DOWNLOAD]]\r\n<!-- END filesharing_file -->\r\n\r\nDie Person hat eine Nachricht hinterlassen:\r\n[[MESSAGE]]\r\n\r\nFreundliche Grüsse");
    ';
    $rc1UpdateFilesharing[] = '
        INSERT INTO `'.DBPREFIX.'module_filesharing_mail_template` (`id`, `lang_id`, `subject`, `content`) VALUES (2, 2, "Somebody is sharing a file with you", "Hi,\r\n\r\nSomebody shared a file with you on [[DOMAIN]].\r\n\r\n<!-- BEGIN filesharing_file -->\r\nDownload link: [[FILE_DOWNLOAD]]\r\n<!-- END filesharing_file -->\r\n\r\nThe person has left a message for you:\r\n[[MESSAGE]]\r\n\r\nBest regards");
    ';
} else {
    $rc1UpdateFilesharing = null;
}

$updatesRc1ToRc2 = array(
    /*
    array(
        'table' => ,
        'structure' => ,
        'keys' => ,
    ),
     */
    array(
        'table' => DBPREFIX.'access_user_profile',
        'structure' => array(
            'user_id'            => array('type' => 'INT(5)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true),
            'gender'             => array('type' => 'ENUM(\'gender_undefined\',\'gender_female\',\'gender_male\')', 'notnull' => true, 'default' => 'gender_undefined', 'after' => 'user_id'),
            'title'              => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'gender'),
            'firstname'          => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'title'),
            'lastname'           => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'firstname'),
            'company'            => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'lastname'),
            'address'            => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'company'),
            'city'               => array('type' => 'VARCHAR(50)', 'notnull' => true, 'default' => '', 'after' => 'address'),
            'zip'                => array('type' => 'VARCHAR(10)', 'notnull' => true, 'default' => '', 'after' => 'city'),
            'country'            => array('type' => 'SMALLINT(5)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'zip'),
            'phone_office'       => array('type' => 'VARCHAR(20)', 'notnull' => true, 'default' => '', 'after' => 'country'),
            'phone_private'      => array('type' => 'VARCHAR(20)', 'notnull' => true, 'default' => '', 'after' => 'phone_office'),
            'phone_mobile'       => array('type' => 'VARCHAR(20)', 'notnull' => true, 'default' => '', 'after' => 'phone_private'),
            'phone_fax'          => array('type' => 'VARCHAR(20)', 'notnull' => true, 'default' => '', 'after' => 'phone_mobile'),
            'birthday'           => array('type' => 'VARCHAR(11)', 'notnull' => false, 'after' => 'phone_fax'),
            'website'            => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'birthday'),
            'profession'         => array('type' => 'VARCHAR(150)', 'notnull' => true, 'default' => '', 'after' => 'website'),
            'interests'          => array('type' => 'text', 'notnull' => true, 'after' => 'profession'),
            'signature'          => array('type' => 'text', 'notnull' => true, 'after' => 'interests'),
            'picture'            => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'signature'),
        ),
        'keys' => array(
            'profile'        => array('fields' => array('firstname' => 100, 'lastname' => 100, 'company' => 50))
        ),
        'engine' => 'InnoDB',
    ),
    '
        UPDATE `'.DBPREFIX.'access_user_profile`
        SET  `interests` = NULL , `signature` = NULL
        WHERE (`interests` = "" AND `signature` = "");
    ',
    '
        INSERT INTO `'.DBPREFIX.'settings` (`setid`, `setname`, `setvalue`, `setmodule`)
        VALUES (103, "availableComponents", "", 66)
    ',
    array(
        'table' => DBPREFIX.'modules',
        'structure' => array(
            'id'                         => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => false),
            'name'                       => array('type' => 'VARCHAR(250)', 'notnull' => true, 'default' => '', 'after' => 'id'),
            'distributor'                => array('type' => 'CHAR(50)', 'after' => 'name'),
            'description_variable'       => array('type' => 'VARCHAR(50)', 'notnull' => true, 'default' => '', 'after' => 'distributor'),
            'status'                     => array('type' => 'SET(\'y\',\'n\')', 'notnull' => true, 'default' => 'n', 'after' => 'description_variable'),
            'is_required'                => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'status'),
            'is_core'                    => array('type' => 'TINYINT(4)', 'notnull' => true, 'default' => '0', 'after' => 'is_required'),
            'is_active'                  => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'is_core'),
        ),
        'keys' => array(
            'id'                         => array('fields' => array('id'), 'type' => 'UNIQUE'),
        ),
    ),
    '
        UPDATE `'.DBPREFIX.'modules`
        SET `distributor` = "Comvation AG"
    ',
    array(
        'table' => DBPREFIX.'module_mediadir_rel_entry_inputfields_clean1',
        'structure' => array(
            'id'             => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'entry_id'       => array('type' => 'INT(7)', 'after' => 'id'),
            'lang_id'        => array('type' => 'INT(7)', 'after' => 'entry_id'),
            'form_id'        => array('type' => 'INT(7)', 'after' => 'lang_id'),
            'field_id'       => array('type' => 'INT(7)', 'after' => 'form_id'),
            'value'          => array('type' => 'longtext', 'after' => 'field_id'),
        ),
        'keys' => array(
            'value'          => array('fields' => array('value'), 'type' => 'FULLTEXT'),
        ),
    ),
    '
        INSERT INTO `'.DBPREFIX.'module_mediadir_rel_entry_inputfields_clean1`
        SELECT NULL, `entry_id`, `lang_id`, `form_id`, `field_id`, `value`
        FROM `'.DBPREFIX.'module_mediadir_rel_entry_inputfields`
        GROUP BY `entry_id`, `form_id`, `field_id`, `lang_id`, `value`
    ',
    '
        TRUNCATE `'.DBPREFIX.'module_mediadir_rel_entry_inputfields`
    ',
    array(
        'table' => DBPREFIX.'module_mediadir_rel_entry_inputfields',
        'structure' => array(
            'entry_id'       => array('type' => 'INT(7)'),
            'lang_id'        => array('type' => 'INT(7)', 'after' => 'entry_id'),
            'form_id'        => array('type' => 'INT(7)', 'after' => 'lang_id'),
            'field_id'       => array('type' => 'INT(7)', 'after' => 'form_id'),
            'value'          => array('type' => 'longtext', 'after' => 'field_id'),
        ),
        'keys' => array(
            'entry_id'       => array('fields' => array('entry_id','lang_id','form_id','field_id'), 'type' => 'UNIQUE'),
            'value'          => array('fields' => array('value'), 'type' => 'FULLTEXT'),
        ),
    ),
    '
        INSERT IGNORE INTO `'.DBPREFIX.'module_mediadir_rel_entry_inputfields`
        SELECT `entry_id`, `lang_id`, `form_id`, `field_id`, `value`
        FROM `'.DBPREFIX.'module_mediadir_rel_entry_inputfields_clean1`
        ORDER BY `id` DESC
    ',
    '
        DROP TABLE IF EXISTS `'.DBPREFIX.'module_mediadir_rel_entry_inputfields_clean1`
    ',
    array(
        'table' => DBPREFIX.'module_repository',
        'structure' => array(
            'id'                 => array('type' => 'INT(6)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true),
            'moduleid'           => array('type' => 'INT(5)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'id'),
            'content'            => array('type' => 'mediumtext', 'after' => 'moduleid'),
            'title'              => array('type' => 'VARCHAR(250)', 'notnull' => true, 'default' => '', 'after' => 'content'),
            'cmd'                => array('type' => 'VARCHAR(20)', 'notnull' => true, 'default' => '', 'after' => 'title'),
            'expertmode'         => array('type' => 'SET(\'y\',\'n\')', 'notnull' => true, 'default' => 'n', 'after' => 'cmd'),
            'parid'              => array('type' => 'INT(5)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'expertmode'),
            'displaystatus'      => array('type' => 'SET(\'on\',\'off\')', 'notnull' => true, 'default' => 'on', 'after' => 'parid'),
            'username'           => array('type' => 'VARCHAR(250)', 'notnull' => true, 'default' => '', 'after' => 'displaystatus'),
            'displayorder'       => array('type' => 'SMALLINT(6)', 'notnull' => true, 'default' => '100', 'after' => 'username')
        ),
        'keys' => array(
            'contentid'          => array('fields' => array('id'), 'type' => 'UNIQUE'),
            'fulltextindex'      => array('fields' => array('title','content'), 'type' => 'FULLTEXT')
        ),
    ),
    '
        TRUNCATE TABLE `'.DBPREFIX.'module_repository`
    ',
    '
        UPDATE `'.DBPREFIX.'backend_areas`
        SET `target` = "_blank"
        WHERE `area_id` = 186
    ',
);
if (!empty($rc1UpdateFilesharing)) {
    $updatesRc1ToRc2 = array_merge($updatesRc1ToRc2, $rc1UpdateFilesharing);
}

$updatesRc2ToStable = array(
    '
        UPDATE `'.DBPREFIX.'core_setting`
        SET `value` = "off"
        WHERE (`section` = "filesharing" AND `name` = "permission" AND `group` = "config")
    ',
    '
        INSERT INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`)
        VALUES  (4, 1, "shop", "currency_name", "Euro"),
                (5, 1, "shop", "currency_name", "United States Dollars")
    ',
    '
        INSERT INTO `'.DBPREFIX.'settings` (`setid`, `setname`, `setvalue`, `setmodule`)
        VALUES  (104, "upgradeUrl", "http://license.contrexx.com/", 66),
                (105, "isUpgradable", "on", 66),
                (106, "dashboardMessages", "", 66)
    ',
    '
        DELETE FROM `'.DBPREFIX.'backend_areas`
        WHERE `'.DBPREFIX.'backend_areas`.`area_id` = 179
    ',
    '
        UPDATE `'.DBPREFIX.'backend_areas`
        SET `order_id` = 4
        WHERE `'.DBPREFIX.'backend_areas`.`area_id` = 8
    ',
    '
        UPDATE `'.DBPREFIX.'backend_areas`
        SET `order_id` = 5
        WHERE `'.DBPREFIX.'backend_areas`.`area_id` = 29
    ',
    '
        UPDATE `'.DBPREFIX.'backend_areas`
        SET `order_id` = 6
        WHERE `'.DBPREFIX.'backend_areas`.`area_id` = 2
    ',
    '
        TRUNCATE TABLE `'.DBPREFIX.'module_repository`
    ',
);
$updatesStableToHotfix = array(
    'UPDATE `'.DBPREFIX.'content_page` SET `customContent` = \'\' WHERE `customContent` = \'(Default)\'',
);
$updatesHotfixToSp1 = array(
    array(
        'table' => DBPREFIX.'module_block_rel_lang_content',
        'structure' => array(
            'block_id'       => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
            'lang_id'        => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'block_id'),
            'content'        => array('type' => 'mediumtext', 'after' => 'lang_id'),
            'active'         => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'content'),
        ),
        'keys' => array(
            'id_lang'        => array('fields' => array('block_id','lang_id'), 'type' => 'UNIQUE'),
        ),
    ),
    array(
        'table' => DBPREFIX.'module_contact_form',
        'structure' => array(
            'id'                     => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'mails'                  => array('type' => 'text', 'after' => 'id'),
            'showForm'               => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'mails'),
            'use_captcha'            => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '1', 'after' => 'showForm'),
            'use_custom_style'       => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'use_captcha'),
            'send_copy'              => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'use_custom_style'),
            'use_email_of_sender'    => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'send_copy'),
            'html_mail'              => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '1', 'after' => 'use_email_of_sender'),
            'send_attachment'        => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'html_mail'),
        ),
        'keys' => array(
        ),
    ),
    array(
        'table' => DBPREFIX.'module_newsletter_access_user',
        'structure' => array(
            'accessUserID'               => array('type' => 'INT(5)', 'unsigned' => true),
            'newsletterCategoryID'       => array('type' => 'INT(11)', 'after' => 'accessUserID'),
            'code'                       => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'newsletterCategoryID'),
        ),
        'keys' => array(
            'rel'                        => array('fields' => array('accessUserID','newsletterCategoryID'), 'type' => 'UNIQUE'),
            'accessUserID'               => array('fields' => array('accessUserID')),
        ),
    ),
    'UPDATE '.DBPREFIX.'module_newsletter_access_user SET `code` = SUBSTR(MD5(RAND()),1,12) WHERE `code` = \'\'',
    array(
        'table' => DBPREFIX.'content_page',
        'structure' => array(
            'id'                                 => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'node_id'                            => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'id'),
            'nodeIdShadowed'                     => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'node_id'),
            'lang'                               => array('type' => 'INT(11)', 'after' => 'nodeIdShadowed'),
            'type'                               => array('type' => 'VARCHAR(16)', 'after' => 'lang'),
            'caching'                            => array('type' => 'TINYINT(1)', 'after' => 'type'),
            'updatedAt'                          => array('type' => 'timestamp', 'default' => null, 'notnull' => false, 'after' => 'caching'),
            'updatedBy'                          => array('type' => 'CHAR(40)', 'after' => 'updatedAt'),
            'title'                              => array('type' => 'VARCHAR(255)', 'after' => 'updatedBy'),
            'linkTarget'                         => array('type' => 'VARCHAR(16)', 'notnull' => false, 'after' => 'title'),
            'contentTitle'                       => array('type' => 'VARCHAR(255)', 'after' => 'linkTarget'),
            'slug'                               => array('type' => 'VARCHAR(255)', 'after' => 'contentTitle'),
            'content'                            => array('type' => 'longtext', 'after' => 'slug'),
            'sourceMode'                         => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'content'),
            'customContent'                      => array('type' => 'VARCHAR(64)', 'notnull' => false, 'after' => 'sourceMode'),
            'cssName'                            => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'customContent'),
            'cssNavName'                         => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'cssName'),
            'skin'                               => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'cssNavName'),
            'metatitle'                          => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'skin'),
            'metadesc'                           => array('type' => 'text', 'after' => 'metatitle'),
            'metakeys'                           => array('type' => 'text', 'after' => 'metadesc'),
            'metarobots'                         => array('type' => 'VARCHAR(7)', 'notnull' => false, 'after' => 'metakeys'),
            'start'                              => array('type' => 'timestamp', 'notnull' => false, 'default' => null, 'after' => 'metarobots'),
            'end'                                => array('type' => 'timestamp', 'notnull' => false, 'default' => null, 'after' => 'start'),
            'editingStatus'                      => array('type' => 'VARCHAR(16)', 'after' => 'end'),
            'protection'                         => array('type' => 'INT(11)', 'after' => 'editingStatus'),
            'frontendAccessId'                   => array('type' => 'INT(11)', 'after' => 'protection'),
            'backendAccessId'                    => array('type' => 'INT(11)', 'after' => 'frontendAccessId'),
            'display'                            => array('type' => 'TINYINT(1)', 'after' => 'backendAccessId'),
            'active'                             => array('type' => 'TINYINT(1)', 'after' => 'display'),
            'target'                             => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'active'),
            'module'                             => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'target'),
            'cmd'                                => array('type' => 'VARCHAR(50)', 'notnull' => true, 'default' => '', 'after' => 'module'),
        ),
        'keys' => array(
            'node_id'                            => array('fields' => array('node_id','lang'), 'type' => 'UNIQUE'),
            'IDX_D8E86F54460D9FD7'               => array('fields' => array('node_id')),
        ),
        'engine' => 'InnoDB',
    ),
    array(
        'table' => DBPREFIX.'core_mail_template',
        'structure' => array(
            'key'            => array('type' => 'tinytext'),
            'section'        => array('type' => 'tinytext', 'notnull' => true, 'after' => 'key'),
            'text_id'        => array('type' => 'INT(10)', 'unsigned' => true, 'after' => 'section'),
            'html'           => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'text_id'),
            'protected'      => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'html'),
        ),
    ),
    '
        ALTER TABLE `'.DBPREFIX.'core_mail_template` ADD PRIMARY KEY (`key` (32), `section` (32))
    ',
    array(
        'table' => DBPREFIX.'languages',
        'structure' => array(
            'id'                     => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'lang'                   => array('type' => 'VARCHAR(5)', 'notnull' => true, 'default' => '', 'after' => 'id'),
            'name'                   => array('type' => 'VARCHAR(250)', 'notnull' => true, 'default' => '', 'after' => 'lang'),
            'charset'                => array('type' => 'VARCHAR(20)', 'notnull' => true, 'default' => 'iso-8859-1', 'after' => 'name'),
            'themesid'               => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '1', 'after' => 'charset'),
            'print_themes_id'        => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '1', 'after' => 'themesid'),
            'pdf_themes_id'          => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'print_themes_id'),
            'frontend'               => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'pdf_themes_id'),
            'backend'                => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'frontend'),
            'is_default'             => array('type' => 'SET(\'true\',\'false\')', 'notnull' => true, 'default' => 'false', 'after' => 'backend'),
            'mobile_themes_id'       => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'is_default'),
            'fallback'               => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'mobile_themes_id'),
            'app_themes_id'          => array('type' => 'INT(2)', 'after' => 'fallback'),
        ),
        'keys' => array(
            'lang'                   => array('fields' => array('lang'), 'type' => 'UNIQUE'),
            'defaultstatus'          => array('fields' => array('is_default')),
        ),
    ),
    '
        DROP TABLE IF EXISTS `'.DBPREFIX.'module_alias_source`
    ',
    '
        DROP TABLE IF EXISTS `'.DBPREFIX.'module_alias_target`
    ',
    '
        DROP TABLE IF EXISTS `'.DBPREFIX.'module_shop_countries`
    ',
    array(
        'table' => DBPREFIX.'module_shop_currencies',
        'structure' => array(
            'id'             => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'code'           => array('type' => 'CHAR(3)', 'notnull' => true, 'default' => '', 'after' => 'id'),
            'symbol'         => array('type' => 'VARCHAR(20)', 'notnull' => true, 'default' => '', 'after' => 'code'),
            'rate'           => array('type' => 'DECIMAL(10,4)', 'unsigned' => true, 'notnull' => true, 'default' => '1.0000', 'after' => 'symbol'),
            'ord'            => array('type' => 'INT(5)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'rate'),
            'active'         => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '1', 'after' => 'ord'),
            'default'        => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'active'),
            'increment'      => array('type' => 'DECIMAL(6,5)', 'unsigned' => true, 'notnull' => true, 'default' => '0.01000', 'after' => 'default'),
        ),
        'keys' => array(),
    ),
    '
        DROP TABLE IF EXISTS `'.DBPREFIX.'module_shop_mail`
    ',
    '
        DROP TABLE IF EXISTS `'.DBPREFIX.'module_shop_mail_content`
    ',
    array(
        'table' => DBPREFIX.'module_shop_payment_processors',
        'structure' => array(
            'id'             => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'type'           => array('type' => 'ENUM(\'internal\',\'external\')', 'notnull' => true, 'default' => 'internal', 'after' => 'id'),
            'name'           => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'type'),
            'description'    => array('type' => 'text', 'after' => 'name'),
            'company_url'    => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'description'),
            'status'         => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '1', 'after' => 'company_url'),
            'picture'        => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'status'),
        ),
        'keys' => array(),
    ),
    '
        DROP TABLE IF EXISTS `'.DBPREFIX.'module_shop_products_downloads`
    ',
    array(
        'table' => DBPREFIX.'module_checkout_settings_mails',
        'structure' => array(
            'id'         => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'title'      => array('type' => 'text', 'after' => 'id'),
            'content'    => array('type' => 'text', 'after' => 'title')
        ),
        'engine' => 'MyISAM',
    ),
    array(
        'table' => DBPREFIX.'module_checkout_settings_yellowpay',
        'structure' => array(
            'id'         => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'name'       => array('type' => 'text', 'after' => 'id'),
            'value'      => array('type' => 'text', 'after' => 'name')
        ),
        'engine' => 'MyISAM',
    ),
);

$updatesSp1ToSp2 = array(
    array (
        'table' => DBPREFIX.'module_block_categories',
        'structure' => array(
            'id'             => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'parent'         => array('type' => 'INT(10)', 'notnull' => true, 'default' => '0', 'after' => 'id'),
            'name'           => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'parent'),
            'seperator'      => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'name'),
            'order'          => array('type' => 'INT(10)', 'notnull' => true, 'default' => '0', 'after' => 'seperator'),
            'status'         => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '1', 'after' => 'order')
        ),
    ),
    array (
        'table' => DBPREFIX.'module_block_blocks',
        'structure' => array(
            'id'                 => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'start'              => array('type' => 'INT(10)', 'notnull' => true, 'default' => '0', 'after' => 'id'),
            'end'                => array('type' => 'INT(10)', 'notnull' => true, 'default' => '0', 'after' => 'start'),
            'name'               => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'end'),
            'random'             => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'name'),
            'random_2'           => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'random'),
            'random_3'           => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'random_2'),
            'random_4'           => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'random_3'),
            'global'             => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'random_4'),
            'category'           => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'global'),
            'direct'             => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'category'),
            'active'             => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'direct'),
            'order'              => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'active'),
            'cat'                => array('type' => 'INT(10)', 'notnull' => true, 'default' => '0', 'after' => 'order'),
            'wysiwyg_editor'     => array('type' => 'INT(1)', 'notnull' => true, 'default' => '1', 'after' => 'cat')
        ),
    ),
    array (
        'table' => DBPREFIX.'module_block_rel_pages',
        'structure' => array(
            'block_id'       => array('type' => 'INT(7)', 'notnull' => true, 'default' => '0', 'primary' => true),
            'page_id'        => array('type' => 'INT(7)', 'notnull' => true, 'default' => '0', 'after' => 'block_id', 'primary' => true),
            'placeholder'    => array('type' => 'ENUM(\'global\',\'direct\',\'category\')', 'notnull' => true, 'default' => 'global', 'after' => 'page_id', 'primary' => true)
        ),
    ),
    '
        INSERT IGNORE INTO `'.DBPREFIX.'access_settings` (`key`, `value`, `status`) VALUES
        (\'sociallogin\', \'\', 0),
        (\'sociallogin_active_automatically\', \'\', 1),
        (\'sociallogin_assign_to_groups\', \'3\', 0),
        (\'sociallogin_show_signup\', \'\', 0),
        (\'use_usernames\', \'0\', 1)
    ',
    array(
        'table' => DBPREFIX.'access_users',
        'structure' => array(
            'id'                     => array('type' => 'INT(5)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'is_admin'               => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'id'),
            'username'               => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'is_admin'),
            'password'               => array('type' => 'VARCHAR(32)', 'notnull' => false, 'after' => 'username'),
            'regdate'                => array('type' => 'INT(14)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'password'),
            'expiration'             => array('type' => 'INT(14)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'regdate'),
            'validity'               => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'expiration'),
            'last_auth'              => array('type' => 'INT(14)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'validity'),
            'last_auth_status'       => array('type' => 'INT(1)', 'notnull' => true, 'default' => '1', 'after' => 'last_auth'),
            'last_activity'          => array('type' => 'INT(14)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'last_auth_status'),
            'email'                  => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'last_activity'),
            'email_access'           => array('type' => 'ENUM(\'everyone\',\'members_only\',\'nobody\')', 'notnull' => true, 'default' => 'nobody', 'after' => 'email'),
            'frontend_lang_id'       => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'email_access'),
            'backend_lang_id'        => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'frontend_lang_id'),
            'active'                 => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'backend_lang_id'),
            'primary_group'          => array('type' => 'INT(6)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'active'),
            'profile_access'         => array('type' => 'ENUM(\'everyone\',\'members_only\',\'nobody\')', 'notnull' => true, 'default' => 'members_only', 'after' => 'primary_group'),
            'restore_key'            => array('type' => 'VARCHAR(32)', 'notnull' => true, 'default' => '', 'after' => 'profile_access'),
            'restore_key_time'       => array('type' => 'INT(14)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'restore_key'),
            'u2u_active'             => array('type' => 'ENUM(\'0\',\'1\')', 'notnull' => true, 'default' => '1', 'after' => 'restore_key_time'),
        ),
        'keys' => array(
            'username'               => array('fields' => array('username'))
        ),
    ),
    array(
        'table' => DBPREFIX.'access_user_network',
        'structure' => array(
            'id'                 => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'oauth_provider'     => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => '', 'after' => 'id'),
            'oauth_id'           => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => '', 'after' => 'oauth_provider'),
            'user_id'            => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'oauth_id')
        ),
        'engine' => 'InnoDB',
    ),
    '
        INSERT IGNORE INTO `'.DBPREFIX.'core_setting` (`section`, `name`, `group`, `type`, `value`, `values`, `ord`) VALUES
        (\'access\', \'providers\', \'sociallogin\', \'text\', \'{"facebook":{"active":"0","settings":["",""]},"twitter":{"active":"0","settings":["",""]},"google":{"active":"0","settings":["","",""]}}\', \'\', 0)
    ',
    array(
        'table' => DBPREFIX.'module_knowledge_tags_articles',
        'structure' => array(
            'article'    => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
            'tag'        => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'article'),
        ),
        'keys' => array(
            'article'    => array('fields' => array('article','tag'), 'type' => 'UNIQUE'),
        )
    ),
);

$userData = json_encode(array(
    'id'   => $_SESSION['contrexx_update']['user_id'],
    'name' => $_SESSION['contrexx_update']['username'],
));
$updatesSp2ToSp3 = array(
    array (
        'table' => DBPREFIX.'module_block_categories',
        'structure' => array(
            'id'             => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'parent'         => array('type' => 'INT(10)', 'notnull' => true, 'default' => '0', 'after' => 'id'),
            'name'           => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'parent'),
            'seperator'      => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'name'),
            'order'          => array('type' => 'INT(10)', 'notnull' => true, 'default' => '0', 'after' => 'seperator'),
            'status'         => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '1', 'after' => 'order')
        ),
    ),
    array (
        'table' => DBPREFIX.'module_block_blocks',
        'structure' => array(
            'id'                 => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'start'              => array('type' => 'INT(10)', 'notnull' => true, 'default' => '0', 'after' => 'id'),
            'end'                => array('type' => 'INT(10)', 'notnull' => true, 'default' => '0', 'after' => 'start'),
            'name'               => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'end'),
            'random'             => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'name'),
            'random_2'           => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'random'),
            'random_3'           => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'random_2'),
            'random_4'           => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'random_3'),
            'global'             => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'random_4'),
            'category'           => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'global'),
            'direct'             => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'category'),
            'active'             => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'direct'),
            'order'              => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'active'),
            'cat'                => array('type' => 'INT(10)', 'notnull' => true, 'default' => '0', 'after' => 'order'),
            'wysiwyg_editor'     => array('type' => 'INT(1)', 'notnull' => true, 'default' => '1', 'after' => 'cat')
        ),
    ),
    array (
        'table' => DBPREFIX.'module_block_rel_pages',
        'structure' => array(
            'block_id'       => array('type' => 'INT(7)', 'notnull' => true, 'default' => '0', 'primary' => true),
            'page_id'        => array('type' => 'INT(7)', 'notnull' => true, 'default' => '0', 'after' => 'block_id', 'primary' => true),
            'placeholder'    => array('type' => 'ENUM(\'global\',\'direct\',\'category\')', 'notnull' => true, 'default' => 'global', 'after' => 'page_id', 'primary' => true)
        ),
    ),
    "INSERT INTO `".DBPREFIX."access_settings` (`key`, `value`, `status`) VALUES ('use_usernames', '0', '1') ON DUPLICATE KEY UPDATE `key` = `key`",
    array (
        'table' => DBPREFIX.'settings_image',
        'structure' => array(
            'id'         => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'name'       => array('type' => 'VARCHAR(50)', 'after' => 'id'),
            'value'      => array('type' => 'text', 'after' => 'name')
        ),
    ),
    "INSERT IGNORE INTO `".DBPREFIX."settings_image` (`name`, `value`) VALUES ('image_cut_width', '500')",
    "INSERT IGNORE INTO `".DBPREFIX."settings_image` (`name`, `value`) VALUES ('image_cut_height', '500')",
    "INSERT IGNORE INTO `".DBPREFIX."settings_image` (`name`, `value`) VALUES ('image_scale_width', '800')",
    "INSERT IGNORE INTO `".DBPREFIX."settings_image` (`name`, `value`) VALUES ('image_scale_height', '800')",
    "INSERT IGNORE INTO `".DBPREFIX."settings_image` (`name`, `value`) VALUES ('image_compression', '100')",
    array (
        'table' => DBPREFIX.'module_egov_product_calendar',
        'structure' => array(
            'calendar_id'            => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'calendar_product'       => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'calendar_id'),
            'calendar_order'         => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'calendar_product'),
            'calendar_day'           => array('type' => 'INT(2)', 'notnull' => true, 'default' => '0', 'after' => 'calendar_order'),
            'calendar_month'         => array('type' => 'INT(2)', 'zerofill' => true, 'default' => '00', 'after' => 'calendar_day'),
            'calendar_year'          => array('type' => 'INT(4)', 'notnull' => true, 'default' => '0', 'after' => 'calendar_month'),
            'calendar_act'           => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'calendar_year')
        ),
        'keys' => array(
            'calendar_product'       => array('fields' => array('calendar_product'))
        ),
    ),
    array (
        'table' => DBPREFIX.'voting_results',
        'structure' => array(
            'id'                     => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'voting_system_id'       => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'id'),
            'question'               => array('type' => 'CHAR(200)', 'notnull' => false, 'after' => 'voting_system_id'),
            'votes'                  => array('type' => 'INT(11)', 'notnull' => false, 'default' => '0', 'after' => 'question')
        ),
    ),
    array (
        'table' => DBPREFIX.'voting_system',
        'structure' => array(
            'id'                     => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'date'                   => array('type' => 'timestamp', 'notnull' => true, 'after' => 'id'),
            'title'                  => array('type' => 'VARCHAR(60)', 'notnull' => true, 'default' => '', 'after' => 'date'),
            'question'               => array('type' => 'text', 'after' => 'title', 'notnull' => false),
            'status'                 => array('type' => 'TINYINT(1)', 'default' => '1', 'notnull' => false, 'after' => 'question'),
            'submit_check'           => array('type' => 'ENUM(\'cookie\',\'email\')', 'notnull' => true, 'default' => 'cookie', 'after' => 'status'),
            'votes'                  => array('type' => 'INT(11)', 'notnull' => false, 'default' => '0', 'after' => 'submit_check'),
            'additional_nickname'    => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'votes'),
            'additional_forename'    => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'additional_nickname'),
            'additional_surname'     => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'additional_forename'),
            'additional_phone'       => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'additional_surname'),
            'additional_street'      => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'additional_phone'),
            'additional_zip'         => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'additional_street'),
            'additional_email'       => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'additional_zip'),
            'additional_city'        => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'additional_email'),
            'additional_comment'     => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'additional_city')
        ),
    ),
    array (
        'table' => DBPREFIX.'module_shop_currencies',
        'structure' => array(
            'id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'code' => array('type' => 'CHAR(3)', 'notnull' => true, 'default' => '', 'after' => 'id'),
            'symbol' => array('type' => 'VARCHAR(20)', 'notnull' => true, 'default' => '', 'after' => 'code'),
            'rate' => array('type' => 'DECIMAL(10,4)', 'unsigned' => true, 'notnull' => true, 'default' => '1.0000', 'after' => 'symbol'),
            'ord' => array('type' => 'INT(5)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'rate'),
            'active' => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '1', 'after' => 'ord'),
            'default' => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'active'),
            'increment' => array('type' => 'DECIMAL(6,5)', 'unsigned' => true, 'notnull' => true, 'default' => '0.01', 'after' => 'default'),
        ),
    ),
    array (
        'table' => DBPREFIX.'module_shop_payment_processors',
        'structure' => array(
            'id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'type' => array('type' => 'ENUM(\'internal\',\'external\')', 'notnull' => true, 'default' => 'internal'),
            'name' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => ''),
            'description' => array('type' => 'TEXT'),
            'company_url' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => ''),
            'status' => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '1'),
            'picture' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => ''),
        ),
    ),
    array (
        'table' => DBPREFIX.'module_knowledge_tags_articles',
        'structure' => array(
            'article'    => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
            'tag'        => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'article')
        ),
        'keys' => array(
            'article'    => array('fields' => array('article', 'tag'), 'type' => 'UNIQUE', 'force' => true)
        ),
    ),
    "INSERT INTO `".DBPREFIX."access_settings` (`key`, `value`, `status`) VALUES ('sociallogin_activation_timeout', '10', '0') ON DUPLICATE KEY UPDATE `key` = `key`",
    array(
        'table' => DBPREFIX.'access_user_profile',
        'structure' => array(
            'user_id'            => array('type' => 'INT(5)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true),
            'gender'             => array('type' => 'ENUM(\'gender_undefined\',\'gender_female\',\'gender_male\')', 'notnull' => true, 'default' => 'gender_undefined', 'after' => 'user_id'),
            'title'              => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'gender'),
            'firstname'          => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'title'),
            'lastname'           => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'firstname'),
            'company'            => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'lastname'),
            'address'            => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'company'),
            'city'               => array('type' => 'VARCHAR(50)', 'notnull' => true, 'default' => '', 'after' => 'address'),
            'zip'                => array('type' => 'VARCHAR(10)', 'notnull' => true, 'default' => '', 'after' => 'city'),
            'country'            => array('type' => 'SMALLINT(5)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'zip'),
            'phone_office'       => array('type' => 'VARCHAR(20)', 'notnull' => true, 'default' => '', 'after' => 'country'),
            'phone_private'      => array('type' => 'VARCHAR(20)', 'notnull' => true, 'default' => '', 'after' => 'phone_office'),
            'phone_mobile'       => array('type' => 'VARCHAR(20)', 'notnull' => true, 'default' => '', 'after' => 'phone_private'),
            'phone_fax'          => array('type' => 'VARCHAR(20)', 'notnull' => true, 'default' => '', 'after' => 'phone_mobile'),
            'birthday'           => array('type' => 'VARCHAR(11)', 'notnull' => false, 'after' => 'phone_fax'),
            'website'            => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'birthday'),
            'profession'         => array('type' => 'VARCHAR(150)', 'notnull' => true, 'default' => '', 'after' => 'website'),
            'interests'          => array('type' => 'text', 'notnull' => true, 'after' => 'profession'),
            'signature'          => array('type' => 'text', 'notnull' => true, 'after' => 'interests'),
            'picture'            => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'signature'),
        ),
        'keys' => array(
            'profile'        => array('fields' => array('firstname' => 100, 'lastname' => 100, 'company' => 50))
        ),
        'engine' => 'InnoDB',
    ),
    array (
        'table' => DBPREFIX.'core_setting',
        'structure' => array(
            'section' => array('type' => 'VARCHAR(32)', 'default' => '', 'primary' => true),
            'name' => array('type' => 'VARCHAR(255)', 'default' => '', 'primary' => true),
            'group' => array('type' => 'VARCHAR(32)', 'default' => '', 'primary' => true),
            'type' => array('type' => 'VARCHAR(32)', 'notnull' => true, 'default' => 'text', 'after' => 'group'),
            'value' => array('type' => 'text', 'notnull' => true, 'after' => 'type'),
            'values' => array('type' => 'text', 'notnull' => true, 'after' => 'value'),
            'ord' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'values'),
        ),
    ),
    array (
        'table' => DBPREFIX.'core_text',
        'structure' => array(
            'id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true),
            'lang_id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '1', 'primary' => true, 'after' => 'id'),
            'section' => array('type' => 'VARCHAR(32)', 'notnull' => true, 'default' => '', 'primary' => true, 'after' => 'lang_id'),
            'key' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'primary' => true, 'after' => 'section'),
            'text' => array('type' => 'text', 'after' => 'key'),
        ),
        'keys' => array(
            'text' => array('fields' => array('text'), 'type' => 'FULLTEXT'),
        ),
    ),
    'UPDATE `' . DBPREFIX . 'log_entry` SET `username` = \'' . $userData . '\' WHERE `username` = \'currently_loggedin_user\'',
    array (
        'table' => DBPREFIX.'module_newsletter_tmp_sending',
        'structure' => array(
            'id'             => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'newsletter'     => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'id'),
            'email'          => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'newsletter'),
            'sendt'          => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'email'),
            'type'           => array('type' => 'ENUM(\'access\',\'newsletter\',\'core\')', 'notnull' => true, 'default' => 'newsletter', 'after' => 'sendt'),
            'code'           => array('type' => 'VARCHAR(10)', 'after' => 'type'),
        ),
        'keys' => array(
            'unique_email'   => array('fields' => array('newsletter','email'), 'type' => 'UNIQUE'),
            'email'          => array('fields' => array('email')),
        ),
    ),
    'UPDATE `' . DBPREFIX . 'modules` SET `status` = \'y\' WHERE `id` = 68',
);

$updatesSp3ToSp4 = array(
    'UPDATE  `' . DBPREFIX . 'backend_areas` SET  `scope` =  \'backend\' WHERE  `area_id` = 161',
);

$updatesSp4To310 = array(
    "INSERT IGNORE INTO `" . DBPREFIX . "settings` (`setid`, `setname`, `setvalue`, `setmodule`) VALUES
    (57, 'forceProtocolFrontend', 'none', 1),
    (58, 'forceProtocolBackend', 'none', 1),
    (59, 'forceDomainUrl', 'off', 1)",
    array (
        'table'  =>  DBPREFIX . 'module_calendar_mail',
        'structure' => array(
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
    ),
    'INSERT INTO  `' . DBPREFIX . 'module_calendar_mail`
    (`title`, `content_text`, `content_html`, `lang_id`, `action_id`,  `status`)
    SELECT
    title.setvalue ,
    content.setvalue ,
    REPLACE(content.setvalue, "\r\n", "<br />\n") ,
    1 ,
    1 ,
    1
    FROM `' . DBPREFIX . 'module_calendar_settings` as content
    JOIN `' . DBPREFIX . 'module_calendar_settings` as title ON title.setid = 3
    WHERE content.setid = 4;
    ',
    'DROP TABLE `' . DBPREFIX . 'module_calendar_settings`',
    array(
        'table'     => DBPREFIX . 'module_calendar_settings',
        'structure' => array(
            'id'         => array('type' => 'INT(7)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'section_id' => array('type' => 'INT(11)', 'after' => 'id'),
            'name'       => array('type' => 'VARCHAR(255)', 'after' => 'section_id'),
            'title'      => array('type' => 'VARCHAR(255)', 'after' => 'name'),
            'value'      => array('type' => 'mediumtext', 'after' => 'title'),
            'info'       => array('type' => 'mediumtext', 'after' => 'value'),
            'type'       => array('type' => 'INT(11)', 'after' => 'info'),
            'options'    => array('type' => 'mediumtext', 'after' => 'type'),
            'special'    => array('type' => 'VARCHAR(255)', 'after' => 'options'),
            'order'      => array('type' => 'INT(11)', 'after' => 'special')
        )
    ),
    array(
        'table'     => DBPREFIX . 'module_calendar_settings_section',
        'structure' => array(
            'id'     => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'parent' => array('type' => 'INT(11)', 'after' => 'id'),
            'order'  => array('type' => 'INT(11)', 'after' => 'parent'),
            'name'   => array('type' => 'VARCHAR(255)', 'after' => 'order'),
            'title'  => array('type' => 'VARCHAR(255)', 'after' => 'name')
        )
    ),
    array (
        'table' => DBPREFIX.'core_text',
        'structure' => array(
            'id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true),
            'lang_id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '1', 'primary' => true, 'after' => 'id'),
            'section' => array('type' => 'VARCHAR(32)', 'notnull' => true, 'default' => '', 'primary' => true, 'after' => 'lang_id'),
            'key' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'primary' => 32, 'after' => 'section'),
            'text' => array('type' => 'text', 'after' => 'key'),
        ),
        'keys' => array(
            'text' => array('fields' => array('text'), 'type' => 'FULLTEXT'),
        ),
    ),
    // set new access_id for filesharing
    "UPDATE `" . DBPREFIX . "backend_areas` SET `access_id` = '8' WHERE `area_id` = 187",
    array(
        'table' => DBPREFIX.'component',
        'structure' => array(
            'id'         => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'name'       => array('type' => 'VARCHAR(100)', 'after' => 'id'),
            'type'       => array('type' => 'ENUM(\'core\',\'core_module\',\'module\')', 'after' => 'name')
        ),
        'engine' => 'InnoDB',
    ),
    "INSERT IGNORE INTO `".DBPREFIX."component` (`id`, `name`, `type`) VALUES
    (70, 'Workbench', 'core_module'),
    (71, 'FrontendEditing', 'core_module'),
    (72, 'ContentManager', 'core')",
    array(
        'table' => DBPREFIX.'module_contact_form',
        'structure' => array(
            'id'                     => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'mails'                  => array('type' => 'text', 'after' => 'id'),
            'showForm'               => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'mails'),
            'use_captcha'            => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '1', 'after' => 'showForm'),
            'use_custom_style'       => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'use_captcha'),
            'save_data_in_crm'       => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'use_custom_style'),
            'send_copy'              => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'save_data_in_crm'),
            'use_email_of_sender'    => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'send_copy'),
            'html_mail'              => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '1', 'after' => 'use_email_of_sender'),
            'send_attachment'        => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'html_mail')
        )
    ),
    array(
        'table' => DBPREFIX.'module_downloads_download_locale',
        'structure' => array(
            'lang_id'        => array('type' => 'INT(11)', 'unsigned' => true, 'notnull' => true, 'primary' => true, 'default' => '0'),
            'download_id'    => array('type' => 'INT(11)', 'unsigned' => true, 'notnull' => true, 'primary' => true, 'default' => '0', 'after' => 'lang_id'),
            'name'           => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'download_id'),
            'source'         => array('type' => 'VARCHAR(1024)', 'notnull' => false, 'after' => 'name'),
            'source_name'    => array('type' => 'VARCHAR(1024)', 'notnull' => false, 'after' => 'source'),
            'description'    => array('type' => 'text', 'after' => 'source_name'),
            'metakeys'       => array('type' => 'text', 'after' => 'description')
        ),
        'keys' => array(
            'name'           => array('fields' => array('name'), 'type' => 'FULLTEXT'),
            'description'    => array('fields' => array('description'), 'type' => 'FULLTEXT')
        )
    ),
);

$updates310To310Sp1 = array(
    "UPDATE `" . DBPREFIX . "modules` SET `is_core` = '1' WHERE `name` = 'upload'",
    // fixing issue with protocol selection in settings
    "INSERT INTO `" . DBPREFIX . "settings` (`setid`, `setname`, `setvalue`, `setmodule`) VALUES
        (57, 'forceProtocolFrontend', 'none', 1),
        (58, 'forceProtocolBackend', 'none', 1)
        ON DUPLICATE KEY UPDATE `setname` = VALUES(`setname`)",
    'ALTER TABLE `' . DBPREFIX . 'module_crm_contacts` CONVERT TO CHARACTER SET `utf8`',
    array(
        'table' => DBPREFIX.'module_crm_contacts',
        'structure' => array(
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
            'notes'                  => array('type' => 'text', 'notnull' => false, 'after' => 'gender'),
            'industry_type'          => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'notes'),
            'contact_type'           => array('type' => 'TINYINT(2)', 'notnull' => false, 'after' => 'industry_type'),
            'user_account'           => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'contact_type'),
            'datasource'             => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'user_account'),
            'profile_picture'        => array('type' => 'VARCHAR(256)', 'after' => 'datasource'),
            'status'                 => array('type' => 'TINYINT(2)', 'notnull' => true, 'default' => '1', 'after' => 'profile_picture'),
            'added_date'             => array('type' => 'date', 'after' => 'status')
        ),
        'keys' => array(
            'contact_customer'       => array('fields' => array('contact_customer')),
            'customer_id'            => array('fields' => array('customer_id')),
            'customer_name'          => array('fields' => array('customer_name')),
            'contact_familyname'     => array('fields' => array('contact_familyname')),
            'contact_role'           => array('fields' => array('contact_role')),
            'customer_id_2'          => array('fields' => array('customer_id','customer_name','contact_familyname','contact_role','notes'), 'type' => 'FULLTEXT')
        ),
    ),
    array(
        'table' => DBPREFIX.'module_crm_currency',
        'structure' => array(
            'id'                     => array('type' => 'INT(10)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'name'                   => array('type' => 'VARCHAR(400)', 'after' => 'id'),
            'active'                 => array('type' => 'INT(1)', 'notnull' => true, 'default' => '1', 'after' => 'name'),
            'pos'                    => array('type' => 'INT(5)', 'notnull' => true, 'default' => '0', 'after' => 'active'),
            'hourly_rate'            => array('type' => 'text', 'after' => 'pos'),
            'default_currency'       => array('type' => 'TINYINT(1)', 'after' => 'hourly_rate')
        ),
        'keys' => array(
            'name'                   => array('fields' => array('name' => 333)),
            'name_2'                 => array('fields' => array('name'), 'type' => 'FULLTEXT')
        ),
    ),
    array(
        'table' => DBPREFIX.'module_crm_customer_comment',
        'structure' => array(
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
        'keys' => array(
            'customer_id'        => array('fields' => array('customer_id')),
            'comment'            => array('fields' => array('comment'), 'type' => 'FULLTEXT')
        ),
    ),
    array(
        'table' => DBPREFIX.'module_crm_customer_contact_address',
        'structure' => array(
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
        'keys' => array(
            'contact_id'         => array('fields' => array('contact_id')),
            'address'            => array('fields' => array('address')),
            'city'               => array('fields' => array('city')),
            'state'              => array('fields' => array('state')),
            'zip'                => array('fields' => array('zip')),
            'country'            => array('fields' => array('country')),
            'address_2'          => array('fields' => array('address','city','state','zip','country'), 'type' => 'FULLTEXT')
        ),
    ),
    array(
        'table' => DBPREFIX.'module_crm_customer_contact_emails',
        'structure' => array(
            'id'             => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'email'          => array('type' => 'VARCHAR(256)', 'after' => 'id'),
            'email_type'     => array('type' => 'TINYINT(4)', 'after' => 'email'),
            'is_primary'     => array('type' => 'ENUM(\'0\',\'1\')', 'notnull' => false, 'default' => '0', 'after' => 'email_type'),
            'contact_id'     => array('type' => 'INT(11)', 'after' => 'is_primary')
        ),
        'keys' => array(
            'contact_id'     => array('fields' => array('contact_id')),
            'email'          => array('fields' => array('email')),
            'email_2'        => array('fields' => array('email'), 'type' => 'FULLTEXT')
        ),
    ),
    array(
        'table' => DBPREFIX.'module_crm_customer_contact_phone',
        'structure' => array(
            'id'             => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'phone'          => array('type' => 'VARCHAR(256)', 'after' => 'id'),
            'phone_type'     => array('type' => 'TINYINT(4)', 'after' => 'phone'),
            'is_primary'     => array('type' => 'ENUM(\'0\',\'1\')', 'notnull' => false, 'default' => '0', 'after' => 'phone_type'),
            'contact_id'     => array('type' => 'INT(11)', 'after' => 'is_primary')
        ),
        'keys' => array(
            'contact_id'     => array('fields' => array('contact_id')),
            'phone'          => array('fields' => array('phone')),
            'phone_2'        => array('fields' => array('phone'), 'type' => 'FULLTEXT')
        ),
    ),
    array(
        'table' => DBPREFIX.'module_crm_customer_contact_social_network',
        'structure' => array(
            'id'             => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'url'            => array('type' => 'VARCHAR(256)', 'after' => 'id'),
            'url_profile'    => array('type' => 'TINYINT(4)', 'after' => 'url'),
            'is_primary'     => array('type' => 'ENUM(\'0\',\'1\')', 'notnull' => false, 'default' => '0', 'after' => 'url_profile'),
            'contact_id'     => array('type' => 'INT(11)', 'after' => 'is_primary')
        ),
        'keys' => array(
            'contact_id'     => array('fields' => array('contact_id')),
            'url'            => array('fields' => array('url')),
            'url_2'          => array('fields' => array('url'), 'type' => 'FULLTEXT')
        ),
    ),
    array(
        'table' => DBPREFIX.'module_crm_customer_contact_websites',
        'structure' => array(
            'id'             => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'url'            => array('type' => 'VARCHAR(256)', 'after' => 'id'),
            'url_type'       => array('type' => 'TINYINT(4)', 'after' => 'url'),
            'url_profile'    => array('type' => 'TINYINT(4)', 'after' => 'url_type'),
            'is_primary'     => array('type' => 'ENUM(\'0\',\'1\')', 'notnull' => false, 'default' => '0', 'after' => 'url_profile'),
            'contact_id'     => array('type' => 'INT(11)', 'after' => 'is_primary')
        ),
        'keys' => array(
            'contact_id'     => array('fields' => array('contact_id')),
            'url'            => array('fields' => array('url')),
            'url_2'          => array('fields' => array('url'), 'type' => 'FULLTEXT')
        ),
    ),
    array(
        'table' => DBPREFIX.'module_crm_customer_types',
        'structure' => array(
            'id'             => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'label'          => array('type' => 'VARCHAR(250)', 'after' => 'id'),
            'hourly_rate'    => array('type' => 'VARCHAR(256)', 'after' => 'label'),
            'active'         => array('type' => 'INT(1)', 'after' => 'hourly_rate'),
            'pos'            => array('type' => 'INT(10)', 'notnull' => true, 'default' => '0', 'after' => 'active'),
            'default'        => array('type' => 'TINYINT(2)', 'notnull' => true, 'default' => '0', 'after' => 'pos')
        ),
        'keys' => array(
            'label'          => array('fields' => array('label')),
            'label_2'        => array('fields' => array('label'), 'type' => 'FULLTEXT')
        ),
    ),
    array(
        'table' => DBPREFIX.'module_crm_industry_type_local',
        'structure' => array(
            'entry_id'       => array('type' => 'INT(11)'),
            'lang_id'        => array('type' => 'INT(11)', 'after' => 'entry_id'),
            'value'          => array('type' => 'VARCHAR(256)', 'after' => 'lang_id')
        ),
        'keys' => array(
            'entry_id'       => array('fields' => array('entry_id')),
            'value'          => array('fields' => array('value')),
            'value_2'        => array('fields' => array('value'), 'type' => 'FULLTEXT')
        ),
    ),
    array(
        'table' => DBPREFIX.'module_crm_membership_local',
        'structure' => array(
            'entry_id'       => array('type' => 'INT(11)'),
            'lang_id'        => array('type' => 'INT(11)', 'after' => 'entry_id'),
            'value'          => array('type' => 'VARCHAR(256)', 'after' => 'lang_id')
        ),
        'keys' => array(
            'entry_id'       => array('fields' => array('entry_id')),
            'value'          => array('fields' => array('value')),
            'value_2'        => array('fields' => array('value'), 'type' => 'FULLTEXT')
        ),
    ),
    array(
        'table' => DBPREFIX.'module_crm_notes',
        'structure' => array(
            'id'                 => array('type' => 'INT(1)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'name'               => array('type' => 'VARCHAR(255)', 'after' => 'id'),
            'status'             => array('type' => 'TINYINT(1)', 'after' => 'name'),
            'icon'               => array('type' => 'VARCHAR(255)', 'after' => 'status'),
            'pos'                => array('type' => 'INT(1)', 'after' => 'icon'),
            'system_defined'     => array('type' => 'TINYINT(2)', 'notnull' => true, 'default' => '0', 'after' => 'pos')
        ),
        'keys' => array(
            'name'               => array('fields' => array('name')),
            'name_2'             => array('fields' => array('name'), 'type' => 'FULLTEXT')
        ),
    ),
    array(
        'table' => DBPREFIX.'module_crm_task_types',
        'structure' => array(
            'id'                 => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'name'               => array('type' => 'VARCHAR(256)', 'after' => 'id'),
            'status'             => array('type' => 'TINYINT(1)', 'after' => 'name'),
            'sorting'            => array('type' => 'INT(11)', 'after' => 'status'),
            'description'        => array('type' => 'text', 'after' => 'sorting'),
            'icon'               => array('type' => 'VARCHAR(255)', 'after' => 'description'),
            'system_defined'     => array('type' => 'TINYINT(4)', 'after' => 'icon')
        ),
        'keys' => array(
            'name'               => array('fields' => array('name')),
            'name_2'             => array('fields' => array('name'), 'type' => 'FULLTEXT')
        ),
    ),
    array(
        'table' => DBPREFIX.'languages',
        'structure' => array(
            'id'                     => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'lang'                   => array('type' => 'VARCHAR(5)', 'notnull' => true, 'default' => '', 'after' => 'id'),
            'name'                   => array('type' => 'VARCHAR(250)', 'notnull' => true, 'default' => '', 'after' => 'lang'),
            'charset'                => array('type' => 'VARCHAR(20)', 'notnull' => true, 'default' => 'iso-8859-1', 'after' => 'name'),
            'themesid'               => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '1', 'after' => 'charset'),
            'print_themes_id'        => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '1', 'after' => 'themesid'),
            'pdf_themes_id'          => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'print_themes_id'),
            'frontend'               => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'pdf_themes_id'),
            'backend'                => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'frontend'),
            'is_default'             => array('type' => 'SET(\'true\',\'false\')', 'notnull' => true, 'default' => 'false', 'after' => 'backend'),
            'mobile_themes_id'       => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'is_default'),
            'fallback'               => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => false, 'default' => '0', 'after' => 'mobile_themes_id'),
            'app_themes_id'          => array('type' => 'INT(2)', 'after' => 'fallback')
        ),
        'keys' => array(
            'lang'                   => array('fields' => array('lang'), 'type' => 'UNIQUE'),
            'defaultstatus'          => array('fields' => array('is_default')),
            'name'                   => array('fields' => array('name')),
            'name_2'                 => array('fields' => array('name'), 'type' => 'FULLTEXT')
        ),
    ),
);

$updates310Sp1To310Sp2 = array(
    '
        INSERT IGNORE INTO `'.DBPREFIX.'settings` (`setid`, `setname`, `setvalue`, `setmodule`)
        VALUES (119, "cacheUserCache", "off", 1)
    ',
    '
        INSERT IGNORE INTO `'.DBPREFIX.'settings` (`setid`, `setname`, `setvalue`, `setmodule`)
        VALUES (120, "cacheOPCache", "off", 1)
    ',
    '
        INSERT IGNORE INTO `'.DBPREFIX.'settings` (`setid`, `setname`, `setvalue`, `setmodule`)
        VALUES (121, "cacheUserCacheMemcacheConfig", "{\"ip\":\"127.0.0.1\",\"port\":11211}", 1)
    ',
    '
        INSERT IGNORE INTO `'.DBPREFIX.'settings` (`setid`, `setname`, `setvalue`, `setmodule`)
        VALUES (122, "cacheProxyCacheVarnishConfig", "{\"ip\":\"127.0.0.1\",\"port\":8080}", 1)
    ',
    'INSERT IGNORE INTO `'.DBPREFIX.'settings` (`setid`, `setname`, `setvalue`, `setmodule`) VALUES (123,"cacheOpStatus","off",1)',
    'INSERT IGNORE INTO `'.DBPREFIX.'settings` (`setid`, `setname`, `setvalue`, `setmodule`) VALUES (124,"cacheDbStatus","off",1)',
    'INSERT IGNORE INTO `'.DBPREFIX.'settings` (`setid`, `setname`, `setvalue`, `setmodule`) VALUES (125,"cacheVarnishStatus","off",1)',
    array(
        'table' => DBPREFIX.'content_page',
        'structure' => array(
            'id'                                 => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'node_id'                            => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'id'),
            'nodeIdShadowed'                     => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'node_id'),
            'lang'                               => array('type' => 'INT(11)', 'after' => 'nodeIdShadowed'),
            'type'                               => array('type' => 'VARCHAR(16)', 'after' => 'lang'),
            'caching'                            => array('type' => 'TINYINT(1)', 'after' => 'type'),
            'updatedAt'                          => array('type' => 'timestamp', 'notnull' => false, 'after' => 'caching'),
            'updatedBy'                          => array('type' => 'CHAR(40)', 'after' => 'updatedAt'),
            'title'                              => array('type' => 'VARCHAR(255)', 'after' => 'updatedBy'),
            'linkTarget'                         => array('type' => 'VARCHAR(16)', 'notnull' => false, 'after' => 'title'),
            'contentTitle'                       => array('type' => 'VARCHAR(255)', 'after' => 'linkTarget'),
            'slug'                               => array('type' => 'VARCHAR(255)', 'after' => 'contentTitle'),
            'content'                            => array('type' => 'longtext', 'after' => 'slug'),
            'sourceMode'                         => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'content'),
            'customContent'                      => array('type' => 'VARCHAR(64)', 'notnull' => false, 'after' => 'sourceMode'),
            'useCustomContentForAllChannels'     => array('type' => 'INT(2)', 'notnull' => false, 'after' => 'customContent'),
            'cssName'                            => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'useCustomContentForAllChannels'),
            'cssNavName'                         => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'cssName'),
            'skin'                               => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'cssNavName'),
            'useSkinForAllChannels'              => array('type' => 'INT(2)', 'notnull' => false, 'after' => 'skin'),
            'metatitle'                          => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'useSkinForAllChannels'),
            'metadesc'                           => array('type' => 'text', 'after' => 'metatitle'),
            'metakeys'                           => array('type' => 'text', 'after' => 'metadesc'),
            'metarobots'                         => array('type' => 'VARCHAR(7)', 'notnull' => false, 'after' => 'metakeys'),
            'start'                              => array('type' => 'timestamp', 'notnull' => false, 'after' => 'metarobots'),
            'end'                                => array('type' => 'timestamp', 'notnull' => false, 'after' => 'start'),
            'editingStatus'                      => array('type' => 'VARCHAR(16)', 'after' => 'end'),
            'protection'                         => array('type' => 'INT(11)', 'after' => 'editingStatus'),
            'frontendAccessId'                   => array('type' => 'INT(11)', 'after' => 'protection'),
            'backendAccessId'                    => array('type' => 'INT(11)', 'after' => 'frontendAccessId'),
            'display'                            => array('type' => 'TINYINT(1)', 'after' => 'backendAccessId'),
            'active'                             => array('type' => 'TINYINT(1)', 'after' => 'display'),
            'target'                             => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'active'),
            'module'                             => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'target'),
            'cmd'                                => array('type' => 'VARCHAR(50)', 'notnull' => true, 'default' => '', 'after' => 'module'),
        ),
        'keys' => array(
            'node_id'                            => array('fields' => array('node_id','lang'), 'type' => 'UNIQUE'),
            'IDX_D8E86F54460D9FD7'               => array('fields' => array('node_id'))
        ),
        'engine' => 'InnoDB',
    ),
    // start migration of new event table structure
    array(
        'table' => DBPREFIX.'module_calendar_event',
        'structure' => array(
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
        'keys' => array(
            'fk_contrexx_module_calendar_notes_contrexx_module_calendar_ca1' => array('fields' => array('catid'))
        ),
    ),
    // backup start and end time as timestamp
    'UPDATE `'.DBPREFIX.'module_calendar_event` SET `startdate_timestamp` = FROM_UNIXTIME(`startdate`), `enddate_timestamp` = FROM_UNIXTIME(`enddate`)',
    array(
        'table' => DBPREFIX.'module_calendar_event',
        'structure' => array(
            'id'                                 => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'type'                               => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0', 'after' => 'id'),
            'startdate'                          => array('type' => 'timestamp', 'notnull' => true, 'default' => '0000-00-00 00:00:00', 'after' => 'type'),
            'enddate'                            => array('type' => 'timestamp', 'notnull' => true, 'default' => '0000-00-00 00:00:00', 'after' => 'startdate'),
            'startdate_timestamp'                => array('type' => 'timestamp', 'notnull' => true, 'default' => '0000-00-00 00:00:00', 'after' => 'enddate'),
            'enddate_timestamp'                  => array('type' => 'timestamp', 'notnull' => true, 'default' => '0000-00-00 00:00:00', 'after' => 'startdate_timestamp'),
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
        'keys' => array(
            'fk_contrexx_module_calendar_notes_contrexx_module_calendar_ca1' => array('fields' => array('catid'))
        ),
    ),
    // remove the temporary columns
    'UPDATE `'.DBPREFIX.'module_calendar_event` SET `startdate` = `startdate_timestamp`, `enddate` = `enddate_timestamp`',
    'ALTER TABLE `'.DBPREFIX.'module_calendar_event` DROP COLUMN `startdate_timestamp`',
    'ALTER TABLE `'.DBPREFIX.'module_calendar_event` DROP COLUMN `enddate_timestamp`',
    // migrate series pattern
    'UPDATE `'.DBPREFIX.'module_calendar_event` SET `series_pattern_end_date` = FROM_UNIXTIME(`series_pattern_end`)',
    array(
        'table' => DBPREFIX.'modules',
        'structure' => array(
            'id'                         => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => false),
            'name'                       => array('type' => 'VARCHAR(250)', 'notnull' => true, 'default' => '', 'after' => 'id'),
            'distributor'                => array('type' => 'CHAR(50)', 'after' => 'name'),
            'description_variable'       => array('type' => 'VARCHAR(50)', 'notnull' => true, 'default' => '', 'after' => 'distributor'),
            'status'                     => array('type' => 'SET(\'y\',\'n\')', 'notnull' => true, 'default' => 'n', 'after' => 'description_variable'),
            'is_required'                => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'status'),
            'is_core'                    => array('type' => 'TINYINT(4)', 'notnull' => true, 'default' => '0', 'after' => 'is_required'),
            'is_active'                  => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'is_core'),
            'is_licensed'                => array('type' => 'TINYINT(1)', 'after' => 'is_active')
        ),
        'keys' => array(
            'id'                         => array('fields' => array('id'), 'type' => 'UNIQUE')
        ),
    ),
    '
        INSERT IGNORE INTO `'.DBPREFIX.'core_setting` (`section`, `name`, `group`, `type`, `value`, `values`, `ord`) VALUES ("shop","payment_lsv_active","config","text","1","",18)
    ',
    '
        INSERT IGNORE INTO `'.DBPREFIX.'core_setting` (`section`, `name`, `group`, `type`, `value`, `values`, `ord`) VALUES ("shop","paymill_active","config","text","1","",3)
    ',
    '
        INSERT IGNORE INTO `'.DBPREFIX.'core_setting` (`section`, `name`, `group`, `type`, `value`, `values`, `ord`) VALUES ("shop","paymill_live_private_key","config","text","","",0)
    ',
    '
        INSERT IGNORE INTO `'.DBPREFIX.'core_setting` (`section`, `name`, `group`, `type`, `value`, `values`, `ord`) VALUES ("shop","paymill_live_public_key","config","text","","",0)
    ',
    '
        INSERT IGNORE INTO `'.DBPREFIX.'core_setting` (`section`, `name`, `group`, `type`, `value`, `values`, `ord`) VALUES ("shop","paymill_live_public_key","config","text","","",0)
    ',
    '
        INSERT IGNORE INTO `'.DBPREFIX.'core_setting` (`section`, `name`, `group`, `type`, `value`, `values`, `ord`) VALUES ("shop","paymill_test_private_key","config","text","","",2)
    ',
    '
        INSERT IGNORE INTO `'.DBPREFIX.'core_setting` (`section`, `name`, `group`, `type`, `value`, `values`, `ord`) VALUES ("shop","paymill_test_public_key","config","text","","",16)
    ',
    '
        INSERT IGNORE INTO `'.DBPREFIX.'core_setting` (`section`, `name`, `group`, `type`, `value`, `values`, `ord`) VALUES ("shop","paymill_use_test_account","config","text","0","",15)
    ',
    '
        INSERT IGNORE INTO `'.DBPREFIX.'core_setting` (`section`, `name`, `group`, `type`, `value`, `values`, `ord`) VALUES (\'shop\',\'orderitems_amount_min\',\'config\',\'text\',\'0\',\'\',0);
    ',
    '
        UPDATE `'.DBPREFIX.'core_text` SET `text` = "VISA, Mastercard (Saferpay)" WHERE `key` = "payment_name" AND `section` = "shop" AND `text` LIKE "%PostFinance%"
    ',
    'INSERT IGNORE INTO `'.DBPREFIX.'core_setting` (`section`, `name`, `group`, `type`, `value`, `values`, `ord`) VALUES (\'crm\',\'numof_mailtemplate_per_page_backend\',\'config\',\'text\',\'25\',\'\',1001)',
    'INSERT IGNORE INTO `'.DBPREFIX.'core_setting` (`section`, `name`, `group`, `type`, `value`, `values`, `ord`) VALUES (\'filesharing\',\'permission\',\'config\',\'text\',\'off\',\'\',0)',
    'INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES (16,1,"shop","payment_name","Kreditkarte (Paymill)")',
    'INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES (16,2,"shop","payment_name","paymill")',
    'INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES (17,1,"shop","payment_name","ELV (Paymill)")',
    'INSERT IGNORE INTO `'.DBPREFIX.'core_text` (`id`, `lang_id`, `section`, `key`, `text`) VALUES (18,1,"shop","payment_name","IBAN/BIC (Paymill)")',
    'INSERT IGNORE INTO `'.DBPREFIX.'module_shop_payment_processors` (`id`, `type`, `name`, `description`, `company_url`, `status`, `picture`) VALUES (12,"external","paymill_cc","","https://www.paymill.com",1,"")',
    'INSERT IGNORE INTO `'.DBPREFIX.'module_shop_payment_processors` (`id`, `type`, `name`, `description`, `company_url`, `status`, `picture`) VALUES (13,"external","paymill_elv","","https://www.paymill.com",1,"")',
    'INSERT IGNORE INTO `'.DBPREFIX.'module_shop_payment_processors` (`id`, `type`, `name`, `description`, `company_url`, `status`, `picture`) VALUES (14,"external","paymill_iban","","https://www.paymill.com",1,"")',
    'INSERT IGNORE INTO `'.DBPREFIX.'module_shop_rel_payment` (`zone_id`, `payment_id`) VALUES (1,16)',
    'INSERT IGNORE INTO `'.DBPREFIX.'module_shop_rel_payment` (`zone_id`, `payment_id`) VALUES (1,17)',
    'INSERT IGNORE INTO `'.DBPREFIX.'module_shop_rel_payment` (`zone_id`, `payment_id`) VALUES (1,18)',
    'UPDATE `'.DBPREFIX.'module_calendar_settings` SET `order` = 10 WHERE `order` = 9',
    'UPDATE `'.DBPREFIX.'module_calendar_settings` SET `order` = 9 WHERE `order` = 8',
    'INSERT IGNORE INTO `'.DBPREFIX.'module_calendar_settings` (`id`, `section_id`, `name`, `title`, `value`, `info`, `type`, `options`, `special`, `order`) VALUES (20,19,"placeData","TXT_CALENDAR_PLACE_DATA","1","TXT_CALENDAR_PLACE_DATA_STATUS_INFO",3,"TXT_CALENDAR_PLACE_DATA_DEFAULT,TXT_CALENDAR_PLACE_DATA_FROM_MEDIADIR,TXT_CALENDAR_PLACE_DATA_FROM_BOTH","",7)',
    'INSERT IGNORE INTO `'.DBPREFIX.'module_calendar_settings` (`id`, `section_id`, `name`, `title`, `value`, `info`, `type`, `options`, `special`, `order`) VALUES (62,19,"placeDataForm","","0","",5,"","getPlaceDataDorpdown",8)',
    'INSERT IGNORE INTO `'.DBPREFIX.'module_calendar_settings` (`id`, `section_id`, `name`, `title`, `value`, `info`, `type`, `options`, `special`, `order`) VALUES (63,19,"placeDataHost","TXT_CALENDAR_PLACE_DATA_HOST","1","TXT_CALENDAR_PLACE_DATA_STATUS_INFO",3,"TXT_CALENDAR_PLACE_DATA_DEFAULT,TXT_CALENDAR_PLACE_DATA_FROM_MEDIADIR,TXT_CALENDAR_PLACE_DATA_FROM_BOTH","",9)',
    'INSERT IGNORE INTO `'.DBPREFIX.'module_calendar_settings` (`id`, `section_id`, `name`, `title`, `value`, `info`, `type`, `options`, `special`, `order`) VALUES (64,19,"placeDataHostForm","","0","",5,"","getPlaceDataDorpdown",10)',
    'INSERT IGNORE INTO `'.DBPREFIX.'module_calendar_settings_section` (`id`, `parent`, `order`, `name`, `title`) VALUES (19,1,4,"location_host","TXT_CALENDAR_EVENT_LOCATION")',
    'INSERT IGNORE INTO `'.DBPREFIX.'module_news_settings` (`name`, `value`) VALUES ("recent_news_message_limit","5")',
    'UPDATE `'.DBPREFIX.'module_mediadir_inputfield_types` set `active`=1, `comment`=\'\' WHERE `name` = \'wysiwyg\'',
    array(
        'table' => DBPREFIX.'module_mediadir_inputfields',
        'structure' => array(
            'id'                 => array('type' => 'INT(10)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'form'               => array('type' => 'INT(7)', 'after' => 'id'),
            'type'               => array('type' => 'INT(10)', 'after' => 'form'),
            'verification'       => array('type' => 'INT(10)', 'after' => 'type'),
            'search'             => array('type' => 'INT(10)', 'after' => 'verification'),
            'required'           => array('type' => 'INT(10)', 'after' => 'search'),
            'order'              => array('type' => 'INT(10)', 'after' => 'required'),
            'show_in'            => array('type' => 'INT(10)', 'after' => 'order'),
            'context_type'       => array('type' => 'ENUM(\'none\',\'title\',\'address\',\'zip\',\'city\',\'country\')', 'after' => 'show_in')
        ),
    ),
    array(
        'table' => DBPREFIX.'module_shop_order_attributes',
        'structure' => array(
            'id'                 => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
            'item_id'            => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'id'),
            'attribute_name'     => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'item_id'),
            'option_name'        => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'attribute_name'),
            'price'              => array('type' => 'DECIMAL(9,2)', 'unsigned' => false, 'notnull' => true, 'default' => '0.00', 'after' => 'option_name')
        ),
        'keys' => array(
            'item_id'            => array('fields' => array('item_id'))
        )
    ),
);

$updatesRc1To310Sp2    = array_merge($updatesRc1ToRc2, $updatesRc2ToStable, $updatesStableToHotfix, $updatesHotfixToSp1, $updatesSp1ToSp2, $updatesSp2ToSp3, $updatesSp3ToSp4, $updatesSp4To310, $updates310To310Sp1, $updates310Sp1To310Sp2);
$updatesRc2To310Sp2    = array_merge($updatesRc2ToStable, $updatesStableToHotfix, $updatesHotfixToSp1, $updatesSp1ToSp2, $updatesSp2ToSp3, $updatesSp3ToSp4, $updatesSp4To310, $updates310To310Sp1, $updates310Sp1To310Sp2);
$updatesStableTo310Sp2 = array_merge($updatesStableToHotfix, $updatesHotfixToSp1, $updatesSp1ToSp2, $updatesSp2ToSp3, $updatesSp3ToSp4, $updatesSp4To310, $updates310To310Sp1, $updates310Sp1To310Sp2);
$updatesHotfixTo310Sp2 = array_merge($updatesHotfixToSp1, $updatesSp1ToSp2, $updatesSp2ToSp3, $updatesSp3ToSp4, $updatesSp4To310, $updates310To310Sp1, $updates310Sp1To310Sp2);
$updatesSp1To310Sp2    = array_merge($updatesSp1ToSp2, $updatesSp2ToSp3, $updatesSp3ToSp4, $updatesSp4To310, $updates310To310Sp1, $updates310Sp1To310Sp2);
$updatesSp2To310Sp2    = array_merge($updatesSp2ToSp3, $updatesSp3ToSp4, $updatesSp4To310, $updates310To310Sp1, $updates310Sp1To310Sp2);
$updatesSp3To310Sp2    = array_merge($updatesSp3ToSp4, $updatesSp4To310, $updates310To310Sp1, $updates310Sp1To310Sp2);
$updatesSp4To310Sp2    = array_merge($updatesSp4To310, $updates310To310Sp1, $updates310Sp1To310Sp2);
$updates310To310Sp2    = array_merge($updates310To310Sp1, $updates310Sp1To310Sp2);
//$updates310Sp1To310Sp2 = $updates310Sp1To310Sp2;


if ($version == 'rc1') {
    $updates = $updatesRc1To310Sp2;
} elseif ($version == 'rc2') {
    $updates = $updatesRc2To310Sp2;
} elseif ($version == 'stable') {
    $updates = $updatesStableTo310Sp2;
} elseif ($version == 'hotfix') {
    $updates = $updatesHotfixTo310Sp2;
} elseif ($version == 'sp1') {
    $updates = $updatesSp1To310Sp2;
} elseif ($version == 'sp2') {
    $updates = $updatesSp2To310Sp2;
} elseif ($version == 'sp3') {
    $updates = $updatesSp3To310Sp2;
} elseif ($version == 'sp4') {
    $updates = $updatesSp4To310Sp2;
} elseif ($version == '310') {
    $updates = $updates310To310Sp2;
} else {
    $updates = $updates310Sp1To310Sp2;
}



/***************************************
 *
 * INSTALLING CRM AND FRONTEND EDITING BEFORE WE DO THE TABLE-UPDATES
 *
 **************************************/
if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.1.0')) {
    require_once(dirname(__FILE__).'/components/module/crm.php');
    $crmInstall = _crmInstall();
    if ($crmInstall) {
        // crm install returns an error
        return $crmInstall;
    }
    if (
        !\MailTemplate::store('crm', array(
            'key' => 'crm_user_account_created',
            'lang_id' => '1',
            'sender' => 'Ihr Firmenname',
            'from' => 'info@example.com',
            'to' => '[CRM_CONTACT_EMAIL]',
            'reply' => 'info@example.com',
            'cc' => '',
            'bcc' => '',
            'subject' => 'Ihr persönlicher Zugang',
            'message' => "Guten Tag,\r\n\r\nNachfolgend erhalten Sie Ihre persönlichen Zugansdaten zur Website http://www.example.com/\r\n\r\nBenutzername: [CRM_CONTACT_USERNAME]\r\nKennwort: [CRM_CONTACT_PASSWORD]",
            'message_html' => "<div>Guten Tag,<br />\r\n<br />\r\nNachfolgend erhalten Sie Ihre pers&ouml;nlichen Zugangsdaten zur Website <a href=\"http://www.example.com/\">http://www.example.com/</a><br />\r\n<br />\r\nBenutzername: [CRM_CONTACT_USERNAME]<br />\r\nKennwort: [CRM_CONTACT_PASSWORD]</div>",
            'html' => 'true',
            'protected' => 'true',
            'name' => 'Benachrichtigung über Benutzerkonto',
        )) ||
        !\MailTemplate::store('crm', array(
            'key' => 'crm_task_assigned',
            'lang_id' => '1',
            'sender' => 'Ihr Firmenname',
            'from' => 'info@example.com',
            'to' => '[CRM_ASSIGNED_USER_MAIL]',
            'reply' => 'info@example.com',
            'cc' => '',
            'bcc' => '',
            'subject' => 'Neue Aufgabe',
            'message' => "Der Mitarbeiter [CRM_TASK_CREATED_USER] hat eine neue Aufgabe erstellt und Ihnen zugewiesen: [CRM_TASK_URL]\r\n\r\nBeschreibung: [CRM_TASK_DESCRIPTION_TEXT_VERSION]\r\n\r\nFällig am: [CRM_TASK_DUE_DATE]\r\n",
            'message_html' => "<div style=\"padding:0px; margin:0px; font-family:Tahoma, sans-serif; font-size:14px; width:620px; color: #333;\">\r\n<div style=\"padding: 0px 20px; border:1px solid #e0e0e0; margin-bottom: 10px; width:618px;\">\r\n<h1 style=\"background-color: #e0e0e0;color: #3d4a6b;font-size: 18px;font-weight: normal;padding: 15px 20px;margin-top: 0 !important;margin-bottom: 0 !important;margin-left: -20px !important;margin-right: -20px !important;-webkit-margin-before: 0 !important;-webkit-margin-after: 0 !important;-webkit-margin-start: -20px !important;-webkit-margin-end: -20px !important;\">Neue Aufgabe wurde Ihnen zugewiesen</h1>\r\n\r\n<p style=\"margin-top: 20px;word-wrap: break-word !important;\">Der Mitarbeiter [CRM_TASK_CREATED_USER] hat eine neue Aufgabe erstellt und Ihnen zugewiesen: [CRM_TASK_LINK]</p>\r\n\r\n<p style=\"margin-top: 20px;word-wrap: break-word !important;\">Beschreibung: [CRM_TASK_DESCRIPTION_HTML_VERSION]<br />\r\nF&auml;llig am: [CRM_TASK_DUE_DATE]</p>\r\n</div>\r\n</div>",
            'html' => 'true',
            'protected' => 'true',
            'name' => 'Neue Aufgabe',
        )) ||
        !\MailTemplate::store('crm', array(
            'key' => 'crm_notify_staff_on_contact_added',
            'lang_id' => '1',
            'sender' => 'Ihr Firmenname',
            'from' => 'info@example.com',
            'to' => '[CRM_ASSIGNED_USER_MAIL]',
            'reply' => 'info@example.com',
            'cc' => '',
            'bcc' => '',
            'subject' => 'Neuer Kontakt erfasst',
            'message' => "Im CRM wurde ein neuer Kontakt erfasst: [CRM_CONTACT_DETAILS_URL]",
            'message_html' => "<div style=\"padding:0px; margin:0px; font-family:Tahoma, sans-serif; font-size:14px; width:620px; color: #333;\">\r\n<div style=\"padding: 0px 20px; border:1px solid #e0e0e0; margin-bottom: 10px; width:618px;\">\r\n<h1 style=\"background-color: #e0e0e0;color: #3d4a6b;font-size: 18px;font-weight: normal;padding: 15px 20px;margin-top: 0 !important;margin-bottom: 0 !important;margin-left: -20px !important;margin-right: -20px !important;-webkit-margin-before: 0 !important;-webkit-margin-after: 0 !important;-webkit-margin-start: -20px !important;-webkit-margin-end: -20px !important;\">Neuer Kontakt im CRM</h1>\r\n\r\n<p style=\"margin-top: 20px;word-wrap: break-word !important;\">Neuer Kontakt: [CRM_CONTACT_DETAILS_LINK].</p>\r\n</div>\r\n</div>\r\n",
            'html' => 'true',
            'protected' => 'true',
            'name' => 'Benachrichtigung an Mitarbeiter über neue Kontakte',
        ))
    ) {
        return false;
    }
}


foreach ($updates as $update) {
    if (is_array($update)) {
        try {
            \Cx\Lib\UpdateUtil::table(
                $update['table'],
                $update['structure'],
                $update['keys'],
                isset($update['engine']) ? $update['engine'] : 'MyISAM'
            );
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
    } else {
        $result = \Cx\Lib\UpdateUtil::sql($update);
        if (!$result) {
            setUpdateMsg('Update failed: ' . contrexx_raw2xhtml($update));
            return false;
        }
    }
}


if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.1.0')) {
    // install crm
    require_once(dirname(__FILE__).'/components/core/modules.php');
    $crmModuleInfo = getModuleInfo('crm');
    try {
        \Cx\Lib\UpdateUtil::sql("INSERT IGNORE INTO ".DBPREFIX."modules ( `id` , `name` , `description_variable` , `status` , `is_required` , `is_core` , `distributor` ) VALUES ( ".$crmModuleInfo['id']." , '".$crmModuleInfo['name']."', '".$crmModuleInfo['description_variable']."', '".$crmModuleInfo['status']."', '".$crmModuleInfo['is_required']."', '".$crmModuleInfo['is_core']."', 'Comvation AG') ON DUPLICATE KEY UPDATE `id` = `id`");
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }
    \DBG::log('installing crm module');

    // install frontend editing
    $frontendEditingModuleInfo = getModuleInfo('FrontendEditing');
    try {
        \Cx\Lib\UpdateUtil::sql("INSERT IGNORE INTO ".DBPREFIX."modules ( `id` , `name` , `description_variable` , `status` , `is_required` , `is_core` , `distributor` ) VALUES ( ".$frontendEditingModuleInfo['id']." , '".$frontendEditingModuleInfo['name']."', '".$frontendEditingModuleInfo['description_variable']."', '".$frontendEditingModuleInfo['status']."', '".$frontendEditingModuleInfo['is_required']."', '".$frontendEditingModuleInfo['is_core']."', 'Comvation AG') ON DUPLICATE KEY UPDATE `id` = `id`");
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }
}

// Add primary keys
$result = \Cx\Lib\UpdateUtil::sql('SHOW KEYS FROM `' . DBPREFIX . 'access_group_dynamic_ids`');
if ($result->EOF) {
    \Cx\Lib\UpdateUtil::sql('ALTER IGNORE TABLE `' . DBPREFIX . 'access_group_dynamic_ids` ADD PRIMARY KEY ( `access_id` , `group_id` )');
}
$result = \Cx\Lib\UpdateUtil::sql('SHOW KEYS FROM `' . DBPREFIX . 'access_group_static_ids`');
if ($result->EOF) {
    \Cx\Lib\UpdateUtil::sql('ALTER IGNORE TABLE `' . DBPREFIX . 'access_group_static_ids` ADD PRIMARY KEY ( `access_id` , `group_id` )');
}

// reimport module repository
\Cx\Lib\UpdateUtil::sql('TRUNCATE TABLE `'.DBPREFIX.'module_repository`');
$sqlQuery = '';
$fp = @fopen($documentRoot.'/installer/data/contrexx_dump_data.sql', 'r');
if ($fp !== false) {
    while (!feof($fp)) {
        $buffer = fgets($fp);
        if ((substr($buffer,0,1) != '#') && (substr($buffer,0,2) != '--')) {
            $sqlQuery .= $buffer;
            if (preg_match('/;[ \t\r\n]*$/', $buffer)) {
                $sqlQuery = preg_replace('/SET FOREIGN_KEY_CHECKS = 0;/', '', $sqlQuery);
                if (substr(trim($sqlQuery), 0, 40) != 'INSERT INTO `contrexx_module_repository`') {
                    $sqlQuery = '';
                    continue;
                }
                $sqlQuery = preg_replace('#`contrexx_module_repository`#', '`'.DBPREFIX.'module_repository`', $sqlQuery);
                $result = \Cx\Lib\UpdateUtil::sql($sqlQuery);
                if ($result === false) {
                    setUpdateMsg('Update failed: ' . contrexx_raw2xhtml($sqlQuery));
                    return false;
                }
                $sqlQuery = '';
            }
        }
    }
} else {
    die('Could not read data dump file!');
}

// add missing "remove page" log entries
$sqlQuery = '
    SELECT
        MAX(l1.version) as `version`,
        l1.object_id
    FROM
        `' . DBPREFIX . 'log_entry` AS l1
    WHERE
        l1.object_id NOT IN (
            SELECT
                id
            FROM
                `' . DBPREFIX . 'content_page`
        )
        AND l1.object_id NOT IN (
            SELECT
                l3.object_id
            FROM
                `' . DBPREFIX . 'log_entry` AS l3
            WHERE
                l3.action LIKE \'remove\'
        )
    GROUP BY
        l1.object_id
';
$result = \Cx\Lib\UpdateUtil::sql($sqlQuery);
if ($result === false) {
    // error, abort
    setUpdateMsg('Update failed: ' . contrexx_raw2xhtml($sqlQuery));
    return false;
}
while (!$result->EOF) {
    $sqlQuery = '
        INSERT INTO
            `' . DBPREFIX . 'log_entry`
            (
                `action`,
                `logged_at`,
                `version`,
                `object_id`,
                `object_class`,
                `data`,
                `username`
            )
        VALUES
            (
                \'remove\',
                NOW(),
                ' . ($result->fields['version'] + 1) . ',
                ' . $result->fields['object_id'] . ',
                \'Cx\\\\Core\\\\ContentManager\\\\Model\\\\Doctrine\\\\Entity\\\\Page\',
                \'N;\',
                \'' . $userData . '\'
            )
    ';
    $result2 = \Cx\Lib\UpdateUtil::sql($sqlQuery);
    if ($result2 === false) {
        // error, abort
        setUpdateMsg('Update failed: ' . contrexx_raw2xhtml($sqlQuery));
        return false;
    }
    $result->MoveNext();
}


// fix fallback pages
if ($version == 'rc1') {
    $em = \Env::get('em');
    $pageRepo = $em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');

    $fallbackPages = $pageRepo->findBy(array(
        'type' => \Cx\Core\ContentManager\Model\Entity\Page::TYPE_FALLBACK,
    ));

    foreach ($fallbackPages as $page) {
        $page->setModule($page->getModule());
        $page->setCmd($page->getCmd());
        $page->setUpdatedAtToNow();
        $em->persist($page);
    }
    $em->flush();
}

// update for sp3
if ($version == 'rc1' || $version == 'rc2'
    || $version == '3.0.0' || $version == '3.0.0.1'
    || $version == '3.0.1' || $version == '3.0.2') {

    // newsletter module
    // decode the urls of newsletter module
    try {
        $objResult = \Cx\Lib\UpdateUtil::sql('SELECT `id`, `url` FROM `'.DBPREFIX.'module_newsletter_email_link`');
        if ($objResult !== false && $objResult->RecordCount() > 0) {
            while (!$objResult->EOF) {
                \Cx\Lib\UpdateUtil::sql(
                    'UPDATE `'.DBPREFIX.'module_newsletter_email_link` SET `url` = ? WHERE `id` = ?',
                        array(html_entity_decode($objResult->fields['url'], ENT_QUOTES, CONTREXX_CHARSET), $objResult->fields['id'])
                );
                $objResult->MoveNext();
            }
        }
        $_SESSION['contrexx_update']['newsletter_links_decoded'] = true;
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    // shop
    $table_name = DBPREFIX.'module_shop_currencies';
    if (   \Cx\Lib\UpdateUtil::table_exist($table_name)
        && \Cx\Lib\UpdateUtil::column_exist($table_name, 'name')) {
        $query = "
            UPDATE `$table_name`
            SET sort_order = 0 WHERE sort_order IS NULL";
        \Cx\Lib\UpdateUtil::sql($query);
        // Currencies table fields
        \Cx\Lib\UpdateUtil::table($table_name,
            array(
                'id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'code' => array('type' => 'CHAR(3)', 'notnull' => true, 'default' => ''),
                'symbol' => array('type' => 'VARCHAR(20)', 'notnull' => true, 'default' => ''),
                'name' => array('type' => 'VARCHAR(50)', 'notnull' => true, 'default' => ''),
                'rate' => array('type' => 'DECIMAL(10,4)', 'unsigned' => true, 'notnull' => true, 'default' => '1.0000'),
                'sort_order' => array('type' => 'INT(5)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                'status' => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '1'),
                'is_default' => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
            )
        );
    }
    $table_name = DBPREFIX.'module_shop_payment_processors';
    if (Cx\Lib\UpdateUtil::table_exist($table_name)) {
        \Cx\Lib\UpdateUtil::table($table_name,
            array(
                'id' => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'type' => array('type' => 'ENUM(\'internal\',\'external\')', 'notnull' => true, 'default' => 'internal'),
                'name' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => ''),
                'description' => array('type' => 'TEXT'),
                'company_url' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => ''),
                'status' => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '1'),
                'picture' => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => ''),
            )
        );
    }
}

if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.1.0')) {
    // contact core_module
    // update the content pages
    $em = \Env::get('em');
    $cl = \Env::get('ClassLoader');
    $cl->loadFile(ASCMS_CORE_MODULE_PATH . '/contact/admin.class.php');
    $pageRepo = $em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
    $Contact = new \ContactManager();
    $Contact->initContactForms();

    foreach ($Contact->arrForms as $id => $form) {
        foreach ($form['lang'] as $langId => $lang) {
            if ($lang['is_active'] == true) {
                $page = $pageRepo->findOneByModuleCmdLang('contact', $id, $langId);
                if ($page) {
                    $page->setContent($Contact->_getSourceCode($id, $langId));
                    $page->setUpdatedAtToNow();
                    $em->persist($page);
                }
            }
        }
    }
    $em->flush();
}

if (   !$objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.0.0')
    && $objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.0.3')
) {
    try {
        // replace sigma template block in discounts page
        \Cx\Lib\UpdateUtil::migrateContentPageUsingRegex(
            array('module'=>'shop', 'cmd' => 'discounts'),
            '/<!--\s+(BEGIN|END)\s+shopProductRow1\s+-->/', '<!-- $1 shopProductRow -->',
            array('content'), '3.0.3'
        );


        // add product options to product-listing and discounts page
        $search = array(
            '/.*\{SHOP_PRODUCT_DESCRIPTION\}.*/ms',
        );
        $callback = function($matches) {
            $htmlProductOptions = <<<HTML

                    <!-- BEGIN shopProductOptionsRow -->
                    <div class="shop_options">
                        {SHOP_PRODUCT_OPTIONS_TITLE}<br />
                        <div id="product_options_layer{SHOP_PRODUCT_ID}" style="display: none;">
                            <div class="shop_options_click">
                                <!-- BEGIN shopProductOptionsValuesRow -->
                                <strong>
                                    {SHOP_PRODUCT_OPTIONS_NAME}
                                    <!-- BEGIN product_attribute_mandatory -->
                                    <span class="mandatory">&nbsp;*</span>
                                    <!-- END product_attribute_mandatory -->
                                </strong><br />
                                {SHOP_PRODCUT_OPTION}
                                <!-- END shopProductOptionsValuesRow -->
                            </div>
                        </div>
                    </div>
                    <!-- END shopProductOptionsRow -->

HTML;
            if (!preg_match('/<!--\s+BEGIN\s+shopProductOptionsRow\s+-->.*<!--\s+END\s+shopProductOptionsRow\s+-->/ms', $matches[0])) {
                $placeholder = '{SHOP_PRODUCT_DESCRIPTION}';
                return str_replace($placeholder, $placeholder.$htmlProductOptions, $matches[0]);
            } else {
                return $matches[0];
            }
        };
        \Cx\Lib\UpdateUtil::migrateContentPageUsingRegexCallback(array('module' => 'shop', 'cmd' => ''), $search, $callback, array('content'), '3.0.3');
        \Cx\Lib\UpdateUtil::migrateContentPageUsingRegexCallback(array('module' => 'shop', 'cmd' => 'discounts'), $search, $callback, array('content'), '3.0.3');


        // add needed placeholders
        // this adds the missing placeholders [[SHOP_AGB]], [[SHOP_CANCELLATION_TERMS_CHECKED]]
        $search = array(
        '/(<input[^>]+name=")(agb|cancellation_terms)(")([^>]*>)/ms',
        );
        $callback = function($matches) {
            switch ($matches[2]) {
                case 'agb':
                    $placeholder = "{SHOP_AGB}";
                    break;
                case 'cancellation_terms':
                    $placeholder = "{SHOP_CANCELLATION_TERMS_CHECKED}";
                    break;
            }
            if (strpos($matches[1].$matches[4], $placeholder) === false) {
                return $matches[1].$matches[2].$matches[3].' '.$placeholder.' '.$matches[4];
            } else {
                return $matches[0];
            }
        };
        \Cx\Lib\UpdateUtil::migrateContentPageUsingRegexCallback(array('module' => 'shop', 'cmd' => 'payment'), $search, $callback, array('content'), '3.0.3');
        \Cx\Lib\UpdateUtil::setSourceModeOnContentPage(array('module' => 'shop', 'cmd' => 'payment'), '3.0.3');

        // replace comments placeholder with a sigma block , news module
        $search = array(
            '/.*\{NEWS_COUNT_COMMENTS\}.*/ms',
        );
        $callback = function($matches) {
            $placeholder = '{NEWS_COUNT_COMMENTS}';
            $htmlCode = '<!-- BEGIN news_comments_count -->'.$placeholder.'<!-- END news_comments_count -->';
            if (!preg_match('/<!--\s+BEGIN\s+news_comments_count\s+-->.*<!--\s+END\s+news_comments_count\s+-->/ms', $matches[0])) {
                return str_replace($placeholder, $htmlCode, $matches[0]);
            } else {
                return $matches[0];
            }
        };
        \Cx\Lib\UpdateUtil::migrateContentPageUsingRegexCallback(array('module' => 'news', 'cmd' => ''), $search, $callback, array('content'), '3.0.3');
        \Cx\Lib\UpdateUtil::migrateContentPageUsingRegexCallback(array('module' => 'news', 'cmd' => 'details'), $search, $callback, array('content'), '3.0.3');

        // remove the script tag at the beginning of the gallery page
        \Cx\Lib\UpdateUtil::migrateContentPageUsingRegex(array('module' => 'gallery'), '/^\s*(<script[^>]+>.+?Shadowbox.+?<\/script>)+/sm', '', array('content'), '3.0.3');
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }
}

try {
    // migrate content page to version 3.0.1
    $search = array(
        '/(.*)/ms',
    );
    $callback = function($matches) {
        $content = $matches[1];
        if (empty($content)) {
            return $content;
        }

        // fix duplicated social networks blocks
        if (preg_match('/<!--\s+BEGIN\s+access_social_networks\s+-->.*<!--\s+BEGIN\s+access_social_networks\s+-->/ms', $content)) {
            $content = preg_replace('/<br\s+\/><br\s+\/><!--\s+BEGIN\s+access_social_networks\s+-->.*?<!--\s+END\s+access_social_networks\s+-->/ms', '', $content);
        }

        // add missing access_social_networks template block
        if (!preg_match('/<!--\s+BEGIN\s+access_social_networks\s+-->.*<!--\s+END\s+access_social_networks\s+-->/ms', $content)) {
            $content = preg_replace('/(<!--\s+BEGIN\s+access_signup_form\s+-->.*?)(<div[^>]*>|)(.*?\{ACCESS_SIGNUP_MESSAGE\}.*?)(<\/div>|)/ms', '$1<br /><br /><!-- BEGIN access_social_networks --><fieldset><legend>oder Login mit Social Media</legend><!-- BEGIN access_social_networks_facebook -->        <a class="facebook loginbutton" href="{ACCESS_SOCIALLOGIN_FACEBOOK}">Facebook</a>        <!-- END access_social_networks_facebook -->        <!-- BEGIN access_social_networks_google -->        <a class="google loginbutton" href="{ACCESS_SOCIALLOGIN_GOOGLE}">Google</a>        <!-- END access_social_networks_google -->        <!-- BEGIN access_social_networks_twitter -->        <a class="twitter loginbutton" href="{ACCESS_SOCIALLOGIN_TWITTER}">Twitter</a>        <!-- END access_social_networks_twitter -->    </fieldset>    <!-- END access_social_networks -->$2$3$4', $content);
        }

        return $content;
    };

    \Cx\Lib\UpdateUtil::migrateContentPageUsingRegexCallback(array('module' => 'access', 'cmd' => 'signup'), $search, $callback, array('content'), '3.0.3');
} catch (\Cx\Lib\UpdateException $e) {
    return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
}

// update page and node constraints
try {
    \Cx\Lib\UpdateUtil::set_constraints(DBPREFIX.'content_node', array(
        'parent_id' => array(
            'table'     => DBPREFIX.'content_node',
            'column'    => 'id',
            'onDelete'  => 'NO ACTION',
            'onUpdate'  => 'NO ACTION',
        ),
    ));
    \Cx\Lib\UpdateUtil::set_constraints(DBPREFIX.'content_page', array(
        'node_id' => array(
            'table'     => DBPREFIX.'content_node',
            'column'    => 'id',
            'onDelete'  => 'SET NULL',
            'onUpdate'  => 'NO ACTION',
        )
    ));
} catch (\Cx\Lib\UpdateException $e) {
    return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
}

// update filesharing page, add confirm deletion view
$search = array(
    '/.*/ms',
);
$callback = function($matches) {
    $newHtmlCode = <<<HTMLCODE
    <!-- BEGIN confirm_delete -->
    <form action="[[FORM_ACTION]]" class="fileshareForm" id="contactForm" method="[[FORM_METHOD]]" style="float: left;">
        <p>
            <label>[[TXT_FILESHARING_FILE_NAME]]</label>[[FILESHARING_FILE_NAME]]
        </p>
        <p>
            <input name="delete" type="submit" value="[[TXT_FILESHARING_CONFIRM_DELETE]]" />
        </p>
    </form>
    <!-- END confirm_delete -->
HTMLCODE;
    if (!preg_match('/<!--\s+BEGIN\s+confirm_delete\s+-->.*<!--\s+END\s+confirm_delete\s+-->/ms', $matches[0])) {
        return str_replace('<!-- END upload_form -->', $newHtmlCode, $matches[0]);
    } else {
        return $matches[0];
    }
};
\Cx\Lib\UpdateUtil::migrateContentPageUsingRegexCallback(array('module' => 'filesharing', 'cmd' => ''), $search, $callback, array('content'), '3.1.0');

if($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.1.0') &&
    (!\Cx\Lib\UpdateUtil::table_exist(DBPREFIX.'module_news_categories_catid') || \Cx\Lib\UpdateUtil::table_empty(DBPREFIX.'module_news_categories_catid'))
){
    try {
        /************************************************
        * EXTENSION:    Categories as NestedSet         *
        * ADDED:        Contrexx v3.1.0                 *
        ************************************************/
        $nestedSetRootId = null;
        $count = null;
        $leftAndRight = 2;
        $sorting = 1;
        $level = 2;

        // add nested set columns
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_news_categories',
            array(
                'catid'          => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'parent_id'      => array('type' => 'INT(11)', 'after' => 'catid'),
                'left_id'        => array('type' => 'INT(11)', 'after' => 'parent_id'),
                'right_id'       => array('type' => 'INT(11)', 'after' => 'left_id'),
                'sorting'        => array('type' => 'INT(11)', 'after' => 'right_id'),
                'level'          => array('type' => 'INT(11)', 'after' => 'sorting')
            )
        );

        // add nested set root node and select its id
        $objResultRoot = \Cx\Lib\UpdateUtil::sql('INSERT INTO `'.DBPREFIX.'module_news_categories` (`catid`, `parent_id`, `left_id`, `right_id`, `sorting`, `level`) VALUES (0, 0, 0, 0, 0, 0)');
        if ($objResultRoot) {
            $nestedSetRootId = $objDatabase->Insert_ID();
        }

        // count categories
        $objResultCount = \Cx\Lib\UpdateUtil::sql('SELECT count(`catid`) AS count FROM `'.DBPREFIX.'module_news_categories`');
        if ($objResultCount && !$objResultCount->EOF) {
            $count = $objResultCount->fields['count'];
        }

        // add nested set information to root node
        \Cx\Lib\UpdateUtil::sql('
            UPDATE `'.DBPREFIX.'module_news_categories` SET
            `parent_id` = '.$nestedSetRootId.',
            `left_id` = 1,
            `right_id` = '.($count*2).',
            `sorting` = 1,
            `level` = 1
            WHERE `catid` = '.$nestedSetRootId.'
        ');

        // add nested set information to all categories
        $objResultCatSelect = \Cx\Lib\UpdateUtil::sql('SELECT `catid` FROM `'.DBPREFIX.'module_news_categories` ORDER BY `catid` ASC');
        if ($objResultCatSelect) {
            while (!$objResultCatSelect->EOF) {
                $catId = $objResultCatSelect->fields['catid'];
                if ($catId != $nestedSetRootId) {
                    \Cx\Lib\UpdateUtil::sql('
                        UPDATE `'.DBPREFIX.'module_news_categories` SET
                        `parent_id` = '.$nestedSetRootId.',
                        `left_id` = '.$leftAndRight++.',
                        `right_id` = '.$leftAndRight++.',
                        `sorting` = '.$sorting++.',
                        `level` = '.$level.'
                        WHERE `catid` = '.$catId.'
                    ');
                }
                $objResultCatSelect->MoveNext();
            }
        }

        // add new tables
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_news_categories_locks',
            array(
                'lockId'         => array('type' => 'VARCHAR(32)'),
                'lockTable'      => array('type' => 'VARCHAR(32)', 'after' => 'lockId'),
                'lockStamp'      => array('type' => 'BIGINT(11)', 'notnull' => true, 'after' => 'lockTable')
            )
        );
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_news_categories_catid',
            array(
                'id'     => array('type' => 'INT(11)', 'notnull' => true)
            )
        );

        // insert id of last added category
        \Cx\Lib\UpdateUtil::sql('INSERT INTO `'.DBPREFIX.'module_news_categories_catid` (`id`) VALUES ('.$nestedSetRootId.')');
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }
}

/***************************************
 *
 * STRICT_TRANS_TABLES ISSUE FIX FOR PROFILE TABLE
 *
 * ADD NEW ACCESS ID FOR FILESHARING
 *
 * MIGRATE CALENDAR
 *
 **************************************/
if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.1.0')) {
    try {
        \Cx\Lib\UpdateUtil::sql("ALTER TABLE `".DBPREFIX."access_user_profile` CHANGE `interests` `interests` TEXT NULL");
        \Cx\Lib\UpdateUtil::sql("ALTER TABLE `".DBPREFIX."access_user_profile` CHANGE `signature` `signature` TEXT NULL");
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    // add access to filesharing for existing groups
    try {
        $result = \Cx\Lib\UpdateUtil::sql("SELECT `group_id` FROM `" . DBPREFIX . "access_group_static_ids` WHERE access_id = 7 GROUP BY group_id");
        if ($result !== false) {
            while (!$result->EOF) {
                \Cx\Lib\UpdateUtil::sql("INSERT IGNORE INTO `" . DBPREFIX . "access_group_static_ids` (`access_id`, `group_id`)
                                            VALUES (8, " . intval($result->fields['group_id']) . ")");
                $result->MoveNext();
            }
        }
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    $calendarComponentUpdateFile = dirname(__FILE__).'/components/module/calendar.php';
    require_once($calendarComponentUpdateFile);
    $CalendarUpdate31 = new CalendarUpdate31();

    // if something fails, return the error or message
    $calendarMigration = $CalendarUpdate31->run();
    if ($calendarMigration !== true) {
        \DBG::dump($calendarMigration);
        return $calendarMigration;
    }

    // rewrite backendAreas
    require_once(dirname(__FILE__).'/components/core/backendAreas.php');
    $backendAreasUpdate = _updateBackendAreas();
    if ($backendAreasUpdate !== true) {
        return $backendAreasUpdate;
    }
} elseif ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.1.1')) {
    $calendarComponentUpdateFile = dirname(__FILE__).'/components/module/calendar.php';
    require_once($calendarComponentUpdateFile);
    $CalendarUpdate31 = new CalendarUpdate31();
    $calendarMigration = $CalendarUpdate31->migrateContentPages();
    if ($calendarMigration !== true) {
        \DBG::dump($calendarMigration);
        return $calendarMigration;
    }
}

/***************************************
 *
 * CONTACT: Add multi-file upload field
 *
 **************************************/
if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.1.0')) {
    try {
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_contact_form_field',
            array(
                'id'             => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'id_form'        => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                'type'           => array('type' => 'ENUM(\'text\',\'label\',\'checkbox\',\'checkboxGroup\',\'country\',\'date\',\'file\',\'multi_file\',\'fieldset\',\'hidden\',\'horizontalLine\',\'password\',\'radio\',\'select\',\'textarea\',\'recipient\',\'special\')', 'notnull' => true, 'default' => 'text'),
                'special_type'   => array('type' => 'VARCHAR(20)', 'notnull' => true),
                'is_required'    => array('type' => 'SET(\'0\',\'1\')', 'notnull' => true, 'default' => '0'),
                'check_type'     => array('type' => 'INT(3)', 'notnull' => true, 'default' => '1'),
                'order_id'       => array('type' => 'SMALLINT(5)', 'unsigned' => true, 'notnull' => true, 'default' => '0')
            )
        );
        // change all fields currently set to 'file' to 'multi_file' ('multi_file' is same as former 'file' in previous versions)
        \Cx\Lib\UpdateUtil::sql("UPDATE `".DBPREFIX."module_contact_form_field` SET `type` = 'multi_file' WHERE `type` = 'file'");
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }
}

/***************************************
 *
 * NEWSLETTER: ACCESS IDS
 *
 **************************************/
// add access id 176 for user groups which had access to 172 if version is older than 3.1.0
if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.1.0')) {
    try {
        $result = \Cx\Lib\UpdateUtil::sql("SELECT `group_id` FROM `" . DBPREFIX . "access_group_static_ids` WHERE access_id = 172 GROUP BY `group_id`");
        if ($result !== false) {
            while (!$result->EOF) {
                \Cx\Lib\UpdateUtil::sql("INSERT IGNORE INTO `" . DBPREFIX . "access_group_static_ids` (`access_id`, `group_id`)
                                            VALUES (176, " . intval($result->fields['group_id']) . ")");
                $result->MoveNext();
            }
        }
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }
}

/***************************************
 *
 * E-COMMERCE: ACCESS IDS
 *
 **************************************/
// add access id 4 for user groups which had access to 13 or 161
if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.1.0')) {
    try {
        $result = \Cx\Lib\UpdateUtil::sql("SELECT `group_id` FROM `" . DBPREFIX . "access_group_static_ids` WHERE access_id = 13 OR access_id = 161 GROUP BY `group_id`");
        if ($result !== false) {
            while (!$result->EOF) {
                \Cx\Lib\UpdateUtil::sql("INSERT IGNORE INTO `" . DBPREFIX . "access_group_static_ids` (`access_id`, `group_id`)
                                            VALUES (4, " . intval($result->fields['group_id']) . ")");
                $result->MoveNext();
            }
        }
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }
}

/***************************************
 *
 * STATS: ACCESS IDS
 *
 **************************************/
// add permission to stats settings if the user had permission to stats
if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.1.0')) {
    try {
        $result = \Cx\Lib\UpdateUtil::sql("SELECT `group_id` FROM `" . DBPREFIX . "access_group_static_ids` WHERE access_id = 163 GROUP BY `group_id`");
        if ($result !== false) {
            while (!$result->EOF) {
                \Cx\Lib\UpdateUtil::sql("INSERT IGNORE INTO `" . DBPREFIX . "access_group_static_ids` (`access_id`, `group_id`)
                                            VALUES (170, " . intval($result->fields['group_id']) . ")");
                $result->MoveNext();
            }
        }
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }
}

/***************************************
 *
 * DOWNLOADS: ACCESS IDS
 *
 **************************************/
// add permission to downloads edit all downloads if the user had permission to downloads administer
if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.1.0.2')) {
    try {
        $result = \Cx\Lib\UpdateUtil::sql("SELECT `group_id` FROM `" . DBPREFIX . "access_group_static_ids` WHERE access_id = 142 GROUP BY `group_id`");
        if ($result !== false) {
            while (!$result->EOF) {
                \Cx\Lib\UpdateUtil::sql("INSERT IGNORE INTO `" . DBPREFIX . "access_group_static_ids` (`access_id`, `group_id`)
                                            VALUES (143, " . intval($result->fields['group_id']) . ")");
                $result->MoveNext();
            }
        }
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }
}

if (file_exists(ASCMS_DOCUMENT_ROOT.ASCMS_BACKEND_PATH.'/index.php')) {
    // move cadmin index.php if its customized
    if (!loadMd5SumOfOriginalCxFiles()) {
        return false;
    }
    if (!verifyMd5SumOfFile(ASCMS_DOCUMENT_ROOT.ASCMS_BACKEND_PATH.'/index.php', '', false)) {
        \DBG::msg('...and it\'s customized, so let\'s move it to customizing directory');
        // changes, backup modified file
        if (!backupModifiedFile(ASCMS_DOCUMENT_ROOT.ASCMS_BACKEND_PATH.'/index.php')) {
            setUpdateMsg('Die Datei \''.ASCMS_DOCUMENT_ROOT.ASCMS_BACKEND_PATH.'/index.php\' konnte nicht kopiert werden.');
            return false;
        }
    } else {
        \DBG::msg('...but it\'s not customized');
    }
    // no non-backupped changes, can delete
    try {
        \DBG::msg('So let\'s remove it...');
        $cadminIndex = new \Cx\Lib\FileSystem\File(ASCMS_DOCUMENT_ROOT.ASCMS_BACKEND_PATH.'/index.php');
        $cadminIndex->delete();
    } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
        setUpdateMsg('Die Datei \''.ASCMS_DOCUMENT_ROOT.ASCMS_BACKEND_PATH.'/index.php\' konnte nicht gelöscht werden.');
        return false;
    }
}

/***************************************
 *
 * CALENDAR: FIX TABLE
 * only for 3.1.0
 *
 **************************************/

// fixing news container text setting which cannot be activated
if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.1.1')) {
    try {
        $result = \Cx\Lib\UpdateUtil::sql('SELECT `name` FROM `'.DBPREFIX.'module_news_settings` WHERE `name` = "news_use_teaser_text"');
        if ($result && ($result->RecordCount() == 0)) {
            \Cx\Lib\UpdateUtil::sql('INSERT INTO `'.DBPREFIX.'module_news_settings` (`name`, `value`) VALUES ("news_use_teaser_text", 1)');
        }
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }
}

$arrContentSites = array(
    'media1', 'media2', 'media3', 'media4',
);
// replace source url to image
foreach ($arrContentSites as $module) {
    try {
        \Cx\Lib\UpdateUtil::migrateContentPage(
            $module,
            '',
            'images/modules/media/_base.gif',
            'core_modules/media/View/Media/_base.gif',
            '3.1.1'
        );
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }
}


$arrContentSites = array(
    'media1', 'media2', 'media3', 'media4',
);
// replace source url to image
foreach ($arrContentSites as $module) {
    try {
        \Cx\Lib\UpdateUtil::migrateContentPage(
            $module,
            '',
            'images/modules/media/_base.gif',
            'core_modules/media/View/Media/_base.gif',
            '3.1.2'
        );
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

if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.2.0')) {
    $crmComponentUpdateFile = dirname(__FILE__).'/components/module/crm.php';
    require_once($crmComponentUpdateFile);
    $crmUpdate = _crmUpdate();
    if ($crmUpdate !== true) {
        return $crmUpdate;
    }
}

// fix tree
\Env::get('em')->getRepository('Cx\Core\ContentManager\Model\Entity\Node')->recover();

require(dirname(__FILE__).'/config.inc.php');
\Cx\Lib\UpdateUtil::sql('UPDATE `'.DBPREFIX.'settings` SET `setvalue` = \'' . $arrUpdate['cmsVersion'] . '\' WHERE `setname` = \'coreCmsVersion\'');
\Cx\Lib\UpdateUtil::sql('UPDATE `'.DBPREFIX.'settings` SET `setvalue` = \'' . $arrUpdate['cmsCodeName'] . '\' WHERE `setname` = \'coreCmsCodeName\'');
\Cx\Lib\UpdateUtil::sql('UPDATE `'.DBPREFIX.'settings` SET `setvalue` = \'' . $arrUpdate['cmsReleaseDate'] . '\' WHERE `setname` = \'coreCmsReleaseDate\'');
\Cx\Lib\UpdateUtil::sql('UPDATE `'.DBPREFIX.'settings` SET `setvalue` = \'' . $arrUpdate['cmsName'] . '\' WHERE `setname` = \'coreCmsName\'');
\Cx\Lib\UpdateUtil::sql('UPDATE `'.DBPREFIX.'settings` SET `setvalue` = \'' . $arrUpdate['cmsStatus'] . '\' WHERE `setname` = \'coreCmsStatus\'');

// define the missing placeholders which are used by settingsManager to locate the settings file
if (!defined('ASCMS_INSTANCE_PATH')) {
    define('ASCMS_INSTANCE_PATH', $_PATHCONFIG['ascms_root']);
}
if (!defined('ASCMS_INSTANCE_OFFSET')) {
    define('ASCMS_INSTANCE_OFFSET', $_PATHCONFIG['ascms_root_offset']);
}
$objSettings = new \settingsManager();
$objSettings->writeSettingsFile();
require($documentRoot.'/config/settings.php');
return true;
