ALTER TABLE contrexx_access_user_attribute ADD is_default TINYINT(1) DEFAULT '0' NOT NULL;
ALTER TABLE contrexx_access_user_attribute_value DROP FOREIGN KEY FK_B0DEA323A76ED395;
ALTER TABLE contrexx_access_user_attribute_value ADD CONSTRAINT FK_B0DEA323B6E62EFA
  FOREIGN KEY (attribute_id) REFERENCES contrexx_access_user_attribute (id);

ALTER TABLE contrexx_access_user_attribute_value ADD CONSTRAINT FK_B0DEA323A76ED395A76ED395A76ED395A76ED395
  FOREIGN KEY (user_id) REFERENCES contrexx_access_users (id);
CREATE INDEX IDX_B0DEA323B6E62EFA ON contrexx_access_user_attribute_value (attribute_id);

/*Migrate core attribute to user attribute*/
INSERT INTO `contrexx_access_user_attribute`(
  `mandatory`, `sort_type`, `order_id`, `access_special`, `access_id`, `read_access_id`, `is_default`
) SELECT `mandatory`, `sort_type`, `order_id`, `access_special`, `access_id`, `read_access_id`, '1'
  FROM `contrexx_access_user_core_attribute`;

/*Migrate user profile to user attribute*/
ALTER TABLE contrexx_access_user_attribute ADD tmp_name TEXT;

INSERT INTO contrexx_access_user_attribute (tmp_name, is_default)
SELECT COLUMN_NAME, 1
FROM INFORMATION_SCHEMA.COLUMNS
WHERE table_name = 'contrexx_access_user_profile'
GROUP BY COLUMN_NAME;

INSERT INTO `contrexx_access_user_attribute`(`access_id`, `type`, `read_access_id`, `is_default`, `tmp_name`)
  VALUES (0, 'menu_option', 0, 1, 'title-w');
INSERT INTO `contrexx_access_user_attribute`(`access_id`, `type`, `read_access_id`, `is_default`, `tmp_name`)
  VALUES (0, 'menu_option', 0, 1, 'title-m');

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

UPDATE contrexx_access_user_attribute SET type = 'menu' WHERE tmp_name = 'title';

ALTER TABLE contrexx_access_user_profile ADD tmp_name TEXT;

ALTER TABLE contrexx_access_user_attribute_value ADD tmp_name TEXT;

ALTER TABLE `contrexx_access_user_attribute_value` CHANGE `attribute_id` `attribute_id` INT(11) NULL;

ALTER TABLE `contrexx_access_user_attribute_value` DROP FOREIGN KEY `FK_B0DEA323B6E62EFA`;

ALTER TABLE contrexx_access_user_attribute_value DROP FOREIGN KEY FK_B0DEA323A76ED395A76ED395A76ED395A76ED395;

UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'gender';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`, `attribute_id`, `user_id`, `value`)
  SELECT `tmp_name`, (
    SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'gender'
  ), `user_id`, `gender`
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


ALTER TABLE contrexx_access_user_attribute_value ADD CONSTRAINT FK_B0DEA323B6E62EFA
  FOREIGN KEY (attribute_id) REFERENCES contrexx_access_user_attribute (id);
ALTER TABLE contrexx_access_user_attribute_value ADD CONSTRAINT FK_B0DEA323A76ED395A76ED395A76ED395A76ED395
  FOREIGN KEY (user_id) REFERENCES contrexx_access_users (id);

/*Insert attribute name*/
ALTER TABLE contrexx_access_user_attribute_name ADD `order` INT;

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES(
  (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'gender'), 'gender'
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

UPDATE `contrexx_access_user_attribute_value` SET `value` = (
  SELECT name.attribute_id FROM contrexx_access_user_attribute_name AS name
    JOIN contrexx_access_user_title AS title ON title.order_id = name.order
) WHERE `value` = 1 AND `tmp_name` = 'title';

ALTER TABLE contrexx_access_user_attribute_value DROP tmp_name, CHANGE attribute_id attribute_id INT NOT NULL;

DROP TABLE contrexx_access_user_profile;
DROP TABLE contrexx_access_user_title;
DROP TABLE contrexx_access_user_core_attribute;

/* View for user title
 * This view is only temporary
 */
CREATE VIEW `contrexx_access_user_title` AS SELECT `order` AS id, `name` AS title, 0 as order_id
FROM `contrexx_access_user_attribute_name` AS `name`
WHERE `name`.`order` > 0;

/* View for user core attribute
 * This view is only temporary
 */
CREATE VIEW `contrexx_access_user_core_attribute` AS
SELECT `tmp_name` AS `id`, `mandatory`, `sort_type`, `order_id`, `access_special`, `access_id`, `read_access_id`
FROM `contrexx_access_user_attribute`
WHERE `is_default` = '1';

/* View for user profile
 * This view is extremely slow but since it is only temporary, it will be left as it is
 */
CREATE VIEW `contrexx_access_user_profile` AS (SELECT 
id as 'user_id',                                               
(SELECT value.value FROM contrexx_access_users AS user 
JOIN contrexx_access_user_attribute_value as value on value.user_id = user.id
JOIN contrexx_access_user_attribute_name as name on value.attribute_id = name.attribute_id
WHERE name.name = 'gender' AND lang_id = 0 AND value.user_id = users.id) AS 'gender',
(SELECT value.value FROM contrexx_access_users AS user 
JOIN contrexx_access_user_attribute_value as value on value.user_id = user.id
JOIN contrexx_access_user_attribute_name as name on value.attribute_id = name.attribute_id
WHERE name.name = 'title' AND lang_id = 0 AND value.user_id = users.id) AS 'title',
(SELECT value.value FROM contrexx_access_users AS user
JOIN contrexx_access_user_attribute_value as value on value.user_id = user.id
JOIN contrexx_access_user_attribute_name as name on value.attribute_id = name.attribute_id
WHERE name.name = 'designation' AND lang_id = 0 AND value.user_id = users.id) AS 'designation',
(SELECT value.value FROM contrexx_access_users AS user 
JOIN contrexx_access_user_attribute_value as value on value.user_id = user.id
JOIN contrexx_access_user_attribute_name as name on value.attribute_id = name.attribute_id
WHERE name.name = 'firstname' AND lang_id = 0 AND value.user_id = users.id) AS 'firstname',
(SELECT value.value FROM contrexx_access_users AS user 
JOIN contrexx_access_user_attribute_value as value on value.user_id = user.id
JOIN contrexx_access_user_attribute_name as name on value.attribute_id = name.attribute_id
WHERE name.name = 'lastname' AND lang_id = 0 AND value.user_id = users.id) AS 'lastname',
(SELECT value.value FROM contrexx_access_users AS user 
JOIN contrexx_access_user_attribute_value as value on value.user_id = user.id
JOIN contrexx_access_user_attribute_name as name on value.attribute_id = name.attribute_id
WHERE name.name = 'company' AND lang_id = 0 AND value.user_id = users.id) AS 'company',
(SELECT value.value FROM contrexx_access_users AS user 
JOIN contrexx_access_user_attribute_value as value on value.user_id = user.id
JOIN contrexx_access_user_attribute_name as name on value.attribute_id = name.attribute_id
WHERE name.name = 'address' AND lang_id = 0 AND value.user_id = users.id) AS 'address',
(SELECT value.value FROM contrexx_access_users AS user 
JOIN contrexx_access_user_attribute_value as value on value.user_id = user.id
JOIN contrexx_access_user_attribute_name as name on value.attribute_id = name.attribute_id
WHERE name.name = 'city' AND lang_id = 0 AND value.user_id = users.id) AS 'city',
(SELECT value.value FROM contrexx_access_users AS user 
JOIN contrexx_access_user_attribute_value as value on value.user_id = user.id
JOIN contrexx_access_user_attribute_name as name on value.attribute_id = name.attribute_id
WHERE name.name = 'zip' AND lang_id = 0 AND value.user_id = users.id) AS 'zip',
(SELECT value.value FROM contrexx_access_users AS user 
JOIN contrexx_access_user_attribute_value as value on value.user_id = user.id
JOIN contrexx_access_user_attribute_name as name on value.attribute_id = name.attribute_id
WHERE name.name = 'country' AND lang_id = 0 AND value.user_id = users.id) AS 'country',
(SELECT value.value FROM contrexx_access_users AS user 
JOIN contrexx_access_user_attribute_value as value on value.user_id = user.id
JOIN contrexx_access_user_attribute_name as name on value.attribute_id = name.attribute_id
WHERE name.name = 'phone_office' AND lang_id = 0 AND value.user_id = users.id) AS 'phone_office',
(SELECT value.value FROM contrexx_access_users AS user 
JOIN contrexx_access_user_attribute_value as value on value.user_id = user.id
JOIN contrexx_access_user_attribute_name as name on value.attribute_id = name.attribute_id
WHERE name.name = 'phone_private' AND lang_id = 0 AND value.user_id = users.id) AS 'phone_private',
(SELECT value.value FROM contrexx_access_users AS user 
JOIN contrexx_access_user_attribute_value as value on value.user_id = user.id
JOIN contrexx_access_user_attribute_name as name on value.attribute_id = name.attribute_id
WHERE name.name = 'phone_mobile' AND lang_id = 0 AND value.user_id = users.id) AS 'phone_mobile',
(SELECT value.value FROM contrexx_access_users AS user 
JOIN contrexx_access_user_attribute_value as value on value.user_id = user.id
JOIN contrexx_access_user_attribute_name as name on value.attribute_id = name.attribute_id
WHERE name.name = 'phone_fax' AND lang_id = 0 AND value.user_id = users.id) AS 'phone_fax',
(SELECT value.value FROM contrexx_access_users AS user 
JOIN contrexx_access_user_attribute_value as value on value.user_id = user.id
JOIN contrexx_access_user_attribute_name as name on value.attribute_id = name.attribute_id
WHERE name.name = 'birthday' AND lang_id = 0 AND value.user_id = users.id) AS 'birthday',
(SELECT value.value FROM contrexx_access_users AS user 
JOIN contrexx_access_user_attribute_value as value on value.user_id = user.id
JOIN contrexx_access_user_attribute_name as name on value.attribute_id = name.attribute_id
WHERE name.name = 'website' AND lang_id = 0 AND value.user_id = users.id) AS 'website',
(SELECT value.value FROM contrexx_access_users AS user 
JOIN contrexx_access_user_attribute_value as value on value.user_id = user.id
JOIN contrexx_access_user_attribute_name as name on value.attribute_id = name.attribute_id
WHERE name.name = 'profession' AND lang_id = 0 AND value.user_id = users.id) AS 'profession',
(SELECT value.value FROM contrexx_access_users AS user 
JOIN contrexx_access_user_attribute_value as value on value.user_id = user.id
JOIN contrexx_access_user_attribute_name as name on value.attribute_id = name.attribute_id
WHERE name.name = 'interests' AND lang_id = 0 AND value.user_id = users.id) AS 'interests',
(SELECT value.value FROM contrexx_access_users AS user 
JOIN contrexx_access_user_attribute_value as value on value.user_id = user.id
JOIN contrexx_access_user_attribute_name as name on value.attribute_id = name.attribute_id
WHERE name.name = 'signature' AND lang_id = 0 AND value.user_id = users.id) AS 'signature',
(SELECT value.value FROM contrexx_access_users AS user 
JOIN contrexx_access_user_attribute_value as value on value.user_id = user.id
JOIN contrexx_access_user_attribute_name as name on value.attribute_id = name.attribute_id
WHERE name.name = 'picture' AND lang_id = 0 AND value.user_id = users.id) AS 'picture'
FROM contrexx_access_users AS users);

/*Alter table access_users unsigned*/
ALTER TABLE dev.contrexx_access_rel_user_group DROP FOREIGN KEY FK_401DFD43A76ED395;
ALTER TABLE dev.contrexx_access_user_attribute_value DROP FOREIGN KEY FK_B0DEA323A76ED395A76ED395A76ED395A76ED395;

ALTER TABLE contrexx_access_users
  CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL,
  CHANGE auth_token_timeout auth_token_timeout INT UNSIGNED DEFAULT 0 NOT NULL,
  CHANGE regdate regdate INT UNSIGNED DEFAULT 0 NOT NULL,
  CHANGE expiration expiration INT UNSIGNED DEFAULT 0 NOT NULL,
  CHANGE validity validity INT UNSIGNED DEFAULT 0 NOT NULL,
  CHANGE last_auth last_auth INT UNSIGNED DEFAULT 0 NOT NULL,
  CHANGE last_activity last_activity INT UNSIGNED DEFAULT 0 NOT NULL,
  CHANGE frontend_lang_id frontend_lang_id INT UNSIGNED DEFAULT 0 NOT NULL,
  CHANGE backend_lang_id backend_lang_id INT UNSIGNED DEFAULT 0 NOT NULL,
  CHANGE primary_group primary_group INT UNSIGNED DEFAULT 0 NOT NULL,
  CHANGE restore_key_time restore_key_time INT UNSIGNED DEFAULT 0 NOT NULL;
ALTER TABLE contrexx_access_rel_user_group CHANGE user_id user_id INT UNSIGNED NOT NULL;
ALTER TABLE contrexx_access_user_attribute_value CHANGE user_id user_id INT UNSIGNED NOT NULL;

ALTER TABLE contrexx_access_rel_user_group ADD CONSTRAINT FK_401DFD43A76ED395
  FOREIGN KEY (user_id) REFERENCES contrexx_access_users (id);
ALTER TABLE contrexx_access_user_attribute_value ADD CONSTRAINT FK_B0DEA323A76ED395
  FOREIGN KEY (user_id) REFERENCES contrexx_access_users (id) ON DELETE RESTRICT;

/*Add unique index to access_user_attribute_name*/
ALTER TABLE contrexx_access_user_attribute_name DROP PRIMARY KEY;
ALTER TABLE contrexx_access_user_attribute_name ADD id INT AUTO_INCREMENT NOT NULL PRIMARY KEY;
CREATE UNIQUE INDEX fk_module_user_attribute_name_unique_idx
  ON contrexx_access_user_attribute_name (attribute_id, lang_id);
