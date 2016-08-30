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


function _dataUpdate()
{
    global $objDatabase;

    $arrTables  = $objDatabase->MetaTables('TABLES');
    if (!sizeof($arrTables)) {
        return _databaseError("MetaTables('TABLES')", 'Could not read Table metadata');
    }

    // Create neccessary tables if not present
    $tables = array(
        'module_data_categories' => "CREATE TABLE `".DBPREFIX."module_data_categories` (
              `category_id` int(4) unsigned NOT NULL default '0',
              `lang_id` int(2) unsigned NOT NULL default '0',
              `is_active` enum('0','1') NOT NULL default '1',
              `parent_id` int(10) unsigned NOT NULL default '0',
              `name` varchar(100) NOT NULL default '',
              `active` enum('0','1') NOT NULL default '1',
              `cmd` int(10) unsigned NOT NULL default '1',
              `action` enum('content','overlaybox','subcategories') NOT NULL default 'content',
              `sort` int(10) unsigned NOT NULL default '1',
              `box_height` int(10) unsigned NOT NULL default '500',
              `box_width` int(11) NOT NULL default '350',
              `template` text NOT NULL,
              PRIMARY KEY (`category_id`,`lang_id`)
            ) ENGINE=InnoDB",
        #################################################################################
        'module_data_message_to_category' => "CREATE TABLE `".DBPREFIX."module_data_message_to_category` (
              `message_id` int(6) unsigned NOT NULL default '0',
              `category_id` int(4) unsigned NOT NULL default '0',
              `lang_id` int(2) unsigned NOT NULL default '0',
              PRIMARY KEY (`message_id`,`category_id`,`lang_id`),
              KEY `category_id` (`category_id`)
            ) ENGINE=InnoDB",
        #################################################################################
        'module_data_messages' => "CREATE TABLE `".DBPREFIX."module_data_messages` (
              `message_id` int(6) unsigned NOT NULL auto_increment,
              `user_id` int(5) unsigned NOT NULL default '0',
              `time_created` int(14) unsigned NOT NULL default '0',
              `time_edited` int(14) unsigned NOT NULL default '0',
              `hits` int(7) unsigned NOT NULL default '0',
              `active` enum('0','1') NOT NULL default '1',
              `sort` int(10) unsigned NOT NULL default '1',
              `mode` set('normal','forward') NOT NULL default 'normal',
              `release_time` int(15) NOT NULL default '0',
              `release_time_end` int(15) NOT NULL default '0',
              PRIMARY KEY (`message_id`)
            ) ENGINE=InnoDB",
        #################################################################################
        'module_data_settings' => "CREATE TABLE `".DBPREFIX."module_data_settings` (
              `name` varchar(50) NOT NULL default '',
              `value` text NOT NULL,
              PRIMARY KEY (`name`)
            ) ENGINE=InnoDB"
    );

      ///////////////////////////////////////////////////////////////////
     // Create tables                                                 //
    ///////////////////////////////////////////////////////////////////
    foreach ($tables as $name => $query) {
        if (in_array(DBPREFIX.$name, $arrTables)) continue;
        if (!$objDatabase->Execute($query)) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
// TODO: Unused
//        $installed[] = $name;
    }



    try{
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_data_placeholders',
            array(
                'id'             => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'type'           => array('type' => 'SET(\'cat\',\'entry\')', 'notnull' => true, 'default' => ''),
                'ref_id'         => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0'),
                'placeholder'    => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '')
            ),
            array(
                'placeholder'    => array('fields' => array('placeholder'), 'type' => 'UNIQUE'),
                'type'           => array('fields' => array('type','ref_id'), 'type' => 'UNIQUE')
            )
        );
    }
    catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }




    $settings_query = "
