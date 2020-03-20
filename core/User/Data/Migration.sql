ALTER TABLE contrexx_access_user_attribute
    ADD is_default TINYINT(1) DEFAULT '0' NOT NULL;

/*Migrate core attribute to user attribute*/
INSERT INTO `contrexx_access_user_attribute`(
  `mandatory`, `sort_type`, `order_id`, `access_special`, `access_id`, `read_access_id`, `is_default`
) SELECT `mandatory`, `sort_type`, `order_id`, `access_special`, `access_id`, `read_access_id`, '1'
  FROM `contrexx_access_user_core_attribute`;
UPDATE `contrexx_access_user_attribute` SET `parent_id`= null WHERE parent_id = 0;

/*Migrate user profile to user attribute*/
ALTER TABLE contrexx_access_user_attribute ADD tmp_name TEXT;
INSERT INTO `contrexx_access_user_attribute`
  (`tmp_name`, `type`, `order_id`, `access_id`, `read_access_id`, `is_default`)
  VALUES
    ('gender',        'menu',     2,  0, 0, 1),
    ('title',         'menu',     3,  0, 0, 1),
    ('designation',   'text',     4,  0, 0, 1),
    ('firstname',     'text',     5,  0, 0, 1),
    ('lastname',      'text',     6,  0, 0, 1),
    ('company',       'text',     7,  0, 0, 1),
    ('address',       'text',      8,  0, 0, 1),
    ('city',          'text',     9,  0, 0, 1),
    ('country',       'menu',     11, 0, 0, 1),
    ('zip',           'text',     10, 0, 0, 1),
    ('phone_office',  'text',     12, 0, 0, 1),
    ('phone_private', 'text',     13, 0, 0, 1),
    ('phone_mobile',  'text',     14, 0, 0, 1),
    ('phone_fax',     'text',     15, 0, 0, 1),
    ('birthday',      'date',     16, 0, 0, 1),
    ('website',       'uri',      17, 0, 0, 1),
    ('profession',    'text',     18, 0, 0, 1),
    ('interests',     'textarea', 19, 0, 0, 1),
    ('signature',     'textarea', 20, 0, 0, 1),
    ('picture',       'image',    1,  0, 0, 1);


UPDATE contrexx_access_user_attribute as userattr
  SET parent_id = (SELECT ua.id FROM (SELECT * FROM contrexx_access_user_attribute) AS ua
    WHERE ua.tmp_name = 'title'
  ) WHERE userattr.tmp_name = 'title-w' OR userattr.tmp_name = 'title-m';
UPDATE contrexx_access_user_attribute SET type = 'menu' WHERE tmp_name = 'title';

INSERT INTO
	`contrexx_access_user_attribute`(`parent_id`, `access_id`, `type`, `read_access_id`, `is_default`, `tmp_name`)
SELECT
	ua.id AS parent_id, null AS access_id, 'menu_option' AS TYPE, null AS read_access_id, 1 AS is_default,
	'title-c' AS tmp_name
FROM
	contrexx_access_user_title
JOIN
	contrexx_access_user_attribute AS `ua` ON ua.tmp_name = 'title';

ALTER TABLE contrexx_access_user_profile ADD tmp_name TEXT;

ALTER TABLE contrexx_access_user_attribute_value ADD tmp_name TEXT;

ALTER TABLE `contrexx_access_user_attribute_value` DROP FOREIGN KEY IF EXISTS `FK_B0DEA323B6E62EFA`;

ALTER TABLE contrexx_access_user_attribute_value DROP FOREIGN KEY IF EXISTS FK_B0DEA323A76ED395;

ALTER TABLE `contrexx_access_user_attribute_value` CHANGE `attribute_id` `attribute_id` INT(11) UNSIGNED NOT NULL;

UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'gender';

