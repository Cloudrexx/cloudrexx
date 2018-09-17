ALTER TABLE contrexx_access_user_attribute ADD is_default ENUM('0','1') DEFAULT '0' NOT NULL;
ALTER TABLE contrexx_access_user_attribute_value DROP FOREIGN KEY FK_B0DEA323A76ED395;
ALTER TABLE contrexx_access_user_attribute_value ADD CONSTRAINT FK_B0DEA323B6E62EFA FOREIGN KEY (attribute_id) REFERENCES contrexx_access_user_attribute (id);

ALTER TABLE contrexx_access_user_attribute_value ADD CONSTRAINT FK_B0DEA323A76ED395A76ED395A76ED395A76ED395 FOREIGN KEY (user_id) REFERENCES contrexx_access_users (id);
CREATE INDEX IDX_B0DEA323B6E62EFA ON contrexx_access_user_attribute_value (attribute_id);

/*Migrate core attribute to user attribute*/
ALTER TABLE contrexx_access_user_core_attribute ADD is_default ENUM('0','1') DEFAULT '1' NOT NULL;
INSERT INTO `contrexx_access_user_attribute`(`mandatory`, `sort_type`, `order_id`, `access_special`, `access_id`, `read_access_id`, `is_default`) SELECT `mandatory`, `sort_type`, `order_id`, `access_special`, `access_id`, `read_access_id`, `is_default` FROM `contrexx_access_user_core_attribute`;

/*Migrate user profile to user attribute*/
ALTER TABLE contrexx_access_user_attribute ADD tmp_name TEXT;

INSERT INTO contrexx_access_user_attribute (tmp_name)
SELECT COLUMN_NAME
FROM INFORMATION_SCHEMA.COLUMNS
WHERE table_name = 'contrexx_access_user_profile';

/*
DELETE FROM `contrexx_access_user_attribute` WHERE `contrexx_access_user_attribute`.`tmp_name` = 'user_id'; 

INSERT INTO `contrexx_access_user_attribute_value`(`user_id`) SELECT `user_id` FROM `contrexx_access_user_profile`;

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) SELECT `id`, `tmp_name` FROM `contrexx_access_user_attribute`;*/

ALTER TABLE contrexx_access_user_profile ADD tmp_name TEXT;

ALTER TABLE contrexx_access_user_attribute_value ADD tmp_name TEXT;

ALTER TABLE `contrexx_access_user_attribute_value` CHANGE `attribute_id` `attribute_id` INT(11) NULL;

UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'gender';

ALTER TABLE `contrexx_access_user_attribute_value` DROP FOREIGN KEY `FK_B0DEA323B6E62EFA`;

ALTER TABLE `contrexx_access_user_attribute_value` DROP PRIMARY KEY, ADD PRIMARY KEY (`user_id`, `history_id`) USING BTREE;

ALTER TABLE `contrexx_access_user_attribute_value` DROP PRIMARY KEY, ADD PRIMARY KEY (`history_id`) USING BTREE;

ALTER TABLE contrexx_access_user_attribute_value DROP FOREIGN KEY FK_B0DEA323A76ED395A76ED395A76ED395A76ED395;

ALTER TABLE `contrexx_access_user_attribute_value` DROP PRIMARY KEY;


INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`, `user_id`, `value`) SELECT `tmp_name`, `user_id`, `gender` FROM `contrexx_access_user_profile`;

UPDATE `contrexx_access_user_attribute_value` SET `attribute_id` = (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'gender') WHERE `tmp_name` = 'gender';


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'title';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`, `user_id`, `value`) SELECT `tmp_name`, `user_id`, `title` FROM `contrexx_access_user_profile`;

UPDATE `contrexx_access_user_attribute_value` SET `attribute_id` = (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'title') WHERE `tmp_name` = 'title';


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'designation';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`, `user_id`, `value`) SELECT `tmp_name`, `user_id`, `title` FROM `contrexx_access_user_profile`;

UPDATE `contrexx_access_user_attribute_value` SET `attribute_id` = (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'designation') WHERE `tmp_name` = 'designation';


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'firstname';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`, `user_id`, `value`) SELECT `tmp_name`, `user_id`, `firstname` FROM `contrexx_access_user_profile`;

