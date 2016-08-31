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


function _accessUpdate()
{
    global $objDatabase, $objUpdate, $_CONFIG, $_ARRAYLANG, $_CORELANG;

    $arrTables = $objDatabase->MetaTables('TABLES');
    if (!$arrTables) {
        setUpdateMsg($_ARRAYLANG['TXT_UNABLE_DETERMINE_DATABASE_STRUCTURE']);
        return false;
    }

    /****************************
     *
     * ADD NOTIFICATION E-MAILS
     *
     ***************************/
    try{
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'access_user_mail',
            array(
                'type'           => array('type' => 'ENUM(\'reg_confirm\',\'reset_pw\',\'user_activated\',\'user_deactivated\',\'new_user\')', 'notnull' => true, 'default' => 'reg_confirm'),
                'lang_id'        => array('type' => 'TINYINT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                'sender_mail'    => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => ''),
                'sender_name'    => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => ''),
                'subject'        => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => ''),
                'format'         => array('type' => 'ENUM(\'text\',\'html\',\'multipart\')', 'notnull' => true, 'default' => 'text'),
                'body_text'      => array('type' => 'TEXT'),
                'body_html'      => array('type' => 'TEXT')
            ),
            array(
                'mail'           => array('fields' => array('type','lang_id'), 'type' => 'UNIQUE')
            ),
            'InnoDB'
        );
        $result = \Cx\Lib\UpdateUtil::sql('SHOW KEYS FROM `' . DBPREFIX . 'access_group_dynamic_ids`');
        if ($result->EOF) {
            \Cx\Lib\UpdateUtil::sql('ALTER IGNORE TABLE `' . DBPREFIX . 'access_group_dynamic_ids` ADD PRIMARY KEY ( `access_id` , `group_id` )');
        }
        $result = \Cx\Lib\UpdateUtil::sql('SHOW KEYS FROM `' . DBPREFIX . 'access_group_static_ids`');
        if ($result->EOF) {
            \Cx\Lib\UpdateUtil::sql('ALTER IGNORE TABLE `' . DBPREFIX . 'access_group_static_ids` ADD PRIMARY KEY ( `access_id` , `group_id` )');
        }
    }
    catch (\Cx\Lib\UpdateException $e) {
        // we COULD do something else here..
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    \DBG::msg('001');
    $arrMails = array(
        array(
            'type'            => 'reg_confirm',
            'subject'        => 'Benutzerregistrierung bestätigen',
            'body_text'    => 'Hallo [[USERNAME]],\r\n\r\nVielen Dank für Ihre Anmeldung bei [[HOST]].\r\nBitte klicken Sie auf den folgenden Link, um Ihre E-Mail-Adresse zu bestätigen:\r\n[[ACTIVATION_LINK]]\r\n\r\nUm sich später einzuloggen, geben Sie bitte Ihren Benutzernamen \"[[USERNAME]]\" und das Passwort ein, das Sie bei der Registrierung festgelegt haben.\r\n\r\n\r\n--\r\nIhr [[SENDER]]'
        ),
        array(
            'type'            => 'reset_pw',
            'subject'        => 'Kennwort zurücksetzen',
            'body_text'        => 'Hallo [[USERNAME]],\r\n\r\nUm ein neues Passwort zu wählen, müssen Sie auf die unten aufgeführte URL gehen und dort Ihr neues Passwort eingeben.\r\n\r\nWICHTIG: Die Gültigkeit der URL wird nach 60 Minuten verfallen, nachdem diese E-Mail abgeschickt wurde.\r\nFalls Sie mehr Zeit benötigen, geben Sie Ihre E-Mail Adresse einfach ein weiteres Mal ein.\r\n\r\nIhre URL:\r\n[[URL]]\r\n\r\n\r\n--\r\n[[SENDER]]'
        ),
        array(
            'type'            => 'user_activated',
            'subject'        => 'Ihr Benutzerkonto wurde aktiviert',
            'body_text'        => 'Hallo [[USERNAME]],\r\n\r\nIhr Benutzerkonto auf [[HOST]] wurde soeben aktiviert und kann von nun an verwendet werden.\r\n\r\n\r\n--\r\n[[SENDER]]'
        ),
        array(
            'type'            => 'user_deactivated',
            'subject'        => 'Ihr Benutzerkonto wurde deaktiviert',
            'body_text'        => 'Hallo [[USERNAME]],\r\n\r\nIhr Benutzerkonto auf [[HOST]] wurde soeben deaktiviert.\r\n\r\n\r\n--\r\n[[SENDER]]'
        ),
        array(
            'type'            => 'new_user',
            'subject'        => 'Ein neuer Benutzer hat sich registriert',
            'body_text'        => 'Der Benutzer [[USERNAME]] hat sich soeben registriert und muss nun frei geschaltet werden.\r\n\r\nÜber die folgende Adresse kann das Benutzerkonto von [[USERNAME]] verwaltet werden:\r\n[[LINK]]\r\n\r\n\r\n--\r\n[[SENDER]]'
        )
    );

    foreach ($arrMails as $arrMail) {
        $query = "SELECT 1 FROM `".DBPREFIX."access_user_mail` WHERE `type` = '".$arrMail['type']."'";
        $objMail = $objDatabase->SelectLimit($query, 1);
        if ($objMail !== false) {
            if ($objMail->RecordCount() == 0) {
                $query = "INSERT INTO `".DBPREFIX."access_user_mail` (
                    `type`,
                    `lang_id`,
                    `sender_mail`,
                    `sender_name`,
                    `subject`,
                    `body_text`,
                    `body_html`
                ) VALUES (
                    '".$arrMail['type']."',
                    0,
                    '".addslashes($_CONFIG['coreAdminEmail'])."',
                    '".addslashes($_CONFIG['coreAdminName'])."',
                    '".$arrMail['subject']."',
                    '".$arrMail['body_text']."',
                    ''
                )";
                if ($objDatabase->Execute($query) === false) {
                    return _databaseError($query, $objDatabase->ErrorMsg());
                }
            }
        } else {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }



    /****************
     *
     * ADD SETTINGS
     *
     ***************/
    try {
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'access_settings',
            array(
                'key'        => array('type' => 'VARCHAR(32)', 'notnull' => true, 'default' => ''),
                'value'      => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'key'),
                'status'     => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'value')
            ),
            array(
                'key'        => array('fields' => array('key'), 'type' => 'UNIQUE')
            ),
            'InnoDB'
        );

        if (in_array(DBPREFIX."communit_config", $arrTables)) {
            $objResult = \Cx\Lib\UpdateUtil::sql('SELECT `name`, `value`, `status` FROM `'.DBPREFIX.'community_config`');
            while (!$objResult->EOF) {
                $arrCommunityConfig[$objResult->fields['name']] = array(
                    'value'        => $objResult->fields['value'],
                    'status'    => $objResult->fields['status']
                );
                $objResult->MoveNext();
            }
        }

        $arrSettings = array(
            'user_activation'                 =>array('value'=> '',               'status'    => isset($arrCommunityConfig['user_activation']['status']) ? $arrCommunityConfig['user_activation']['status'] : 0),
            'user_activation_timeout'         =>array('value'=> isset($arrCommunityConfig['user_activation_timeout']['value']) ? $arrCommunityConfig['user_activation_timeout']['value'] : 0, 'status'    => isset($arrCommunityConfig['user_activation_timeout']['status']) ? $arrCommunityConfig['user_activation_timeout']['status'] : 0),
            'assigne_to_groups'               =>array('value'=> isset($arrCommunityConfig['community_groups']['value']) ? $arrCommunityConfig['community_groups']['value'] : '', 'status'    => 1),
            'max_profile_pic_width'           =>array('value'=>'160',            'status'  => 1),
            'max_profile_pic_height'          =>array('value'=>'160',            'status'  => 1),
            'profile_thumbnail_pic_width'     =>array('value'=>'50',             'status'  => 1),
            'profile_thumbnail_pic_height'    =>array('value'=>'50',             'status'  => 1),
            'max_profile_pic_size'            =>array('value'=>'30000',          'status'  => 1),
            'max_pic_width'                   =>array('value'=>'600',            'status'  => 1),
            'max_pic_height'                  =>array('value'=>'600',            'status'  => 1),
            'max_thumbnail_pic_width'         =>array('value'=>'130',            'status'  => 1),
            'max_thumbnail_pic_height'        =>array('value'=>'130',            'status'  => 1),
            'max_pic_size'                    =>array('value'=>'200000',         'status'  => 1),
            'notification_address'            =>array('value'=>addslashes($_CONFIG['coreAdminEmail']), 'status'=>1),
            'user_config_email_access'        =>array('value'=> '',              'status'    => 1),
            'user_config_profile_access'      =>array('value'=> '',              'status'    => 1),
            'default_email_access'            =>array('value'=> 'members_only',  'status'    => 1),
            'default_profile_access'          =>array('value'=> 'members_only',  'status'    => 1),
            'user_delete_account'             =>array('value'=> '',              'status'    => 1),
            'block_currently_online_users'    =>array('value'=> '10',            'status'    => 0),
            'block_currently_online_users_pic'=>array('value'=> '',              'status'    => 0),
            'block_last_active_users'         =>array('value'=> '10',            'status'    => 0),
            'block_last_active_users_pic'     =>array('value'=> '',              'status'    => 0),
            'block_latest_reg_users'          =>array('value'=> '10',            'status'    => 0),
            'block_latest_reg_users_pic'      =>array('value'=> '',              'status'    => 0),
            'block_birthday_users'            =>array('value'=> '10',            'status'    => 0),
            'block_birthday_users_pic'        =>array('value'=> '',              'status'    => 0),
            'session_user_interval'           =>array('value'=> '0',             'status'    => 1),
            'user_accept_tos_on_signup'       =>array('value'=> '',              'status'    => 0),
            'user_captcha'                    =>array('value'=> '',              'status'    => 0),
            'profile_thumbnail_method'        =>array('value'=> 'crop',          'status'    => 1),
            'profile_thumbnail_scale_color'   =>array('value'=> '#FFFFFF',       'status'    => 1),
        );

        foreach ($arrSettings as $key => $arrSetting) {
            if (!\Cx\Lib\UpdateUtil::sql("SELECT 1 FROM `".DBPREFIX."access_settings` WHERE `key` = '".$key."'")->RecordCount()) {
                \Cx\Lib\UpdateUtil::sql("INSERT INTO `".DBPREFIX."access_settings`
                    SET `key`       = '".$key."',
                        `value`     = '".$arrSetting['value']."',
                        `status`    = '".$arrSetting['status']."'
                ");
            }
        }

        // delete obsolete table community_config
        \Cx\Lib\UpdateUtil::drop_table(DBPREFIX.'community_config');

        // delete obsolete table user_validity
        \Cx\Lib\UpdateUtil::drop_table(DBPREFIX.'user_validity');
    }
    catch (\Cx\Lib\UpdateException $e) {
        // we COULD do something else here..
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }


    /********************
     *
     * ADD USER PROFILE
     *
     *******************/
    try {
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'access_user_profile',
            array(
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
                'interests'          => array('type' => 'text', 'after' => 'profession'),
                'signature'          => array('type' => 'text', 'after' => 'interests'),
                'picture'            => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'signature')
            ),
            array(
                'profile'        => array('fields' => array('firstname' => 100, 'lastname' => 100, 'company' => 50))
            ),
            'InnoDB'
        );
    }
    catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }



    /***************************
     *
     * MIGRATE GROUP RELATIONS
     *
     **************************/
    try {
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'access_rel_user_group',
            array(
                'user_id'        => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true),
                'group_id'       => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true, 'after' => 'user_id')
            ),
            array(),
            'InnoDB'
        );
    }
    catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }


    $arrColumns = $objDatabase->MetaColumnNames(DBPREFIX.'access_users');
    if ($arrColumns === false) {
        setUpdateMsg(sprintf($_ARRAYLANG['TXT_UNABLE_GETTING_DATABASE_TABLE_STRUCTURE'], DBPREFIX.'access_users'));
        return false;
    }

    if (in_array('groups', $arrColumns)) {
        $query = "SELECT `id`, `groups` FROM ".DBPREFIX."access_users WHERE `groups` != ''";
        $objUser = $objDatabase->Execute($query);
        if ($objUser) {
            while (!$objUser->EOF) {
                $arrGroups = explode(',', $objUser->fields['groups']);
                foreach ($arrGroups as $groupId) {
                    $query = "SELECT 1 FROM ".DBPREFIX."access_rel_user_group WHERE `user_id` = ".$objUser->fields['id']." AND `group_id` = ".intval($groupId);
                    $objRel = $objDatabase->SelectLimit($query, 1);
                    if ($objRel) {
                        if ($objRel->RecordCount() == 0) {
                            $query = "INSERT INTO ".DBPREFIX."access_rel_user_group (`user_id`, `group_id`) VALUES (".$objUser->fields['id'].", ".intval($groupId).")";
                            if ($objDatabase->Execute($query) === false) {
                                return _databaseError($query, $objDatabase->ErrorMsg());
                            }
                        }
                    } else {
                        return _databaseError($query, $objDatabase->ErrorMsg());
                    }
                }

                $objUser->MoveNext();
            }
        } else {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }

        $query = "ALTER TABLE `".DBPREFIX."access_users` DROP `groups`";
        if ($objDatabase->Execute($query) === false) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }

\DBG::msg('002');

    /*********************
     *
     * ADD USER VALIDITY
     *
     ********************/
    try {
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'access_user_validity',
            array(
                'validity'   => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true)
            ),
            array(),
            'InnoDB'
        );
    }
    catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    $query = "SELECT 1 FROM `".DBPREFIX."access_user_validity`";
    $objResult = $objDatabase->SelectLimit($query, 1);
    if ($objResult) {
        if ($objResult->RecordCount() == 0) {
            $query = "
                INSERT INTO `".DBPREFIX."access_user_validity` (`validity`) VALUES
                    ('0'), ('1'), ('15'), ('31'), ('62'),
                    ('92'), ('123'), ('184'), ('366'), ('731')
                ";
            if ($objDatabase->Execute($query) === false) {
                return _databaseError($query, $objDatabase->ErrorMsg());
            }
        }
    } else {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }

    /********************
     *
     * MIGRATE PROFILES
     *
     *******************/
    if (in_array('firstname', $arrColumns)) {
        $query = "SELECT `id`, `firstname`, `lastname`, `residence`, `profession`, `interests`, `webpage`, `company`, `zip`, `phone`, `mobile`, `street` FROM `".DBPREFIX."access_users`";
        $objUser = $objDatabase->Execute($query);
        if ($objUser) {
            while (!$objUser->EOF) {
                $query = "SELECT 1 FROM `".DBPREFIX."access_user_profile` WHERE `user_id` = ".$objUser->fields['id'];
                $objProfile = $objDatabase->SelectLimit($query, 1);
                if ($objProfile) {
                    if ($objProfile->RecordCount() == 0) {
                        $query = "INSERT INTO `".DBPREFIX."access_user_profile` (
                            `user_id`,
                            `gender`,
                            `firstname`,
                            `lastname`,
                            `company`,
                            `address`,
                            `city`,
                            `zip`,
                            `country`,
                            `phone_office`,
                            `phone_private`,
                            `phone_mobile`,
                            `phone_fax`,
                            `website`,
                            `profession`,
                            `interests`,
                            `picture`
                        ) VALUES (
                            ".$objUser->fields['id'].",
                            'gender_undefined',
                            '".addslashes($objUser->fields['firstname'])."',
                            '".addslashes($objUser->fields['lastname'])."',
                            '".addslashes($objUser->fields['company'])."',
                            '".addslashes($objUser->fields['street'])."',
                            '".addslashes($objUser->fields['residence'])."',
                            '".addslashes($objUser->fields['zip'])."',
                            0,
                            '',
                            '".addslashes($objUser->fields['phone'])."',
                            '".addslashes($objUser->fields['mobile'])."',
                            '',
                            '".addslashes($objUser->fields['webpage'])."',
                            '".addslashes($objUser->fields['profession'])."',
                            '".addslashes($objUser->fields['interests'])."',
                            ''
                        )";
                        if ($objDatabase->Execute($query) === false) {
                            return _databaseError($query, $objDatabase->ErrorMsg());
                        }
                    }
                } else {
                    return _databaseError($query, $objDatabase->ErrorMsg());
                }

                $objUser->MoveNext();
            }
        } else {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }

    $arrRemoveColumns = array(
        'firstname',
        'lastname',
        'residence',
        'profession',
        'interests',
        'webpage',
        'company',
        'zip',
        'phone',
        'mobile',
        'street',
        'levelid'
    );

    foreach ($arrRemoveColumns as $column) {
        if (in_array($column, $arrColumns)) {
            $query = "ALTER TABLE ".DBPREFIX."access_users DROP `".$column."`";
            if ($objDatabase->Execute($query) === false) {
                return _databaseError($query, $objDatabase->ErrorMsg());
            }
        }
    }

    $arrColumnDetails = $objDatabase->MetaColumns(DBPREFIX.'access_users');
    if ($arrColumnDetails === false) {
        setUpdateMsg(sprintf($_ARRAYLANG['TXT_UNABLE_GETTING_DATABASE_TABLE_STRUCTURE'], DBPREFIX.'access_users'));
        return false;
    }

    if (in_array('regdate', $arrColumns)) {
        if ($arrColumnDetails['REGDATE']->type == 'date') {
            if (!in_array('regdate_new', $arrColumns)) {
                $query = "ALTER TABLE `".DBPREFIX."access_users` ADD `regdate_new` INT( 14 ) UNSIGNED NULL DEFAULT '0' AFTER `regdate`";
                if ($objDatabase->Execute($query) === false) {
                    return _databaseError($query, $objDatabase->ErrorMsg());
                }
            }

            $query = "UPDATE `".DBPREFIX."access_users` SET `regdate_new` = UNIX_TIMESTAMP(`regdate`), `regdate` = '0000-00-00' WHERE `regdate` != '0000-00-00'";
            if ($objDatabase->Execute($query) === false) {
                return _databaseError($query, $objDatabase->ErrorMsg());
            }

            $query = "ALTER TABLE `".DBPREFIX."access_users` DROP `regdate`";
            if ($objDatabase->Execute($query) === false) {
                return _databaseError($query, $objDatabase->ErrorMsg());
            }
        }
    }

    $arrColumns = $objDatabase->MetaColumnNames(DBPREFIX.'access_users');
    if ($arrColumns === false) {
        setUpdateMsg(sprintf($_ARRAYLANG['TXT_UNABLE_GETTING_DATABASE_TABLE_STRUCTURE'], DBPREFIX.'access_users'));
        return false;
    }

    if (in_array('regdate_new', $arrColumns)) {
        $query = "ALTER TABLE `".DBPREFIX."access_users` CHANGE `regdate_new` `regdate` INT( 14 ) UNSIGNED NOT NULL DEFAULT '0'";
        if ($objDatabase->Execute($query) === false) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }

    $query = "ALTER TABLE `".DBPREFIX."access_users` CHANGE `is_admin` `is_admin` TINYINT( 1 ) UNSIGNED NOT NULL DEFAULT '0'";
    if ($objDatabase->Execute($query) === false) {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }

    if (!in_array('email_access', $arrColumns)) {
        $query = "ALTER TABLE `".DBPREFIX."access_users` ADD `email_access` ENUM( 'everyone', 'members_only', 'nobody' ) NOT NULL DEFAULT 'nobody' AFTER `email`";
        if ($objDatabase->Execute($query) === false) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }

    if (!in_array('profile_access', $arrColumns)) {
        $query = "ALTER TABLE `".DBPREFIX."access_users` ADD `profile_access` ENUM( 'everyone', 'members_only', 'nobody' ) NOT NULL DEFAULT 'members_only' AFTER `active`";
        if ($objDatabase->Execute($query) === false) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }

    if (!in_array('frontend_lang_id', $arrColumns)) {
        $query = "ALTER TABLE `".DBPREFIX."access_users` CHANGE `langId` `frontend_lang_id` INT( 2 ) UNSIGNED NOT NULL DEFAULT '0'";
        if ($objDatabase->Execute($query) === false) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }

    if (!in_array('backend_lang_id', $arrColumns)) {
        $query = "ALTER TABLE `".DBPREFIX."access_users` ADD `backend_lang_id` INT( 2 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `frontend_lang_id`";
        if ($objDatabase->Execute($query) === false) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        } else {
             $query = "UPDATE `".DBPREFIX."access_users` SET `backend_lang_id` = `frontend_lang_id`";
             if ($objDatabase->Execute($query) === false) {
                return _databaseError($query, $objDatabase->ErrorMsg());
            }
        }
    }

    if (!in_array('last_auth', $arrColumns)) {
        $query = "ALTER TABLE `".DBPREFIX."access_users` ADD `last_auth` INT( 14 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `regdate`";
        if ($objDatabase->Execute($query) === false) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }

    if (!in_array('last_activity', $arrColumns)) {
        $query = "ALTER TABLE `".DBPREFIX."access_users` ADD `last_activity` INT( 14 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `last_auth`";
        if ($objDatabase->Execute($query) === false) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }

    if (!in_array('expiration', $arrColumns)) {
        $query = "ALTER TABLE `".DBPREFIX."access_users` ADD `expiration` INT( 14 ) UNSIGNED NOT NULL DEFAULT '0' AFTER `regdate`";
        if ($objDatabase->Execute($query) === false) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }

    if (!in_array('validity', $arrColumns)) {
        $query = "ALTER TABLE `".DBPREFIX."access_users` ADD `validity` INT UNSIGNED NOT NULL DEFAULT '0' AFTER `expiration`";
        if ($objDatabase->Execute($query) === false) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    } else {
        try {
            \Cx\Lib\UpdateUtil::sql("UPDATE `".DBPREFIX."access_users` SET `expiration` = `validity`*60*60*24+`regdate` WHERE `expiration` = 0 AND `validity` > 0");
        }
        catch (\Cx\Lib\UpdateException $e) {
            // we COULD do something else here..
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
    }

\DBG::msg('003');

    /***********************************
     *
     * MIGRATE COMMUNITY CONTENT PAGES
     *
     **********************************/
    // only execute this part for versions < 2.0.0
    $pattern = array(
        '/section=community&(amp;)?cmd=profile/',
        '/section=community&(amp;)?cmd=register/',
        '/section=community/',
    );
    $replacement = array(
        'section=access&$1cmd=settings',
        'section=access&$1cmd=signup',
        'section=access',
    );
    try {
        \Cx\Lib\UpdateUtil::migrateContentPageUsingRegex(array(), $pattern, $replacement, array('content', 'target'), '2.0.0');
        \Cx\Lib\UpdateUtil::migrateContentPageUsingRegex(array('module' => 'community'), array('/community/', '/profile/', '/register/'), array('access', 'settings', 'signup'), array('module', 'cmd'), '2.0.0');
    }
    catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }



    /***********************************
     *
     * CREATE PROFILE ATTRIBUTE TABLES
     *
     **********************************/
    try {
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'access_user_attribute',
            array(
                'id'                 => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'parent_id'          => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'id'),
                'type'               => array('type' => 'ENUM(\'text\',\'textarea\',\'mail\',\'uri\',\'date\',\'image\',\'checkbox\',\'menu\',\'menu_option\',\'group\',\'frame\',\'history\')', 'notnull' => true, 'default' => 'text', 'after' => 'parent_id'),
                'mandatory'          => array('type' => 'ENUM(\'0\',\'1\')', 'notnull' => true, 'default' => '0', 'after' => 'type'),
                'sort_type'          => array('type' => 'ENUM(\'asc\',\'desc\',\'custom\')', 'notnull' => true, 'default' => 'asc', 'after' => 'mandatory'),
                'order_id'           => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'sort_type'),
                'access_special'     => array('type' => 'ENUM(\'\',\'menu_select_higher\',\'menu_select_lower\')', 'notnull' => true, 'default' => '', 'after' => 'order_id'),
                'access_id'          => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'access_special')
            ),
            array(),
            'InnoDB'
        );

        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'access_user_attribute_name',
            array(
                'attribute_id'       => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true),
                'lang_id'            => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true, 'after' => 'attribute_id'),
                'name'               => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'lang_id')
            ), array(),
            'InnoDB'
        );

        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'access_user_attribute_value',
            array(
                'attribute_id'       => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true),
                'user_id'            => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true, 'after' => 'attribute_id'),
                'history_id'         => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true, 'after' => 'user_id'),
                'value'              => array('type' => 'text', 'after' => 'history_id')
            ),
            array(
                'value'              => array('fields' => array('value'), 'type' => 'FULLTEXT')
            )
        );

        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'access_user_core_attribute',
            array(
                'id'                 => array('type' => 'VARCHAR(25)', 'primary' => true),
                'mandatory'          => array('type' => 'ENUM(\'0\',\'1\')', 'notnull' => true, 'default' => '0', 'after' => 'id'),
                'sort_type'          => array('type' => 'ENUM(\'asc\',\'desc\',\'custom\')', 'notnull' => true, 'default' => 'asc', 'after' => 'mandatory'),
                'order_id'           => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'sort_type'),
                'access_special'     => array('type' => 'ENUM(\'\',\'menu_select_higher\',\'menu_select_lower\')', 'notnull' => true, 'default' => '', 'after' => 'order_id'),
                'access_id'          => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'access_special')
            ),
            array(),
            'InnoDB'
        );



        /************************
         *
         * ADD USER TITLE TABLE
         *
         ***********************/
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'access_user_title',
            array(
                'id'             => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'title'          => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'id'),
                'order_id'       => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'title')
            ),
            array(
                'title'          => array('fields' => array('title'), 'type' => 'UNIQUE')
            ),
            'InnoDB'
        );

        $arrDefaultTitle = array(
            'Sehr geehrte Frau',
            'Sehr geehrter Herr',
            'Dear Ms',
            'Dear Mr',
            'Madame',
            'Monsieur'
        );

        foreach ($arrDefaultTitle as $title) {
            \Cx\Lib\UpdateUtil::sql("INSERT INTO `".DBPREFIX."access_user_title` SET `title` = '".$title."' ON DUPLICATE KEY UPDATE `id` = `id`");
        }
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }



    /******************************
     *
     * REMOVE OBSOLETE ACCESS IDS
     *
     *****************************/
    $query = 'DELETE FROM `'.DBPREFIX.'access_group_static_ids` WHERE `access_id` IN (28, 29, 30, 33, 34, 36)';
    if ($objDatabase->Execute($query) === false) {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }



    /*******************
     *
     * MIGRATE SESSION
     *
     ******************/
    $arrColumns = $objDatabase->MetaColumnNames(DBPREFIX.'sessions');
    if ($arrColumns === false) {
        setUpdateMsg(sprintf($_ARRAYLANG['TXT_UNABLE_GETTING_DATABASE_TABLE_STRUCTURE'], DBPREFIX.'sessions'));
        return false;
    }

    if (!in_array('user_id', $arrColumns)) {
         $query = "
            ALTER TABLE `".DBPREFIX."sessions`
             DROP `username`,
              ADD `user_id` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `status`";
        if ($objDatabase->Execute($query) === false) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }

    /***************************************
     *
     * ADD CHECKBOX PROFILE ATTRIBUTE TYPE
     *
     **************************************/
    $query = "ALTER TABLE `".DBPREFIX."access_user_attribute` CHANGE `type` `type` enum('text','textarea','mail','uri','date','image','checkbox','menu','menu_option','group','frame','history') NOT NULL DEFAULT 'text'";
    if ($objDatabase->Execute($query) === false) {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }



    // Currently, this is only here to create the u2u_active field.. but instead of adding
    // 10 lines for each new field in the future, why not just extend this block
    try{
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'access_users',
            array(
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
                'u2u_active'             => array('type' => 'ENUM(\'0\',\'1\')', 'notnull' => true, 'default' => '1', 'after' => 'restore_key_time')
            ),
            array(
                'username'               => array('fields' => array('username'))
            )
        );

        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'access_user_groups',
            array(
                'group_id'               => array('type' => 'INT(6)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'group_name'             => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => '', 'after' => 'group_id'),
                'group_description'      => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'group_name'),
                'is_active'              => array('type' => 'TINYINT(4)', 'notnull' => true, 'default' => '1', 'after' => 'group_description'),
                'type'                   => array('type' => 'ENUM(\'frontend\',\'backend\')', 'notnull' => true, 'default' => 'frontend', 'after' => 'is_active'),
                'homepage'               => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'type')
            )
        );
    }
    catch (\Cx\Lib\UpdateException $e) {
        // we COULD do something else here..
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }
\DBG::msg('004');

    // only update if installed version is at least a version 2.0.0
    // older versions < 2.0 have a complete other structure of the content page and must therefore completely be reinstalled
    if (!$objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '2.0.0')) {
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

                // add missing access_captcha template block
                if (!preg_match('/<!--\s+BEGIN\s+access_captcha\s+-->.*<!--\s+END\s+access_captcha\s+-->/ms', $content)) {
                    $content = preg_replace('/(<\/fieldset>|)(\s*)(<p[^>]*>|)(\{ACCESS_SIGNUP_BUTTON\})(<\/p>|)/ms', '$2<!-- BEGIN access_captcha -->$2$3<label>{TXT_ACCESS_CAPTCHA}</label>{ACCESS_CAPTCHA_CODE}$5$2<!-- END access_captcha -->$2$1$2$3$4$5', $content);
                }

                // add missing access_newsletter template block
                if (!preg_match('/<!--\s+BEGIN\s+access_newsletter\s+-->.*<!--\s+END\s+access_newsletter\s+-->/ms', $content)) {
                    $content = preg_replace('/(\s*)(<p[^>]*>|)(\{ACCESS_SIGNUP_BUTTON\})(<\/p>|)/ms', '$1<!-- BEGIN access_newsletter -->$1<fieldset><legend>Newsletter abonnieren</legend>$1    <!-- BEGIN access_newsletter_list -->$1    <p>$1        <label for="access_user_newsletters-{ACCESS_NEWSLETTER_ID}">&nbsp;{ACCESS_NEWSLETTER_NAME}</label>$1        <input type="checkbox" name="access_user_newsletters[]" id="access_user_newsletters-{ACCESS_NEWSLETTER_ID}" value="{ACCESS_NEWSLETTER_ID}"{ACCESS_NEWSLETTER_SELECTED} />$1    </p>$1    <!-- END access_newsletter_list -->$1</fieldset>$1<!-- END access_newsletter -->$1$2$3$4', $content);
                }

                return $content;
            };

            \Cx\Lib\UpdateUtil::migrateContentPageUsingRegexCallback(array('module' => 'access', 'cmd' => 'signup'), $search, $callback, array('content'), '3.0.1');
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
    }



    /***************************************
     *
     * ADD NETWORK TABLE FOR SOCIAL LOGIN
     *
     **************************************/
    if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.0.3')) {
        try {
            \Cx\Lib\UpdateUtil::table(
                DBPREFIX.'access_user_network',
                array(
                    'id'                 => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                    'oauth_provider'     => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => '', 'after' => 'id'),
                    'oauth_id'           => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => '', 'after' => 'oauth_provider'),
                    'user_id'            => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'oauth_id')
                ),
                array(),
                'InnoDB'
            );
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }

        /***************************************
         *
         * ADD NEW VALUES FOR SOCIAL LOGIN
         *
         **************************************/
        try {
            \Cx\Lib\UpdateUtil::sql("INSERT INTO `".DBPREFIX."access_settings` (`key`, `value`, `status`) VALUES ('sociallogin', '', '0') ON DUPLICATE KEY UPDATE `key` = `key`");
            \Cx\Lib\UpdateUtil::sql("INSERT INTO `".DBPREFIX."access_settings` (`key`, `value`, `status`) VALUES ('sociallogin_show_signup', '', 0) ON DUPLICATE KEY UPDATE `key` = `key`");
            \Cx\Lib\UpdateUtil::sql("INSERT INTO `".DBPREFIX."access_settings` (`key`, `value`, `status`) VALUES ('use_usernames', '0', '1') ON DUPLICATE KEY UPDATE `key` = `key`");
            \Cx\Lib\UpdateUtil::sql("INSERT INTO `".DBPREFIX."access_settings` (`key`, `value`, `status`) VALUES ('sociallogin_assign_to_groups', '3', '0') ON DUPLICATE KEY UPDATE `key` = `key`");
            \Cx\Lib\UpdateUtil::sql("INSERT INTO `".DBPREFIX."access_settings` (`key`, `value`, `status`) VALUES ('sociallogin_active_automatically', '', '1') ON DUPLICATE KEY UPDATE `key` = `key`");
            \Cx\Lib\UpdateUtil::sql("INSERT INTO `".DBPREFIX."core_setting` (`section`, `name`, `group`, `type`, `value`, `values`, `ord`) VALUES ('access', 'providers', 'sociallogin', 'text', '{\"facebook\":{\"active\":\"0\",\"settings\":[\"\",\"\"]},\"twitter\":{\"active\":\"0\",\"settings\":[\"\",\"\"]},\"google\":{\"active\":\"0\",\"settings\":[\"\",\"\",\"\"]}}', '', '0') ON DUPLICATE KEY UPDATE `section` = `section`");
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }

        /**
         * Content page
         * access signup
         */
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

            \Cx\Lib\UpdateUtil::migrateContentPageUsingRegexCallback(array('module' => 'access', 'cmd' => 'signup'), $search, $callback, array('content'), '3.0.2');
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
    }


    /***************************************
     *
     * ADD SETTING FOR SOCIAL LOGIN
     *
     **************************************/
    if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.0.3')) {
        try {
            \Cx\Lib\UpdateUtil::sql("INSERT INTO `".DBPREFIX."access_settings` (`key`, `value`, `status`) VALUES ('sociallogin_activation_timeout', '10', '0') ON DUPLICATE KEY UPDATE `key` = `key`");
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
    }

    /***************************************
     *
     * STRICT_TRANS_TABLES ISSUE FIX FOR PROFILE TABLE
     *
     **************************************/
    if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.1.0')) {
        try {
            \Cx\Lib\UpdateUtil::sql("ALTER TABLE `".DBPREFIX."access_user_profile` CHANGE `interests` `interests` TEXT NULL");
            \Cx\Lib\UpdateUtil::sql("ALTER TABLE `".DBPREFIX."access_user_profile` CHANGE `signature` `signature` TEXT NULL");

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
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
    }

    /************************************************
    * BUGFIX:    Set write access to the upload dir  *
    ************************************************/
    // This is obsolete due to the new \Cx\Lib\FileSystem
    /*
    require_once ASCMS_FRAMEWORK_PATH.'/File.class.php';
    $objFile = new File();
    if (is_writeable(ASCMS_ACCESS_PROFILE_IMG_PATH) || $objFile->setChmod(ASCMS_ACCESS_PROFILE_IMG_PATH, ASCMS_ACCESS_PROFILE_IMG_WEB_PATH, '')) {
        if ($mediaDir = @opendir(ASCMS_ACCESS_PROFILE_IMG_PATH)) {
            while($file = readdir($mediaDir)) {
                if ($file != '.' && $file != '..') {
                    if (!is_writeable(ASCMS_ACCESS_PROFILE_IMG_PATH.'/'.$file) && !$objFile->setChmod(ASCMS_ACCESS_PROFILE_IMG_PATH.'/', ASCMS_ACCESS_PROFILE_IMG_WEB_PATH.'/', $file)) {
                        setUpdateMsg(sprintf($_ARRAYLANG['TXT_SET_WRITE_PERMISSON_TO_FILE'], ASCMS_ACCESS_PROFILE_IMG_PATH.'/'.$file, $_CORELANG['TXT_UPDATE_TRY_AGAIN']), 'msg');
                        return false;
                    }
                }
            }
        } else {
            setUpdateMsg(sprintf($_ARRAYLANG['TXT_SET_WRITE_PERMISSON_TO_DIR_AND_CONTENT'], ASCMS_ACCESS_PROFILE_IMG_PATH.'/', $_CORELANG['TXT_UPDATE_TRY_AGAIN']), 'msg');
            return false;
        }
    } else {
        setUpdateMsg(sprintf($_ARRAYLANG['TXT_SET_WRITE_PERMISSON_TO_DIR_AND_CONTENT'], ASCMS_ACCESS_PROFILE_IMG_PATH.'/', $_CORELANG['TXT_UPDATE_TRY_AGAIN']), 'msg');
        return false;
    }

    require_once ASCMS_FRAMEWORK_PATH.'/File.class.php';
    $objFile = new File();
    if (is_writeable(ASCMS_ACCESS_PHOTO_IMG_PATH) || $objFile->setChmod(ASCMS_ACCESS_PHOTO_IMG_PATH, ASCMS_ACCESS_PHOTO_IMG_WEB_PATH, '')) {
        if ($mediaDir = @opendir(ASCMS_ACCESS_PHOTO_IMG_PATH)) {
            while($file = readdir($mediaDir)) {
                if ($file != '.' && $file != '..') {
                    if (!is_writeable(ASCMS_ACCESS_PHOTO_IMG_PATH.'/'.$file) && !$objFile->setChmod(ASCMS_ACCESS_PHOTO_IMG_PATH.'/', ASCMS_ACCESS_PHOTO_IMG_WEB_PATH.'/', $file)) {
                        setUpdateMsg(sprintf($_ARRAYLANG['TXT_SET_WRITE_PERMISSON_TO_FILE'], ASCMS_ACCESS_PHOTO_IMG_PATH.'/'.$file, $_CORELANG['TXT_UPDATE_TRY_AGAIN']), 'msg');
                        return false;
                    }
                }
            }
        } else {
            setUpdateMsg(sprintf($_ARRAYLANG['TXT_SET_WRITE_PERMISSON_TO_DIR_AND_CONTENT'], ASCMS_ACCESS_PHOTO_IMG_PATH.'/', $_CORELANG['TXT_UPDATE_TRY_AGAIN']), 'msg');
            return false;
        }
    } else {
        setUpdateMsg(sprintf($_ARRAYLANG['TXT_SET_WRITE_PERMISSON_TO_DIR_AND_CONTENT'], ASCMS_ACCESS_PHOTO_IMG_PATH.'/', $_CORELANG['TXT_UPDATE_TRY_AGAIN']), 'msg');
        return false;
    }*/

    return true;
}