INSERT INTO
	`contrexx_access_user_attribute`(`parent_id`, `access_id`, `type`, `read_access_id`, `is_default`, `tmp_name`)
	VALUES (
	    (SELECT `amale`.`id` FROM `contrexx_access_user_attribute` AS `amale` WHERE `amale`.`tmp_name` = 'gender'),
	    null, 'menu_option', null, 1, 'gender_male'
	), (
	    (SELECT `afemale`.`id` FROM `contrexx_access_user_attribute` AS `afemale` WHERE `afemale`.`tmp_name` = 'gender'),
	    null, 'menu_option', null, 1, 'gender_female'
	);

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`, `attribute_id`, `user_id`, `value`)
  SELECT `tmp_name`, (
    SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'gender'
  ), `user_id`, (
    SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = `gender`
  )
  FROM `contrexx_access_user_profile`;

UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'title';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`, `attribute_id`, `user_id`, `value`)
  SELECT `tmp_name`, (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'title'), `user_id`, `title`
  FROM `contrexx_access_user_profile`;


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'designation';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`,`attribute_id`, `user_id`, `value`)
  SELECT `tmp_name`, (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'designation'),`user_id`,
    `designation`
  FROM `contrexx_access_user_profile`;


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'firstname';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`,`attribute_id`, `user_id`, `value`)
  SELECT `tmp_name`, (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'firstname'), `user_id`,
    `firstname`
  FROM `contrexx_access_user_profile`;


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'lastname';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`,`attribute_id`, `user_id`, `value`)
  SELECT `tmp_name`, (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'lastname'), `user_id`,
    `lastname`
  FROM `contrexx_access_user_profile`;


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'company';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`,`attribute_id`, `user_id`, `value`)
  SELECT `tmp_name`, (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'company'), `user_id`,
    `company`
  FROM `contrexx_access_user_profile`;


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'address';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`,`attribute_id`, `user_id`, `value`)
  SELECT `tmp_name`, (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'address'), `user_id`,
    `address`
  FROM `contrexx_access_user_profile`;


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'city';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`,`attribute_id`, `user_id`, `value`)
  SELECT `tmp_name`, (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'city'), `user_id`, `city`
  FROM `contrexx_access_user_profile`;


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'zip';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`,`attribute_id`, `user_id`, `value`)
  SELECT `tmp_name`, (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'zip'), `user_id`, `zip`
  FROM `contrexx_access_user_profile`;


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'country';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`,`attribute_id`, `user_id`, `value`)
  SELECT `tmp_name`, (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'country'), `user_id`,
    `country`
  FROM `contrexx_access_user_profile`;


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'phone_office';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`,`attribute_id`, `user_id`, `value`)
  SELECT `tmp_name`, (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'phone_office'), `user_id`,
    `phone_office`
  FROM `contrexx_access_user_profile`;


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'phone_private';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`,`attribute_id`, `user_id`, `value`)
  SELECT `tmp_name`, (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'phone_private'), `user_id`,
    `phone_private`
  FROM `contrexx_access_user_profile`;


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'phone_mobile';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`,`attribute_id`, `user_id`, `value`)
  SELECT `tmp_name`, (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'phone_mobile'), `user_id`,
    `phone_mobile`
  FROM `contrexx_access_user_profile`;


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'phone_fax';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`,`attribute_id`, `user_id`, `value`)
  SELECT `tmp_name`, (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'phone_fax'), `user_id`,
    `phone_fax`
  FROM `contrexx_access_user_profile`;


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'birthday';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`,`attribute_id`, `user_id`, `value`)
  SELECT `tmp_name`, (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'birthday'), `user_id`,
    `birthday`
  FROM `contrexx_access_user_profile`;


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'website';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`,`attribute_id`, `user_id`, `value`)
  SELECT `tmp_name`, (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'website'), `user_id`,
    `website`
  FROM `contrexx_access_user_profile`;


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'profession';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`,`attribute_id`, `user_id`, `value`)
  SELECT `tmp_name`, (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'profession'), `user_id`,
    `profession`
  FROM `contrexx_access_user_profile`;


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'interests';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`,`attribute_id`, `user_id`, `value`)
  SELECT `tmp_name`, (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'interests'), `user_id`,
    `interests`
  FROM `contrexx_access_user_profile`;


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'signature';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`,`attribute_id`, `user_id`, `value`)
  SELECT `tmp_name`, (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'signature'), `user_id`,
    `signature`
  FROM `contrexx_access_user_profile`;


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'picture';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`,`attribute_id`, `user_id`, `value`)
  SELECT `tmp_name`, (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'picture'), `user_id`,
    `picture`
  FROM `contrexx_access_user_profile`;

/* Delete all user attribute values which do not belong to any user */
DELETE v FROM `contrexx_access_user_attribute_value` AS v
LEFT JOIN contrexx_access_users as u ON u.id = v.user_id
LEFT JOIN contrexx_access_user_attribute as a ON a.id = v.attribute_id
WHERE u.id IS NULL OR a.id IS NULL;

