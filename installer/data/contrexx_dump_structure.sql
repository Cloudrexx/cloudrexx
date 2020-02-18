SET FOREIGN_KEY_CHECKS = 0;
SET SESSION `sql_mode`=(SELECT REPLACE(REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''),'STRICT_TRANS_TABLES',''));
CREATE TABLE `contrexx_access_group_dynamic_ids` (
  `access_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY (`access_id`,`group_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_access_group_static_ids` (
  `access_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY (`access_id`,`group_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_access_id` (
  `id` int(11) AUTO_INCREMENT NOT NULL,
  `entity_class_name` varchar(255) NOT NULL,
  `entity_class_id` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_access_rel_user_group` (
  `user_id` int NOT NULL,
  `group_id` int NOT NULL,
  PRIMARY KEY (`user_id`,`group_id`),
  INDEX `contrexx_access_rel_user_group_user_id_ibfk` (`user_id`),
  INDEX `contrexx_access_rel_user_group_group_id_ibfk` (`group_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_access_settings` (
  `key` varchar(32) NOT NULL DEFAULT '',
  `value` varchar(255) NOT NULL DEFAULT '',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_access_user_attribute` (
  `id` int NOT NULL AUTO_INCREMENT,
  `parent_id` int DEFAULT NULL,
  `type` enum('text','textarea','mail','uri','date','image','checkbox','menu','menu_option','group','frame','history') NOT NULL DEFAULT 'text',
  `mandatory` enum('0','1') NOT NULL DEFAULT '0',
  `sort_type` enum('asc','desc','custom') NOT NULL DEFAULT 'asc',
  `order_id` int NOT NULL DEFAULT '0',
  `access_special` enum('','menu_select_higher','menu_select_lower') NOT NULL DEFAULT '',
  `access_id` int NOT NULL,
  `read_access_id` int NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `contrexx_access_user_attribute_parent_id_ibfk` (`parent_id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_access_user_attribute_name` (
  `attribute_id` int NOT NULL DEFAULT '0',
  `lang_id` int NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`attribute_id`,`lang_id`),
  INDEX `contrexx_access_user_attribute_name_attribute_id_ibfk` (`attribute_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_access_user_attribute_value` (
  `attribute_id` int NOT NULL,
  `user_id` int NOT NULL,
  `history_id` int NOT NULL DEFAULT '0',
  `value` text NOT NULL,
  PRIMARY KEY (`attribute_id`,`user_id`,`history_id`),
  FULLTEXT KEY `value` (`value`),
  INDEX `contrexx_access_user_attribute_value_user_id_ibfk` (`user_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_access_user_core_attribute` (
  `id` varchar(25) NOT NULL,
  `mandatory` enum('0','1') NOT NULL DEFAULT '0',
  `sort_type` enum('asc','desc','custom') NOT NULL DEFAULT 'asc',
  `order_id` int NOT NULL DEFAULT '0',
  `access_special` enum('','menu_select_higher','menu_select_lower') NOT NULL DEFAULT '',
  `access_id` int NOT NULL,
  `read_access_id` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_access_user_groups` (
  `group_id` int NOT NULL AUTO_INCREMENT,
  `group_name` varchar(100) NOT NULL DEFAULT '',
  `group_description` varchar(255) NOT NULL DEFAULT '',
  `is_active` smallint NOT NULL DEFAULT '1',
  `type` enum('frontend','backend') NOT NULL DEFAULT 'frontend',
  `homepage` varchar(255) NOT NULL DEFAULT '',
  `toolbar` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`group_id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_access_user_mail` (
  `type` enum('reg_confirm','reset_pw','user_activated','user_deactivated','new_user','user_account_invitation','signup_notification', 'user_profile_modification') NOT NULL DEFAULT 'reg_confirm',
  `lang_id` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `sender_mail` varchar(255) NOT NULL DEFAULT '',
  `sender_name` varchar(255) NOT NULL DEFAULT '',
  `subject` varchar(255) NOT NULL DEFAULT '',
  `format` enum('text','html','multipart') NOT NULL DEFAULT 'text',
  `body_text` text NOT NULL,
  `body_html` text NOT NULL,
  UNIQUE KEY `mail` (`type`,`lang_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_access_user_network` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `oauth_provider` varchar(100) NOT NULL DEFAULT '',
  `oauth_id` varchar(100) NOT NULL DEFAULT '',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_access_user_profile` (
  `user_id` int NOT NULL,
  `gender` enum('gender_undefined','gender_female','gender_male') NOT NULL DEFAULT 'gender_undefined',
  `title` int DEFAULT NULL,
  `designation` varchar(255) NOT NULL DEFAULT '',
  `firstname` varchar(255) NOT NULL DEFAULT '',
  `lastname` varchar(255) NOT NULL DEFAULT '',
  `company` varchar(255) NOT NULL DEFAULT '',
  `address` varchar(255) NOT NULL DEFAULT '',
  `city` varchar(50) NOT NULL DEFAULT '',
  `zip` varchar(10) NOT NULL DEFAULT '',
  `country` int NOT NULL DEFAULT '0',
  `phone_office` varchar(20) NOT NULL DEFAULT '',
  `phone_private` varchar(20) NOT NULL DEFAULT '',
  `phone_mobile` varchar(20) NOT NULL DEFAULT '',
  `phone_fax` varchar(20) NOT NULL DEFAULT '',
  `birthday` varchar(11) DEFAULT NULL,
  `website` varchar(255) NOT NULL DEFAULT '',
  `profession` varchar(150) NOT NULL DEFAULT '',
  `interests` text,
  `signature` text,
  `picture` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`user_id`),
  KEY `profile` (`firstname`(100),`lastname`(100),`company`(50)),
  INDEX `contrexx_access_user_profile_title_ibfk` (`title`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_access_user_title` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `order_id` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `title` (`title`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_access_user_validity` (
  `validity` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`validity`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_access_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `auth_token` varchar(32) NOT NULL,
  `auth_token_timeout` int NOT NULL DEFAULT '0',
  `regdate` int NOT NULL DEFAULT '0',
  `expiration` int NOT NULL DEFAULT '0',
  `validity` int NOT NULL DEFAULT '0',
  `last_auth` int NOT NULL DEFAULT '0',
  `last_auth_status` smallint NOT NULL DEFAULT '1',
  `last_activity` int NOT NULL DEFAULT '0',
  `email` varchar(255) DEFAULT NULL,
  `email_access` enum('everyone','members_only','nobody') NOT NULL DEFAULT 'nobody',
  `frontend_lang_id` int NOT NULL DEFAULT '0',
  `backend_lang_id` int NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `verified` tinyint(1) NOT NULL DEFAULT '1',
  `primary_group` int NOT NULL DEFAULT '0',
  `profile_access` enum('everyone','members_only','nobody') NOT NULL DEFAULT 'members_only',
  `restore_key` varchar(32) NOT NULL DEFAULT '',
  `restore_key_time` int NOT NULL DEFAULT '0',
  `u2u_active` enum('0','1') NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `username` (`username`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_backend_areas` (
  `area_id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `parent_area_id` int(6) unsigned NOT NULL DEFAULT '0',
  `type` enum('group','function','navigation') DEFAULT 'navigation',
  `scope` enum('global','frontend','backend') NOT NULL DEFAULT 'global',
  `area_name` varchar(100) DEFAULT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT '1',
  `uri` varchar(255) NOT NULL DEFAULT '',
  `target` varchar(50) NOT NULL DEFAULT '_self',
  `module_id` int(6) unsigned NOT NULL DEFAULT '0',
  `order_id` int(6) unsigned NOT NULL DEFAULT '0',
  `access_id` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`area_id`),
  KEY `area_name` (`area_name`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_backups` (
  `id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `date` varchar(14) NOT NULL DEFAULT '',
  `version` varchar(20) NOT NULL DEFAULT '',
  `edition` varchar(30) NOT NULL DEFAULT '',
  `type` enum('sql','csv') NOT NULL DEFAULT 'sql',
  `description` varchar(100) NOT NULL DEFAULT '',
  `usedtables` text NOT NULL,
  `size` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `date` (`date`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_component` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('core','core_module','module') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_content_node` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `lft` int(11) NOT NULL,
  `rgt` int(11) NOT NULL,
  `lvl` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `contrexx_content_node_parent_id_ibfk` (`parent_id`),
  CONSTRAINT `contrexx_content_node_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `contrexx_content_node` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_content_page` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `node_id` int(11) DEFAULT NULL,
  `nodeIdShadowed` int(11) DEFAULT NULL,
  `lang` int(11) NOT NULL,
  `type` varchar(16) NOT NULL,
  `caching` tinyint(1) NOT NULL,
  `updatedAt` timestamp NULL DEFAULT NULL,
  `updatedBy` varchar(40) NOT NULL,
  `title` varchar(255) NOT NULL,
  `linkTarget` varchar(16) DEFAULT NULL,
  `contentTitle` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `sourceMode` tinyint(1) NOT NULL DEFAULT '0',
  `customContent` varchar(64) DEFAULT NULL,
  `useCustomContentForAllChannels` smallint DEFAULT NULL,
  `applicationTemplate` varchar(100) DEFAULT NULL,
  `useCustomApplicationTemplateForAllChannels` smallint DEFAULT NULL,
  `cssName` varchar(255) DEFAULT NULL,
  `cssNavName` varchar(255) DEFAULT NULL,
  `skin` int(11) DEFAULT NULL,
  `useSkinForAllChannels` smallint DEFAULT NULL,
  `metatitle` varchar(255) DEFAULT NULL,
  `metadesc` text NOT NULL,
  `metakeys` text NOT NULL,
  `metarobots` varchar(7) DEFAULT NULL,
  `metaimage` varchar(255) DEFAULT NULL,
  `start` timestamp NULL DEFAULT NULL,
  `end` timestamp NULL DEFAULT NULL,
  `editingStatus` varchar(16) NOT NULL,
  `protection` int(11) NOT NULL,
  `frontendAccessId` int(11) NOT NULL,
  `backendAccessId` int(11) NOT NULL,
  `display` tinyint(1) NOT NULL,
  `active` tinyint(1) NOT NULL,
  `target` varchar(255) DEFAULT NULL,
  `module` varchar(255) DEFAULT NULL,
  `cmd` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `node_id` (`node_id`,`lang`),
  KEY `contrexx_content_page_node_id_ibfk` (`node_id`),
  CONSTRAINT `contrexx_content_page_ibfk_3` FOREIGN KEY (`node_id`) REFERENCES `contrexx_content_node` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_core_country` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alpha2` char(2) NOT NULL DEFAULT '',
  `alpha3` char(3) NOT NULL DEFAULT '',
  `ord` int(5) unsigned NOT NULL DEFAULT '0',
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_core_country_country` (
  `alpha2` varchar(2) NOT NULL,
  `alpha3` varchar(3) NOT NULL DEFAULT '',
  `ord` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`alpha2`),
  UNIQUE KEY `alpha3` (`alpha3`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_core_mail_template` (
  `key` tinytext NOT NULL,
  `section` tinytext NOT NULL,
  `text_id` int(10) unsigned NOT NULL,
  `html` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `protected` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`key`(32),`section`(32))
) ENGINE=InnoDB;
CREATE TABLE `contrexx_core_data_source` (
  `id` int(11) AUTO_INCREMENT NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `options` longtext NOT NULL COMMENT '(DC2Type:array)',
  `type` varchar(50) NOT NULL,
  PRIMARY KEY(`id`),
  UNIQUE KEY `identifier` (`identifier`)
) ENGINE = InnoDB;
CREATE TABLE `contrexx_core_locale_backend` (
  `id` int AUTO_INCREMENT NOT NULL,
  `iso_1` varchar(2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `contrexx_core_locale_backend_iso_1_ibfk` (iso_1)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_core_locale_language` (
  `iso_1` varchar(2) NOT NULL,
  `iso_3` varchar(3) DEFAULT NULL,
  `source` tinyint(1) NOT NULL,
  PRIMARY KEY (`iso_1`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_core_locale_locale` (
  `id` int AUTO_INCREMENT NOT NULL,
  `iso_1` varchar(2) NOT NULL,
  `label` varchar(255) DEFAULT NULL,
  `country` varchar(2) DEFAULT NULL,
  `fallback` int DEFAULT NULL,
  `source_language` varchar(2) NOT NULL,
  `order_no` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `iso_1` (`iso_1`, `country`),
  CONSTRAINT `contrexx_core_locale_locale_ibfk_country` FOREIGN KEY (`country`) REFERENCES `contrexx_core_country_country` (`alpha2`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `contrexx_core_locale_locale_ibfk_fallback` FOREIGN KEY (`fallback`) REFERENCES `contrexx_core_locale_locale` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `contrexx_core_locale_locale_ibfk_iso_1` FOREIGN KEY (`iso_1`) REFERENCES `contrexx_core_locale_language` (`iso_1`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `contrexx_core_locale_locale_ibfk_source_language` FOREIGN KEY (`source_language`) REFERENCES `contrexx_core_locale_language` (`iso_1`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB;
CREATE TABLE `contrexx_core_modules_access_permission` (
  `id` int(11) AUTO_INCREMENT NOT NULL,
  `allowed_protocols` longtext NOT NULL COMMENT '(DC2Type:array)',
  `allowed_methods` longtext NOT NULL COMMENT '(DC2Type:array)',
  `requires_login` tinyint(1) NOT NULL,
  `valid_user_groups` longtext NOT NULL COMMENT '(DC2Type:array)',
  `valid_access_ids` longtext NOT NULL COMMENT '(DC2Type:array)',
  `callback` longtext NOT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY(`id`)
) ENGINE = InnoDB;
CREATE TABLE `contrexx_core_module_data_access` (
  `id` int(11) AUTO_INCREMENT NOT NULL,
  `read_permission` int(11) DEFAULT NULL,
  `write_permission` int(11) DEFAULT NULL,
  `data_source_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `field_list` longtext NOT NULL COMMENT '(DC2Type:array)',
  `access_condition` longtext NOT NULL COMMENT '(DC2Type:array)',
  `allowed_output_methods` longtext NOT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY(`id`),
  UNIQUE KEY `name` (`name`),
  KEY `read_permission` (`read_permission`),
  KEY `write_permission` (`write_permission`),
  KEY `data_source_id` (`data_source_id`),
  CONSTRAINT `contrexx_core_module_data_access_ibfk_read_permission` FOREIGN KEY (`read_permission`) REFERENCES `contrexx_core_modules_access_permission` (`id`),
  CONSTRAINT `contrexx_core_module_data_access_ibfk_write_permission` FOREIGN KEY (`write_permission`) REFERENCES `contrexx_core_modules_access_permission` (`id`),
  CONSTRAINT `contrexx_core_module_data_access_ibfk_data_source_id` FOREIGN KEY (`data_source_id`) REFERENCES `contrexx_core_data_source` (`id`)
) ENGINE = InnoDB;
CREATE TABLE `contrexx_core_module_data_access_apikey` (
  `id` int(11) AUTO_INCREMENT NOT NULL,
  `api_key` varchar(32) NOT NULL,
  PRIMARY KEY(`id`),
  UNIQUE KEY `api_key` (`api_key`)
) ENGINE = InnoDB;
CREATE TABLE `contrexx_core_module_data_access_data_access_apikey` (
  `id` int(11) AUTO_INCREMENT NOT NULL,
  `api_key_id` int(11) DEFAULT NULL,
  `data_access_id` int(11) DEFAULT NULL,
  `read_only` tinyint(1) DEFAULT NULL,
  PRIMARY KEY(id),
  KEY `api_key_id` (`api_key_id`),
  KEY `data_access_id` (`data_access_id`),
  CONSTRAINT `contrexx_core_module_data_access_apikey_ibfk_api_key_id` FOREIGN KEY (`api_key_id`) REFERENCES `contrexx_core_module_data_access_apikey` (`id`),
  CONSTRAINT `contrexx_core_module_data_access_apikey_ibfk_data_access_id` FOREIGN KEY (`data_access_id`) REFERENCES `contrexx_core_module_data_access` (`id`)
) ENGINE = InnoDB;
CREATE TABLE `contrexx_core_module_cron_job` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) NOT NULL,
  `expression` varchar(255) NOT NULL,
  `command` varchar(255) NOT NULL,
  `last_ran` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_core_module_linkmanager_crawler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lang` smallint(2) NOT NULL,
  `startTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `endTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `totalLinks` int(11) NOT NULL,
  `totalBrokenLinks` int(11) NOT NULL,
  `runStatus` enum('running','incomplete','completed') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_core_module_linkmanager_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lang` smallint NOT NULL,
  `requestedPath` text NOT NULL,
  `linkStatusCode` smallint DEFAULT NULL,
  `entryTitle` varchar(255) NOT NULL,
  `moduleName` varchar(100) DEFAULT NULL,
  `moduleAction` varchar(100) DEFAULT NULL,
  `moduleParams` varchar(255) DEFAULT NULL,
  `detectedTime` timestamp NOT NULL,
  `flagStatus` tinyint(2) NOT NULL,
  `updatedBy` int(2) NOT NULL,
  `requestedLinkType` varchar(25) DEFAULT NULL,
  `refererPath` text,
  `leadPath` text NOT NULL,
  `linkStatus` tinyint(2) NOT NULL,
  `linkRecheck` tinyint(2) NOT NULL,
  `brokenLinkText` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_core_module_linkmanager_link` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lang` smallint NOT NULL,
  `requestedPath` text NOT NULL,
  `linkStatusCode` smallint DEFAULT NULL,
  `entryTitle` varchar(255) NOT NULL,
  `moduleName` varchar(100) DEFAULT NULL,
  `moduleAction` varchar(100) DEFAULT NULL,
  `moduleParams` varchar(255) DEFAULT NULL,
  `detectedTime` timestamp NOT NULL,
  `flagStatus` tinyint(2) NOT NULL,
  `updatedBy` int(2) NOT NULL,
  `requestedLinkType` varchar(25) DEFAULT NULL,
  `refererPath` text,
  `leadPath` text,
  `linkStatus` tinyint(2) NOT NULL,
  `linkRecheck` tinyint(2) NOT NULL,
  `brokenLinkText` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_core_module_sync` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `data_access_id` int(11) DEFAULT NULL,
  `to_uri` varchar(255) NOT NULL,
  `api_key` varchar(32) NOT NULL,
  `active` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `data_access_id` (`data_access_id`),
  CONSTRAINT `contrexx_core_module_sync_ibfk_data_access_id` FOREIGN KEY (`data_access_id`) REFERENCES `contrexx_core_module_data_access` (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_core_module_sync_change` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sync_id` int NOT NULL,
  `origin_sync_id` int NOT NULL,
  `event_type` varchar(6) NOT NULL,
  `condition` varchar(7) NOT NULL,
  `entity_index_data` longtext NOT NULL COMMENT '(DC2Type:array)',
  `origin_entity_index_data` longtext NOT NULL COMMENT '(DC2Type:array)',
  `contents` longtext NOT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`),
  INDEX `contrexx_core_module_sync_change_sync_id_ibfk` (`sync_id`),
  INDEX `contrexx_core_module_sync_change_origin_sync_id_ibfk` (`origin_sync_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_core_module_sync_change_host` (
  `change_id` int(11) NOT NULL,
  `host_id` int(11) NOT NULL,
  PRIMARY KEY (`change_id`,`host_id`),
  INDEX `contrexx_core_module_sync_change_host_change_id_ibfk` (`change_id`),
  INDEX `contrexx_core_module_sync_change_host_host_id_ibfk` (`host_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_core_module_sync_host` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `host` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL,
  `api_key` varchar(32) NOT NULL,
  `api_version` int(11) NOT NULL,
  `url_template` varchar(255) NOT NULL,
  `state` int(1) NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `host_UNIQUE` (`host`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_core_module_sync_host_entity` (
  `sync_id` int(11) NOT NULL,
  `host_id` int(11) NOT NULL,
  `entity_id` varchar(255) NOT NULL,
  PRIMARY KEY (`sync_id`,`host_id`,`entity_id`),
  KEY `host_id` (`host_id`),
  CONSTRAINT `contrexx_core_module_sync_host_entity_ibfk_sync_id` FOREIGN KEY (`sync_id`) REFERENCES `contrexx_core_module_sync` (`id`),
  CONSTRAINT `contrexx_core_module_sync_host_entity_ibfk_host_id` FOREIGN KEY (`host_id`) REFERENCES `contrexx_core_module_sync_host` (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_core_module_sync_id_mapping` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `foreign_host` varchar(255) NOT NULL,
  `entity_type` varchar(255) NOT NULL,
  `foreign_id` LONGTEXT NOT NULL COMMENT '(DC2Type:array)',
  `local_id` LONGTEXT NOT NULL COMMENT '(DC2Type:array)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_core_module_sync_relation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL,
  `related_sync_id` int(11) NOT NULL,
  `foreign_data_access_id` int(11) NOT NULL,
  `lft` int(11) NOT NULL,
  `rgt` int(11) NOT NULL,
  `lvl` int(11) NOT NULL,
  `local_field_name` varchar(50) NOT NULL,
  `do_sync` tinyint(1) NOT NULL,
  `default_entity_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `related_sync_id` (`related_sync_id`),
  KEY `contrexx_core_module_sync_relation_ibfk_foreign_data_access_id` (`foreign_data_access_id`),
  CONSTRAINT `contrexx_core_module_sync_relation_ibfk_foreign_data_access_id` FOREIGN KEY (`foreign_data_access_id`) REFERENCES `contrexx_core_module_data_access` (`id`),
  CONSTRAINT `contrexx_core_module_sync_relation_ibfk_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `contrexx_core_module_sync_relation` (`id`),
  CONSTRAINT `contrexx_core_module_sync_relation_ibfk_related_sync_id` FOREIGN KEY (`related_sync_id`) REFERENCES `contrexx_core_module_sync` (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_core_rewrite_rule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `regular_expression` varchar(255) NOT NULL,
  `continue_on_match` tinyint(1) NOT NULL,
  `rewrite_status_code` enum('301','302','intern') NOT NULL,
  `order_no` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_core_setting` (
  `section` varchar(32) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `group` varchar(32) NOT NULL DEFAULT '',
  `type` varchar(32) NOT NULL DEFAULT 'text',
  `value` text NOT NULL,
  `values` text NOT NULL,
  `ord` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`section`,`name`,`group`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_core_text` (
  `id` int(10) unsigned NOT NULL DEFAULT '0',
  `lang_id` int(10) unsigned NOT NULL DEFAULT '1',
  `section` varchar(32) NOT NULL DEFAULT '',
  `key` varchar(255) NOT NULL,
  `text` text NOT NULL,
  PRIMARY KEY (`id`,`lang_id`,`section`,`key`(32)),
  FULLTEXT KEY `text` (`text`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_core_view_frontend` (
  `language` int NOT NULL,
  `theme` int NOT NULL,
  `channel` enum('default','mobile','print','pdf','app') NOT NULL,
  PRIMARY KEY (`language`,`theme`,`channel`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_core_wysiwyg_template` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `imagePath` varchar(255) NOT NULL,
  `htmlContent` text,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_core_wysiwyg_toolbar` (
  `id` int(6) NOT NULL AUTO_INCREMENT,
  `available_functions` text NOT NULL,
  `removed_buttons` text NOT NULL,
  `is_default` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_core_module_pdf_template` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `html_content` longtext NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY(`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_ids` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` int(14) DEFAULT NULL,
  `type` varchar(100) NOT NULL DEFAULT '',
  `remote_addr` varchar(15) DEFAULT NULL,
  `http_x_forwarded_for` varchar(15) NOT NULL DEFAULT '',
  `http_via` varchar(255) NOT NULL DEFAULT '',
  `user` mediumtext,
  `gpcs` mediumtext NOT NULL,
  `file` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_languages` (
  `id` int(2) unsigned NOT NULL AUTO_INCREMENT,
  `lang` varchar(5) NOT NULL DEFAULT '',
  `name` varchar(250) NOT NULL DEFAULT '',
  `charset` varchar(20) NOT NULL DEFAULT 'iso-8859-1',
  `themesid` int(2) unsigned NOT NULL DEFAULT '1',
  `print_themes_id` int(2) unsigned NOT NULL DEFAULT '1',
  `pdf_themes_id` int(2) unsigned NOT NULL DEFAULT '0',
  `frontend` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `backend` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `is_default` set('true','false') NOT NULL DEFAULT 'false',
  `mobile_themes_id` int(2) unsigned NOT NULL DEFAULT '0',
  `fallback` int(2) unsigned DEFAULT '0',
  `app_themes_id` int(2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lang` (`lang`),
  KEY `defaultstatus` (`is_default`),
  KEY `name` (`name`),
  FULLTEXT KEY `name_2` (`name`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_lib_country` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `iso_code_2` char(2) NOT NULL,
  `iso_code_3` char(3) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`iso_code_2`),
  KEY `INDEX_COUNTRIES_NAME` (`name`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_log` (
  `id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(6) unsigned DEFAULT NULL,
  `datetime` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `useragent` varchar(250) DEFAULT NULL,
  `userlanguage` varchar(250) DEFAULT NULL,
  `remote_addr` varchar(250) DEFAULT NULL,
  `remote_host` varchar(250) DEFAULT NULL,
  `http_via` varchar(250) NOT NULL DEFAULT '',
  `http_client_ip` varchar(250) NOT NULL DEFAULT '',
  `http_x_forwarded_for` varchar(250) NOT NULL DEFAULT '',
  `referer` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_log_entry` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action` varchar(8) NOT NULL,
  `logged_at` timestamp NOT NULL,
  `version` int(11) NOT NULL,
  `object_id` varchar(32) DEFAULT NULL,
  `object_class` varchar(255) NOT NULL,
  `data` longtext DEFAULT NULL COMMENT '(DC2Type:array)',
  `username` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `log_version_lookup_idx` (`version`,`object_id`,`object_class`),
  KEY `log_class_lookup_idx` (`object_class`),
  KEY `log_date_lookup_idx` (`logged_at`),
  KEY `log_user_lookup_idx` (`username`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_block_blocks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `start` int(10) NOT NULL DEFAULT '0',
  `end` int(10) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `random` int(1) NOT NULL DEFAULT '0',
  `random_2` int(1) NOT NULL DEFAULT '0',
  `random_3` int(1) NOT NULL DEFAULT '0',
  `random_4` int(1) NOT NULL DEFAULT '0',
  `global` int(1) NOT NULL DEFAULT '0',
  `category` int(1) NOT NULL DEFAULT '0',
  `direct` int(1) NOT NULL DEFAULT '0',
  `active` int(1) NOT NULL DEFAULT '0',
  `order` int(1) NOT NULL DEFAULT '0',
  `cat` int(10) NOT NULL DEFAULT '0',
  `wysiwyg_editor` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_block_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent` int(10) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `seperator` varchar(255) NOT NULL DEFAULT '',
  `order` int(10) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_block_rel_lang_content` (
  `block_id` int(10) unsigned NOT NULL DEFAULT '0',
  `lang_id` int(10) unsigned NOT NULL DEFAULT '0',
  `content` mediumtext NOT NULL,
  `active` int(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `id_lang` (`block_id`,`lang_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_block_rel_pages` (
  `block_id` int(7) NOT NULL DEFAULT '0',
  `page_id` int(7) NOT NULL DEFAULT '0',
  `placeholder` enum('global','direct','category') NOT NULL DEFAULT 'global',
  PRIMARY KEY (`block_id`,`page_id`,`placeholder`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_block_settings` (
  `id` int(7) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `value` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_block_targeting_option` (
  `block_id` int(11) NOT NULL,
  `filter` enum('include','exclude') NOT NULL DEFAULT 'include',
  `type` enum('country') NOT NULL DEFAULT 'country',
  `value` text NOT NULL,
  PRIMARY KEY (`block_id`,`type`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_blog_categories` (
  `category_id` int(4) unsigned NOT NULL DEFAULT '0',
  `lang_id` int(2) unsigned NOT NULL DEFAULT '0',
  `is_active` enum('0','1') NOT NULL DEFAULT '1',
  `name` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`category_id`,`lang_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_blog_comments` (
  `comment_id` int(7) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` int(6) unsigned NOT NULL DEFAULT '0',
  `lang_id` int(2) unsigned NOT NULL DEFAULT '0',
  `is_active` enum('0','1') NOT NULL DEFAULT '1',
  `time_created` int(14) unsigned NOT NULL DEFAULT '0',
  `ip_address` varchar(32) NOT NULL DEFAULT '',
  `user_id` int(5) unsigned NOT NULL DEFAULT '0',
  `user_name` varchar(50) DEFAULT NULL,
  `user_mail` varchar(250) DEFAULT NULL,
  `user_www` varchar(255) DEFAULT NULL,
  `subject` varchar(250) NOT NULL DEFAULT '',
  `comment` text NOT NULL,
  PRIMARY KEY (`comment_id`),
  KEY `message_id` (`message_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_blog_message_to_category` (
  `message_id` int(6) unsigned NOT NULL DEFAULT '0',
  `category_id` int(4) unsigned NOT NULL DEFAULT '0',
  `lang_id` int(2) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`message_id`,`category_id`,`lang_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_blog_messages` (
  `message_id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(5) unsigned NOT NULL DEFAULT '0',
  `time_created` int(14) unsigned NOT NULL DEFAULT '0',
  `time_edited` int(14) unsigned NOT NULL DEFAULT '0',
  `hits` int(7) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_blog_messages_lang` (
  `message_id` int(6) unsigned NOT NULL,
  `lang_id` int(2) unsigned NOT NULL,
  `is_active` enum('0','1') NOT NULL DEFAULT '1',
  `subject` varchar(250) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  `tags` varchar(250) NOT NULL DEFAULT '',
  `image` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`message_id`,`lang_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_blog_networks` (
  `network_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(250) NOT NULL DEFAULT '',
  `url` varchar(255) NOT NULL DEFAULT '',
  `url_link` varchar(255) NOT NULL DEFAULT '',
  `icon` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`network_id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_blog_networks_lang` (
  `network_id` int(8) unsigned NOT NULL DEFAULT '0',
  `lang_id` int(2) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`network_id`,`lang_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_blog_settings` (
  `name` varchar(50) NOT NULL,
  `value` varchar(250) NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_blog_votes` (
  `vote_id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` int(6) unsigned NOT NULL DEFAULT '0',
  `time_voted` int(14) unsigned NOT NULL DEFAULT '0',
  `ip_address` varchar(32) NOT NULL DEFAULT '',
  `vote` enum('1','2','3','4','5','6','7','8','9','10') NOT NULL DEFAULT '1',
  PRIMARY KEY (`vote_id`),
  KEY `message_id` (`message_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_calendar_category` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `pos` int(5) DEFAULT NULL,
  `status` int(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_calendar_category_name` (
  `cat_id` int(11) NOT NULL,
  `lang_id` int(11) NOT NULL,
  `name` varchar(225) NOT NULL,
  PRIMARY KEY (`cat_id`,`lang_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_calendar_event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` int(11) NOT NULL DEFAULT '0',
  `startdate` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `enddate` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `use_custom_date_display` tinyint(1) NOT NULL,
  `showStartDateList` int(1) NOT NULL,
  `showEndDateList` int(1) NOT NULL,
  `showStartTimeList` int(1) NOT NULL,
  `showEndTimeList` int(1) NOT NULL,
  `showTimeTypeList` int(1) NOT NULL,
  `showStartDateDetail` int(1) NOT NULL,
  `showEndDateDetail` int(1) NOT NULL,
  `showStartTimeDetail` int(1) NOT NULL,
  `showEndTimeDetail` int(1) NOT NULL,
  `showTimeTypeDetail` int(1) NOT NULL,
  `google` int(11) NOT NULL,
  `access` int(1) NOT NULL DEFAULT '0',
  `priority` int(1) NOT NULL DEFAULT '3',
  `price` int(11) NOT NULL DEFAULT '0',
  `link` varchar(255) NOT NULL,
  `pic` varchar(255) NOT NULL DEFAULT '',
  `attach` varchar(255) NOT NULL,
  `place_mediadir_id` int(11) NOT NULL,
  `show_in` varchar(255) NOT NULL,
  `invited_groups` varchar(255) DEFAULT NULL,
  `invited_crm_groups` varchar(255) DEFAULT NULL,
  `excluded_crm_groups` varchar(255) DEFAULT NULL,
  `invited_mails` mediumtext,
  `invitation_sent` int(1) NOT NULL,
  `invitation_email_template` varchar(255) NOT NULL,
  `registration` int(1) NOT NULL DEFAULT '0',
  `registration_form` int(11) NOT NULL,
  `registration_num` varchar(45) DEFAULT NULL,
  `registration_notification` varchar(1024) DEFAULT NULL,
  `email_template` varchar(255) NOT NULL,
  `registration_external_link` text NOT NULL,
  `registration_external_fully_booked` tinyint(1) NOT NULL DEFAULT '0',
  `ticket_sales` tinyint(1) NOT NULL DEFAULT '0',
  `num_seating` text NOT NULL,
  `series_status` int(4) NOT NULL DEFAULT '0',
  `independent_series` smallint NOT NULL DEFAULT '1',
  `series_type` int(11) NOT NULL DEFAULT '0',
  `series_pattern_count` int(11) NOT NULL DEFAULT '0',
  `series_pattern_weekday` varchar(7) NOT NULL,
  `series_pattern_day` int(11) NOT NULL DEFAULT '0',
  `series_pattern_week` int(11) NOT NULL DEFAULT '0',
  `series_pattern_month` int(11) NOT NULL DEFAULT '0',
  `series_pattern_type` int(11) NOT NULL DEFAULT '0',
  `series_pattern_dourance_type` int(11) NOT NULL DEFAULT '0',
  `series_pattern_end` int(11) NOT NULL DEFAULT '0',
  `series_pattern_end_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `series_pattern_begin` int(11) NOT NULL DEFAULT '0',
  `series_pattern_exceptions` longtext NOT NULL,
  `series_additional_recurrences` longtext NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `confirmed` tinyint(1) NOT NULL DEFAULT '1',
  `show_detail_view` tinyint(1) NOT NULL DEFAULT '1',
  `author` varchar(255) NOT NULL,
  `all_day` tinyint(1) NOT NULL DEFAULT '0',
  `location_type` tinyint(1) NOT NULL DEFAULT '1',
  `place_id` int(11) NOT NULL,
  `place_street` varchar(255) DEFAULT NULL,
  `place_zip` varchar(10) DEFAULT NULL,
  `place_website` varchar(255) NOT NULL DEFAULT '',
  `place_link` varchar(255) NOT NULL,
  `place_phone` varchar(20) NOT NULL DEFAULT '',
  `place_map` varchar(255) NOT NULL,
  `host_type` tinyint(1) NOT NULL DEFAULT '1',
  `org_street` varchar(255) NOT NULL,
  `org_zip` varchar(10) NOT NULL,
  `org_website` varchar(255) NOT NULL DEFAULT '',
  `org_link` varchar(255) NOT NULL,
  `org_phone` varchar(20) NOT NULL DEFAULT '',
  `org_email` varchar(255) NOT NULL,
  `host_mediadir_id` int(11) NOT NULL,
  INDEX contrexx_module_calendar_registration_form_ibkf (`registration_form`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_calendar_event_field` (
  `event_id` int(11) NOT NULL,
  `lang_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `teaser` text DEFAULT NULL,
  `description` mediumtext,
  `redirect` varchar(255) NOT NULL,
  `place` varchar(255) NOT NULL,
  `place_city` varchar(255) NOT NULL,
  `place_country` varchar(255) NOT NULL,
  `org_name` varchar(255) NOT NULL,
  `org_city` varchar(255) NOT NULL,
  `org_country` varchar(255) NOT NULL,
  PRIMARY KEY (`event_id`,`lang_id`),
  KEY `lang_field` (`title`),
  KEY `fk_contrexx_module_calendar_note_field_contrexx_module_calend1` (`event_id`),
  FULLTEXT KEY `eventIndex` (`title`,`teaser`,`description`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_calendar_events_categories` (
  `event_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  PRIMARY KEY (`event_id`, `category_id`),
  INDEX `contrexx_module_calendar_events_categories_event_id_ibfk` (`event_id`),
  INDEX `contrexx_module_calendar_events_categories_category_id_ibfk` (`category_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_calendar_invite` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `date` int unsigned NOT NULL,
  `invitee_type` enum('-', 'AccessUser','CrmContact') NOT NULL,
  `invitee_id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `token` varchar(32) NOT NULL,
  INDEX contrexx_module_calendar_event_id_ibfk (event_id),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_calendar_host` (
  `id` int(1) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `uri` mediumtext NOT NULL,
  `cat_id` int(11) NOT NULL,
  `key` varchar(32) NOT NULL,
  `confirmed` int(11) NOT NULL,
  `status` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_contrexx_module_calendar_shared_hosts_contrexx_module_cale1` (`cat_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_calendar_mail` (
  `id` int(7) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content_text` longtext NOT NULL,
  `content_html` longtext NOT NULL,
  `recipients` mediumtext NOT NULL,
  `lang_id` int(1) NOT NULL,
  `action_id` int(1) NOT NULL,
  `is_default` int(1) NOT NULL,
  `status` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_calendar_registration` (
  `id` int(7) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `date` int(15) NOT NULL,
  `submission_date` timestamp DEFAULT '0000-00-00 00:00:00',
  `type` int(1) NOT NULL,
  `invite_id` int NULL DEFAULT NULL,
  `user_id` int(7) NOT NULL,
  `lang_id` int(11) NOT NULL,
  `export` int(11) NOT NULL,
  `payment_method` int(11) NOT NULL,
  `paid` int(11) NOT NULL,
  UNIQUE INDEX UNIQ_7F5FE63EA417747 (`invite_id`),
  INDEX `contrexx_module_calendar_registration_event_id_ibfk` (`event_id`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_calendar_registration_form` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` int(11) NOT NULL,
  `order` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_calendar_registration_form_field` (
  `id` int(7) NOT NULL AUTO_INCREMENT,
  `form` int(11) NOT NULL,
  `type` enum('inputtext','textarea','select','radio','checkbox','mail','seating','agb','salutation','firstname','lastname','selectBillingAddress','fieldset') NOT NULL,
  `required` int(1) NOT NULL,
  `order` int(3) NOT NULL,
  `affiliation` varchar(45) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_calendar_registration_form_field_name` (
  `field_id` int(7) NOT NULL,
  `form_id` int(11) NOT NULL,
  `lang_id` int(1) NOT NULL,
  `name` varchar(255) NOT NULL,
  `default` mediumtext NOT NULL,
  PRIMARY KEY (`field_id`,`form_id`,`lang_id`),
  INDEX `contrexx_module_calendar_registration_form_field_name_13` (`field_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_calendar_registration_form_field_value` (
  `reg_id` int(7) NOT NULL,
  `field_id` int(7) NOT NULL,
  `value` mediumtext NOT NULL,
  INDEX `contrexx_module_calendar_registration_form_field_value_11` (`reg_id`),
  INDEX `contrexx_module_calendar_registration_form_field_value_14` (`field_id`),
  PRIMARY KEY (`reg_id`,`field_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_calendar_rel_event_host` (
  `host_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_calendar_settings` (
  `id` int(7) NOT NULL AUTO_INCREMENT,
  `section_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `info` mediumtext NOT NULL,
  `type` int(11) NOT NULL,
  `options` mediumtext NOT NULL,
  `special` varchar(255) NOT NULL,
  `order` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_calendar_settings_section` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent` int(11) NOT NULL,
  `order` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_calendar_style` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tableWidth` varchar(4) NOT NULL DEFAULT '141',
  `tableHeight` varchar(4) NOT NULL DEFAULT '92',
  `tableColor` varchar(7) NOT NULL DEFAULT '',
  `tableBorder` int(11) NOT NULL DEFAULT '0',
  `tableBorderColor` varchar(7) NOT NULL DEFAULT '',
  `tableSpacing` int(11) NOT NULL DEFAULT '0',
  `fontSize` int(11) NOT NULL DEFAULT '10',
  `fontColor` varchar(7) NOT NULL DEFAULT '',
  `numColor` varchar(7) NOT NULL DEFAULT '',
  `normalDayColor` varchar(7) NOT NULL DEFAULT '',
  `normalDayRollOverColor` varchar(7) NOT NULL DEFAULT '',
  `curDayColor` varchar(7) NOT NULL DEFAULT '',
  `curDayRollOverColor` varchar(7) NOT NULL DEFAULT '',
  `eventDayColor` varchar(7) NOT NULL DEFAULT '',
  `eventDayRollOverColor` varchar(7) NOT NULL DEFAULT '',
  `shownEvents` int(4) NOT NULL DEFAULT '10',
  `periodTime` varchar(5) NOT NULL DEFAULT '00 23',
  `stdCat` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_checkout_settings_general` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `value` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_checkout_settings_mails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` text NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_checkout_settings_yellowpay` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_checkout_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time` int(10) NOT NULL DEFAULT '0',
  `status` enum('confirmed','waiting','cancelled') NOT NULL,
  `invoice_number` varchar(255) NOT NULL,
  `invoice_currency` int(11) NOT NULL DEFAULT '1',
  `invoice_amount` int(15) NOT NULL,
  `contact_title` enum('mister','miss') NOT NULL,
  `contact_forename` varchar(255) NOT NULL DEFAULT '',
  `contact_surname` varchar(255) NOT NULL DEFAULT '',
  `contact_company` varchar(255) NOT NULL DEFAULT '',
  `contact_street` varchar(255) NOT NULL DEFAULT '',
  `contact_postcode` varchar(255) NOT NULL DEFAULT '',
  `contact_place` varchar(255) NOT NULL DEFAULT '',
  `contact_country` int(11) NOT NULL DEFAULT '204',
  `contact_phone` varchar(255) NOT NULL DEFAULT '',
  `contact_email` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_contact_form` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `mails` text NOT NULL,
  `showForm` tinyint(1) NOT NULL DEFAULT '0',
  `use_captcha` tinyint(1) NOT NULL DEFAULT '1',
  `use_custom_style` tinyint(1) NOT NULL DEFAULT '0',
  `save_data_in_crm` tinyint(1) NOT NULL DEFAULT '0',
  `send_copy` tinyint(1) NOT NULL DEFAULT '0',
  `use_email_of_sender` tinyint(1) NOT NULL DEFAULT '0',
  `html_mail` tinyint(1) NOT NULL DEFAULT '1',
  `send_attachment` tinyint(1) NOT NULL DEFAULT '0',
  `crm_customer_groups` LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)',
  `send_multiple_reply` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_contact_form_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_form` int(10) unsigned NOT NULL DEFAULT '0',
  `id_lang` int(10) unsigned NOT NULL DEFAULT '1',
  `time` int(14) unsigned NOT NULL DEFAULT '0',
  `host` varchar(255) NOT NULL DEFAULT '',
  `lang` varchar(64) NOT NULL DEFAULT '',
  `browser` varchar(255) NOT NULL DEFAULT '',
  `ipaddress` varchar(15) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  INDEX `id_form` (`id_form`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_contact_form_field` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_form` int(10) unsigned NOT NULL DEFAULT '0',
  `type` enum('text','label','checkbox','checkboxGroup','country','date','file','multi_file','fieldset','hidden','horizontalLine','password','radio','select','textarea','recipient','special','datetime') NOT NULL DEFAULT 'text',
  `special_type` varchar(20) NOT NULL,
  `is_required` set('0','1') NOT NULL DEFAULT '0',
  `check_type` int(3) NOT NULL DEFAULT '1',
  `order_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  INDEX `id_form` (`id_form`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_contact_form_field_lang` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fieldID` int(10) unsigned NOT NULL,
  `langID` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `attributes` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fieldID` (`fieldID`,`langID`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_contact_form_lang` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `formID` int(10) unsigned NOT NULL,
  `langID` int(10) unsigned NOT NULL,
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `name` varchar(255) NOT NULL,
  `text` text NOT NULL,
  `feedback` text NOT NULL,
  `mailTemplate` text NOT NULL,
  `subject` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `formID` (`formID`,`langID`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_contact_form_submit_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_entry` int(10) unsigned NOT NULL,
  `id_field` int(10) unsigned NOT NULL,
  `formlabel` text NOT NULL,
  `formvalue` text NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `id_entry` (`id_entry`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_contact_recipient` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_form` int(11) NOT NULL DEFAULT '0',
  `email` varchar(250) NOT NULL DEFAULT '',
  `sort` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_contact_recipient_lang` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `recipient_id` int(10) unsigned NOT NULL,
  `langID` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `recipient_id` (`recipient_id`,`langID`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_contact_settings` (
  `setid` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `setname` varchar(250) NOT NULL DEFAULT '',
  `setvalue` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`setid`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_crm_company_size` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_size` varchar(100) NOT NULL,
  `sorting` int(11) NOT NULL,
  `status` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `company_size` (`company_size`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_crm_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` varchar(256) DEFAULT NULL,
  `customer_type` int(11) DEFAULT NULL,
  `customer_name` varchar(256) DEFAULT NULL,
  `customer_website` varchar(256) DEFAULT NULL,
  `customer_addedby` int(11) DEFAULT NULL,
  `company_size` int(11) DEFAULT NULL,
  `customer_currency` int(11) DEFAULT NULL,
  `contact_amount` VARCHAR(256) DEFAULT NULL,
  `contact_familyname` varchar(256) DEFAULT NULL,
  `contact_title` VARCHAR(256) DEFAULT NULL,
  `contact_role` varchar(256) DEFAULT NULL,
  `contact_customer` int(11) DEFAULT NULL,
  `contact_language` int(11) DEFAULT NULL,
  `gender` tinyint(2) NOT NULL,
  `salutation` int(11) NOT NULL DEFAULT '0',
  `notes` text,
  `industry_type` int(11) DEFAULT NULL,
  `contact_type` tinyint(2) DEFAULT NULL,
  `user_account` int(11) DEFAULT NULL,
  `datasource` int(11) DEFAULT NULL,
  `profile_picture` varchar(256) NOT NULL,
  `status` tinyint(2) NOT NULL DEFAULT '1',
  `added_date` date NOT NULL,
  `updated_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `email_delivery` tinyint(2) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `contact_customer` (`contact_customer`),
  KEY `customer_id` (`customer_id`),
  KEY `customer_name` (`customer_name`),
  KEY `contact_familyname` (`contact_familyname`),
  KEY `contact_role` (`contact_role`),
  FULLTEXT KEY `customer_id_2` (`customer_id`,`customer_name`,`contact_familyname`,`contact_role`,`notes`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_crm_currency` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(400) NOT NULL,
  `active` tinyint NOT NULL DEFAULT '1',
  `pos` int NOT NULL DEFAULT '0',
  `hourly_rate` text NOT NULL,
  `default_currency` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`(255))
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_crm_customer_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT NULL,
  `notes_type_id` int(1) NOT NULL,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `comment` text,
  `added_date` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  FULLTEXT KEY `comment` (`comment`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_crm_customer_contact_address` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `address` varchar(256) NOT NULL,
  `city` varchar(256) NOT NULL,
  `state` varchar(256) NOT NULL,
  `zip` varchar(256) NOT NULL,
  `country` varchar(256) NOT NULL,
  `Address_Type` tinyint(4) NOT NULL,
  `is_primary` enum('0','1') NOT NULL,
  `contact_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_id` (`contact_id`),
  KEY `address` (`address`),
  KEY `city` (`city`),
  KEY `state` (`state`),
  KEY `zip` (`zip`),
  KEY `country` (`country`),
  FULLTEXT KEY `address_2` (`address`,`city`,`state`,`zip`,`country`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_crm_customer_contact_emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(256) NOT NULL,
  `email_type` tinyint(4) NOT NULL,
  `is_primary` enum('0','1') DEFAULT '0',
  `contact_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_id` (`contact_id`),
  KEY `email` (`email`),
  FULLTEXT KEY `email_2` (`email`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_crm_customer_contact_phone` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `phone` varchar(256) NOT NULL,
  `phone_type` tinyint(4) NOT NULL,
  `is_primary` enum('0','1') DEFAULT '0',
  `contact_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_id` (`contact_id`),
  KEY `phone` (`phone`),
  FULLTEXT KEY `phone_2` (`phone`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_crm_customer_contact_social_network` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(256) NOT NULL,
  `url_profile` tinyint(4) NOT NULL,
  `is_primary` enum('0','1') DEFAULT '0',
  `contact_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_id` (`contact_id`),
  KEY `url` (`url`),
  FULLTEXT KEY `url_2` (`url`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_crm_customer_contact_websites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(256) NOT NULL,
  `url_type` tinyint(4) NOT NULL,
  `url_profile` tinyint(4) NOT NULL,
  `is_primary` enum('0','1') DEFAULT '0',
  `contact_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_id` (`contact_id`),
  KEY `url` (`url`),
  FULLTEXT KEY `url_2` (`url`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_crm_customer_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_name` varchar(256) NOT NULL,
  `added_by` int(11) NOT NULL,
  `uploaded_date` datetime NOT NULL,
  `contact_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `contact_id` (`contact_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_crm_customer_membership` (
  `contact_id` int(11) NOT NULL,
  `membership_id` int(11) NOT NULL
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_crm_customer_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(250) NOT NULL,
  `hourly_rate` varchar(256) NOT NULL,
  `active` int(1) NOT NULL,
  `pos` int(10) NOT NULL DEFAULT '0',
  `default` tinyint(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `label` (`label`),
  FULLTEXT KEY `label_2` (`label`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_crm_datasources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `datasource` varchar(256) NOT NULL,
  `status` tinyint(2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_crm_deals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(256) NOT NULL,
  `website` int(11) NOT NULL,
  `customer` int(11) NOT NULL,
  `customer_contact` int(11) NOT NULL,
  `quoted_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `quote_number` varchar(256) NOT NULL,
  `assigned_to` int(11) NOT NULL,
  `due_date` date DEFAULT NULL,
  `stage` int(11) NOT NULL,
  `description` text NOT NULL,
  `project_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `customer` (`customer`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_crm_industry_type_local` (
  `entry_id` int(11) NOT NULL,
  `lang_id` int(11) NOT NULL,
  `value` varchar(256) NOT NULL,
  KEY `entry_id` (`entry_id`),
  KEY `value` (`value`),
  FULLTEXT KEY `value_2` (`value`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_crm_industry_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `sorting` int(11) NOT NULL,
  `status` smallint(2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_crm_membership_local` (
  `entry_id` int(11) NOT NULL,
  `lang_id` int(11) NOT NULL,
  `value` varchar(256) NOT NULL,
  KEY `entry_id` (`entry_id`),
  KEY `value` (`value`),
  FULLTEXT KEY `value_2` (`value`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_crm_memberships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sorting` int(11) NOT NULL,
  `status` smallint(2) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_crm_notes` (
  `id` int(1) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `icon` varchar(255) NOT NULL,
  `pos` int(1) NOT NULL,
  `system_defined` tinyint(2) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  FULLTEXT KEY `name_2` (`name`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_crm_settings` (
  `setid` int(7) NOT NULL AUTO_INCREMENT,
  `setname` varchar(255) NOT NULL,
  `setvalue` text NOT NULL,
  PRIMARY KEY (`setid`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_crm_stages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(256) NOT NULL,
  `stage` varchar(256) NOT NULL,
  `status` tinyint(2) NOT NULL,
  `sorting` int(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_crm_success_rate` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(256) NOT NULL,
  `rate` varchar(256) NOT NULL,
  `status` tinyint(2) NOT NULL,
  `sorting` int(4) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_crm_task` (
  `id` int(2) NOT NULL AUTO_INCREMENT,
  `task_id` varchar(10) NOT NULL,
  `task_title` varchar(255) NOT NULL,
  `task_type_id` int(2) NOT NULL,
  `customer_id` int(2) NOT NULL,
  `due_date` datetime NOT NULL,
  `assigned_to` int(11) NOT NULL,
  `description` text NOT NULL,
  `task_status` tinyint(1) NOT NULL DEFAULT '1',
  `added_by` int(11) NOT NULL,
  `added_date_time` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_crm_task_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(256) NOT NULL,
  `status` tinyint(1) NOT NULL,
  `sorting` int(11) NOT NULL,
  `description` text NOT NULL,
  `icon` varchar(255) NOT NULL,
  `system_defined` tinyint(4) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  FULLTEXT KEY `name_2` (`name`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_data_categories` (
  `category_id` int(4) unsigned NOT NULL DEFAULT '0',
  `lang_id` int(2) unsigned NOT NULL DEFAULT '0',
  `is_active` enum('0','1') NOT NULL DEFAULT '1',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL DEFAULT '',
  `active` enum('0','1') NOT NULL DEFAULT '1',
  `cmd` int(10) unsigned NOT NULL DEFAULT '1',
  `action` enum('content','overlaybox','subcategories') NOT NULL DEFAULT 'content',
  `sort` int(10) unsigned NOT NULL DEFAULT '1',
  `box_height` int(10) unsigned NOT NULL DEFAULT '500',
  `box_width` int(11) NOT NULL DEFAULT '350',
  `template` text NOT NULL,
  PRIMARY KEY (`category_id`,`lang_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_data_message_to_category` (
  `message_id` int(6) unsigned NOT NULL DEFAULT '0',
  `category_id` int(4) unsigned NOT NULL DEFAULT '0',
  `lang_id` int(2) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`message_id`,`category_id`,`lang_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_data_messages` (
  `message_id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(5) unsigned NOT NULL DEFAULT '0',
  `time_created` int(14) unsigned NOT NULL DEFAULT '0',
  `time_edited` int(14) unsigned NOT NULL DEFAULT '0',
  `hits` int(7) unsigned NOT NULL DEFAULT '0',
  `active` enum('0','1') NOT NULL DEFAULT '1',
  `sort` int(10) unsigned NOT NULL DEFAULT '1',
  `mode` set('normal','forward') NOT NULL DEFAULT 'normal',
  `release_time` int(15) NOT NULL DEFAULT '0',
  `release_time_end` int(15) NOT NULL DEFAULT '0',
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_data_messages_lang` (
  `message_id` int(6) unsigned NOT NULL DEFAULT '0',
  `lang_id` int(2) unsigned NOT NULL DEFAULT '0',
  `is_active` enum('0','1') NOT NULL DEFAULT '1',
  `subject` varchar(250) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  `tags` varchar(250) NOT NULL DEFAULT '',
  `image` varchar(250) NOT NULL DEFAULT '',
  `thumbnail` varchar(250) NOT NULL,
  `thumbnail_type` enum('original','thumbnail') NOT NULL DEFAULT 'original',
  `thumbnail_width` int(11) unsigned NOT NULL DEFAULT '0',
  `thumbnail_height` int(11) unsigned NOT NULL DEFAULT '0',
  `attachment` varchar(255) NOT NULL DEFAULT '',
  `attachment_description` varchar(255) NOT NULL DEFAULT '',
  `mode` set('normal','forward') NOT NULL DEFAULT 'normal',
  `forward_url` varchar(255) NOT NULL DEFAULT '',
  `forward_target` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`message_id`,`lang_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_data_placeholders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` set('cat','entry') NOT NULL DEFAULT '',
  `ref_id` int(11) NOT NULL DEFAULT '0',
  `placeholder` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `placeholder` (`placeholder`),
  UNIQUE KEY `type` (`type`,`ref_id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_data_settings` (
  `name` varchar(50) NOT NULL DEFAULT '',
  `value` text NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_directory_categories` (
  `id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `parentid` int(6) unsigned NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL DEFAULT '',
  `description` varchar(250) NOT NULL DEFAULT '',
  `displayorder` smallint(6) unsigned NOT NULL DEFAULT '1000',
  `metadesc` varchar(250) NOT NULL DEFAULT '',
  `metakeys` varchar(250) NOT NULL DEFAULT '',
  `showentries` int(1) NOT NULL DEFAULT '1',
  `status` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `name` (`name`),
  KEY `parentid` (`parentid`),
  KEY `displayorder` (`displayorder`),
  KEY `status` (`status`),
  FULLTEXT KEY `directoryindex` (`name`,`description`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_directory_dir` (
  `id` int(7) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL DEFAULT '',
  `attachment` varchar(255) NOT NULL DEFAULT '',
  `rss_file` varchar(255) NOT NULL DEFAULT '',
  `rss_link` varchar(255) NOT NULL DEFAULT '',
  `link` varchar(255) NOT NULL DEFAULT '',
  `date` varchar(14) DEFAULT NULL,
  `description` mediumtext NOT NULL,
  `platform` varchar(40) NOT NULL DEFAULT '',
  `language` varchar(40) NOT NULL DEFAULT '',
  `relatedlinks` varchar(255) NOT NULL DEFAULT '',
  `hits` int(9) NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `addedby` varchar(50) NOT NULL DEFAULT '',
  `validatedate` varchar(14) NOT NULL DEFAULT '',
  `lastip` varchar(50) NOT NULL DEFAULT '',
  `popular_date` varchar(30) NOT NULL DEFAULT '',
  `popular_hits` int(7) NOT NULL DEFAULT '0',
  `xml_refresh` varchar(15) NOT NULL DEFAULT '',
  `canton` varchar(50) NOT NULL DEFAULT '',
  `searchkeys` varchar(255) NOT NULL DEFAULT '',
  `company_name` varchar(100) NOT NULL DEFAULT '',
  `street` varchar(255) NOT NULL DEFAULT '',
  `zip` varchar(5) NOT NULL DEFAULT '',
  `city` varchar(50) NOT NULL DEFAULT '',
  `country` varchar(255) NOT NULL DEFAULT '',
  `phone` varchar(20) NOT NULL DEFAULT '',
  `contact` varchar(100) NOT NULL DEFAULT '',
  `information` varchar(100) NOT NULL DEFAULT '',
  `fax` varchar(20) NOT NULL DEFAULT '',
  `mobile` varchar(20) NOT NULL DEFAULT '',
  `mail` varchar(50) NOT NULL DEFAULT '',
  `homepage` varchar(50) NOT NULL DEFAULT '',
  `industry` varchar(100) NOT NULL DEFAULT '',
  `legalform` varchar(50) NOT NULL DEFAULT '',
  `conversion` varchar(50) NOT NULL DEFAULT '',
  `employee` varchar(255) NOT NULL DEFAULT '',
  `foundation` varchar(10) NOT NULL DEFAULT '',
  `mwst` varchar(50) NOT NULL DEFAULT '',
  `opening` varchar(255) NOT NULL DEFAULT '',
  `holidays` varchar(255) NOT NULL DEFAULT '',
  `places` varchar(255) NOT NULL DEFAULT '',
  `logo` varchar(50) DEFAULT NULL,
  `team` varchar(255) NOT NULL DEFAULT '',
  `portfolio` varchar(255) NOT NULL DEFAULT '',
  `offers` varchar(255) NOT NULL DEFAULT '',
  `concept` varchar(255) NOT NULL DEFAULT '',
  `map` varchar(255) DEFAULT NULL,
  `lokal` varchar(255) DEFAULT NULL,
  `spezial` int(4) NOT NULL DEFAULT '0',
  `premium` int(1) NOT NULL DEFAULT '0',
  `longitude` decimal(18,15) NOT NULL DEFAULT '0.000000000000000',
  `latitude` decimal(18,15) NOT NULL DEFAULT '0.000000000000000',
  `zoom` decimal(18,15) NOT NULL DEFAULT '1.000000000000000',
  `spez_field_1` varchar(255) NOT NULL DEFAULT '',
  `spez_field_2` varchar(255) NOT NULL DEFAULT '',
  `spez_field_3` varchar(255) NOT NULL DEFAULT '',
  `spez_field_4` varchar(255) NOT NULL DEFAULT '',
  `spez_field_5` varchar(255) NOT NULL DEFAULT '',
  `spez_field_6` mediumtext NOT NULL,
  `spez_field_7` mediumtext NOT NULL,
  `spez_field_8` mediumtext NOT NULL,
  `spez_field_9` mediumtext NOT NULL,
  `spez_field_10` mediumtext NOT NULL,
  `spez_field_11` varchar(255) DEFAULT NULL,
  `spez_field_12` varchar(255) DEFAULT NULL,
  `spez_field_13` varchar(255) DEFAULT NULL,
  `spez_field_14` varchar(255) DEFAULT NULL,
  `spez_field_15` varchar(255) DEFAULT NULL,
  `spez_field_21` varchar(255) NOT NULL DEFAULT '',
  `spez_field_22` varchar(255) NOT NULL DEFAULT '',
  `spez_field_16` varchar(255) DEFAULT NULL,
  `spez_field_17` varchar(255) DEFAULT NULL,
  `spez_field_18` varchar(255) DEFAULT NULL,
  `spez_field_19` varchar(255) DEFAULT NULL,
  `spez_field_20` varchar(255) DEFAULT NULL,
  `spez_field_23` varchar(255) NOT NULL DEFAULT '',
  `spez_field_24` varchar(255) NOT NULL DEFAULT '',
  `spez_field_25` varchar(255) NOT NULL DEFAULT '',
  `spez_field_26` varchar(255) NOT NULL DEFAULT '',
  `spez_field_27` varchar(255) NOT NULL DEFAULT '',
  `spez_field_28` varchar(255) NOT NULL DEFAULT '',
  `spez_field_29` varchar(255) NOT NULL DEFAULT '',
  `youtube` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`),
  KEY `temphitsout` (`hits`),
  KEY `status` (`status`),
  FULLTEXT KEY `name` (`title`,`description`),
  FULLTEXT KEY `description` (`description`),
  FULLTEXT KEY `title` (`title`,`description`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_directory_inputfields` (
  `id` int(7) NOT NULL AUTO_INCREMENT,
  `typ` int(2) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `active` int(1) NOT NULL DEFAULT '0',
  `active_backend` int(1) NOT NULL DEFAULT '0',
  `is_required` int(11) NOT NULL DEFAULT '0',
  `read_only` int(1) NOT NULL DEFAULT '0',
  `sort` int(5) NOT NULL DEFAULT '0',
  `exp_search` int(1) NOT NULL DEFAULT '0',
  `is_search` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_directory_levels` (
  `id` int(7) NOT NULL AUTO_INCREMENT,
  `parentid` int(7) NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `metadesc` varchar(100) NOT NULL DEFAULT '',
  `metakeys` varchar(100) NOT NULL DEFAULT '',
  `displayorder` int(7) NOT NULL DEFAULT '0',
  `showlevels` int(1) NOT NULL DEFAULT '0',
  `showcategories` int(1) NOT NULL DEFAULT '0',
  `onlyentries` int(1) NOT NULL DEFAULT '0',
  `status` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `displayorder` (`displayorder`),
  KEY `parentid` (`parentid`),
  KEY `name` (`name`),
  KEY `status` (`status`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_directory_mail` (
  `id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `content` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_directory_rel_dir_cat` (
  `dir_id` int(7) NOT NULL DEFAULT '0',
  `cat_id` int(7) NOT NULL DEFAULT '0',
  PRIMARY KEY (`dir_id`,`cat_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_directory_rel_dir_level` (
  `dir_id` int(7) NOT NULL DEFAULT '0',
  `level_id` int(7) NOT NULL DEFAULT '0',
  PRIMARY KEY (`dir_id`,`level_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_directory_settings` (
  `setid` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `setname` varchar(250) NOT NULL DEFAULT '',
  `setvalue` text NOT NULL,
  `settyp` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`setid`),
  KEY `setname` (`setname`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_directory_vote` (
  `id` int(7) NOT NULL AUTO_INCREMENT,
  `feed_id` int(7) NOT NULL DEFAULT '0',
  `vote` int(2) NOT NULL DEFAULT '0',
  `count` int(7) NOT NULL DEFAULT '0',
  `client` varchar(255) NOT NULL DEFAULT '',
  `time` varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_docsys` (
  `id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `date` int(14) DEFAULT NULL,
  `title` varchar(250) NOT NULL DEFAULT '',
  `author` varchar(150) NOT NULL DEFAULT '',
  `text` mediumtext NOT NULL,
  `source` varchar(250) NOT NULL DEFAULT '',
  `url1` varchar(250) NOT NULL DEFAULT '',
  `url2` varchar(250) NOT NULL DEFAULT '',
  `lang` int(2) unsigned NOT NULL DEFAULT '0',
  `userid` int(6) unsigned NOT NULL DEFAULT '0',
  `startdate` int(14) unsigned NOT NULL DEFAULT '0',
  `enddate` int(14) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `changelog` int(14) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `newsindex` (`title`,`text`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_docsys_categories` (
  `catid` int(2) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `lang` int(2) unsigned NOT NULL DEFAULT '1',
  `sort_style` enum('alpha','date','date_alpha') NOT NULL DEFAULT 'alpha',
  PRIMARY KEY (`catid`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_docsys_entry_category` (
  `entry` int(10) unsigned NOT NULL DEFAULT '0',
  `category` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`entry`,`category`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_downloads_category` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `visibility` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `owner_id` int(5) unsigned NOT NULL DEFAULT '0',
  `order` int(3) unsigned NOT NULL DEFAULT '0',
  `deletable_by_owner` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `modify_access_by_owner` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `read_access_id` int(11) unsigned NOT NULL DEFAULT '0',
  `add_subcategories_access_id` int(11) unsigned NOT NULL DEFAULT '0',
  `manage_subcategories_access_id` int(11) unsigned NOT NULL DEFAULT '0',
  `add_files_access_id` int(11) unsigned NOT NULL DEFAULT '0',
  `manage_files_access_id` int(11) unsigned NOT NULL DEFAULT '0',
  `image` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `is_active` (`is_active`),
  KEY `visibility` (`visibility`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_downloads_category_locale` (
  `lang_id` int(11) unsigned NOT NULL DEFAULT '0',
  `category_id` int(11) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  PRIMARY KEY (`lang_id`,`category_id`),
  FULLTEXT KEY `name` (`name`),
  FULLTEXT KEY `description` (`description`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_downloads_download` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('file','url') NOT NULL DEFAULT 'file',
  `mime_type` enum('image','document','pdf','media','archive','application','link') NOT NULL DEFAULT 'image',
  `size` int(10) unsigned NOT NULL DEFAULT '0',
  `image` varchar(255) NOT NULL DEFAULT '',
  `owner_id` int(5) unsigned NOT NULL DEFAULT '0',
  `access_id` int(10) unsigned NOT NULL DEFAULT '0',
  `license` varchar(255) NOT NULL DEFAULT '',
  `version` varchar(10) NOT NULL DEFAULT '',
  `author` varchar(100) NOT NULL DEFAULT '',
  `website` varchar(255) NOT NULL DEFAULT '',
  `ctime` int(14) unsigned NOT NULL DEFAULT '0',
  `mtime` int(14) unsigned NOT NULL DEFAULT '0',
  `is_active` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `visibility` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `order` int(3) unsigned NOT NULL DEFAULT '0',
  `views` int(10) unsigned NOT NULL DEFAULT '0',
  `download_count` int(10) unsigned NOT NULL DEFAULT '0',
  `expiration` int(14) unsigned NOT NULL DEFAULT '0',
  `validity` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `is_active` (`is_active`),
  KEY `visibility` (`visibility`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_downloads_download_locale` (
  `lang_id` int(11) unsigned NOT NULL DEFAULT '0',
  `download_id` int(11) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `source` varchar(1024) DEFAULT NULL,
  `source_name` varchar(1024) DEFAULT NULL,
  `file_type` varchar(10) DEFAULT NULL,
  `description` text NOT NULL,
  `metakeys` text NOT NULL,
  PRIMARY KEY (`lang_id`,`download_id`),
  FULLTEXT KEY `name` (`name`),
  FULLTEXT KEY `description` (`description`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_downloads_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `type` enum('file','url') NOT NULL DEFAULT 'file',
  `info_page` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_downloads_group_locale` (
  `lang_id` int(11) unsigned NOT NULL DEFAULT '0',
  `group_id` int(11) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`lang_id`,`group_id`),
  FULLTEXT KEY `name` (`name`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_downloads_rel_download_category` (
  `download_id` int(10) unsigned NOT NULL DEFAULT '0',
  `category_id` int(10) unsigned NOT NULL DEFAULT '0',
  `order` int(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`download_id`,`category_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_downloads_rel_download_download` (
  `id1` int(10) unsigned NOT NULL DEFAULT '0',
  `id2` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id1`,`id2`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_downloads_rel_group_category` (
  `group_id` int(10) unsigned NOT NULL DEFAULT '0',
  `category_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`group_id`,`category_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_downloads_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL DEFAULT '',
  `value` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_ecard_ecards` (
  `code` varchar(35) NOT NULL DEFAULT '',
  `date` int(10) unsigned NOT NULL DEFAULT '0',
  `TTL` int(10) unsigned NOT NULL DEFAULT '0',
  `salutation` varchar(100) NOT NULL DEFAULT '',
  `senderName` varchar(100) NOT NULL DEFAULT '',
  `senderEmail` varchar(100) NOT NULL DEFAULT '',
  `recipientName` varchar(100) NOT NULL DEFAULT '',
  `recipientEmail` varchar(100) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_ecard_settings` (
  `setting_name` varchar(100) NOT NULL DEFAULT '',
  `setting_value` text NOT NULL,
  PRIMARY KEY (`setting_name`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_egov_configuration` (
  `name` varchar(255) NOT NULL DEFAULT '',
  `value` text NOT NULL,
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_egov_orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `order_ip` varchar(255) NOT NULL DEFAULT '',
  `order_product` int(11) NOT NULL DEFAULT '0',
  `order_values` text NOT NULL,
  `order_reservation_date` date NOT NULL DEFAULT '0000-00-00',
  `order_state` tinyint(4) NOT NULL DEFAULT '0',
  `order_quant` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`order_id`),
  KEY `order_product` (`order_product`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_egov_product_calendar` (
  `calendar_id` int(11) NOT NULL AUTO_INCREMENT,
  `calendar_product` int(11) NOT NULL DEFAULT '0',
  `calendar_order` int(11) NOT NULL DEFAULT '0',
  `calendar_day` int(2) NOT NULL DEFAULT '0',
  `calendar_month` int(2) unsigned zerofill NOT NULL DEFAULT '00',
  `calendar_year` int(4) NOT NULL DEFAULT '0',
  `calendar_act` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`calendar_id`),
  KEY `calendar_product` (`calendar_product`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_egov_product_fields` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `type` enum('text','label','checkbox','checkboxGroup','file','hidden','password','radio','select','textarea') NOT NULL DEFAULT 'text',
  `attributes` text NOT NULL,
  `is_required` set('0','1') NOT NULL DEFAULT '0',
  `check_type` int(3) NOT NULL DEFAULT '1',
  `order_id` int(5) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `product` (`product`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_egov_products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_autostatus` tinyint(1) NOT NULL DEFAULT '0',
  `product_name` varchar(255) NOT NULL DEFAULT '',
  `product_desc` text NOT NULL,
  `product_price` decimal(11,2) NOT NULL DEFAULT '0.00',
  `product_per_day` enum('yes','no') NOT NULL DEFAULT 'no',
  `product_quantity` tinyint(2) NOT NULL DEFAULT '0',
  `product_quantity_limit` tinyint(2) unsigned NOT NULL DEFAULT '1',
  `product_target_email` varchar(255) NOT NULL DEFAULT '',
  `product_target_url` varchar(255) NOT NULL DEFAULT '',
  `product_message` text NOT NULL,
  `product_status` tinyint(1) NOT NULL DEFAULT '1',
  `product_electro` tinyint(1) NOT NULL DEFAULT '0',
  `product_file` varchar(255) NOT NULL DEFAULT '',
  `product_sender_name` varchar(255) NOT NULL DEFAULT '',
  `product_sender_email` varchar(255) NOT NULL DEFAULT '',
  `product_target_subject` varchar(255) NOT NULL,
  `product_target_body` text NOT NULL,
  `product_paypal` tinyint(1) NOT NULL DEFAULT '0',
  `product_paypal_sandbox` varchar(255) NOT NULL DEFAULT '',
  `product_paypal_currency` varchar(255) NOT NULL DEFAULT '',
  `product_orderby` int(11) NOT NULL DEFAULT '0',
  `yellowpay` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `alternative_names` text NOT NULL,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_feed_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL DEFAULT '',
  `status` int(1) NOT NULL DEFAULT '1',
  `time` int(100) NOT NULL DEFAULT '0',
  `lang` int(1) NOT NULL DEFAULT '0',
  `pos` int(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_feed_news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subid` int(11) NOT NULL DEFAULT '0',
  `name` varchar(150) NOT NULL DEFAULT '',
  `link` varchar(150) NOT NULL DEFAULT '',
  `filename` varchar(150) NOT NULL DEFAULT '',
  `articles` int(2) NOT NULL DEFAULT '0',
  `cache` int(4) NOT NULL DEFAULT '3600',
  `time` int(100) NOT NULL DEFAULT '0',
  `image` int(1) NOT NULL DEFAULT '1',
  `status` int(1) NOT NULL DEFAULT '1',
  `pos` int(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_feed_newsml_association` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pId_master` text NOT NULL,
  `pId_slave` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_feed_newsml_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `providerId` text NOT NULL,
  `name` varchar(40) NOT NULL DEFAULT '',
  `subjectCodes` text NOT NULL,
  `showSubjectCodes` enum('all','only','exclude') NOT NULL DEFAULT 'all',
  `template` text NOT NULL,
  `limit` smallint(6) NOT NULL DEFAULT '0',
  `showPics` enum('0','1') NOT NULL DEFAULT '1',
  `auto_update` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_feed_newsml_documents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `publicIdentifier` varchar(255) NOT NULL DEFAULT '',
  `providerId` text NOT NULL,
  `dateId` int(8) unsigned NOT NULL DEFAULT '0',
  `newsItemId` text NOT NULL,
  `revisionId` int(5) unsigned NOT NULL DEFAULT '0',
  `thisRevisionDate` int(14) NOT NULL DEFAULT '0',
  `urgency` smallint(5) unsigned NOT NULL DEFAULT '0',
  `subjectCode` int(10) unsigned NOT NULL DEFAULT '0',
  `headLine` varchar(67) NOT NULL DEFAULT '',
  `dataContent` text NOT NULL,
  `is_associated` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `media_type` enum('Text','Graphic','Photo','Audio','Video','ComplexData') NOT NULL DEFAULT 'Text',
  `source` text NOT NULL,
  `properties` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`publicIdentifier`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_feed_newsml_providers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `providerId` text NOT NULL,
  `name` varchar(40) NOT NULL DEFAULT '',
  `path` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_filesharing` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `file` varchar(250) NOT NULL,
  `source` varchar(250) NOT NULL,
  `cmd` varchar(50) NOT NULL,
  `hash` varchar(50) NOT NULL,
  `check` varchar(50) NOT NULL,
  `expiration_date` timestamp NULL DEFAULT NULL,
  `upload_id` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_filesharing_mail_template` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `lang_id` int(1) NOT NULL,
  `subject` varchar(250) NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_forum_access` (
  `category_id` int(5) unsigned NOT NULL DEFAULT '0',
  `group_id` int(5) unsigned NOT NULL DEFAULT '0',
  `read` set('0','1') NOT NULL DEFAULT '0',
  `write` set('0','1') NOT NULL DEFAULT '0',
  `edit` set('0','1') NOT NULL DEFAULT '0',
  `delete` set('0','1') NOT NULL DEFAULT '0',
  `move` set('0','1') NOT NULL DEFAULT '0',
  `close` set('0','1') NOT NULL DEFAULT '0',
  `sticky` set('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`category_id`,`group_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_forum_categories` (
  `id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(5) unsigned NOT NULL DEFAULT '0',
  `order_id` int(5) unsigned NOT NULL DEFAULT '0',
  `status` set('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_forum_categories_lang` (
  `category_id` int(5) unsigned NOT NULL DEFAULT '0',
  `lang_id` int(5) unsigned NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  PRIMARY KEY (`category_id`,`lang_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_forum_notification` (
  `category_id` int(10) unsigned NOT NULL DEFAULT '0',
  `thread_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(5) unsigned NOT NULL DEFAULT '0',
  `is_notified` set('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`category_id`,`thread_id`,`user_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_forum_postings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(5) unsigned NOT NULL DEFAULT '0',
  `thread_id` int(10) unsigned NOT NULL DEFAULT '0',
  `prev_post_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(5) unsigned NOT NULL DEFAULT '0',
  `time_created` int(14) unsigned NOT NULL DEFAULT '0',
  `time_edited` int(14) unsigned NOT NULL DEFAULT '0',
  `is_locked` set('0','1') NOT NULL DEFAULT '0',
  `is_sticky` set('0','1') NOT NULL DEFAULT '0',
  `rating` int(11) NOT NULL DEFAULT '0',
  `views` int(10) unsigned NOT NULL DEFAULT '0',
  `icon` smallint(5) unsigned NOT NULL DEFAULT '0',
  `keywords` text NOT NULL,
  `subject` varchar(250) NOT NULL DEFAULT '',
  `content` text NOT NULL,
  `attachment` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`,`thread_id`,`prev_post_id`,`user_id`),
  FULLTEXT KEY `fulltext` (`keywords`,`subject`,`content`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_forum_rating` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `post_id` int(11) NOT NULL DEFAULT '0',
  `time` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`,`post_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_forum_settings` (
  `id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  `value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_forum_statistics` (
  `category_id` int(5) unsigned NOT NULL DEFAULT '0',
  `thread_count` int(10) unsigned NOT NULL DEFAULT '0',
  `post_count` int(10) unsigned NOT NULL DEFAULT '0',
  `last_post_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_gallery_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL DEFAULT '0',
  `sorting` int(6) NOT NULL DEFAULT '0',
  `status` set('0','1') NOT NULL DEFAULT '1',
  `comment` set('0','1') NOT NULL DEFAULT '0',
  `voting` set('0','1') NOT NULL DEFAULT '0',
  `backendProtected` int(11) NOT NULL DEFAULT '0',
  `backend_access_id` int(11) NOT NULL DEFAULT '0',
  `frontendProtected` int(11) NOT NULL DEFAULT '0',
  `frontend_access_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_gallery_comments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `picid` int(10) unsigned NOT NULL DEFAULT '0',
  `date` int(14) unsigned NOT NULL DEFAULT '0',
  `name` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(250) NOT NULL DEFAULT '',
  `www` varchar(250) NOT NULL DEFAULT '',
  `comment` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_gallery_language` (
  `gallery_id` int(10) unsigned NOT NULL DEFAULT '0',
  `lang_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` set('name','desc') NOT NULL DEFAULT '',
  `value` text NOT NULL,
  PRIMARY KEY (`gallery_id`,`lang_id`,`name`),
  FULLTEXT KEY `galleryindex` (`value`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_gallery_language_pics` (
  `picture_id` int(10) unsigned NOT NULL DEFAULT '0',
  `lang_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `desc` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`picture_id`,`lang_id`),
  FULLTEXT KEY `galleryindex` (`name`,`desc`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_gallery_pictures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `catid` int(11) NOT NULL DEFAULT '0',
  `validated` set('0','1') NOT NULL DEFAULT '0',
  `status` set('0','1') NOT NULL DEFAULT '1',
  `catimg` set('0','1') NOT NULL DEFAULT '0',
  `sorting` int(6) unsigned NOT NULL DEFAULT '999',
  `size_show` set('0','1') NOT NULL DEFAULT '1',
  `path` text NOT NULL,
  `link` text NOT NULL,
  `lastedit` int(14) NOT NULL DEFAULT '0',
  `size_type` set('abs','proz') NOT NULL DEFAULT 'proz',
  `size_proz` int(3) NOT NULL DEFAULT '0',
  `size_abs_h` int(11) NOT NULL DEFAULT '0',
  `size_abs_w` int(11) NOT NULL DEFAULT '0',
  `quality` tinyint(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `galleryPicturesIndex` (`path`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_gallery_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL DEFAULT '',
  `value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_gallery_votes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `picid` int(10) unsigned NOT NULL DEFAULT '0',
  `date` int(14) unsigned NOT NULL DEFAULT '0',
  `md5` varchar(32) NOT NULL DEFAULT '',
  `mark` int(2) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_guestbook` (
  `id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `status` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `forename` varchar(255) NOT NULL DEFAULT '',
  `gender` char(1) NOT NULL DEFAULT '',
  `url` tinytext NOT NULL,
  `email` tinytext NOT NULL,
  `comment` text NOT NULL,
  `location` tinytext NOT NULL,
  `lang_id` tinyint(2) NOT NULL DEFAULT '1',
  `datetime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `comment` (`comment`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_guestbook_settings` (
  `name` varchar(50) NOT NULL DEFAULT '',
  `value` varchar(250) NOT NULL DEFAULT '',
  KEY `name` (`name`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_immo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reference` varchar(20) NOT NULL DEFAULT '-',
  `ref_nr_note` varchar(255) DEFAULT NULL,
  `logo` enum('logo1','logo2') NOT NULL DEFAULT 'logo1',
  `special_offer` tinyint(1) NOT NULL DEFAULT '0',
  `visibility` enum('disabled','reference','listing') NOT NULL DEFAULT 'disabled',
  `object_type` enum('flat','house','multifamily','estate','industry','parking') NOT NULL DEFAULT 'flat',
  `new_building` tinyint(1) NOT NULL DEFAULT '0',
  `property_type` enum('purchase','rent') NOT NULL DEFAULT 'purchase',
  `longitude` decimal(18,15) NOT NULL DEFAULT '0.000000000000000',
  `latitude` decimal(18,15) NOT NULL DEFAULT '0.000000000000000',
  `zoom` tinyint(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `reference` (`reference`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_immo_contact` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) NOT NULL DEFAULT '',
  `firstname` varchar(255) NOT NULL DEFAULT '',
  `street` varchar(255) NOT NULL DEFAULT '',
  `zip` int(5) NOT NULL DEFAULT '0',
  `location` varchar(255) NOT NULL DEFAULT '',
  `company` varchar(255) NOT NULL DEFAULT '',
  `telephone` varchar(30) NOT NULL DEFAULT '',
  `telephone_office` varchar(30) NOT NULL DEFAULT '',
  `telephone_mobile` varchar(30) NOT NULL DEFAULT '',
  `purchase` tinyint(1) NOT NULL DEFAULT '0',
  `funding` tinyint(1) NOT NULL DEFAULT '0',
  `comment` text NOT NULL,
  `immo_id` int(11) NOT NULL DEFAULT '0',
  `field_id` int(11) NOT NULL DEFAULT '0',
  `timestamp` int(14) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `immo_id` (`immo_id`),
  KEY `field_id` (`field_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_immo_content` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `immo_id` int(11) NOT NULL DEFAULT '0',
  `lang_id` tinyint(4) NOT NULL DEFAULT '0',
  `field_id` int(10) unsigned NOT NULL DEFAULT '0',
  `fieldvalue` text NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `field_id` (`field_id`),
  KEY `immo_id` (`immo_id`),
  KEY `fieldvalue` (`fieldvalue`(64))
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_immo_field` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('text','textarea','img','link','protected_link','panorama','digits_only','price') NOT NULL DEFAULT 'text',
  `order` int(11) NOT NULL DEFAULT '1000',
  `mandatory` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_immo_fieldname` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `field_id` int(10) unsigned NOT NULL DEFAULT '0',
  `lang_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '-',
  PRIMARY KEY (`id`),
  KEY `field_id` (`field_id`),
  KEY `lang_id` (`lang_id`),
  KEY `name` (`name`(5))
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_immo_image` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `immo_id` int(11) NOT NULL DEFAULT '0',
  `field_id` int(10) unsigned NOT NULL DEFAULT '0',
  `uri` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `immo_id` (`immo_id`),
  KEY `field_id` (`field_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_immo_interest` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `immo_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(60) NOT NULL DEFAULT '',
  `firstname` varchar(60) NOT NULL DEFAULT '',
  `street` varchar(100) NOT NULL DEFAULT '',
  `zip` varchar(10) NOT NULL DEFAULT '',
  `location` varchar(100) NOT NULL DEFAULT '',
  `email` varchar(60) NOT NULL DEFAULT '',
  `phone_office` varchar(40) NOT NULL DEFAULT '',
  `phone_home` varchar(40) NOT NULL DEFAULT '',
  `phone_mobile` varchar(40) NOT NULL DEFAULT '',
  `doc_via_mail` tinyint(1) NOT NULL DEFAULT '0',
  `funding_advice` tinyint(1) NOT NULL DEFAULT '0',
  `inspection` tinyint(1) NOT NULL DEFAULT '0',
  `contact_via_phone` tinyint(1) NOT NULL DEFAULT '0',
  `comment` text NOT NULL,
  `time` int(14) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `immo_id` (`immo_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_immo_languages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `language` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_immo_settings` (
  `setid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setname` varchar(80) NOT NULL DEFAULT '',
  `setvalue` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`setid`),
  UNIQUE KEY `setname` (`setname`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_immo_statistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `immo_id` int(11) NOT NULL DEFAULT '0',
  `field_id` int(11) NOT NULL DEFAULT '0',
  `hits` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_jobs` (
  `id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `date` int(14) DEFAULT NULL,
  `title` varchar(250) NOT NULL DEFAULT '',
  `author` varchar(150) NOT NULL DEFAULT '',
  `text` mediumtext NOT NULL,
  `workloc` varchar(250) NOT NULL DEFAULT '',
  `workload` varchar(250) NOT NULL DEFAULT '',
  `work_start` int(14) NOT NULL DEFAULT '0',
  `catid` int(2) unsigned NOT NULL DEFAULT '0',
  `lang` int(2) unsigned NOT NULL DEFAULT '0',
  `userid` int(6) unsigned NOT NULL DEFAULT '0',
  `startdate` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `enddate` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `changelog` int(14) NOT NULL DEFAULT '0',
  `hot` TINYINT(4) NOT NULL DEFAULT '0',
  `paid` TINYINT(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `newsindex` (`title`,`text`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_jobs_categories` (
  `catid` int(2) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `lang` int(2) unsigned NOT NULL DEFAULT '1',
  `sort_style` enum('alpha','date','date_alpha') NOT NULL DEFAULT 'alpha',
  PRIMARY KEY (`catid`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_jobs_location` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_jobs_rel_loc_jobs` (
  `job` int(10) unsigned NOT NULL DEFAULT '0',
  `location` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`job`,`location`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_jobs_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(250) NOT NULL DEFAULT '',
  `value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_knowledge_article_content` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `article` int(10) unsigned NOT NULL DEFAULT '0',
  `lang` int(10) unsigned NOT NULL DEFAULT '0',
  `question` text NOT NULL,
  `answer` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `module_knowledge_article_content_lang` (`lang`),
  KEY `module_knowledge_article_content_article` (`article`),
  FULLTEXT KEY `content` (`question`,`answer`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_knowledge_articles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category` int(10) unsigned NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `hits` int(11) NOT NULL DEFAULT '0',
  `votes` int(11) NOT NULL DEFAULT '0',
  `votevalue` int(11) NOT NULL DEFAULT '0',
  `sort` int(11) NOT NULL DEFAULT '0',
  `date_created` int(14) NOT NULL DEFAULT '0',
  `date_updated` int(14) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_knowledge_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `parent` int(10) unsigned NOT NULL DEFAULT '0',
  `sort` int(10) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `module_knowledge_categories_sort` (`sort`),
  KEY `module_knowledge_categories_parent` (`parent`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_knowledge_categories_content` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `lang` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_knowledge_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `value` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `module_knowledge_settings_name` (`name`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_knowledge_tags` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `lang` int(10) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `module_knowledge_tags_name` (`name`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_knowledge_tags_articles` (
  `article` int(10) unsigned NOT NULL DEFAULT '0',
  `tag` int(10) unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY `article` (`article`,`tag`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_livecam` (
  `id` int(10) unsigned NOT NULL DEFAULT '1',
  `currentImagePath` varchar(255) NOT NULL DEFAULT '/webcam/cam1/current.jpg',
  `archivePath` varchar(255) NOT NULL DEFAULT '/webcam/cam1/archive/',
  `thumbnailPath` varchar(255) NOT NULL DEFAULT '/webcam/cam1/thumbs/',
  `maxImageWidth` int(10) unsigned NOT NULL DEFAULT '400',
  `thumbMaxSize` int(10) unsigned NOT NULL DEFAULT '200',
  `shadowboxActivate` set('1','0') NOT NULL DEFAULT '1',
  `showFrom` int(14) NOT NULL DEFAULT '0',
  `showTill` int(14) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_livecam_settings` (
  `setid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setname` varchar(255) NOT NULL DEFAULT '',
  `setvalue` text NOT NULL,
  PRIMARY KEY (`setid`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_market` (
  `id` int(9) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `type` set('search','offer') NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` mediumtext NOT NULL,
  `color` varchar(50) NOT NULL DEFAULT '',
  `premium` int(1) NOT NULL DEFAULT '0',
  `picture` varchar(255) NOT NULL DEFAULT '',
  `catid` int(4) NOT NULL DEFAULT '0',
  `price` varchar(10) NOT NULL DEFAULT '',
  `regdate` varchar(20) NOT NULL DEFAULT '',
  `enddate` varchar(20) NOT NULL DEFAULT '',
  `userid` int(4) NOT NULL DEFAULT '0',
  `userdetails` int(1) NOT NULL DEFAULT '0',
  `status` int(1) NOT NULL DEFAULT '0',
  `regkey` varchar(50) NOT NULL DEFAULT '',
  `paypal` int(1) NOT NULL DEFAULT '0',
  `sort_id` int(4) NOT NULL DEFAULT '0',
  `spez_field_1` varchar(255) NOT NULL,
  `spez_field_2` varchar(255) NOT NULL,
  `spez_field_3` varchar(255) NOT NULL,
  `spez_field_4` varchar(255) NOT NULL,
  `spez_field_5` varchar(255) NOT NULL,
  `spez_field_6` VARCHAR(255) NOT NULL,
  `spez_field_7` VARCHAR(255) NOT NULL,
  `spez_field_8` VARCHAR(255) NOT NULL,
  `spez_field_9` VARCHAR(255) NOT NULL,
  `spez_field_10` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `description` (`description`),
  FULLTEXT KEY `title` (`description`,`title`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_market_categories` (
  `id` int(6) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `displayorder` int(4) NOT NULL DEFAULT '0',
  `status` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_market_mail` (
  `id` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `content` longtext NOT NULL,
  `mailto` varchar(10) NOT NULL,
  `mailcc` mediumtext NOT NULL,
  `active` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_market_paypal` (
  `id` int(4) NOT NULL AUTO_INCREMENT,
  `active` int(1) NOT NULL DEFAULT '0',
  `profile` varchar(255) NOT NULL DEFAULT '',
  `price` varchar(10) NOT NULL DEFAULT '',
  `price_premium` varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_market_settings` (
  `id` int(6) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `value` varchar(255) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `type` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_market_spez_fields` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `value` varchar(100) NOT NULL,
  `type` int(1) NOT NULL DEFAULT '1',
  `lang_id` int(2) NOT NULL DEFAULT '0',
  `active` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_media_settings` (
  `name` varchar(50) NOT NULL,
  `value` varchar(250) NOT NULL,
  KEY `name` (`name`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_mediadir_categories` (
  `id` int(7) NOT NULL AUTO_INCREMENT,
  `parent_id` int(7) NOT NULL,
  `order` int(7) NOT NULL,
  `show_subcategories` int(11) NOT NULL,
  `show_entries` int(1) NOT NULL,
  `picture` mediumtext NOT NULL,
  `active` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_mediadir_categories_names` (
  `lang_id` int(1) NOT NULL,
  `category_id` int(7) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `category_description` mediumtext NOT NULL,
  `category_metadesc` varchar(160) NOT NULL DEFAULT '',
  UNIQUE INDEX `category` (`lang_id`, `category_id`),
  KEY `lang_id` (`lang_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_mediadir_comments` (
  `id` int(7) NOT NULL AUTO_INCREMENT,
  `entry_id` int(7) NOT NULL,
  `added_by` varchar(255) NOT NULL,
  `date` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `mail` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  `notification` int(1) NOT NULL DEFAULT '0',
  `comment` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_mediadir_entries` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `order` int(7) NOT NULL DEFAULT '0',
  `form_id` int(7) NOT NULL,
  `create_date` int(50) NOT NULL,
  `update_date` int(50) NOT NULL,
  `validate_date` int(50) NOT NULL,
  `added_by` int(10) NOT NULL,
  `updated_by` int(10) NOT NULL,
  `lang_id` int(1) NOT NULL,
  `hits` int(10) NOT NULL,
  `popular_hits` int(10) NOT NULL,
  `popular_date` varchar(20) NOT NULL,
  `last_ip` varchar(50) NOT NULL,
  `ready_to_confirm` int(1) NOT NULL DEFAULT '0',
  `confirmed` int(1) NOT NULL,
  `active` int(1) NOT NULL,
  `duration_type` int(1) NOT NULL,
  `duration_start` int(50) NOT NULL,
  `duration_end` int(50) NOT NULL,
  `duration_notification` int(1) NOT NULL,
  `translation_status` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `lang_id` (`lang_id`),
  KEY `active` (`active`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_mediadir_entry_associated_entry` (
  `source_entry_id` int(11) unsigned NOT NULL,
  `target_entry_id` int(11) unsigned NOT NULL,
  `ord` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`source_entry_id`, `target_entry_id`),
  KEY (`ord`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_mediadir_form_associated_form` (
  `source_form_id` int(11) unsigned NOT NULL,
  `target_form_id` int(11) unsigned NOT NULL,
  `ord` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`source_form_id`, `target_form_id`),
  KEY (`ord`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_mediadir_form_names` (
  `lang_id` int(1) NOT NULL,
  `form_id` int(7) NOT NULL,
  `form_name` varchar(255) NOT NULL,
  `form_description` mediumtext NOT NULL,
  UNIQUE INDEX `form` (`lang_id`, `form_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_mediadir_forms` (
  `id` int(7) NOT NULL AUTO_INCREMENT,
  `order` int(7) NOT NULL,
  `picture` mediumtext NOT NULL,
  `active` int(1) NOT NULL,
  `use_level` int(1) NOT NULL,
  `use_category` int(1) NOT NULL,
  `use_ready_to_confirm` int(1) NOT NULL,
  `use_associated_entries` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `entries_per_page` int(7) NOT NULL DEFAULT '0',
  `cmd` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_mediadir_inputfield_names` (
  `lang_id` int(10) NOT NULL,
  `form_id` int(7) NOT NULL,
  `field_id` int(10) NOT NULL,
  `field_name` varchar(255) NOT NULL,
  `field_default_value` mediumtext NOT NULL,
  `field_info` mediumtext NOT NULL,
  UNIQUE INDEX `field` (`lang_id`, `form_id`, `field_id`),
  KEY `field_id` (`field_id`),
  KEY `lang_id` (`lang_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_mediadir_inputfield_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `active` int(1) NOT NULL,
  `multi_lang` int(1) NOT NULL,
  `exp_search` int(7) NOT NULL,
  `dynamic` int(1) NOT NULL,
  `comment` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_mediadir_inputfield_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `regex` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_mediadir_inputfields` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `form` int(7) NOT NULL,
  `type` int(10) NOT NULL,
  `verification` int(10) NOT NULL,
  `search` int(10) NOT NULL,
  `required` int(10) NOT NULL,
  `order` int(10) NOT NULL,
  `show_in` int(10) NOT NULL,
  `context_type` enum('none','title','content','address','zip','city','country','image','keywords','slug') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_mediadir_level_names` (
  `lang_id` int(1) NOT NULL,
  `level_id` int(7) NOT NULL,
  `level_name` varchar(255) NOT NULL,
  `level_description` mediumtext NOT NULL,
  `level_metadesc` varchar(160) NOT NULL DEFAULT '',
  UNIQUE INDEX `level` (`lang_id`, `level_id`),
  KEY `lang_id` (`lang_id`),
  KEY `category_id` (`level_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_mediadir_levels` (
  `id` int(7) NOT NULL AUTO_INCREMENT,
  `parent_id` int(7) NOT NULL,
  `order` int(7) NOT NULL,
  `show_sublevels` int(11) NOT NULL,
  `show_categories` int(1) NOT NULL,
  `show_entries` int(1) NOT NULL,
  `picture` mediumtext NOT NULL,
  `active` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_mediadir_mail_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `default_recipient` enum('admin','author') NOT NULL,
  `need_auth` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_mediadir_mails` (
  `id` int(7) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `recipients` mediumtext NOT NULL,
  `lang_id` int(1) NOT NULL,
  `action_id` int(1) NOT NULL,
  `is_default` int(1) NOT NULL,
  `active` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_mediadir_masks` (
  `id` int(7) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `fields` mediumtext NOT NULL,
  `active` int(11) NOT NULL,
  `form_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_mediadir_order_rel_forms_selectors` (
  `selector_id` int(7) NOT NULL,
  `form_id` int(7) NOT NULL,
  `selector_order` int(7) NOT NULL,
  `exp_search` int(1) NOT NULL
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_mediadir_rel_entry_categories` (
  `entry_id` int(10) NOT NULL,
  `category_id` int(10) NOT NULL,
  KEY `entry_id` (`entry_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_mediadir_rel_entry_inputfields` (
  `entry_id` int(7) NOT NULL,
  `lang_id` int(7) NOT NULL,
  `form_id` int(7) NOT NULL,
  `field_id` int(7) NOT NULL,
  `value` longtext NOT NULL,
  UNIQUE KEY `entry_id` (`entry_id`,`lang_id`,`form_id`,`field_id`),
  FULLTEXT KEY `value` (`value`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_mediadir_rel_entry_levels` (
  `entry_id` int(10) NOT NULL,
  `level_id` int(10) NOT NULL,
  KEY `entry_id` (`entry_id`),
  KEY `category_id` (`level_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_mediadir_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_mediadir_settings_num_categories` (
  `group_id` int(1) NOT NULL,
  `num_categories` varchar(10) NOT NULL
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_mediadir_settings_num_entries` (
  `group_id` int(1) NOT NULL,
  `num_entries` varchar(10) NOT NULL DEFAULT 'n'
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_mediadir_settings_num_levels` (
  `group_id` int(1) NOT NULL,
  `num_levels` varchar(10) NOT NULL
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_mediadir_settings_perm_group_forms` (
  `group_id` int(7) NOT NULL,
  `form_id` int(1) NOT NULL,
  `status_group` int(1) NOT NULL
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_mediadir_votes` (
  `id` int(7) NOT NULL AUTO_INCREMENT,
  `entry_id` int(7) NOT NULL,
  `added_by` varchar(255) NOT NULL,
  `date` varchar(100) NOT NULL,
  `ip` varchar(100) NOT NULL,
  `vote` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_memberdir_directories` (
  `dirid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parentdir` int(11) NOT NULL DEFAULT '0',
  `active` set('1','0') NOT NULL DEFAULT '1',
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `displaymode` set('0','1','2') NOT NULL DEFAULT '0',
  `sort` int(11) NOT NULL DEFAULT '1',
  `pic1` set('1','0') NOT NULL DEFAULT '0',
  `pic2` set('1','0') NOT NULL DEFAULT '0',
  `lang_id` int(2) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`dirid`),
  FULLTEXT KEY `memberdir_dir` (`name`,`description`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_memberdir_name` (
  `field` int(10) unsigned NOT NULL DEFAULT '0',
  `dirid` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `active` set('0','1') NOT NULL DEFAULT '',
  `lang_id` int(2) unsigned NOT NULL DEFAULT '1'
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_memberdir_settings` (
  `setid` int(4) unsigned NOT NULL AUTO_INCREMENT,
  `setname` varchar(255) NOT NULL DEFAULT '',
  `setvalue` text NOT NULL,
  `lang_id` int(2) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`setid`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_memberdir_values` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dirid` int(14) NOT NULL DEFAULT '0',
  `pic1` varchar(255) NOT NULL DEFAULT '',
  `pic2` varchar(255) NOT NULL DEFAULT '',
  `0` smallint(5) unsigned NOT NULL DEFAULT '0',
  `1` text NOT NULL,
  `2` text NOT NULL,
  `3` text NOT NULL,
  `4` text NOT NULL,
  `5` text NOT NULL,
  `6` text NOT NULL,
  `7` text NOT NULL,
  `8` text NOT NULL,
  `9` text NOT NULL,
  `10` text NOT NULL,
  `11` text NOT NULL,
  `12` text NOT NULL,
  `13` text NOT NULL,
  `14` text NOT NULL,
  `15` text NOT NULL,
  `16` text NOT NULL,
  `17` text NOT NULL,
  `18` text NOT NULL,
  `lang_id` int(2) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_news` (
  `id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `date` int(14) DEFAULT NULL,
  `redirect` varchar(250) NOT NULL DEFAULT '',
  `source` varchar(250) NOT NULL DEFAULT '',
  `url1` varchar(250) NOT NULL DEFAULT '',
  `url2` varchar(250) NOT NULL DEFAULT '',
  `typeid` int(2) unsigned NOT NULL DEFAULT '0',
  `publisher` varchar(255) NOT NULL DEFAULT '',
  `publisher_id` int(5) unsigned NOT NULL DEFAULT '0',
  `author` varchar(255) NOT NULL DEFAULT '',
  `author_id` int(5) unsigned NOT NULL DEFAULT '0',
  `userid` int(6) unsigned NOT NULL DEFAULT '0',
  `startdate` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `enddate` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  `validated` enum('0','1') NOT NULL DEFAULT '0',
  `frontend_access_id` int(10) unsigned NOT NULL DEFAULT '0',
  `backend_access_id` int(10) unsigned NOT NULL DEFAULT '0',
  `teaser_only` enum('0','1') NOT NULL DEFAULT '0',
  `teaser_frames` text NOT NULL,
  `teaser_show_link` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `teaser_image_path` text NOT NULL,
  `teaser_image_thumbnail_path` text NOT NULL,
  `changelog` int(14) NOT NULL DEFAULT '0',
  `allow_comments` tinyint(1) NOT NULL DEFAULT '0',
  `enable_related_news` tinyint(1) NOT NULL DEFAULT '0',
  `enable_tags` tinyint(1) NOT NULL DEFAULT '0',
  `redirect_new_window` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_news_categories` (
  `catid` int(2) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `left_id` int(11) NOT NULL,
  `right_id` int(11) NOT NULL,
  `sorting` int(11) NOT NULL,
  `level` int(11) NOT NULL,
  `display` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`catid`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_news_categories_catid` (
  `id` int(11) NOT NULL
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_news_categories_locale` (
  `category_id` int(11) unsigned NOT NULL DEFAULT '0',
  `lang_id` int(11) unsigned NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`category_id`,`lang_id`),
  FULLTEXT KEY `name` (`name`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_news_categories_locks` (
  `lockId` varchar(32) NOT NULL,
  `lockTable` varchar(32) NOT NULL,
  `lockStamp` bigint(11) NOT NULL
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_news_comments` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(250) NOT NULL DEFAULT '',
  `text` mediumtext NOT NULL,
  `newsid` int(6) unsigned NOT NULL DEFAULT '0',
  `date` int(14) DEFAULT NULL,
  `poster_name` varchar(255) NOT NULL DEFAULT '',
  `userid` int(5) unsigned NOT NULL DEFAULT '0',
  `ip_address` varchar(32) NOT NULL DEFAULT '',
  `is_active` enum('0','1') NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_news_locale` (
  `news_id` int(11) unsigned NOT NULL DEFAULT '0',
  `lang_id` int(11) unsigned NOT NULL DEFAULT '0',
  `is_active` int(1) unsigned NOT NULL DEFAULT '1',
  `title` varchar(250) NOT NULL DEFAULT '',
  `text` mediumtext NOT NULL,
  `teaser_text` text NOT NULL,
  PRIMARY KEY (`news_id`,`lang_id`),
  FULLTEXT KEY `newsindex` (`text`,`title`,`teaser_text`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_news_rel_categories` (
  `news_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  UNIQUE KEY `NewsTagsRelation` (`news_id`,`category_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_news_rel_news` (
  `news_id` int(11) NOT NULL,
  `related_news_id` int(11) NOT NULL,
  UNIQUE KEY `related_news` (`news_id`,`related_news_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_news_rel_tags` (
  `news_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  UNIQUE KEY `NewsTagsRelation` (`news_id`,`tag_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_news_settings` (
  `name` varchar(50) NOT NULL DEFAULT '',
  `value` varchar(250) NOT NULL DEFAULT '',
  UNIQUE KEY `name` (`name`) USING BTREE
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_news_settings_locale` (
  `name` varchar(50) NOT NULL DEFAULT '',
  `lang_id` int(11) unsigned NOT NULL DEFAULT '0',
  `value` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`name`,`lang_id`),
  FULLTEXT KEY `name` (`name`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_news_stats_view` (
  `user_sid` char(32) NOT NULL,
  `news_id` int(6) unsigned NOT NULL,
  `time` timestamp NOT NULL,
  KEY `idx_user_sid` (`user_sid`),
  KEY `idx_news_id` (`news_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_news_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tag` varchar(255) binary NOT NULL,
  `viewed_count` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tag` (`tag`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_news_teaser_frame` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lang_id` int(3) unsigned NOT NULL DEFAULT '0',
  `frame_template_id` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_news_teaser_frame_templates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `description` varchar(100) NOT NULL DEFAULT '',
  `html` text NOT NULL,
  `source_code_mode` enum('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_news_ticker` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `charset` enum('ISO-8859-1','UTF-8') NOT NULL DEFAULT 'ISO-8859-1',
  `urlencode` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `prefix` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_news_types` (
  `typeid` int(2) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`typeid`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_news_types_locale` (
  `lang_id` int(11) unsigned NOT NULL DEFAULT '0',
  `type_id` int(11) unsigned NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`lang_id`,`type_id`),
  FULLTEXT KEY `name` (`name`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_newsletter` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject` varchar(255) NOT NULL DEFAULT '',
  `template` int(11) NOT NULL DEFAULT '0',
  `content` mediumtext NOT NULL,
  `attachment` enum('0','1') NOT NULL DEFAULT '0',
  `priority` tinyint(1) NOT NULL DEFAULT '0',
  `sender_email` varchar(255) NOT NULL DEFAULT '',
  `sender_name` varchar(255) NOT NULL DEFAULT '',
  `return_path` varchar(255) NOT NULL DEFAULT '',
  `smtp_server` int(10) unsigned NOT NULL DEFAULT '0',
  `status` int(1) NOT NULL DEFAULT '0',
  `count` int(11) NOT NULL DEFAULT '0',
  `recipient_count` int(11) unsigned NOT NULL DEFAULT '0',
  `date_create` int(14) unsigned NOT NULL DEFAULT '0',
  `date_sent` int(14) unsigned NOT NULL DEFAULT '0',
  `tmp_copy` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_newsletter_access_user` (
  `accessUserID` int(5) unsigned NOT NULL,
  `newsletterCategoryID` int(11) NOT NULL,
  `code` varchar(255) NOT NULL DEFAULT '',
  `source` enum('backend','opt-in','api') NOT NULL DEFAULT 'backend',
  `consent` timestamp NULL DEFAULT NULL,
  UNIQUE KEY `rel` (`accessUserID`,`newsletterCategoryID`),
  KEY `accessUserID` (`accessUserID`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_newsletter_attachment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `newsletter` int(11) NOT NULL DEFAULT '0',
  `file_name` varchar(255) NOT NULL DEFAULT '',
  `file_nr` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `newsletter` (`newsletter`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_newsletter_category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `notification_email` varchar(250) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `name` (`name`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_newsletter_email_link` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email_id` int(11) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `email_id` (`email_id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_newsletter_email_link_feedback` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `link_id` int(11) unsigned NOT NULL,
  `email_id` int(11) unsigned NOT NULL,
  `recipient_id` int(11) unsigned NOT NULL,
  `recipient_type` enum('access','newsletter','crm') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `link_id` (`link_id`,`email_id`,`recipient_id`,`recipient_type`),
  KEY `email_id` (`email_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_newsletter_rel_cat_news` (
  `newsletter` int(11) NOT NULL DEFAULT '0',
  `category` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`newsletter`,`category`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_newsletter_rel_crm_membership_newsletter` (
  `membership_id` int(10) unsigned NOT NULL,
  `newsletter_id` int(10) unsigned NOT NULL,
  `type` enum('associate', 'include', 'exclude') NOT NULL,
  UNIQUE KEY `uniq` (`membership_id`,`newsletter_id`,`type`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_newsletter_rel_user_cat` (
  `user` int(11) NOT NULL DEFAULT '0',
  `category` int(11) NOT NULL DEFAULT '0',
  `source` enum('backend','opt-in','api') NOT NULL DEFAULT 'backend',
  `consent` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user`,`category`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_newsletter_rel_usergroup_newsletter` (
  `userGroup` int(10) unsigned NOT NULL,
  `newsletter` int(10) unsigned NOT NULL,
  UNIQUE KEY `uniq` (`userGroup`,`newsletter`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_newsletter_settings` (
  `setid` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `setname` varchar(250) NOT NULL DEFAULT '',
  `setvalue` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`setid`),
  UNIQUE KEY `setname` (`setname`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_newsletter_template` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `html` text NOT NULL,
  `required` int(1) NOT NULL DEFAULT '0',
  `type` enum('e-mail','news') NOT NULL DEFAULT 'e-mail',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_newsletter_tmp_sending` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `newsletter` int(11) NOT NULL DEFAULT '0',
  `email` varchar(255) NOT NULL DEFAULT '',
  `sendt` tinyint(1) NOT NULL DEFAULT '0',
  `type` enum('access','newsletter','core','crm') NOT NULL DEFAULT 'newsletter',
  `code` varchar(10) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`newsletter`,`email`),
  KEY `email` (`email`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_newsletter_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `uri` varchar(255) NOT NULL DEFAULT '',
  `sex` enum('m','f') DEFAULT NULL,
  `salutation` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(255) NOT NULL DEFAULT '',
  `lastname` varchar(255) NOT NULL DEFAULT '',
  `firstname` varchar(255) NOT NULL DEFAULT '',
  `position` varchar(255) NOT NULL DEFAULT '',
  `company` varchar(255) NOT NULL DEFAULT '',
  `industry_sector` varchar(255) NOT NULL DEFAULT '',
  `address` varchar(255) NOT NULL DEFAULT '',
  `zip` varchar(255) NOT NULL DEFAULT '',
  `city` varchar(255) NOT NULL DEFAULT '',
  `country_id` smallint(5) unsigned NOT NULL DEFAULT '0',
  `phone_office` varchar(255) NOT NULL DEFAULT '',
  `phone_private` varchar(255) NOT NULL DEFAULT '',
  `phone_mobile` varchar(255) NOT NULL DEFAULT '',
  `fax` varchar(255) NOT NULL DEFAULT '',
  `notes` text NOT NULL,
  `birthday` varchar(10) NOT NULL DEFAULT '00-00-0000',
  `status` int(1) NOT NULL DEFAULT '0',
  `emaildate` int(14) unsigned NOT NULL DEFAULT '0',
  `language` int(3) unsigned NOT NULL DEFAULT '0',
  `source` enum('backend','opt-in','api') NOT NULL DEFAULT 'backend',
  `consent` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `status` (`status`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_newsletter_user_title` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `title` (`title`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_order_invoice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `contrexx_module_order_invoice_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `contrexx_module_order_order` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_order_invoice_item` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `price` decimal(10,0) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `contrexx_module_order_invoice_item_invoice_id_ibfk` (`invoice_id`),
  CONSTRAINT `contrexx_module_order_invoice_item_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `contrexx_module_order_invoice` (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_order_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contact_id` int(10) unsigned NOT NULL,
  `currency_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `currency_id` (`currency_id`),
  CONSTRAINT `contrexx_module_order_order_ibfk_1` FOREIGN KEY (`currency_id`) REFERENCES `contrexx_module_crm_currency` (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_order_payment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL,
  `amount` decimal(10,0) NOT NULL,
  `transaction_reference` varchar(255) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `handler` varchar(12) NOT NULL,
  `transaction_data` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_id` (`invoice_id`),
  CONSTRAINT `contrexx_module_order_payment_ibfk_2` FOREIGN KEY (`invoice_id`) REFERENCES `contrexx_module_order_invoice` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_order_subscription` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `subscription_date` timestamp NULL DEFAULT NULL,
  `expiration_date` timestamp NULL DEFAULT NULL,
  `product_entity_id` int(11) DEFAULT NULL,
  `payment_amount` decimal(10,2) NOT NULL,
  `payment_state` varchar(12) NOT NULL,
  `renewal_unit` varchar(5) DEFAULT NULL,
  `renewal_quantifier` int(10) unsigned DEFAULT NULL,
  `renewal_date` timestamp NULL DEFAULT NULL,
  `external_subscription_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `state` varchar(12) DEFAULT NULL,
  `termination_date` datetime DEFAULT NULL,
  `note` text,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `contrexx_module_order_subscription_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `contrexx_module_order_order` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION,
  CONSTRAINT `contrexx_module_order_subscription_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `contrexx_module_pim_product` (`id`) ON DELETE SET NULL ON UPDATE NO ACTION
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_pim_price` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `currency_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `amount` decimal(10,0) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `currency_id` (`currency_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `contrexx_module_pim_price_ibfk_1` FOREIGN KEY (`currency_id`) REFERENCES `contrexx_module_crm_currency` (`id`),
  CONSTRAINT `contrexx_module_pim_price_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `contrexx_module_pim_product` (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_pim_product` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `entity_class` varchar(255) NOT NULL,
  `entity_attributes` text NOT NULL,
  `renewable` tinyint(1) NOT NULL,
  `expirable` tinyint(1) NOT NULL,
  `upgradable` tinyint(1) NOT NULL,
  `expiration_unit` varchar(5) NOT NULL,
  `expiration_quantifier` int(11) NOT NULL,
  `note_entity` text NOT NULL,
  `note_renewal` text NOT NULL,
  `note_upgrade` text NOT NULL,
  `note_expiration` text NOT NULL,
  `note_price` text NOT NULL,
  `cancellation_unit` varchar(5) NOT NULL,
  `cancellation_quantifier` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_pim_product_upgrade` (
  `product_id` int(11) NOT NULL,
  `upgrade_product_id` int(11) NOT NULL,
  PRIMARY KEY (`product_id`,`upgrade_product_id`),
  KEY `contrexx_module_pim_product_upgrade_product_id_ibfk` (`product_id`),
  KEY `contrexx_module_pim_product_upgrade_upgrade_product_id_ibfk` (`upgrade_product_id`),
  CONSTRAINT `contrexx_module_pim_product_upgrade_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `contrexx_module_pim_product` (`id`),
  CONSTRAINT `contrexx_module_pim_product_upgrade_ibfk_2` FOREIGN KEY (`upgrade_product_id`) REFERENCES `contrexx_module_pim_product` (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_podcast_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `podcastindex` (`title`,`description`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_podcast_medium` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `youtube_id` varchar(25) NOT NULL DEFAULT '',
  `author` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `source` text NOT NULL,
  `thumbnail` varchar(255) NOT NULL DEFAULT '',
  `template_id` int(11) unsigned NOT NULL DEFAULT '0',
  `width` int(10) unsigned NOT NULL DEFAULT '0',
  `height` int(10) unsigned NOT NULL DEFAULT '0',
  `playlenght` int(10) unsigned NOT NULL DEFAULT '0',
  `size` int(10) unsigned NOT NULL DEFAULT '0',
  `views` int(10) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `date_added` int(14) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  FULLTEXT KEY `podcastindex` (`title`,`description`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_podcast_rel_category_lang` (
  `category_id` int(10) unsigned NOT NULL DEFAULT '0',
  `lang_id` int(10) unsigned NOT NULL DEFAULT '0'
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_podcast_rel_medium_category` (
  `medium_id` int(10) unsigned NOT NULL DEFAULT '0',
  `category_id` int(10) unsigned NOT NULL DEFAULT '0'
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_podcast_settings` (
  `setid` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `setname` varchar(250) NOT NULL DEFAULT '',
  `setvalue` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`setid`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_podcast_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `description` varchar(255) NOT NULL DEFAULT '',
  `template` text NOT NULL,
  `extensions` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `description` (`description`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_recommend` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `value` text NOT NULL,
  `lang_id` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_repository` (
  `id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `moduleid` int(5) unsigned NOT NULL DEFAULT '0',
  `content` mediumtext NOT NULL,
  `title` varchar(250) NOT NULL DEFAULT '',
  `cmd` varchar(20) NOT NULL DEFAULT '',
  `expertmode` set('y','n') NOT NULL DEFAULT 'n',
  `parid` int(5) unsigned NOT NULL DEFAULT '0',
  `displaystatus` set('on','off') NOT NULL DEFAULT 'on',
  `username` varchar(250) NOT NULL DEFAULT '',
  `displayorder` smallint(6) NOT NULL DEFAULT '100',
  UNIQUE KEY `contentid` (`id`),
  FULLTEXT KEY `fulltextindex` (`title`,`content`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_shop_article_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT '' NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_shop_attribute` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` int(1) unsigned NOT NULL DEFAULT 1,
  `name` varchar(255) DEFAULT '' NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_shop_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `ord` int(5) unsigned NOT NULL DEFAULT '0',
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `picture` varchar(255) NOT NULL DEFAULT '',
  `flags` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) DEFAULT '' NOT NULL,
  `description` text NOT NULL,
  `short_description` text NOT NULL,
  PRIMARY KEY (`id`),
  FULLTEXT KEY `flags` (`flags`),
  INDEX `IDX_A9242624727ACA70` (`parent_id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_shop_currencies` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` char(3) NOT NULL DEFAULT '',
  `symbol` varchar(20) NOT NULL DEFAULT '',
  `rate` decimal(10,4) unsigned NOT NULL DEFAULT '1.0000',
  `ord` int(5) unsigned NOT NULL DEFAULT '0',
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `default` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `increment` decimal(6,5) unsigned NOT NULL DEFAULT '0.01000',
  `name` varchar(255) DEFAULT '' NOT NULL,
  UNIQUE INDEX `fk_module_shop_currency_idx` (`code`),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_shop_customer_group` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT '' NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_shop_discount_coupon` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL DEFAULT '',
  `customer_id` int(10) DEFAULT NULL,
  `payment_id` int(10) unsigned DEFAULT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `start_time` int(10) unsigned NOT NULL DEFAULT '0',
  `end_time` int(10) unsigned NOT NULL DEFAULT '0',
  `uses` int(10) unsigned NOT NULL DEFAULT '0',
  `global` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `minimum_amount` decimal(9,2) unsigned NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(9,2) unsigned NOT NULL DEFAULT '0.00',
  `discount_rate` decimal(3,0) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  INDEX `IDX_7E70AB1A4C3A3BB` (`payment_id`),
  INDEX `IDX_7E70AB1A4584665A` (`product_id`),
  INDEX `IDX_7E70AB1A9395C3F3` (`customer_id`),
  UNIQUE INDEX `fk_module_shop_discount_coupon_unique_idx` (`code`, `customer_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_shop_discountgroup_count_name` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cumulative` int(1) unsigned NOT NULL DEFAULT '0',
  `unit` varchar(255) DEFAULT '' NOT NULL,
  `name` varchar(255) DEFAULT '' NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_shop_discountgroup_count_rate` (
  `group_id` int(10) unsigned NOT NULL DEFAULT '0',
  `count` int(10) unsigned NOT NULL DEFAULT '1',
  `rate` decimal(5,2) unsigned NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`group_id`,`count`),
  INDEX `IDX_3F3DD477FE54D947` (`group_id`),
  INDEX `fk_contrexx_module_shop_discountgroup_count_rate_contrexx_m_idx` (`count`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_shop_importimg` (
  `img_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `img_name` varchar(255) NOT NULL DEFAULT '',
  `img_cats` text NOT NULL,
  `img_fields_file` text NOT NULL,
  `img_fields_db` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`img_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_shop_lsv` (
  `order_id` int(10) unsigned NOT NULL,
  `holder` tinytext NOT NULL,
  `bank` tinytext NOT NULL,
  `blz` tinytext NOT NULL,
  PRIMARY KEY (`order_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_shop_manufacturer` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uri` varchar(255) DEFAULT '' NOT NULL,
  `name` varchar(255) DEFAULT '' NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_shop_option` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `attribute_id` int(10) unsigned NOT NULL,
  `price` decimal(9,2) NOT NULL DEFAULT '0.00',
  `name` varchar(255) DEFAULT '' NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `IDX_658196EFB6E62EFA` (`attribute_id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_shop_order_attributes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item_id` int(10) unsigned NOT NULL DEFAULT '0',
  `attribute_name` varchar(255) NOT NULL DEFAULT '',
  `option_name` TEXT NOT NULL DEFAULT '',
  `price` decimal(9,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_shop_order_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL DEFAULT '0',
  `product_id` int(10) unsigned DEFAULT NULL,
  `product_name` varchar(255) NOT NULL DEFAULT '',
  `price` decimal(9,2) unsigned NOT NULL DEFAULT '0.00',
  `quantity` int(10) unsigned NOT NULL DEFAULT '0',
  `vat_rate` decimal(5,2) unsigned DEFAULT NULL,
  `weight` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `order` (`order_id`),
  INDEX `IDX_1D79476B4584665A` (`product_id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_shop_orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) DEFAULT NULL,
  `currency_id` int(10) unsigned NOT NULL DEFAULT '0',
  `sum` decimal(9,2) unsigned NOT NULL DEFAULT '0.00',
  `date_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `status` int(1) unsigned NOT NULL DEFAULT '0',
  `gender` varchar(50) DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `firstname` varchar(40) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `address` varchar(40) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `zip` varchar(10) DEFAULT NULL,
  `country_id` int(10) unsigned DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `vat_amount` decimal(9,2) unsigned NOT NULL DEFAULT '0.00',
  `shipment_amount` decimal(9,2) unsigned NOT NULL DEFAULT '0.00',
  `shipment_id` int(10) unsigned DEFAULT NULL,
  `payment_id` int(10) unsigned DEFAULT NULL,
  `payment_amount` decimal(9,2) unsigned NOT NULL DEFAULT '0.00',
  `ip` varchar(50) NOT NULL DEFAULT '',
  `lang_id` int(10) NOT NULL DEFAULT '0',
  `note` text NOT NULL,
  `modified_on` datetime NULL DEFAULT NULL,
  `modified_by` varchar(50) DEFAULT NULL,
  `billing_gender` varchar(50) DEFAULT NULL,
  `billing_company` varchar(100) DEFAULT NULL,
  `billing_firstname` varchar(40) DEFAULT NULL,
  `billing_lastname` varchar(100) DEFAULT NULL,
  `billing_address` varchar(40) DEFAULT NULL,
  `billing_city` varchar(50) DEFAULT NULL,
  `billing_zip` varchar(10) DEFAULT NULL,
  `billing_country_id` int(10) unsigned DEFAULT NULL,
  `billing_phone` varchar(20) DEFAULT NULL,
  `billing_fax` varchar(20) DEFAULT NULL,
  `billing_email` varchar(255) DEFAULT NULL,
  INDEX `IDX_DA286BB1B213FA4` (`lang_id`),
  INDEX `IDX_DA286BB138248176` (`currency_id`),
  INDEX `IDX_DA286BB17BE036FC` (`shipment_id`),
  INDEX `IDX_DA286BB14C3A3BB` (`payment_id`),
  INDEX `IDX_DA286BB19395C3F3` (`customer_id`),
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_shop_payment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `processor_id` int(10) unsigned NOT NULL DEFAULT '0',
  `fee` decimal(9,2) unsigned NOT NULL DEFAULT '0.00',
  `free_from` decimal(9,2) unsigned NOT NULL DEFAULT '0.00',
  `ord` int(5) unsigned NOT NULL DEFAULT '0',
  `type` enum('fix','percent') NOT NULL DEFAULT 'fix',
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `name` VARCHAR(255) DEFAULT '' NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `IDX_96C3CFFE37BAC19A` (`processor_id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_shop_payment_processors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('internal','external') NOT NULL DEFAULT 'internal',
  `name` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `company_url` varchar(255) NOT NULL DEFAULT '',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `picture` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_shop_pricelists` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(25) NOT NULL DEFAULT '',
  `lang_id` int(10) NOT NULL DEFAULT '0',
  `border_on` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `header_on` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `header_left` text,
  `header_right` text,
  `footer_on` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `footer_left` text,
  `footer_right` text,
  `all_categories` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  INDEX `IDX_BB867D48B213FA4` (`lang_id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_shop_products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `picture` varchar(4096) DEFAULT NULL,
  `distribution` varchar(16) NOT NULL DEFAULT '',
  `normalprice` decimal(9,2) NOT NULL DEFAULT '0.00',
  `resellerprice` decimal(9,2) NOT NULL DEFAULT '0.00',
  `stock` int(10) NOT NULL DEFAULT '10',
  `stock_visible` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `discountprice` decimal(9,2) NOT NULL DEFAULT '0.00',
  `discount_active` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `b2b` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `b2c` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `date_start` datetime DEFAULT NULL,
  `date_end` datetime DEFAULT NULL,
  `manufacturer_id` int(10) unsigned DEFAULT NULL,
  `ord` int(10) NOT NULL DEFAULT '0',
  `vat_id` int(10) unsigned DEFAULT NULL,
  `weight` int(10) unsigned DEFAULT NULL,
  `flags` varchar(4096) DEFAULT NULL,
  `group_id` int(10) unsigned DEFAULT NULL,
  `article_id` int(10) unsigned DEFAULT NULL,
  `minimum_order_quantity` int(10) NOT NULL DEFAULT '0',
  `uri` varchar(255) DEFAULT '' NOT NULL,
  `short` text NOT NULL,
  `long`  text NOT NULL,
  `name` VARCHAR(255) DEFAULT '' NOT NULL,
  `keys`  text NOT NULL,
  `code` VARCHAR(255) DEFAULT '' NOT NULL,
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`),
  KEY `article_id` (`article_id`),
  FULLTEXT KEY `flags` (`flags`),
  INDEX `IDX_97F512B7A23B42D` (`manufacturer_id`),
  INDEX `IDX_97F512B7B5B63A6B` (`vat_id`)
) ENGINE=InnoDB ;
CREATE TABLE contrexx_module_shop_rel_category_pricelist (
  category_id INT UNSIGNED NOT NULL,
  pricelist_id INT UNSIGNED NOT NULL,
  PRIMARY KEY(category_id, pricelist_id),
  INDEX `IDX_B56E91A112469DE2` (`category_id`),
  INDEX `IDX_B56E91A189045958` (`pricelist_id`)
) ENGINE = InnoDB;
CREATE TABLE contrexx_module_shop_rel_category_product (
  category_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  PRIMARY KEY(category_id, product_id),
  INDEX `IDX_DA4CA51112469DE2` (`category_id`),
  INDEX `IDX_DA4CA5114584665A` (`product_id`)
) ENGINE = InnoDB;
CREATE TABLE contrexx_module_shop_rel_product_user_group (
  product_id INT UNSIGNED NOT NULL,
  usergroup_id INT NOT NULL,
  PRIMARY KEY(product_id, usergroup_id),
  INDEX `IDX_32A4494A4584665A` (`product_id`),
  INDEX `IDX_32A4494AD2112630` (`usergroup_id`)
) ENGINE = InnoDB;
CREATE TABLE `contrexx_module_shop_rel_countries` (
  `zone_id` int(10) unsigned NOT NULL DEFAULT '0',
  `country_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`zone_id`, `country_id`),
  INDEX `IDX_C859EA8B9F2C3FAB` (`zone_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_shop_rel_customer_coupon` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL DEFAULT '',
  `customer_id` int(10) DEFAULT NULL,
  `order_id` int(10) unsigned DEFAULT NULL,
  `count` int(10) unsigned NOT NULL DEFAULT '0',
  `amount` decimal(9,2) unsigned NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  INDEX `IDX_6A7FBE248D9F6D38` (`order_id`),
  INDEX `IDX_6A7FBE249395C3F3` (`customer_id`),
  UNIQUE INDEX `fk_module_shop_rel_customer_coupon_unique_idx` (`code`, `customer_id`, `order_id`)
) ENGINE=InnoDB;

CREATE TABLE `contrexx_module_shop_rel_discount_group` (
  `customer_group_id` int(10) unsigned NOT NULL DEFAULT '0',
  `article_group_id` int(10) unsigned NOT NULL DEFAULT '0',
  `rate` decimal(9,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`customer_group_id`,`article_group_id`),
  INDEX `IDX_93D6FD61D2919A68` (`customer_group_id`),
  INDEX `IDX_93D6FD61ABBC2D2C` (`article_group_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_shop_rel_payment` (
  `zone_id` int(10) unsigned NOT NULL,
  `payment_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`zone_id`,`payment_id`),
  INDEX `IDX_43EB87989F2C3FAB` (`zone_id`),
  INDEX `IDX_43EB87984C3A3BB` (`payment_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_shop_rel_product_attribute` (
  `product_id` int(10) unsigned NOT NULL DEFAULT '0',
  `option_id` int(10) unsigned NOT NULL,
  `ord` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`product_id`,`option_id`),
  INDEX `IDX_E17E240B4584665A` (`product_id`),
  INDEX `IDX_E17E240BA7C41D6F` (`option_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_shop_shipment_cost` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shipper_id` int(10) unsigned NOT NULL DEFAULT '0',
  `max_weight` int(10) unsigned DEFAULT NULL,
  `fee` decimal(9,2) unsigned DEFAULT NULL,
  `free_from` decimal(9,2) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `IDX_2329A4538459F23` (`shipper_id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_shop_shipper` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `ord` int(10) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) DEFAULT '' NOT NULL,
  `zone_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `IDX_52CD810E9F2C3FAB` (`zone_id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_shop_vat` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rate` decimal(5,2) unsigned NOT NULL DEFAULT '0.00',
  `class` varchar(255) DEFAULT '' NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_shop_zones` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `active` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `name` varchar(255) DEFAULT '' NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_survey_addtionalfields` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `survey_id` varchar(10) NOT NULL,
  `salutation` varchar(400) NOT NULL,
  `nickname` varchar(400) NOT NULL,
  `forename` varchar(400) NOT NULL,
  `surname` varchar(400) NOT NULL,
  `agegroup` varchar(400) NOT NULL,
  `phone` varchar(400) NOT NULL,
  `street` varchar(400) NOT NULL,
  `zip` varchar(400) NOT NULL,
  `email` varchar(400) NOT NULL,
  `city` varchar(400) NOT NULL,
  `added_date` timestamp NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_survey_columnChoices` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `question_id` int(10) NOT NULL,
  `choice` varchar(400) NOT NULL,
  `votes` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_survey_email` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `survey_id` int(10) NOT NULL,
  `email` varchar(400) NOT NULL,
  `voted` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_survey_poll_result` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `survey_id` int(10) NOT NULL,
  `question_id` int(10) NOT NULL,
  `user_id` int(10) NOT NULL,
  `comment` text NOT NULL,
  `answers` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_survey_settings` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `salutation` text NOT NULL,
  `agegroup` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_survey_surveyAnswers` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `question_id` int(10) NOT NULL,
  `answer` varchar(400) NOT NULL,
  `votes` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_survey_surveyQuestions` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `survey_id` int(10) NOT NULL,
  `created` timestamp NOT NULL,
  `isActive` int(2) NOT NULL DEFAULT '1',
  `isCommentable` int(2) NOT NULL DEFAULT '0',
  `QuestionType` int(10) NOT NULL,
  `Question` varchar(1000) NOT NULL,
  `pos` int(10) NOT NULL DEFAULT '0',
  `votes` int(10) NOT NULL DEFAULT '0',
  `skipped` int(10) NOT NULL DEFAULT '0',
  `column_choice` varchar(400) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_survey_surveygroup` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `title` varchar(400) NOT NULL,
  `UserRestriction` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `isActive` int(2) NOT NULL DEFAULT '1',
  `isHomeBox` int(2) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL,
  `updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `votes` int(10) NOT NULL DEFAULT '0',
  `additional_salutation` tinyint(1) NOT NULL,
  `additional_nickname` tinyint(1) NOT NULL,
  `additional_forename` tinyint(1) NOT NULL,
  `additional_surname` tinyint(1) NOT NULL,
  `additional_agegroup` tinyint(1) NOT NULL,
  `additional_phone` tinyint(1) NOT NULL,
  `additional_street` tinyint(1) NOT NULL,
  `additional_zip` tinyint(1) NOT NULL,
  `additional_email` tinyint(1) NOT NULL,
  `additional_city` tinyint(1) NOT NULL,
  `textAfterButton` text NOT NULL,
  `text1` text NOT NULL,
  `text2` text NOT NULL,
  `thanksMSG` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_u2u_address_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `buddies_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_u2u_message_log` (
  `message_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `message_text` text NOT NULL,
  `message_title` text NOT NULL,
  PRIMARY KEY (`message_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_u2u_sent_messages` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL DEFAULT '0',
  `message_id` int(11) unsigned NOT NULL DEFAULT '0',
  `receiver_id` int(11) unsigned NOT NULL DEFAULT '0',
  `mesage_open_status` enum('0','1') NOT NULL DEFAULT '0',
  `date_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_module_u2u_settings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_module_u2u_user_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(11) unsigned NOT NULL DEFAULT '0',
  `user_sent_items` int(11) unsigned NOT NULL DEFAULT '0',
  `user_unread_items` int(11) unsigned NOT NULL DEFAULT '0',
  `user_status` enum('0','1') NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_modules` (
  `id` int(2) unsigned DEFAULT NULL,
  `name` varchar(250) NOT NULL DEFAULT '',
  `distributor` char(50) NOT NULL,
  `description_variable` varchar(50) NOT NULL DEFAULT '',
  `status` set('y','n') NOT NULL DEFAULT 'n',
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `is_core` tinyint(4) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `is_licensed` tinyint(1) NOT NULL,
  `additional_data` text,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_session_variable` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL,
  `sessionid` varchar(32) NOT NULL DEFAULT '',
  `lastused` timestamp NOT NULL,
  `key` varchar(100) NOT NULL DEFAULT '',
  `value` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key_index` (`parent_id`,`key`,`sessionid`),
  KEY `key_parent_id_sessionid` (`parent_id`,`sessionid`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_sessions` (
  `sessionid` varchar(255) NOT NULL DEFAULT '',
  `remember_me` int(1) NOT NULL DEFAULT '0',
  `startdate` varchar(14) NOT NULL DEFAULT '',
  `lastupdated` varchar(14) NOT NULL DEFAULT '',
  `status` varchar(20) NOT NULL DEFAULT '',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`sessionid`),
  KEY `LastUpdated` (`lastupdated`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_settings_image` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_settings_smtp` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `hostname` varchar(255) NOT NULL,
  `port` smallint(5) unsigned NOT NULL DEFAULT '25',
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_settings_thumbnail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `size` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_skins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `themesname` varchar(50) NOT NULL DEFAULT '',
  `foldername` varchar(50) NOT NULL DEFAULT '',
  `expert` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `theme_unique` (`themesname`),
  UNIQUE KEY `folder_unique` (`foldername`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_stats_browser` (
  `id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) binary NOT NULL DEFAULT '',
  `count` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`name`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_stats_colourdepth` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `depth` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `count` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`depth`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_stats_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT '',
  `value` varchar(255) NOT NULL DEFAULT '',
  `status` int(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_stats_country` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `country` varchar(100) binary NOT NULL DEFAULT '',
  `count` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`country`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_stats_hostname` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `hostname` varchar(255) binary NOT NULL DEFAULT '',
  `count` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`hostname`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_stats_javascript` (
  `id` int(3) unsigned NOT NULL AUTO_INCREMENT,
  `support` enum('0','1') DEFAULT '0',
  `count` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_stats_operatingsystem` (
  `id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) binary NOT NULL DEFAULT '',
  `count` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`name`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_stats_referer` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `uri` varchar(255) binary NOT NULL DEFAULT '',
  `timestamp` int(11) unsigned NOT NULL DEFAULT '0',
  `count` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `sid` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`uri`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_stats_requests` (
  `id` int(9) unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` int(11) DEFAULT '0',
  `pageId` int(6) unsigned NOT NULL DEFAULT '0',
  `page` varchar(255) binary NOT NULL DEFAULT '',
  `visits` int(9) unsigned NOT NULL DEFAULT '0',
  `sid` varchar(32) NOT NULL DEFAULT '',
  `pageTitle` varchar(250) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pageId` (`pageId`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_stats_requests_summary` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(10) NOT NULL DEFAULT '',
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `count` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`type`,`timestamp`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_stats_screenresolution` (
  `id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `resolution` varchar(11) NOT NULL DEFAULT '',
  `count` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`resolution`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_stats_search` (
  `id` int(5) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) binary NOT NULL DEFAULT '',
  `count` int(10) unsigned NOT NULL DEFAULT '0',
  `sid` varchar(32) NOT NULL DEFAULT '',
  `external` enum('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`name`,`external`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_stats_spiders` (
  `id` int(9) unsigned NOT NULL AUTO_INCREMENT,
  `last_indexed` int(14) DEFAULT NULL,
  `page` varchar(100) binary DEFAULT NULL,
  `pageId` mediumint(6) unsigned NOT NULL DEFAULT '0',
  `count` int(11) NOT NULL DEFAULT '0',
  `spider_useragent` varchar(255) DEFAULT NULL,
  `spider_ip` varchar(100) DEFAULT NULL,
  `spider_host` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`page`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_stats_spiders_summary` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) binary NOT NULL DEFAULT '',
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `count` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`name`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_stats_visitors` (
  `id` int(8) unsigned NOT NULL AUTO_INCREMENT,
  `sid` varchar(32) NOT NULL DEFAULT '',
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `client_ip` varchar(100) DEFAULT NULL,
  `client_host` varchar(255) DEFAULT NULL,
  `client_useragent` varchar(255) DEFAULT NULL,
  `proxy_ip` varchar(100) DEFAULT NULL,
  `proxy_host` varchar(255) DEFAULT NULL,
  `proxy_useragent` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`sid`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_stats_visitors_summary` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(10) NOT NULL DEFAULT '',
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `count` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`type`,`timestamp`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_syslog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` datetime NOT NULL,
  `severity` enum('INFO','WARNING','FATAL') NOT NULL,
  `message` varchar(255) NOT NULL,
  `data` text NOT NULL,
  `logger` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_translations` (
  `id` int AUTO_INCREMENT,
  `locale` varchar(8) NOT NULL,
  `object_class` varchar(255) NOT NULL,
  `field` varchar(32) NOT NULL,
  `foreign_key` varchar(64) NOT NULL,
  `content` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lookup_unique_idx` (`locale`,`object_class`,`foreign_key`,`field`),
  INDEX `content_lookup_idx` (`content`(255), `object_class`, `field`),
  INDEX translations_lookup_idx (`locale`,`object_class`,`foreign_key`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_voting_additionaldata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nickname` varchar(80) NOT NULL DEFAULT '',
  `surname` varchar(80) NOT NULL DEFAULT '',
  `phone` varchar(80) NOT NULL DEFAULT '',
  `street` varchar(80) NOT NULL DEFAULT '',
  `zip` varchar(30) NOT NULL DEFAULT '',
  `city` varchar(80) NOT NULL DEFAULT '',
  `email` varchar(80) NOT NULL DEFAULT '',
  `comment` text NOT NULL,
  `voting_system_id` int(11) NOT NULL DEFAULT '0',
  `date_entered` timestamp NOT NULL,
  `forename` varchar(80) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `voting_system_id` (`voting_system_id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_voting_email` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `valid` enum('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_voting_rel_email_system` (
  `email_id` int(10) unsigned NOT NULL DEFAULT '0',
  `system_id` int(10) unsigned NOT NULL DEFAULT '0',
  `voting_id` int(10) unsigned NOT NULL DEFAULT '0',
  `valid` enum('0','1') NOT NULL DEFAULT '0',
  UNIQUE KEY `email_id` (`email_id`,`system_id`)
) ENGINE=InnoDB;
CREATE TABLE `contrexx_voting_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `voting_system_id` int(11) DEFAULT NULL,
  `question` char(200) DEFAULT NULL,
  `votes` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
CREATE TABLE `contrexx_voting_system` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` timestamp NOT NULL,
  `title` varchar(60) NOT NULL DEFAULT '',
  `question` text,
  `status` tinyint(1) DEFAULT '1',
  `submit_check` enum('cookie','email') NOT NULL DEFAULT 'cookie',
  `votes` int(11) DEFAULT '0',
  `additional_nickname` tinyint(1) NOT NULL DEFAULT '0',
  `additional_forename` tinyint(1) NOT NULL DEFAULT '0',
  `additional_surname` tinyint(1) NOT NULL DEFAULT '0',
  `additional_phone` tinyint(1) NOT NULL DEFAULT '0',
  `additional_street` tinyint(1) NOT NULL DEFAULT '0',
  `additional_zip` tinyint(1) NOT NULL DEFAULT '0',
  `additional_email` tinyint(1) NOT NULL DEFAULT '0',
  `additional_city` tinyint(1) NOT NULL DEFAULT '0',
  `additional_comment` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB ;
ALTER TABLE contrexx_module_calendar_invite ADD CONSTRAINT FK_842085E171F7E88B FOREIGN KEY (event_id) REFERENCES contrexx_module_calendar_event (id);
ALTER TABLE contrexx_module_calendar_registration_form_field_value ADD CONSTRAINT FK_F58DB1FA990B26CC FOREIGN KEY (reg_id) REFERENCES contrexx_module_calendar_registration (id);
ALTER TABLE contrexx_module_calendar_registration_form_field_value ADD CONSTRAINT FK_F58DB1FA443707B0 FOREIGN KEY (field_id) REFERENCES contrexx_module_calendar_registration_form_field (id);
ALTER TABLE contrexx_module_calendar_registration ADD CONSTRAINT FK_7F5FE63EA417747 FOREIGN KEY (invite_id) REFERENCES contrexx_module_calendar_invite (id);
ALTER TABLE contrexx_module_calendar_registration ADD CONSTRAINT FK_7F5FE6371F7E88B FOREIGN KEY (event_id) REFERENCES contrexx_module_calendar_event (id);
ALTER TABLE contrexx_module_calendar_category_name ADD CONSTRAINT FK_49D45FB1E6ADA943 FOREIGN KEY (cat_id) REFERENCES contrexx_module_calendar_category (id);
ALTER TABLE contrexx_module_calendar_event ADD CONSTRAINT FK_90D256CF9DB6EA93 FOREIGN KEY (registration_form) REFERENCES contrexx_module_calendar_registration_form (id);
ALTER TABLE contrexx_module_calendar_events_categories ADD CONSTRAINT FK_3974DFDB71F7E88B FOREIGN KEY (event_id) REFERENCES contrexx_module_calendar_event (id);
ALTER TABLE contrexx_module_calendar_events_categories ADD CONSTRAINT FK_3974DFDB12469DE2 FOREIGN KEY (category_id) REFERENCES contrexx_module_calendar_category (id);
ALTER TABLE contrexx_module_calendar_registration_form_field_name ADD CONSTRAINT FK_1C1E8341443707B0 FOREIGN KEY (field_id) REFERENCES contrexx_module_calendar_registration_form_field (id);
ALTER TABLE contrexx_module_calendar_registration_form_field ADD CONSTRAINT FK_AAEED23C5288FD4F FOREIGN KEY (form) REFERENCES contrexx_module_calendar_registration_form (id);
ALTER TABLE contrexx_module_calendar_event_field ADD CONSTRAINT FK_F76EF62C71F7E88B FOREIGN KEY (event_id) REFERENCES contrexx_module_calendar_event (id);
ALTER TABLE contrexx_access_user_attribute_name ADD CONSTRAINT FK_90502F6CB6E62EFA FOREIGN KEY (attribute_id) REFERENCES contrexx_access_user_attribute (id);
ALTER TABLE contrexx_access_rel_user_group ADD CONSTRAINT FK_401DFD43A76ED395 FOREIGN KEY (user_id) REFERENCES contrexx_access_users (id);
ALTER TABLE contrexx_access_rel_user_group ADD CONSTRAINT FK_401DFD43FE54D947 FOREIGN KEY (group_id) REFERENCES contrexx_access_user_groups (group_id);
ALTER TABLE contrexx_access_user_attribute ADD CONSTRAINT FK_D97727BE727ACA70 FOREIGN KEY (parent_id) REFERENCES contrexx_access_user_attribute (id);
ALTER TABLE contrexx_access_user_attribute_value ADD CONSTRAINT FK_B0DEA323A76ED395 FOREIGN KEY (user_id) REFERENCES contrexx_access_user_profile (user_id);
ALTER TABLE contrexx_access_user_profile ADD CONSTRAINT FK_959DBF6CA76ED395 FOREIGN KEY (user_id) REFERENCES contrexx_access_users (id);
ALTER TABLE contrexx_access_user_profile ADD CONSTRAINT FK_959DBF6C2B36786B FOREIGN KEY (title) REFERENCES contrexx_access_user_title (id);
ALTER TABLE contrexx_core_module_sync_change ADD CONSTRAINT FK_E98B92F1FA50C422 FOREIGN KEY (sync_id) REFERENCES contrexx_core_module_sync (id);
ALTER TABLE contrexx_core_module_sync_change ADD CONSTRAINT FK_E98B92F14F27D14F FOREIGN KEY (origin_sync_id) REFERENCES contrexx_core_module_sync (id);
ALTER TABLE contrexx_core_module_sync_change_host ADD CONSTRAINT FK_92C38FE0213C8BF4 FOREIGN KEY (change_id) REFERENCES contrexx_core_module_sync_change (id);
ALTER TABLE contrexx_core_module_sync_change_host ADD CONSTRAINT FK_92C38FE01FB8D185 FOREIGN KEY (host_id) REFERENCES contrexx_core_module_sync_host (id);
ALTER TABLE contrexx_core_view_frontend ADD CONSTRAINT `contrexx_core_view_frontend_ibfk_locale` FOREIGN KEY (`language`) REFERENCES `contrexx_core_locale_locale` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
ALTER TABLE contrexx_core_view_frontend ADD CONSTRAINT `contrexx_core_view_frontend_ibfk_theme` FOREIGN KEY (`theme`) REFERENCES `contrexx_skins` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
ALTER TABLE contrexx_core_locale_backend ADD CONSTRAINT FK_B8F1327C4FC20EF FOREIGN KEY (iso_1) REFERENCES contrexx_core_locale_language (iso_1) ON DELETE NO ACTION ON UPDATE NO ACTION;
ALTER TABLE contrexx_module_shop_orders ADD CONSTRAINT FK_DA286BB1B213FA4 FOREIGN KEY (lang_id) REFERENCES contrexx_core_locale_locale (id);
ALTER TABLE contrexx_module_shop_orders ADD CONSTRAINT FK_DA286BB138248176 FOREIGN KEY (currency_id) REFERENCES contrexx_module_shop_currencies (id);
ALTER TABLE contrexx_module_shop_orders ADD CONSTRAINT FK_DA286BB17BE036FC FOREIGN KEY (shipment_id) REFERENCES contrexx_module_shop_shipper (id);
ALTER TABLE contrexx_module_shop_orders ADD CONSTRAINT FK_DA286BB14C3A3BB FOREIGN KEY (payment_id) REFERENCES contrexx_module_shop_payment (id);
ALTER TABLE contrexx_module_shop_orders ADD CONSTRAINT FK_DA286BB19395C3F3 FOREIGN KEY (customer_id) REFERENCES contrexx_access_users (id) ON DELETE SET NULL;
ALTER TABLE contrexx_module_shop_order_attributes ADD CONSTRAINT FK_273F59F6126F525E FOREIGN KEY (item_id) REFERENCES contrexx_module_shop_order_items (id);
ALTER TABLE contrexx_module_shop_option ADD CONSTRAINT FK_658196EFB6E62EFA FOREIGN KEY (attribute_id) REFERENCES contrexx_module_shop_attribute (id);
ALTER TABLE contrexx_module_shop_products ADD CONSTRAINT FK_97F512B7A23B42D FOREIGN KEY (manufacturer_id) REFERENCES contrexx_module_shop_manufacturer (id);
ALTER TABLE contrexx_module_shop_products ADD CONSTRAINT FK_97F512B7FE54D947 FOREIGN KEY (group_id) REFERENCES contrexx_module_shop_discountgroup_count_name (id);
ALTER TABLE contrexx_module_shop_products ADD CONSTRAINT FK_97F512B77294869C FOREIGN KEY (article_id) REFERENCES contrexx_module_shop_article_group (id);
ALTER TABLE contrexx_module_shop_products ADD CONSTRAINT FK_97F512B7B5B63A6B FOREIGN KEY (vat_id) REFERENCES contrexx_module_shop_vat (id);
ALTER TABLE contrexx_module_shop_payment ADD CONSTRAINT FK_96C3CFFE37BAC19A FOREIGN KEY (processor_id) REFERENCES contrexx_module_shop_payment_processors (id);
ALTER TABLE contrexx_module_shop_rel_product_attribute ADD CONSTRAINT FK_E17E240B4584665A FOREIGN KEY (product_id) REFERENCES contrexx_module_shop_products (id);
ALTER TABLE contrexx_module_shop_rel_product_attribute ADD CONSTRAINT FK_E17E240BA7C41D6F FOREIGN KEY (option_id) REFERENCES contrexx_module_shop_option (id);
ALTER TABLE contrexx_module_shop_order_items ADD CONSTRAINT FK_1D79476B8D9F6D38 FOREIGN KEY (order_id) REFERENCES contrexx_module_shop_orders (id);
ALTER TABLE contrexx_module_shop_order_items ADD CONSTRAINT FK_1D79476B4584665A FOREIGN KEY (product_id) REFERENCES contrexx_module_shop_products (id);
ALTER TABLE contrexx_module_shop_discount_coupon ADD CONSTRAINT FK_7E70AB1A4C3A3BB FOREIGN KEY (payment_id) REFERENCES contrexx_module_shop_payment (id) ON DELETE SET NULL;
ALTER TABLE contrexx_module_shop_discount_coupon ADD CONSTRAINT FK_7E70AB1A4584665A FOREIGN KEY (product_id) REFERENCES contrexx_module_shop_products (id) ON DELETE SET NULL;
ALTER TABLE contrexx_module_shop_discount_coupon ADD CONSTRAINT FK_7E70AB1A9395C3F3 FOREIGN KEY (customer_id) REFERENCES contrexx_access_users (id) ON DELETE SET NULL;
ALTER TABLE contrexx_module_shop_discountgroup_count_rate ADD CONSTRAINT FK_3F3DD477FE54D947 FOREIGN KEY (group_id) REFERENCES contrexx_module_shop_discountgroup_count_name (id);
ALTER TABLE contrexx_module_shop_pricelists ADD CONSTRAINT FK_BB867D48B213FA4 FOREIGN KEY (lang_id) REFERENCES contrexx_core_locale_locale (id);
ALTER TABLE contrexx_module_shop_lsv ADD CONSTRAINT FK_889921958D9F6D38 FOREIGN KEY (order_id) REFERENCES contrexx_module_shop_orders (id);
ALTER TABLE contrexx_module_shop_rel_customer_coupon ADD CONSTRAINT FK_6A7FBE248D9F6D38 FOREIGN KEY (order_id) REFERENCES contrexx_module_shop_orders (id);
ALTER TABLE contrexx_module_shop_rel_customer_coupon ADD CONSTRAINT FK_6A7FBE249395C3F3 FOREIGN KEY (customer_id) REFERENCES contrexx_access_users (id) ON DELETE SET NULL;
ALTER TABLE contrexx_module_shop_rel_discount_group ADD CONSTRAINT FK_93D6FD61D2919A68 FOREIGN KEY (customer_group_id) REFERENCES contrexx_module_shop_customer_group (id);
ALTER TABLE contrexx_module_shop_rel_discount_group ADD CONSTRAINT FK_93D6FD61ABBC2D2C FOREIGN KEY (article_group_id) REFERENCES contrexx_module_shop_article_group (id);
ALTER TABLE contrexx_module_shop_rel_payment ADD CONSTRAINT FK_43EB87989F2C3FAB FOREIGN KEY (zone_id) REFERENCES contrexx_module_shop_zones (id);
ALTER TABLE contrexx_module_shop_rel_payment ADD CONSTRAINT FK_43EB87984C3A3BB FOREIGN KEY (payment_id) REFERENCES contrexx_module_shop_payment (id);
ALTER TABLE contrexx_module_shop_shipment_cost ADD CONSTRAINT FK_2329A4538459F23 FOREIGN KEY (shipper_id) REFERENCES contrexx_module_shop_shipper (id);
ALTER TABLE contrexx_module_shop_rel_countries ADD CONSTRAINT FK_C859EA8B9F2C3FAB FOREIGN KEY (zone_id) REFERENCES contrexx_module_shop_zones (id);
ALTER TABLE contrexx_module_shop_categories ADD CONSTRAINT FK_A9242624727ACA70 FOREIGN KEY (parent_id) REFERENCES contrexx_module_shop_categories (id);
ALTER TABLE contrexx_module_shop_rel_product_user_group ADD CONSTRAINT FK_32A4494A4584665A FOREIGN KEY (product_id) REFERENCES contrexx_module_shop_products (id);
ALTER TABLE contrexx_module_shop_rel_product_user_group ADD CONSTRAINT FK_32A4494AD2112630 FOREIGN KEY (usergroup_id) REFERENCES contrexx_access_user_groups (group_id);
ALTER TABLE contrexx_module_shop_rel_category_pricelist ADD CONSTRAINT FK_B56E91A112469DE2 FOREIGN KEY (category_id) REFERENCES contrexx_module_shop_categories (id);
ALTER TABLE contrexx_module_shop_rel_category_pricelist ADD CONSTRAINT FK_B56E91A189045958 FOREIGN KEY (pricelist_id) REFERENCES contrexx_module_shop_pricelists (id);
ALTER TABLE contrexx_module_shop_rel_category_product ADD CONSTRAINT FK_DA4CA51112469DE2 FOREIGN KEY (category_id) REFERENCES contrexx_module_shop_categories (id);
ALTER TABLE contrexx_module_shop_rel_category_product ADD CONSTRAINT FK_DA4CA5114584665A FOREIGN KEY (product_id) REFERENCES contrexx_module_shop_products (id);
ALTER TABLE contrexx_module_shop_shipper ADD CONSTRAINT FK_52CD810E9F2C3FAB FOREIGN KEY (`zone_id`) REFERENCES contrexx_module_shop_zones (`id`);