UPDATE `contrexx_access_user_attribute_value` SET `attribute_id` = (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'firstname') WHERE `tmp_name` = 'firstname';


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'lastname';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`, `user_id`, `value`) SELECT `tmp_name`, `user_id`, `lastname` FROM `contrexx_access_user_profile`;

UPDATE `contrexx_access_user_attribute_value` SET `attribute_id` = (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'lastname') WHERE `tmp_name` = 'lastname';


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'company';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`, `user_id`, `value`) SELECT `tmp_name`, `user_id`, `company` FROM `contrexx_access_user_profile`;

UPDATE `contrexx_access_user_attribute_value` SET `attribute_id` = (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'company') WHERE `tmp_name` = 'company';


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'address';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`, `user_id`, `value`) SELECT `tmp_name`, `user_id`, `address` FROM `contrexx_access_user_profile`;

UPDATE `contrexx_access_user_attribute_value` SET `attribute_id` = (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'address') WHERE `tmp_name` = 'address';


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'city';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`, `user_id`, `value`) SELECT `tmp_name`, `user_id`, `city` FROM `contrexx_access_user_profile`;

UPDATE `contrexx_access_user_attribute_value` SET `attribute_id` = (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'city') WHERE `tmp_name` = 'city';


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'zip';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`, `user_id`, `value`) SELECT `tmp_name`, `user_id`, `zip` FROM `contrexx_access_user_profile`;

UPDATE `contrexx_access_user_attribute_value` SET `attribute_id` = (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'zip') WHERE `tmp_name` = 'zip';


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'country';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`, `user_id`, `value`) SELECT `tmp_name`, `user_id`, `country` FROM `contrexx_access_user_profile`;

UPDATE `contrexx_access_user_attribute_value` SET `attribute_id` = (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'country') WHERE `tmp_name` = 'country';


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'phone_office';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`, `user_id`, `value`) SELECT `tmp_name`, `user_id`, `phone_office` FROM `contrexx_access_user_profile`;

UPDATE `contrexx_access_user_attribute_value` SET `attribute_id` = (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'phone_office') WHERE `tmp_name` = 'phone_office';


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'phone_private';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`, `user_id`, `value`) SELECT `tmp_name`, `user_id`, `phone_private` FROM `contrexx_access_user_profile`;

UPDATE `contrexx_access_user_attribute_value` SET `attribute_id` = (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'phone_private') WHERE `tmp_name` = 'phone_private';


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'phone_mobile';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`, `user_id`, `value`) SELECT `tmp_name`, `user_id`, `phone_mobile` FROM `contrexx_access_user_profile`;

UPDATE `contrexx_access_user_attribute_value` SET `attribute_id` = (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'phone_mobile') WHERE `tmp_name` = 'phone_mobile';


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'phone_fax';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`, `user_id`, `value`) SELECT `tmp_name`, `user_id`, `phone_fax` FROM `contrexx_access_user_profile`;

UPDATE `contrexx_access_user_attribute_value` SET `attribute_id` = (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'phone_fax') WHERE `tmp_name` = 'phone_fax';


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'birthday';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`, `user_id`, `value`) SELECT `tmp_name`, `user_id`, `birthday` FROM `contrexx_access_user_profile`;

UPDATE `contrexx_access_user_attribute_value` SET `attribute_id` = (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'birthday') WHERE `tmp_name` = 'birthday';


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'website';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`, `user_id`, `value`) SELECT `tmp_name`, `user_id`, `website` FROM `contrexx_access_user_profile`;

UPDATE `contrexx_access_user_attribute_value` SET `attribute_id` = (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'website') WHERE `tmp_name` = 'website';


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'profession';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`, `user_id`, `value`) SELECT `tmp_name`, `user_id`, `profession` FROM `contrexx_access_user_profile`;

UPDATE `contrexx_access_user_attribute_value` SET `attribute_id` = (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'profession') WHERE `tmp_name` = 'profession';


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'interests';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`, `user_id`, `value`) SELECT `tmp_name`, `user_id`, `interests` FROM `contrexx_access_user_profile`;