/*Insert attribute name*/
ALTER TABLE contrexx_access_user_attribute_name ADD `order` INT;

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES(
  (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'gender'), 'gender'
);

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES(
  (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'gender_female'), 'gender_female'
);

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES(
  (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'gender_male'), 'gender_male'
);

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES(
  (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'title'), 'title'
);

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`, `lang_id`, `order`)
  SELECT (
    SELECT `id`+`title`.`id`-1 FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'title-c' LIMIT 1
  ) AS attribute_id, title AS name, 0 as lang_id, id as `order`
  FROM `contrexx_access_user_title` AS title;

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES(
  (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'designation'), 'designation'
);

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES(
  (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'firstname'), 'firstname'
);

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES(
  (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'lastname'), 'lastname'
);

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES(
  (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'company'), 'company'
);

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES(
  (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'address'), 'address'
);

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES(
  (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'city'), 'city'
);

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES(
  (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'zip'), 'zip'
);

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES(
  (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'country'), 'country'
);

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES(
  (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'phone_office'), 'phone_office'
);

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES(
  (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'phone_private'), 'phone_private'
);

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES(
  (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'phone_mobile'), 'phone_mobile'
);

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES(
  (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'phone_fax'), 'phone_fax'
);

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES(
  (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'birthday'), 'birthday'
);

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES(
  (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'website'), 'website'
);

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES(
  (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'profession'), 'profession'
);

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES(
  (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'interests'), 'interests'
);

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES(
  (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'signature'), 'signature'
);

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES(
  (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'picture'), 'picture'
);

UPDATE `contrexx_access_user_attribute_value` JOIN `contrexx_access_user_attribute_name` AS `attrName` ON `attrName`.`order` = `contrexx_access_user_attribute_value`.`value`
SET `contrexx_access_user_attribute_value`.`value` = `attrName`.`attribute_id`
WHERE `contrexx_access_user_attribute_value`.`tmp_name` = 'title';

ALTER TABLE contrexx_access_user_profile DROP FOREIGN KEY IF EXISTS FK_959DBF6CA76ED395;
ALTER TABLE contrexx_access_user_profile DROP FOREIGN KEY IF EXISTS  FK_959DBF6C2B36786B;

DROP TABLE contrexx_access_user_title;
DROP TABLE contrexx_access_user_core_attribute;
DROP TABLE contrexx_access_user_profile;

/*Alter table access_users */
ALTER TABLE contrexx_access_rel_user_group DROP FOREIGN KEY IF EXISTS FK_401DFD43A76ED395;

ALTER TABLE contrexx_access_users
  CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL,
  CHANGE email email VARCHAR(255) NOT NULL,
  CHANGE auth_token auth_token VARCHAR(32) DEFAULT '0' NOT NULL,
  CHANGE auth_token_timeout auth_token_timeout INT UNSIGNED DEFAULT 0 NOT NULL,
  CHANGE regdate regdate INT UNSIGNED DEFAULT 0 NOT NULL,
  CHANGE expiration expiration INT UNSIGNED DEFAULT 0 NOT NULL,
  CHANGE validity validity INT UNSIGNED DEFAULT 0 NOT NULL,
  CHANGE last_auth last_auth INT UNSIGNED DEFAULT 0 NOT NULL,
  CHANGE last_activity last_activity INT UNSIGNED DEFAULT 0 NOT NULL,
  CHANGE last_auth_status last_auth_status SMALLINT DEFAULT '0' NOT NULL,
  CHANGE frontend_lang_id frontend_lang_id INT UNSIGNED DEFAULT 0 NOT NULL,
  CHANGE backend_lang_id backend_lang_id INT UNSIGNED DEFAULT 0 NOT NULL,
  CHANGE primary_group primary_group INT UNSIGNED DEFAULT 0 NOT NULL,
  CHANGE restore_key_time restore_key_time INT UNSIGNED DEFAULT 0 NOT NULL,
  CHANGE active active TINYINT(1) DEFAULT '1' NOT NULL,
  CHANGE u2u_active u2u_active TINYINT(1) DEFAULT '1' NOT NULL;
ALTER TABLE contrexx_access_rel_user_group CHANGE user_id user_id INT UNSIGNED NOT NULL;

/** Drop Foreign Keys To Modify Tables **/
ALTER TABLE `contrexx_access_user_attribute_name` DROP FOREIGN KEY IF EXISTS `FK_90502F6CB6E62EFA`;
ALTER TABLE `contrexx_access_user_attribute` DROP FOREIGN KEY IF EXISTS `FK_D97727BE727ACA70`;
ALTER TABLE `contrexx_access_rel_user_group` DROP FOREIGN KEY IF EXISTS `FK_401DFD43FE54D947`;

ALTER TABLE contrexx_access_user_attribute
	CHANGE id id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	CHANGE parent_id parent_id INT UNSIGNED DEFAULT NULL,
	CHANGE mandatory mandatory TINYINT(1) DEFAULT '0' NOT NULL;

ALTER TABLE contrexx_access_user_attribute_name
	CHANGE attribute_id attribute_id INT UNSIGNED NOT NULL;

ALTER TABLE contrexx_access_user_attribute_value
	CHANGE attribute_id attribute_id INT UNSIGNED NOT NULL,
	CHANGE user_id user_id INT UNSIGNED NOT NULL,
	CHANGE history_id history_id INT UNSIGNED DEFAULT 0 NOT NULL;

ALTER TABLE contrexx_access_rel_user_group CHANGE group_id group_id INT UNSIGNED NOT NULL;

ALTER TABLE contrexx_access_user_groups
  CHANGE group_id group_id INT UNSIGNED AUTO_INCREMENT NOT NULL,
  CHANGE is_active is_active TINYINT(1) DEFAULT '1' NOT NULL;

/** Delete all invalid entries **/
DELETE FROM contrexx_access_user_attribute_value WHERE attribute_id = 0;
/* Delete all user attribute values which do not belong to any user */
DELETE v FROM `contrexx_access_user_attribute_value` AS v
LEFT JOIN contrexx_access_users as u ON u.id = v.user_id
WHERE u.id IS NULL;

UPDATE `contrexx_access_user_attribute` SET `parent_id`= null WHERE parent_id = 0;

DELETE g FROM contrexx_access_rel_user_group AS g LEFT JOIN contrexx_access_users AS u ON u.id = g.user_id WHERE u.id IS NULL;

ALTER TABLE contrexx_access_rel_user_group ADD CONSTRAINT FK_401DFD43A76ED395 FOREIGN KEY (user_id) REFERENCES contrexx_access_users (id);

/*Add unique index to access_user_attribute_name*/
ALTER TABLE contrexx_access_user_attribute_name DROP PRIMARY KEY;
ALTER TABLE contrexx_access_user_attribute_name ADD id INT UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY FIRST;
CREATE UNIQUE INDEX fk_module_user_attribute_name_unique_idx
  ON contrexx_access_user_attribute_name (attribute_id, lang_id);

/** Add Foreign Keys **/
ALTER TABLE `contrexx_access_user_attribute_value` ADD CONSTRAINT `FK_B0DEA323B6E62EFA`
	FOREIGN KEY (`attribute_id`) REFERENCES `contrexx_access_user_attribute` (`id`);
ALTER TABLE `contrexx_access_user_attribute_value` ADD CONSTRAINT `FK_B0DEA323A76ED395A76ED395A76ED395A76ED395`
	FOREIGN KEY (`user_id`) REFERENCES `contrexx_access_users` (`id`);

ALTER TABLE `contrexx_access_user_attribute_name`ADD CONSTRAINT `FK_90502F6CB6E62EFA`
	FOREIGN KEY (`attribute_id`) REFERENCES `contrexx_access_user_attribute`(`id`);

ALTER TABLE `contrexx_access_user_attribute`ADD CONSTRAINT `FK_D97727BE727ACA70`
	FOREIGN KEY (`parent_id`) REFERENCES `contrexx_access_user_attribute`(`id`);

ALTER TABLE contrexx_access_rel_user_group ADD CONSTRAINT FK_401DFD43FE54D947
  FOREIGN KEY (group_id) REFERENCES contrexx_access_user_groups (group_id);

/** Add Indexes **/
CREATE INDEX IDX_B0DEA323B6E62EFA ON contrexx_access_user_attribute_value (attribute_id);
CREATE UNIQUE INDEX UNIQ_7CD32875E7927C74 ON contrexx_access_users (email);

/** Drop tmp names **/
ALTER TABLE `contrexx_access_user_attribute_value` DROP `tmp_name`;
ALTER TABLE `contrexx_access_user_attribute` DROP `tmp_name`;

/** Migrate attribute_names **/
ALTER TABLE `contrexx_access_user_attribute` ADD COLUMN `name` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE contrexx_access_user_attribute_name DROP FOREIGN KEY FK_90502F6CB6E62EFA;
DROP INDEX contrexx_access_user_attribute_name_attribute_id_ibfk ON contrexx_access_user_attribute_name;

INSERT INTO `contrexx_translations` (`locale`, `object_class`, `field`, `foreign_key`, `content`)
SELECT
	(
	    CASE
	        WHEN `a`.`lang_id` = 3 THEN 'fr'
            WHEN `l`.`iso_1` IS NULL THEN 'de'
            ELSE `l`.`iso_1`
        END
    ) AS `locale`,
    'Cx\\Core\\User\\Model\\Entity\\UserAttribute' AS `object_class`,
    'name' AS `field`,
    `a`.`attribute_id` AS `foreign_key`,
    `a`.`name` AS `content`
FROM `contrexx_access_user_attribute_name` AS `a`
LEFT JOIN `contrexx_core_locale_locale` AS `l` ON `a`.`lang_id` = `l`.`id`;

DROP TABLE `contrexx_access_user_attribute_name`;