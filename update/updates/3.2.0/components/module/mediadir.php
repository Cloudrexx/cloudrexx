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

function _mediadirUpdate()
{
    global $_ARRAYLANG, $_CORELANG, $objUpdate, $_CONFIG;

    //create / update tables
    try {
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_mediadir_categories',
            array(
                  'id'                     => array('type' => 'INT(7)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                    'parent_id'              => array('type' => 'INT(7)', 'after' => 'id'),
                    'order'                  => array('type' => 'INT(7)', 'after' => 'parent_id'),
                    'show_subcategories'     => array('type' => 'INT(11)', 'after' => 'order'),
                    'show_entries'           => array('type' => 'INT(1)', 'after' => 'show_subcategories'),
                    'picture'                => array('type' => 'mediumtext', 'after' => 'show_entries'),
                    'active'                 => array('type' => 'INT(1)', 'after' => 'picture')
            )
       );

       \Cx\Lib\UpdateUtil::table(
           DBPREFIX.'module_mediadir_categories_names',
           array(
                 'lang_id'                    => array('type' => 'INT(1)'),
                    'category_id'                => array('type' => 'INT(7)', 'after' => 'lang_id'),
                    'category_name'              => array('type' => 'VARCHAR(255)', 'after' => 'category_id'),
                    'category_description'       => array('type' => 'mediumtext', 'after' => 'category_name')
           ),
           array(
                    'lang_id'                    => array('fields' => array('lang_id')),
                    'category_id'                => array('fields' => array('category_id'))
           )
       );

      \Cx\Lib\UpdateUtil::table(
          DBPREFIX.'module_mediadir_comments',
          array(
              'id'                 => array('type' => 'INT(7)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
              'entry_id'           => array('type' => 'INT(7)', 'after' => 'id'),
              'added_by'           => array('type' => 'VARCHAR(255)', 'after' => 'entry_id'),
              'date'               => array('type' => 'VARCHAR(100)', 'after' => 'added_by'),
              'ip'                 => array('type' => 'VARCHAR(100)', 'after' => 'date'),
              'name'               => array('type' => 'VARCHAR(255)', 'after' => 'ip'),
              'mail'               => array('type' => 'VARCHAR(255)', 'after' => 'name'),
              'url'                => array('type' => 'VARCHAR(255)', 'after' => 'mail'),
              'notification'       => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'url'),
              'comment'            => array('type' => 'mediumtext', 'after' => 'notification')
          )
      );

      \Cx\Lib\UpdateUtil::table(
          DBPREFIX.'module_mediadir_entries',
          array(
              'id'                         => array('type' => 'INT(10)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
              'order'                      => array('type' => 'INT(7)', 'notnull' => true, 'default' => '0', 'after' => 'id'),
              'form_id'                    => array('type' => 'INT(7)', 'after' => 'order'),
              'create_date'                => array('type' => 'INT(50)', 'after' => 'form_id'),
              'update_date'                => array('type' => 'INT(50)', 'after' => 'create_date'),
              'validate_date'              => array('type' => 'INT(50)', 'after' => 'update_date'),
              'added_by'                   => array('type' => 'INT(10)', 'after' => 'validate_date'),
              'updated_by'                 => array('type' => 'INT(10)', 'after' => 'added_by'),
              'lang_id'                    => array('type' => 'INT(1)', 'after' => 'updated_by'),
              'hits'                       => array('type' => 'INT(10)', 'after' => 'lang_id'),
              'popular_hits'               => array('type' => 'INT(10)', 'after' => 'hits'),
              'popular_date'               => array('type' => 'VARCHAR(20)', 'after' => 'popular_hits'),
              'last_ip'                    => array('type' => 'VARCHAR(50)', 'after' => 'popular_date'),
              'ready_to_confirm'           => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'last_ip'),
              'confirmed'                  => array('type' => 'INT(1)', 'after' => 'ready_to_confirm'),
              'active'                     => array('type' => 'INT(1)', 'after' => 'confirmed'),
              'duration_type'              => array('type' => 'INT(1)', 'after' => 'active'),
              'duration_start'             => array('type' => 'INT(50)', 'after' => 'duration_type'),
              'duration_end'               => array('type' => 'INT(50)', 'after' => 'duration_start'),
              'duration_notification'      => array('type' => 'INT(1)', 'after' => 'duration_end'),
              'translation_status'         => array('type' => 'VARCHAR(255)', 'after' => 'duration_notification')
          ),
          array(
                'lang_id'                    => array('fields' => array('lang_id')),
              'active'                     => array('fields' => array('active'))
          )
      );

      \Cx\Lib\UpdateUtil::table(
          DBPREFIX.'module_mediadir_form_names',
          array(
              'lang_id'                => array('type' => 'INT(1)'),
              'form_id'                => array('type' => 'INT(7)', 'after' => 'lang_id'),
              'form_name'              => array('type' => 'VARCHAR(255)', 'after' => 'form_id'),
              'form_description'       => array('type' => 'mediumtext', 'notnull' => true, 'after' => 'form_name')
          )
      );

      \Cx\Lib\UpdateUtil::table(
          DBPREFIX.'module_mediadir_forms',
          array(
              'id'                         => array('type' => 'INT(7)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
              'order'                      => array('type' => 'INT(7)', 'after' => 'id'),
              'picture'                    => array('type' => 'mediumtext', 'after' => 'order'),
              'active'                     => array('type' => 'INT(1)', 'after' => 'picture'),
              'use_level'                  => array('type' => 'INT(1)', 'after' => 'active'),
              'use_category'               => array('type' => 'INT(1)', 'after' => 'use_level'),
              'use_ready_to_confirm'       => array('type' => 'INT(1)', 'after' => 'use_category'),
              'cmd'                        => array('type' => 'VARCHAR(50)', 'after' => 'use_ready_to_confirm')
          )
      );

      \Cx\Lib\UpdateUtil::table(
          DBPREFIX.'module_mediadir_inputfield_names',
          array(
              'lang_id'                => array('type' => 'INT(10)'),
              'form_id'                => array('type' => 'INT(7)', 'after' => 'lang_id'),
              'field_id'               => array('type' => 'INT(10)', 'after' => 'form_id'),
              'field_name'             => array('type' => 'VARCHAR(255)', 'after' => 'field_id'),
              'field_default_value'    => array('type' => 'mediumtext', 'after' => 'field_name'),
              'field_info'             => array('type' => 'mediumtext', 'after' => 'field_default_value')
          ),
          array(
              'field_id'               => array('fields' => array('field_id')),
              'lang_id'                => array('fields' => array('lang_id'))
          )
      );

      \Cx\Lib\UpdateUtil::table(
          DBPREFIX.'module_mediadir_inputfield_types',
          array(
              'id'             => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
              'name'           => array('type' => 'VARCHAR(255)', 'after' => 'id'),
              'active'         => array('type' => 'INT(1)', 'after' => 'name'),
              'multi_lang'     => array('type' => 'INT(1)', 'after' => 'active'),
              'exp_search'     => array('type' => 'INT(7)', 'after' => 'multi_lang'),
              'dynamic'        => array('type' => 'INT(1)', 'after' => 'exp_search'),
              'comment'        => array('type' => 'VARCHAR(255)', 'after' => 'dynamic')
          ),
          array(
                'name'           => array('fields' => array('name'), 'type' => 'UNIQUE'),
          )
      );

      \Cx\Lib\UpdateUtil::table(
          DBPREFIX.'module_mediadir_inputfield_verifications',
          array(
              'id'         => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
              'name'       => array('type' => 'VARCHAR(255)', 'after' => 'id'),
              'regex'      => array('type' => 'VARCHAR(255)', 'after' => 'name')
          ),
          array(
              'name'       => array('fields' => array('name'), 'type' => 'UNIQUE')
          )
      );

      \Cx\Lib\UpdateUtil::table(
          DBPREFIX.'module_mediadir_inputfields',
          array(
              'id'                 => array('type' => 'INT(10)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
              'form'               => array('type' => 'INT(7)', 'after' => 'id'),
              'type'               => array('type' => 'INT(10)', 'after' => 'form'),
              'verification'       => array('type' => 'INT(10)', 'after' => 'type'),
              'search'             => array('type' => 'INT(10)', 'after' => 'verification'),
              'required'           => array('type' => 'INT(10)', 'after' => 'search'),
              'order'              => array('type' => 'INT(10)', 'after' => 'required'),
              'show_in'            => array('type' => 'INT(10)', 'after' => 'order'),
              'context_type'       => array('type' => 'ENUM(\'none\',\'title\',\'address\',\'zip\',\'city\',\'country\')', 'after' => 'show_in')
          )
      );

      \Cx\Lib\UpdateUtil::table(
          DBPREFIX.'module_mediadir_level_names',
          array(
              'lang_id'                => array('type' => 'INT(1)'),
              'level_id'               => array('type' => 'INT(7)', 'after' => 'lang_id'),
              'level_name'             => array('type' => 'VARCHAR(255)', 'after' => 'level_id'),
              'level_description'      => array('type' => 'mediumtext', 'after' => 'level_name')
          ),
          array(
              'lang_id'                => array('fields' => array('lang_id')),
              'category_id'            => array('fields' => array('level_id'))
          )
      );

      \Cx\Lib\UpdateUtil::table(
          DBPREFIX.'module_mediadir_levels',
          array(
              'id'                 => array('type' => 'INT(7)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
              'parent_id'          => array('type' => 'INT(7)', 'after' => 'id'),
              'order'              => array('type' => 'INT(7)', 'after' => 'parent_id'),
              'show_sublevels'     => array('type' => 'INT(11)', 'after' => 'order'),
              'show_categories'    => array('type' => 'INT(1)', 'after' => 'show_sublevels'),
              'show_entries'       => array('type' => 'INT(1)', 'after' => 'show_categories'),
              'picture'            => array('type' => 'mediumtext', 'after' => 'show_entries'),
              'active'             => array('type' => 'INT(1)', 'after' => 'picture')
          )
      );

      \Cx\Lib\UpdateUtil::table(
          DBPREFIX.'module_mediadir_mail_actions',
          array(
              'id'                     => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
              'name'                   => array('type' => 'VARCHAR(255)', 'after' => 'id'),
              'default_recipient'      => array('type' => 'ENUM(\'admin\',\'author\')', 'after' => 'name'),
              'need_auth'              => array('type' => 'INT(11)', 'after' => 'default_recipient')
          )
      );

      \Cx\Lib\UpdateUtil::table(
          DBPREFIX.'module_mediadir_mails',
          array(
              'id'             => array('type' => 'INT(7)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
              'title'          => array('type' => 'VARCHAR(255)', 'after' => 'id'),
              'content'        => array('type' => 'longtext', 'after' => 'title'),
              'recipients'     => array('type' => 'mediumtext', 'after' => 'content'),
              'lang_id'        => array('type' => 'INT(1)', 'after' => 'recipients'),
              'action_id'      => array('type' => 'INT(1)', 'after' => 'lang_id'),
              'is_default'     => array('type' => 'INT(1)', 'after' => 'action_id'),
              'active'         => array('type' => 'INT(1)', 'after' => 'is_default')
          )
      );

      \Cx\Lib\UpdateUtil::table(
          DBPREFIX.'module_mediadir_masks',
          array(
              'id'         => array('type' => 'INT(7)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
              'title'      => array('type' => 'VARCHAR(255)', 'after' => 'id'),
              'fields'     => array('type' => 'mediumtext', 'after' => 'title'),
              'active'     => array('type' => 'INT(11)', 'after' => 'fields'),
              'form_id'    => array('type' => 'INT(11)', 'after' => 'active')
          )
      );

      \Cx\Lib\UpdateUtil::table(
          DBPREFIX.'module_mediadir_order_rel_forms_selectors',
          array(
              'selector_id'        => array('type' => 'INT(7)'),
              'form_id'            => array('type' => 'INT(7)', 'after' => 'selector_id'),
              'selector_order'     => array('type' => 'INT(7)', 'after' => 'form_id'),
              'exp_search'         => array('type' => 'INT(1)', 'notnull' => true, 'after' => 'selector_order')
          )
      );

      \Cx\Lib\UpdateUtil::table(
          DBPREFIX.'module_mediadir_rel_entry_categories',
          array(
              'entry_id'       => array('type' => 'INT(10)'),
              'category_id'    => array('type' => 'INT(10)', 'after' => 'entry_id')
          ),
          array(
              'entry_id'       => array('fields' => array('entry_id')),
              'category_id'    => array('fields' => array('category_id'))
          )
      );

      \Cx\Lib\UpdateUtil::table(
          DBPREFIX.'module_mediadir_rel_entry_inputfields_clean',
          array(
              'id'             => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
              'entry_id'       => array('type' => 'INT(7)', 'after' => 'id'),
              'lang_id'        => array('type' => 'INT(7)', 'after' => 'entry_id'),
              'form_id'        => array('type' => 'INT(7)', 'after' => 'lang_id'),
              'field_id'       => array('type' => 'INT(7)', 'after' => 'form_id'),
              'value'          => array('type' => 'longtext', 'after' => 'field_id')
          ),
          array(
              'value'          => array('fields' => array('value'), 'type' => 'FULLTEXT')
          )
      );

      \Cx\Lib\UpdateUtil::sql('
          INSERT INTO `'.DBPREFIX.'module_mediadir_rel_entry_inputfields_clean`
          SELECT NULL, `entry_id`, `lang_id`, `form_id`, `field_id`, `value`
          FROM `'.DBPREFIX.'module_mediadir_rel_entry_inputfields`
          GROUP BY `entry_id`, `form_id`, `field_id`, `lang_id`, `value`
      ');

      \Cx\Lib\UpdateUtil::sql('
          TRUNCATE `'.DBPREFIX.'module_mediadir_rel_entry_inputfields`
      ');

      \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_mediadir_rel_entry_inputfields',
            array(
                'entry_id'       => array('type' => 'INT(7)'),
                'lang_id'        => array('type' => 'INT(7)', 'after' => 'entry_id'),
                'form_id'        => array('type' => 'INT(7)', 'after' => 'lang_id'),
                'field_id'       => array('type' => 'INT(7)', 'after' => 'form_id'),
                'value'          => array('type' => 'longtext', 'after' => 'field_id')
            ),
            array(
                'entry_id'       => array('fields' => array('entry_id','lang_id','form_id','field_id'), 'type' => 'UNIQUE'),
                'value'          => array('fields' => array('value'), 'type' => 'FULLTEXT')
            )
      );

      \Cx\Lib\UpdateUtil::sql('
          INSERT IGNORE INTO `'.DBPREFIX.'module_mediadir_rel_entry_inputfields`
          SELECT `entry_id`, `lang_id`, `form_id`, `field_id`, `value`
          FROM `'.DBPREFIX.'module_mediadir_rel_entry_inputfields_clean`
          ORDER BY `id` DESC
      ');

      \Cx\Lib\UpdateUtil::sql('
          DROP TABLE `'.DBPREFIX.'module_mediadir_rel_entry_inputfields_clean`
      ');

      \Cx\Lib\UpdateUtil::table(
          DBPREFIX.'module_mediadir_rel_entry_levels',
          array(
              'entry_id'       => array('type' => 'INT(10)'),
              'level_id'       => array('type' => 'INT(10)', 'after' => 'entry_id')
          ),
          array(
              'entry_id'       => array('fields' => array('entry_id')),
              'category_id'    => array('fields' => array('level_id'))
          )
      );

      \Cx\Lib\UpdateUtil::table(
          DBPREFIX.'module_mediadir_settings',
          array(
                'id'         => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
              'name'       => array('type' => 'VARCHAR(100)', 'after' => 'id'),
              'value'      => array('type' => 'VARCHAR(255)', 'after' => 'name')
          ),
          array(
              'name'       => array('fields' => array('name'), 'type' => 'UNIQUE')
          )
      );

      \Cx\Lib\UpdateUtil::table(
          DBPREFIX.'module_mediadir_settings_num_categories',
          array(
              'group_id'           => array('type' => 'INT(1)'),
              'num_categories'     => array('type' => 'VARCHAR(10)', 'notnull' => true, 'after' => 'group_id')
          )
      );

      \Cx\Lib\UpdateUtil::table(
          DBPREFIX.'module_mediadir_settings_num_entries',
          array(
                'group_id'       => array('type' => 'INT(1)'),
                'num_entries'    => array('type' => 'VARCHAR(10)', 'notnull' => true, 'default' => 'n', 'after' => 'group_id')
          )
      );

      \Cx\Lib\UpdateUtil::table(
          DBPREFIX.'module_mediadir_settings_num_levels',
          array(
              'group_id'       => array('type' => 'INT(1)'),
              'num_levels'     => array('type' => 'VARCHAR(10)', 'notnull' => true, 'after' => 'group_id')
          )
      );

      \Cx\Lib\UpdateUtil::table(
          DBPREFIX.'module_mediadir_settings_perm_group_forms',
          array(
              'group_id'           => array('type' => 'INT(7)'),
              'form_id'            => array('type' => 'INT(1)', 'after' => 'group_id'),
              'status_group'       => array('type' => 'INT(1)', 'notnull' => true, 'after' => 'form_id')
          )
      );

      \Cx\Lib\UpdateUtil::table(
          DBPREFIX.'module_mediadir_votes',
          array(
              'id'             => array('type' => 'INT(7)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
              'entry_id'       => array('type' => 'INT(7)', 'after' => 'id'),
              'added_by'       => array('type' => 'VARCHAR(255)', 'after' => 'entry_id'),
              'date'           => array('type' => 'VARCHAR(100)', 'after' => 'added_by'),
              'ip'             => array('type' => 'VARCHAR(100)', 'after' => 'date'),
              'vote'           => array('type' => 'INT(11)', 'after' => 'ip')
          )
      );

        // remove the script tag at the beginning of the mediadir pages
        \Cx\Lib\UpdateUtil::migrateContentPageUsingRegex(array('module' => 'mediadir'), '/^\s*(<script[^>]+>.+?Shadowbox.+?<\/script>)+/sm', '', array('content'), '3.0.4');

    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    //insert default settings
    try {
        //mediadir_settings
        $arrValues = array(array(1,'settingsShowCategoryDescription','1'),array(2,'settingsShowCategoryImage','1'),array(3,'settingsCategoryOrder','1'),array(4,'settingsShowLevels','1'),array(5,'settingsShowLevelDescription','0'),array(6,'settingsShowLevelImage','0'),array(7,'settingsLevelOrder','1'),array(8,'settingsConfirmNewEntries','1'),array(9,'categorySelectorOrder','9'),array(10,'levelSelectorOrder','10'),array(11,'settingsConfirmUpdatedEntries','0'),array(12,'settingsCountEntries','0'),array(13,'settingsThumbSize','120'),array(14,'settingsNumGalleryPics','10'),array(15,'settingsEncryptFilenames','1'),array(16,'settingsAllowAddEntries','1'),array(17,'settingsAllowDelEntries','1'),array(18,'settingsAllowEditEntries','1'),array(19,'settingsAddEntriesOnlyCommunity','1'),array(20,'settingsLatestNumXML','10'),array(21,'settingsLatestNumOverview','5'),array(22,'settingsLatestNumBackend','5'),array(23,'settingsLatestNumFrontend','10'),array(24,'settingsPopularNumFrontend','10'),array(25,'settingsPopularNumRestore','30'),array(26,'settingsLatestNumHeadlines','6'),array(27,'settingsGoogleMapStartposition','46.749647513758326,7.6300048828125,8'),array(28,'settingsAllowVotes','1'),array(29,'settingsVoteOnlyCommunity','0'),array(30,'settingsAllowComments','1'),array(31,'settingsCommentOnlyCommunity','0'),array(32,'settingsGoogleMapAllowKml','0'),array(33,'settingsShowEntriesInAllLang','1'),array(34,'settingsPagingNumEntries','10'),array(35,'settingsGoogleMapType','0'),array(36,'settingsClassificationPoints','5'),array(37,'settingsClassificationSearch','1'),array(38,'settingsEntryDisplaydurationType','1'),array(39,'settingsEntryDisplaydurationValue','0'),array(40,'settingsEntryDisplaydurationValueType','1'),array(41,'settingsEntryDisplaydurationNotification','0'),array(42,'categorySelectorExpSearch','9'),array(43,'levelSelectorExpSearch','10'),array(44,'settingsTranslationStatus','0'),array(45,'settingsReadyToConfirm','0'),array(46,'settingsImageFilesize','300'),array(47,'settingsActiveLanguages','1,2,3'),array(48,'settingsFrontendUseMultilang','0'),array(49,'settingsIndividualEntryOrder','0'));
        foreach($arrValues as $arrValue) {
            if(\Cx\Lib\UpdateUtil::sql('SELECT 1 FROM '.DBPREFIX.'module_mediadir_settings WHERE name="'.$arrValue[1].'"')->EOF) {
                \Cx\Lib\UpdateUtil::sql('INSERT INTO '.DBPREFIX.'module_mediadir_settings VALUES('.$arrValue[0].',"'.$arrValue[1].'","'.$arrValue[2].'")');
            }
        }

        //mediadir_settings_num_categories
        $arrValues = array(array(3,'n'),array(4,'n'),array(5,'n'));
        foreach($arrValues as $arrValue) {
            if(\Cx\Lib\UpdateUtil::sql('SELECT 1 FROM '.DBPREFIX.'module_mediadir_settings_num_categories WHERE group_id='.$arrValue[0])->EOF) {
                \Cx\Lib\UpdateUtil::sql('INSERT INTO '.DBPREFIX.'module_mediadir_settings_num_categories VALUES('.$arrValue[0].',"'.$arrValue[1].'")');
            }
        }

        //mediadir_settings_num_entries
        $arrValues = array(array(3,'n'),array(4,'n'),array(5,'n'));
        foreach($arrValues as $arrValue) {
            if(\Cx\Lib\UpdateUtil::sql('SELECT 1 FROM '.DBPREFIX.'module_mediadir_settings_num_entries WHERE group_id='.$arrValue[0])->EOF) {
                \Cx\Lib\UpdateUtil::sql('INSERT INTO '.DBPREFIX.'module_mediadir_settings_num_entries VALUES('.$arrValue[0].',"'.$arrValue[1].'")');
            }
        }

        //mediadir_settings_num_levels
        $arrValues = array(array(3,'n'),array(4,'n'),array(5,'n'));
        foreach($arrValues as $arrValue) {
            if(\Cx\Lib\UpdateUtil::sql('SELECT 1 FROM '.DBPREFIX.'module_mediadir_settings_num_levels WHERE group_id='.$arrValue[0])->EOF) {
                \Cx\Lib\UpdateUtil::sql('INSERT INTO '.DBPREFIX.'module_mediadir_settings_num_levels VALUES('.$arrValue[0].',"'.$arrValue[1].'")');
            }
        }

        //mediadir_inputfield_verifications
        $arrValues = array(array(1,'normal','.*'),array(2,'e-mail',"^[a-zäàáâöôüûñéè0-9!\\#\\$\\%\\&\\''\\*\\+\\/\\=\\?\\^_\\`\\{\\|\\}\\~-]+(?:\\.[a-zäàáâöôüûñéè0-9!\\#\\$\\%\\&\\" . '"' . "'\\*\\+\\/\\=\\?\\^_\\`\\{\\|\\}\\~-]+)*@(?:[a-zäàáâöôüûñéè0-9](?:[a-zäàáâöôüûñéè0-9-]*[a-zäàáâöôüûñéè0-9])?\\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$"),array(3,'url','^(?:(?:ht|f)tps?\:\/\/)?((([\wÄÀÁÂÖÔÜÛÑÉÈäàáâöôüûñéè\d-]{1,}\.)+[a-z]{2,})|((?:(?:25[0-5]|2[0-4]\d|[01]\d\d|\d?\d)(?:(\.?\d)\.)) {4}))(?:[\w\d]+)?(\/[\w\d\-\.\?\,\'\/\\\+\&\%\$\#\=\~]*)?$'),array(4,'letters','^[A-Za-zÄÀÁÂÖÔÜÛÑÉÈäàáâöôüûñéè\ ]*[A-Za-zÄÀÁÂÖÔÜÛÑÉÈäàáâöôüûñéè]+[A-Za-zÄÀÁÂÖÔÜÛÑÉÈäàáâöôüûñéè\ ]*$'),array(5,'numbers','^[0-9]*$'));
        foreach($arrValues as $arrValue) {
            if(\Cx\Lib\UpdateUtil::sql('SELECT 1 FROM '.DBPREFIX.'module_mediadir_inputfield_verifications WHERE name="'.$arrValue[1].'"')->EOF) {
                \Cx\Lib\UpdateUtil::sql('INSERT INTO '.DBPREFIX.'module_mediadir_inputfield_verifications VALUES('.$arrValue[0].',"'.$arrValue[1].'","'.$arrValue[2].'")');
            } else {
                \Cx\Lib\UpdateUtil::sql('UPDATE '.DBPREFIX.'module_mediadir_inputfield_verifications SET `regex`="'.$arrValue[2].'" WHERE `name`="'.$arrValue[1].'"');
            }
        }

        //mediadir_inputfield_types
        $arrValues = array(array(1,'text',1,1,1,0,''),array(2,'textarea',1,1,1,0,''),array(3,'dropdown',1,0,1,0,''),array(4,'radio',1,0,1,0,''),array(5,'checkbox',1,0,0,0,''),array(7,'file',1,0,0,0,''),array(8,'image',1,0,0,0,''),array(9,'gallery',0,0,0,0,'not yet developed'),array(10,'podcast',0,0,0,0,'not yet developed'),array(11,'classification',1,0,1,0,''),array(12,'link',1,0,0,0,''),array(13,'link_group',1,0,0,0,''),array(14,'rss',0,0,0,0,'not yet developed'),array(15,'google_map',1,0,0,0,''),array(16,'add_step',0,0,0,0,''),array(17,'field_group',0,0,0,0,'not yet developed'),array(18,'label',0,0,0,0,'not yet developed'),array(19,'wysiwyg',0,1,0,0,'developed for OSEC (unstable)'),array(20,'mail',1,0,0,0,''),array(21,'google_weather',1,0,0,0,''),array(22,'relation',0,0,0,0,'developed for OSEC (unstable)'),array(23,'relation_group',0,0,0,0,'developed for OSEC (unstable)'),array(24,'accounts',0,0,0,0,'developed for OSEC (unstable)'),array(25,'country',1,0,0,0,''),array(26,'product_attributes',0,0,1,0,''),array(27,'downloads',0,1,0,1,'developed for CADexchange.ch (unstable)'),array(28,'responsibles',0,1,0,1,'developed for CADexchange.ch (unstable)'),array(29,'references',0,1,0,1,'developed for CADexchange.ch (unstable)'),array(30,'title',0,0,0,0,'developed for CADexchange.ch (unstable)'));
        foreach($arrValues as $arrValue) {
            if(\Cx\Lib\UpdateUtil::sql('SELECT 1 FROM '.DBPREFIX.'module_mediadir_inputfield_types WHERE name="'.$arrValue[1].'"')->EOF) {
                \Cx\Lib\UpdateUtil::sql('INSERT INTO '.DBPREFIX.'module_mediadir_inputfield_types VALUES('.$arrValue[0].',"'.$arrValue[1].'",'.$arrValue[2].','.$arrValue[3].','.$arrValue[4].','.$arrValue[5].',"'.$arrValue[6].'")');
            }
        }

        //mediadir_mail_actions
        $arrValues = array(array(1,'newEntry','admin',0),array(2,'entryAdded','author',1),array(3,'entryConfirmed','author',1),array(4,'entryVoted','author',1),array(5,'entryDeleted','author',1),array(6,'entryEdited','author',1),array(8,'newComment','author',1),array(9,'notificationDisplayduration','admin',0));
        foreach($arrValues as $arrValue) {
            if(\Cx\Lib\UpdateUtil::sql('SELECT 1 FROM '.DBPREFIX.'module_mediadir_mail_actions WHERE name="'.$arrValue[1].'"')->EOF) {
                \Cx\Lib\UpdateUtil::sql('INSERT INTO '.DBPREFIX.'module_mediadir_mail_actions VALUES('.$arrValue[0].',"'.$arrValue[1].'","'.$arrValue[2].'",'.$arrValue[3].')');
            }
        }

        //only insert mails if the table is empty
        if(\Cx\Lib\UpdateUtil::sql('SELECT 1 FROM '.DBPREFIX.'module_mediadir_mails')->EOF) {
            \Cx\Lib\UpdateUtil::sql("INSERT INTO `".DBPREFIX."module_mediadir_mails` (`id`, `title`, `content`, `recipients`, `lang_id`, `action_id`, `is_default`, `active`) VALUES
('23', '[[URL]] - Eintrag erfolgreich bearbeitet', 'Hallo [[FIRSTNAME]] [[LASTNAME]] ([[USERNAME]])

Ihr Eintrag mit dem Titel \"[[TITLE]]\" auf [[URL]] wurde erfolgreich bearbeitet.

Benutzen Sie folgenden Link um direkt zu Ihrem Eintrag zu gelangen:
[[LINK]]

Freundliche Grüsse
[[URL]]-Team

--
Diese Nachricht wurde am [[DATE]] automatisch von Contrexx auf http://[[URL]] generiert.', '', '1', '6', '1', '0'),
('22', '[[URL]] - Eintrag erfolgreich gelöscht', 'Hallo [[FIRSTNAME]] [[LASTNAME]] ([[USERNAME]])

Ihr Eintrag mit dem Titel \"[[TITLE]]\" auf [[URL]] wurde erfolgreich gelöscht.

Freundliche Grüsse
Ihr [[URL]]-Team

--
Diese Nachricht wurde am [[DATE]] automatisch von Contrexx auf http://[[URL]] generiert.', '', '1', '5', '1', '0'),
('21', '[[URL]] - Eintrag wurde bewertet', 'Hallo [[FIRSTNAME]] [[LASTNAME]] ([[USERNAME]])

Zu Ihrem Eintrag mit dem Titel \"[[TITLE]]\" auf [[URL]] wurde eine Bewertung abgegeben.

Benutzen Sie folgenden Link um direkt zu Ihrem Eintrag zu gelangen:
[[LINK]]

Freundliche Grüsse
Ihr [[URL]]-Team

--
Diese Nachricht wurde am [[DATE]] automatisch von Contrexx auf http://[[URL]] generiert.', '', '1', '4', '1', '0'),
('20', '[[URL]] - Ihr Eintrag wurde aufgeschaltet', 'Guten Tag,

Ihr Eintrag \"[[TITLE]]\" wurde geprüft und ist ab sofort einsehbar.

Benutzen Sie folgenden Link um direkt zu ihrem Eintrag zu gelangen:
[[LINK]]


Freundliche Grüsse
Ihr [[URL]]-Team


--
Diese Nachricht wurde am [[DATE]] automatisch von Contrexx auf http://[[URL]] generiert.', '', '1', '3', '1', '0'),
('19', '[[URL]] - Eintrag erfolgteich eingetragen', 'Hallo [[FIRSTNAME]] [[LASTNAME]] ([[USERNAME]])

Ihr Eintrag mit dem Titel \"[[TITLE]]\" wurde auf [[URL]] erfolgreich eingetragen.


Freundliche Grüsse
Ihr [[URL]]-Team

--
Diese Nachricht wurde am [[DATE]] automatisch von Contrexx auf http://[[URL]] generiert.', '', '1', '2', '1', '0'),
('24', '[[URL]] - Neuer Kommentar hinzugefügt', 'Hallo [[FIRSTNAME]] [[LASTNAME]] ([[USERNAME]])

Zu Ihrem Eintrag mit dem Titel \"[[TITLE]]\" auf [[URL]] wurde ein neuer Kommentar hinzugefügt.

Benutzen Sie folgenden Link um direkt zu Ihrem Eintrag zu gelangen:
[[LINK]]

Freundliche Grüsse
Ihr [[URL]]-Team


--
Diese Nachricht wurde am [[DATE]] automatisch von Contrexx auf http://[[URL]] generiert.', '', '1', '8', '1', '0'),
('32', '[[URL]] - Neuer Eintrag zur Prüfung freigegeben', 'Guten Tag,

Auf http://[[URL]] wurde ein neuer Eintrag mit dem Titel \"[[TITLE]]\" erfasst. Bitte prüfen Sie diesen und geben Sie ihn gegebenenfalls frei.


--
Diese Nachricht wurde am [[DATE]] automatisch von Contrexx auf http://[[URL]] generiert.', '', '1', '1', '1', '0'),
('33', '[[URL]] - Die Anzeigedauer eines Eintrages läuft ab', 'Hallo Admin

Auf [[URL]] läuft in Kürze die Anzeigedauer des Eintrages \"[[TITLE]]\" ab.

Freundliche Grüsse
Ihr [[URL]]-Team

--
Diese Nachricht wurde am [[DATE]] automatisch von Contrexx auf http://[[URL]] generiert.', '', '1', '9', '1', '0');");
        }
    }
    catch (\Cx\Lib\UpdateException $e) {
        // we COULD do something else here..
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '2.2.0')) {
        //insert demo values
        tryButDontWorry("INSERT INTO `".DBPREFIX."module_mediadir_categories` VALUES (167,0,0,1,1,'',1),(168,0,0,1,1,'',1),(169,0,0,0,1,'',1),(166,0,0,1,1,'',1),(165,0,0,1,1,'',1),(164,0,0,1,1,'',1);");
        tryButDontWorry("INSERT INTO `".DBPREFIX."module_mediadir_categories_names` VALUES (2,169,'Audio, Video ','test'),(2,165,'Foto',''),(3,165,'Foto',''),(1,166,'Telekommunikation',''),(2,166,'Telekommunikation',''),(3,166,'Telekommunikation',''),(1,167,'Internet',''),(2,167,'Internet',''),(3,167,'Internet',''),(1,168,'Elektronik   ',''),(2,168,'Elektronik   ',''),(3,168,'Elektronik   ',''),(1,169,'Audio, Video ','test'),(1,165,'Foto',''),(1,164,'Computer & Software',''),(2,164,'Computer & Software',''),(3,164,'Computer & Software',''),(3,169,'Audio, Video ','test');");
        tryButDontWorry("INSERT INTO `".DBPREFIX."module_mediadir_entries` VALUES (367,0,18,1300357796,0,1300357796,1,0,1,0,0,'','::1',1,1,1,1,1300316400,1308261600,0,'2'),(368,0,18,1300358208,1301485252,1300358208,1,1,1,2,2,'1301436000','192.168.99.160',1,1,1,1,1301436000,1301436000,0,'2');");
        tryButDontWorry("INSERT INTO `".DBPREFIX."module_mediadir_form_names` VALUES (3,18,'Unternehmen',''),(2,18,'Unternehmen',''),(1,18,'Unternehmen','');");
        tryButDontWorry("INSERT INTO `".DBPREFIX."module_mediadir_forms` VALUES (18,0,'',1,1,1,0,'');");
        tryButDontWorry("INSERT INTO `".DBPREFIX."module_mediadir_inputfield_names` VALUES (2,18,138,'Logo','',''),(1,18,138,'Logo','',''),(3,18,137,'Standort','',''),(2,18,137,'Standort','',''),(1,18,137,'Standort','',''),(3,18,136,'E-Mail','',''),(2,18,136,'E-Mail','',''),(1,18,136,'E-Mail','',''),(3,18,135,'Webseite','',''),(2,18,135,'Webseite','',''),(1,18,135,'Webseite','',''),(3,18,134,'Ort','',''),(2,18,134,'Ort','',''),(1,18,134,'Ort','',''),(3,18,133,'PLZ','',''),(2,18,133,'PLZ','',''),(1,18,133,'PLZ','',''),(3,18,132,'Strasse / Nr.','',''),(2,18,132,'Strasse / Nr.','',''),(1,18,132,'Strasse / Nr.','',''),(3,18,131,'Beschreibung','',''),(2,18,131,'Beschreibung','',''),(1,18,131,'Beschreibung','',''),(3,18,130,'Firmenname','',''),(1,18,130,'Firmenname','',''),(2,18,130,'Firmenname','',''),(3,18,138,'Logo','','');");
        tryButDontWorry("INSERT INTO `".DBPREFIX."module_mediadir_inputfields` VALUES (138,18,8,1,1,0,10,1),(137,18,15,1,0,0,9,1),(136,18,20,1,0,0,8,1),(135,18,12,1,0,0,7,1),(134,18,1,1,0,0,6,1),(133,18,1,1,1,0,5,1),(132,18,1,1,0,0,4,1),(131,18,2,1,0,1,3,1),(130,18,1,1,1,1,2,1);");
        tryButDontWorry("INSERT INTO `".DBPREFIX."module_mediadir_level_names` VALUES (3,18,'Bern',''),(3,26,'[[Neue Ebene]]',''),(3,21,'Graub',''),(1,20,'Wallis',''),(2,20,'Wallis',''),(3,20,'Wallis',''),(2,18,'Bern',''),(1,18,'Bern',''),(2,21,'Graub',''),(1,21,'Graubünden',''),(1,22,'Basel','baaasler ebeni'),(1,23,'Zug',''),(2,23,'Zug',''),(3,23,'Zug',''),(2,26,'[[Neue Ebene]]',''),(2,22,'Basel','baaasler ebeni'),(3,22,'Basel','baaasler ebeni'),(1,26,'Zürich','');");
        tryButDontWorry("INSERT INTO `".DBPREFIX."module_mediadir_levels` VALUES (18,0,0,0,1,0,'',1),(26,0,0,0,1,0,'',1),(20,0,0,1,1,0,'',1),(21,0,0,0,1,0,'',1),(22,0,0,0,1,0,'',1),(23,0,0,1,1,0,'',1);");
        tryButDontWorry("INSERT INTO `".DBPREFIX."module_mediadir_masks` VALUES (4,'Janik\'s 1. Exportmaske','79,78,80,83,118,1',1,15),(5,'2','',1,15);");
        tryButDontWorry("INSERT INTO `".DBPREFIX."module_mediadir_order_rel_forms_selectors` VALUES (10,18,1,1),(9,18,0,1);");
        tryButDontWorry("INSERT INTO `".DBPREFIX."module_mediadir_rel_entry_categories` VALUES (368,167),(368,164);");
        tryButDontWorry("INSERT INTO `".DBPREFIX."module_mediadir_rel_entry_inputfields` VALUES (368,1,18,138,'/mediadir_merge/images/downloads/no_picture.gif'),(368,1,18,137,'46.75759950155461,7.6165080070495605,16,'),(368,1,18,136,'info@comvation.com'),(368,1,18,135,'www.comvation.com'),(368,3,18,134,'Thun'),(368,2,18,134,'Thun'),(368,1,18,134,'Thun'),(368,3,18,133,'3600'),(368,2,18,133,'3600'),(368,1,18,133,'3600'),(368,2,18,132,'Milit'),(368,3,18,132,'Milit'),(368,1,18,132,'Militärstrasse 6'),(368,3,18,131,'Die Comvation AG, Hersteller des globalen Web Content Management System Contrexx'),(368,2,18,131,'Die Comvation AG, Hersteller des globalen Web Content Management System Contrexx'),(368,1,18,131,'Die Comvation AG, Hersteller des globalen Web Content Management System Contrexx'),(368,3,18,130,'Comvation AG'),(368,2,18,130,'Comvation AG'),(368,1,18,130,'Comvation AG');");
        tryButDontWorry("INSERT INTO `".DBPREFIX."module_mediadir_rel_entry_levels` VALUES (368,18);");
        tryButDontWorry("INSERT INTO `".DBPREFIX."module_mediadir_settings_perm_group_forms` VALUES (5,18,1),(4,18,1),(3,18,1);");
    }

    return true;
}

function tryButDontWorry($sql) {
    try {
        \Cx\Lib\UpdateUtil::sql($sql);
    }
    catch (\Cx\Lib\UpdateException $e) {
        //nothing.
    }
}