INSERT INTO `".DBPREFIX."module_data_settings` (`name`, `value`) VALUES
('data_block_activated', '0'),
('data_block_messages', '3'),
('data_comments_activated', '1'),
('data_comments_anonymous', '1'),
('data_comments_autoactivate', '1'),
('data_comments_editor', 'wysiwyg'),
('data_comments_notification', '1'),
('data_comments_timeout', '30'),
('data_entry_action', 'overlaybox'),
('data_general_introduction', '150'),
('data_rss_activated', '0'),
('data_rss_comments', '10'),
('data_rss_messages', '5'),
('data_tags_hitlist', '5'),
('data_target_cmd', '1'),
('data_template_category',
'<!-- BEGIN datalist_category -->
<!-- this displays the category and the subcategories -->
<div class=\\\"datalist_block\\\">
<dl>
  <!-- BEGIN category -->
  <dt class=\\\"cattitle\\\"><div class=\\\"bg\\\"><h4>[[CATTITLE]]</h4></div></dt>
  <dd class=\\\"catcontent\\\">
    <dl>
      <!-- BEGIN entry -->
      <dt>[[TITLE]]</dt>
      <dd>
        [[IMAGE]] [[CONTENT]] <a href=\\\"[[HREF]]\\\" [[CLASS]] [[TARGET]]>[[TXT_MORE]]</a>
        <br style=\\\"clear: both;\\\" />
      </dd>
      <!-- END entry -->
    </dl>
  </dd>
  <!-- END category -->
</dl>
</div>
<!-- END datalist_category -->
<!-- BEGIN datalist_single_category-->
<!-- this displays just the entries of the category -->
<div class=\\\"datalist_block\\\">
<dl>
  <!-- BEGIN single_entry -->
  <dt class=\\\"cattitle\\\"><div class=\\\"bg\\\"><h4>[[TITLE]]</h4></div></dt>
  <dd class=\\\"catcontent2\\\">
    [[IMAGE]] <p>[[CONTENT]] <a href=\\\"[[HREF]]\\\" [[CLASS]] [[TARGET]]>[[TXT_MORE]]</a></p>
    <div style=\\\"clear: both;\\\" />
  </dd>
  <!-- END single_entry -->
</dl>
</div>
<!-- END datalist_single_category -->
'),
('data_template_entry',
'<!-- BEGIN datalist_entry-->
<div class=\\\"datalist_block\\\">
<dl>
  <dt>[[TITLE]]</dt>
  <dd>
    [[IMAGE]] [[CONTENT]] <a href=\\\"[[HREF]]\\\" [[CLASS]]>[[TXT_MORE]]</a>
    <br style=\\\"clear: both;\\\" />
  </dd>
</dl>
</div>
<!-- END datalist_entry -->
    '),
('data_template_thickbox',
'<!-- BEGIN thickbox -->
<dl class=\\\"data_module\\\">
  <dt><h6 style=\\\"margin-bottom:10px;\\\">[[TITLE]]</h6></dt>
  <dd style=\\\"clear:left;\\\">
    <!-- BEGIN image -->
    <img src=\\\"[[PICTURE]]\\\" style=\\\"float: left; margin-right: 5px;\\\" />
    <!-- END image -->
    [[CONTENT]]
    <!-- BEGIN attachment -->
    <img src=\\\"/themes/default/images/arrow.gif\\\" width=\\\"16\\\" height=\\\"8\\\" />
    <a href=\\\"javascript:void(0);\\\" onclick=\\\"window.open(\\'[[HREF]]\\', \\'attachment\\');\\\">[[TXT_DOWNLOAD]]</a>
    <!-- END attachment -->
  </dd>
</dl>
<!--<br /><img src=\\\"/themes/default/images/arrow.gif\\\" width=\\\"16\\\" height=\\\"8\\\" /><a onclick=\\\"Javascript:window.print();\\\" style=\\\"cursor:pointer;\\\">Drucken</a>-->
<!-- END thickbox -->
'),
('data_thickbox_height', '450'),
('data_thickbox_width', '400'),
('data_voting_activated', '0');
";

      ///////////////////////////////////////////////////////////////////
     // data module settings                                          //
    ///////////////////////////////////////////////////////////////////
    $query = "SELECT COUNT(*) AS recordcount FROM `".DBPREFIX."module_data_settings`";
    $objResult = $objDatabase->Execute($query);
    if ($objResult === false) {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }
    if ($objResult->fields['recordcount'] == 0) {
        // module_data_settings table is empty. Fill it with default data.
        if (!$objDatabase->Execute($settings_query)) {
            return _databaseError($settings_query, $objDatabase->ErrorMsg());
        }
    }



    /*********************************************************
    * EXTENSION:    Thunbmail Image & Attachment description *
    * ADDED:        Contrexx v2.1.0                             *
    *********************************************************/
    try {
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_data_messages_lang',
            array(
                'message_id'               => array('type' => 'INT(6)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true),
                'lang_id'                  => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true),
                'is_active'                => array('type' => 'ENUM(\'0\',\'1\')', 'default' => '1'),
                'subject'                  => array('type' => 'VARCHAR(250)', 'default' => ''),
                'content'                  => array('type' => 'text'),
                'tags'                     => array('type' => 'VARCHAR(250)', 'default' => ''),
                'image'                    => array('type' => 'VARCHAR(250)', 'default' => ''),
                'thumbnail'                => array('type' => 'VARCHAR(250)'),
                'thumbnail_type'           => array('type' => 'ENUM(\'original\',\'thumbnail\')', 'default' => 'original', 'after' => 'thumbnail'),
                'thumbnail_width'          => array('type' => 'TINYINT(3)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                'thumbnail_height'         => array('type' => 'TINYINT(3)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                'attachment'               => array('type' => 'VARCHAR(255)', 'default' => ''),
                'attachment_description'   => array('type' => 'VARCHAR(255)', 'default' => ''),
                'mode'                     => array('type' => 'SET(\'normal\',\'forward\')', 'default' => 'normal'),
                'forward_url'              => array('type' => 'VARCHAR(255)', 'default' => ''),
                'forward_target'           => array('type' => 'VARCHAR(40)', 'notnull' => false)
            ),
            array(),
            'InnoDB'
        );
    }
    catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }


    return true;
}