UPDATE `contrexx_access_user_attribute_value` SET `attribute_id` = (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'interests') WHERE `tmp_name` = 'interests';


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'signature';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`, `user_id`, `value`) SELECT `tmp_name`, `user_id`, `signature` FROM `contrexx_access_user_profile`;

UPDATE `contrexx_access_user_attribute_value` SET `attribute_id` = (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'signature') WHERE `tmp_name` = 'signature';


UPDATE `contrexx_access_user_profile` SET `tmp_name` = 'picture';

INSERT INTO `contrexx_access_user_attribute_value`(`tmp_name`, `user_id`, `value`) SELECT `tmp_name`, `user_id`, `picture` FROM `contrexx_access_user_profile`;

UPDATE `contrexx_access_user_attribute_value` SET `attribute_id` = (SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'picture') WHERE `tmp_name` = 'picture';

ALTER TABLE contrexx_access_user_attribute_value ADD CONSTRAINT FK_B0DEA323B6E62EFA FOREIGN KEY (attribute_id) REFERENCES contrexx_access_user_attribute (id);
ALTER TABLE contrexx_access_user_attribute_value ADD CONSTRAINT FK_B0DEA323A76ED395A76ED395A76ED395A76ED395 FOREIGN KEY (user_id) REFERENCES contrexx_access_users (id);

/*Insert attribute name*/
INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES((SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'gender'), 'gender');

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES((SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'title'), 'title');

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES((SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'designation'), 'designation');

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES((SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'firstname'), 'firstname');

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES((SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'lastname'), 'lastname');

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES((SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'company'), 'company');

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES((SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'address'), 'address');

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES((SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'city'), 'city');

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES((SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'country'), 'zip');

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES((SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'phone_office'), 'phone_office');

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES((SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'phone_private'), 'phone_private');

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES((SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'phone_mobile'), 'phone_mobile');

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES((SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'phone_fax'), 'phone_fax');

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES((SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'birthday'), 'birthday');

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES((SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'website'), 'website');

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES((SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'profession'), 'profession');

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES((SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'interests'), 'interests');

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES((SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'signature'), 'signature');

INSERT INTO `contrexx_access_user_attribute_name`(`attribute_id`, `name`) VALUES((SELECT `id` FROM `contrexx_access_user_attribute` WHERE `tmp_name` = 'picture'), 'picture');

UPDATE `contrexx_access_user_attribute_value` SET `value` = 'Sehr geehrte Frau' WHERE `value` = 1 AND `tmp_name` = 'title';

UPDATE `contrexx_access_user_attribute_value` SET `value` = 'Sehr geehrter Herr' WHERE `value` = 2 AND `tmp_name` = 'title';

UPDATE `contrexx_access_user_attribute_value` SET `value` = 'Dear Ms' WHERE `value` = 3 AND `tmp_name` = 'title';

UPDATE `contrexx_access_user_attribute_value` SET `value` = 'Dear Mr' WHERE `value` = 4 AND `tmp_name` = 'title';

UPDATE `contrexx_access_user_attribute_value` SET `value` = 'Madame' WHERE `value` = 5 AND `tmp_name` = 'title';

UPDATE `contrexx_access_user_attribute_value` SET `value` = 'Monsieur' WHERE `value` = 6 AND `tmp_name` = 'title';

ALTER TABLE contrexx_access_user_attribute_value DROP tmp_name, CHANGE attribute_id attribute_id INT NOT NULL, ADD PRIMARY KEY (attribute_id, user_id, history_id);

/*Drop tables*/
ALTER TABLE `contrexx_access_user_attribute` DROP `tmp_name`;

DROP TABLE contrexx_access_user_profile;
DROP TABLE contrexx_access_user_title;
DROP TABLE contrexx_access_user_core_attribute;

/*View for user title*/
CREATE VIEW `contrexx_access_user_title` AS SELECT user.id, value.value, 0 as order_id FROM contrexx_access_users AS user
JOIN contrexx_access_user_attribute_value as value on value.user_id = user.id
JOIN `contrexx_access_user_attribute_name` AS `name`
  ON `name`.`attribute_id`=`value`.`attribute_id`
  WHERE `name`.`name` = 'title';

/*View for user core attribute*/
CREATE VIEW `contrexx_access_user_core_attribute` AS SELECT `mandatory`, `sort_type`, `order_id`, `access_special`, `access_id`, `read_access_id`
FROM `contrexx_access_user_attribute`
WHERE `is_default` = '1';

/*View for user profile*/
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
