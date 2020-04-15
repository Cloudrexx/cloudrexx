ALTER TABLE contrexx_access_user_attribute
    ADD is_default TINYINT(1) DEFAULT '0' NOT NULL,
    ADD tmp_name TEXT;

/** Migrate user profile to user attribute **/
INSERT INTO `contrexx_access_user_attribute`
    (`tmp_name`, `type`, `order_id`, `access_id`, `read_access_id`, `is_default`)
    VALUES
        ('picture',             'image',       1,  0, 0, 1),
        ('gender',              'menu',        2,  0, 0, 1),
        ('title',               'menu',        3,  0, 0, 1),
        ('designation',         'text',        4,  0, 0, 1),
        ('firstname',           'text',        5,  0, 0, 1),
        ('lastname',            'text',        6,  0, 0, 1),
        ('company',             'text',        7,  0, 0, 1),
        ('address',             'text',        8,  0, 0, 1),
        ('city',                'text',        9,  0, 0, 1),
        ('country',             'menu',        10, 0, 0, 1),
        ('zip',                 'text',        11, 0, 0, 1),
        ('phone_office',        'text',        12, 0, 0, 1),
        ('phone_private',       'text',        13, 0, 0, 1),
        ('phone_mobile',        'text',        14, 0, 0, 1),
        ('phone_fax',           'text',        15, 0, 0, 1),
        ('birthday',            'date',        16, 0, 0, 1),
        ('website',             'uri',         17, 0, 0, 1),
        ('profession',          'text',        18, 0, 0, 1),
        ('interests',           'textarea',    19, 0, 0, 1),
        ('signature',           'textarea',    20, 0, 0, 1),
        ('gender_undefined',    'menu_option', 21, 0, 0, 1),
        ('gender_male',         'menu_option', 22, 0, 0, 1),
        ('gender_female',       'menu_option', 23, 0, 0, 1);

/** Define attribute keys as variables **/
SELECT @picture := id FROM contrexx_access_user_attribute WHERE tmp_name = 'picture';
SELECT @gender := id FROM contrexx_access_user_attribute WHERE tmp_name = 'gender';
SELECT @title := id FROM contrexx_access_user_attribute WHERE tmp_name = 'title';
SELECT @designation := id FROM contrexx_access_user_attribute WHERE tmp_name = 'designation';
SELECT @firstname := id FROM contrexx_access_user_attribute WHERE tmp_name = 'firstname';
SELECT @lastname := id FROM contrexx_access_user_attribute WHERE tmp_name = 'lastname';
SELECT @company := id FROM contrexx_access_user_attribute WHERE tmp_name = 'company';
SELECT @address := id FROM contrexx_access_user_attribute WHERE tmp_name = 'address';
SELECT @city := id FROM contrexx_access_user_attribute WHERE tmp_name = 'city';
SELECT @country := id FROM contrexx_access_user_attribute WHERE tmp_name = 'country';
SELECT @zip := id FROM contrexx_access_user_attribute WHERE tmp_name = 'zip';
SELECT @phone_office := id FROM contrexx_access_user_attribute WHERE tmp_name = 'phone_office';
SELECT @phone_private := id FROM contrexx_access_user_attribute WHERE tmp_name = 'phone_private';
SELECT @phone_mobile := id FROM contrexx_access_user_attribute WHERE tmp_name = 'phone_mobile';
SELECT @phone_fax := id FROM contrexx_access_user_attribute WHERE tmp_name = 'phone_fax';
SELECT @birthday := id FROM contrexx_access_user_attribute WHERE tmp_name = 'birthday';
SELECT @website := id FROM contrexx_access_user_attribute WHERE tmp_name = 'website';
SELECT @profession := id FROM contrexx_access_user_attribute WHERE tmp_name = 'profession';
SELECT @interests := id FROM contrexx_access_user_attribute WHERE tmp_name = 'interests';
SELECT @signature := id FROM contrexx_access_user_attribute WHERE tmp_name = 'signature';
SELECT @gender_undefined := id FROM contrexx_access_user_attribute WHERE tmp_name = 'gender_undefined';
SELECT @gender_female := id FROM contrexx_access_user_attribute WHERE tmp_name = 'gender_female';
SELECT @gender_male := id FROM contrexx_access_user_attribute WHERE tmp_name = 'gender_male';

/** Shift order id **/
SELECT @order_offset := COUNT(1) FROM `contrexx_access_user_attribute` WHERE `is_default` = 1;
UPDATE `contrexx_access_user_attribute` SET `order_id` = `order_id` + @order_offset WHERE `is_default` = 0;

/** Migrate core attribute to user attribute **/
UPDATE contrexx_access_user_attribute AS attr
    LEFT JOIN contrexx_access_user_core_attribute AS core ON core.id = attr.tmp_name
    SET attr.mandatory = core.mandatory,
        attr.sort_type = core.sort_type,
        attr.access_special = core.access_special,
        attr.access_id = core.access_id,
        attr.read_access_id = core.read_access_id
    WHERE attr.is_default = 1;

/** Migrate normal user profile names to user attribute names **/
INSERT INTO `contrexx_access_user_attribute_name` (`attribute_id`, `name`)
    SELECT `id`, `tmp_name` FROM `contrexx_access_user_attribute` WHERE `is_default` = '1';

/** Migrate normal user profile values to user attribute value **/
INSERT INTO contrexx_access_user_attribute_value (`attribute_id`, `user_id`, `value`)
    SELECT @picture, `user_id`, `picture` FROM `contrexx_access_user_profile`;

INSERT INTO contrexx_access_user_attribute_value (`attribute_id`, `user_id`, `value`)
    SELECT @designation, `user_id`, `designation` FROM `contrexx_access_user_profile`;

INSERT INTO contrexx_access_user_attribute_value (`attribute_id`, `user_id`, `value`)
    SELECT @firstname, `user_id`, `firstname` FROM `contrexx_access_user_profile`;

INSERT INTO contrexx_access_user_attribute_value (`attribute_id`, `user_id`, `value`)
    SELECT @lastname, `user_id`, `lastname` FROM `contrexx_access_user_profile`;

INSERT INTO contrexx_access_user_attribute_value (`attribute_id`, `user_id`, `value`)
    SELECT @company, `user_id`, `company` FROM `contrexx_access_user_profile`;

INSERT INTO contrexx_access_user_attribute_value (`attribute_id`, `user_id`, `value`)
    SELECT @address, `user_id`, `address` FROM `contrexx_access_user_profile`;

INSERT INTO contrexx_access_user_attribute_value (`attribute_id`, `user_id`, `value`)
    SELECT @city, `user_id`, `city` FROM `contrexx_access_user_profile`;

INSERT INTO contrexx_access_user_attribute_value (`attribute_id`, `user_id`, `value`)
    SELECT @country, `user_id`, `country` FROM `contrexx_access_user_profile`;

INSERT INTO contrexx_access_user_attribute_value (`attribute_id`, `user_id`, `value`)
    SELECT @zip, `user_id`, `zip` FROM `contrexx_access_user_profile`;

INSERT INTO contrexx_access_user_attribute_value (`attribute_id`, `user_id`, `value`)
    SELECT @phone_office, `user_id`, `phone_office` FROM `contrexx_access_user_profile`;

INSERT INTO contrexx_access_user_attribute_value (`attribute_id`, `user_id`, `value`)
    SELECT @phone_private, `user_id`, `phone_private` FROM `contrexx_access_user_profile`;

INSERT INTO contrexx_access_user_attribute_value (`attribute_id`, `user_id`, `value`)
    SELECT @phone_mobile, `user_id`, `phone_mobile` FROM `contrexx_access_user_profile`;

INSERT INTO contrexx_access_user_attribute_value (`attribute_id`, `user_id`, `value`)
    SELECT @phone_fax, `user_id`, `phone_fax` FROM `contrexx_access_user_profile`;

INSERT INTO contrexx_access_user_attribute_value (`attribute_id`, `user_id`, `value`)
    SELECT @birthday, `user_id`, `birthday` FROM `contrexx_access_user_profile`;

INSERT INTO contrexx_access_user_attribute_value (`attribute_id`, `user_id`, `value`)
    SELECT @website, `user_id`, `website` FROM `contrexx_access_user_profile`;

INSERT INTO contrexx_access_user_attribute_value (`attribute_id`, `user_id`, `value`)
    SELECT @profession, `user_id`, `profession` FROM `contrexx_access_user_profile`;

INSERT INTO contrexx_access_user_attribute_value (`attribute_id`, `user_id`, `value`)
    SELECT @interests, `user_id`, `interests` FROM `contrexx_access_user_profile`;

INSERT INTO contrexx_access_user_attribute_value (`attribute_id`, `user_id`, `value`)
    SELECT @signature, `user_id`, `signature` FROM `contrexx_access_user_profile`;

/** SPECIAL CASE GENDER **/
/** Set parent id for gender children **/
UPDATE contrexx_access_user_attribute
    SET parent_id = @gender
    WHERE tmp_name = 'gender_female' OR tmp_name = 'gender_male' OR tmp_name = 'gender_undefined';

/** Migrate gender values to user attribute values **/
INSERT INTO contrexx_access_user_attribute_value (`attribute_id`, `user_id`, `value`)
    SELECT @gender, `user_id`, @gender_female FROM `contrexx_access_user_profile` WHERE `gender` = 'gender_female';

INSERT INTO contrexx_access_user_attribute_value (`attribute_id`, `user_id`, `value`)
    SELECT @gender, `user_id`, @gender_male FROM `contrexx_access_user_profile` WHERE `gender` = 'gender_male';

INSERT INTO contrexx_access_user_attribute_value (`attribute_id`, `user_id`, `value`)
    SELECT @gender, `user_id`, @gender_undefined FROM `contrexx_access_user_profile` WHERE `gender` = 'gender_undefined';

/** SPECIAL CASE TITLE **/
/** Migrate title attributes to user attributes **/
INSERT INTO contrexx_access_user_attribute
    (`tmp_name`, `parent_id`, `type`, `order_id`, `access_id`, `read_access_id`, `is_default`)
    SELECT CONCAT('title_', `id`), @title, 'menu_option', `order_id`, 0, 0, 1  FROM `contrexx_access_user_title`;

/** Migrate title names to user attribute names **/
INSERT INTO contrexx_access_user_attribute_name (`attribute_id`, `name`)
    SELECT
        (SELECT `a`.`id` FROM `contrexx_access_user_attribute` AS `a` WHERE `tmp_name` = CONCAT('title_', `t`.`id`) LIMIT 1),
        `t`.`title`
    FROM `contrexx_access_user_title` AS `t`;

/** Migrate title values to user attribute values **/
INSERT INTO contrexx_access_user_attribute_value (`attribute_id`, `user_id`, `value`)
    SELECT
        @title,
        `user_id`,
        (SELECT `a`.`id` FROM `contrexx_access_user_attribute` AS `a` WHERE `tmp_name` = CONCAT('title_', `p`.`title`) LIMIT 1)
    FROM `contrexx_access_user_profile` AS `p`;

/** Delete all user attribute values which do not belong to any user **/
DELETE v FROM `contrexx_access_user_attribute_value` AS v
    LEFT JOIN contrexx_access_users as u ON u.id = v.user_id
    LEFT JOIN contrexx_access_user_attribute as a ON a.id = v.attribute_id
    WHERE u.id IS NULL OR a.id IS NULL;

/** Correct user attribute parent id **/
UPDATE `contrexx_access_user_attribute` SET `parent_id`= null WHERE parent_id = 0;

/** Remove old user-group relations **/
DELETE g FROM contrexx_access_rel_user_group AS g
    LEFT JOIN contrexx_access_users AS u ON u.id = g.user_id
    WHERE u.id IS NULL;

/** Drop keys **/
ALTER TABLE contrexx_access_user_profile
    DROP FOREIGN KEY IF EXISTS FK_959DBF6CA76ED395,
    DROP FOREIGN KEY IF EXISTS FK_959DBF6C2B36786B;

ALTER TABLE contrexx_access_rel_user_group
    DROP FOREIGN KEY IF EXISTS FK_401DFD43A76ED395,
    DROP FOREIGN KEY IF EXISTS `FK_401DFD43FE54D947`;

ALTER TABLE contrexx_access_user_attribute
	DROP FOREIGN KEY IF EXISTS `FK_D97727BE727ACA70`;

ALTER TABLE contrexx_access_user_attribute_name DROP PRIMARY KEY;

ALTER TABLE contrexx_access_user_attribute_name
    DROP FOREIGN KEY IF EXISTS `FK_90502F6CB6E62EFA`;

ALTER TABLE contrexx_access_user_attribute_value
    DROP PRIMARY KEY;

ALTER TABLE contrexx_access_user_attribute_value
    DROP FOREIGN KEY IF EXISTS `FK_B0DEA323B6E62EFA`,
    DROP FOREIGN KEY IF EXISTS FK_B0DEA323A76ED395;

/** Alter table access_users **/
ALTER TABLE contrexx_access_users
    CHANGE id id INT UNSIGNED AUTO_INCREMENT NOT NULL,
    CHANGE email email VARCHAR(255) NOT NULL,
    CHANGE auth_token auth_token VARCHAR(32) DEFAULT '' NOT NULL,
    CHANGE auth_token_timeout auth_token_timeout INT UNSIGNED DEFAULT 0 NOT NULL,
    CHANGE regdate regdate INT UNSIGNED DEFAULT 0 NOT NULL,
    CHANGE expiration expiration INT UNSIGNED DEFAULT 0 NOT NULL,
    CHANGE validity validity INT UNSIGNED DEFAULT 0 NOT NULL,
    CHANGE last_auth last_auth INT UNSIGNED DEFAULT 0 NOT NULL,
    CHANGE last_activity last_activity INT UNSIGNED DEFAULT 0 NOT NULL,
    CHANGE last_auth_status last_auth_status SMALLINT DEFAULT '1' NOT NULL,
    CHANGE frontend_lang_id frontend_lang_id INT UNSIGNED DEFAULT 0 NOT NULL,
    CHANGE backend_lang_id backend_lang_id INT UNSIGNED DEFAULT 0 NOT NULL,
    CHANGE primary_group primary_group INT UNSIGNED DEFAULT 0 NOT NULL,
    CHANGE restore_key_time restore_key_time INT UNSIGNED DEFAULT 0 NOT NULL,
    CHANGE active active TINYINT(1) DEFAULT '0' NOT NULL,
    CHANGE u2u_active u2u_active TINYINT(1) DEFAULT '1' NOT NULL;

ALTER TABLE contrexx_access_user_attribute
	CHANGE id id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	CHANGE parent_id parent_id INT UNSIGNED DEFAULT NULL,
	CHANGE mandatory mandatory TINYINT(1) DEFAULT '0' NOT NULL;

ALTER TABLE contrexx_access_user_attribute_name
	CHANGE attribute_id attribute_id INT UNSIGNED NOT NULL;

ALTER TABLE contrexx_access_user_attribute_value
    CHANGE history_id history_id INT UNSIGNED DEFAULT 0 NOT NULL,
	CHANGE attribute_id attribute_id INT UNSIGNED NOT NULL,
	CHANGE user_id user_id INT UNSIGNED NOT NULL;

ALTER TABLE contrexx_access_rel_user_group
    CHANGE user_id user_id INT UNSIGNED NOT NULL,
    CHANGE group_id group_id INT UNSIGNED NOT NULL;

ALTER TABLE contrexx_access_user_groups
    CHANGE group_id group_id INT UNSIGNED AUTO_INCREMENT NOT NULL,
    CHANGE is_active is_active TINYINT(1) DEFAULT '1' NOT NULL;

/** Add keys **/
ALTER TABLE `contrexx_access_user_attribute`ADD CONSTRAINT `FK_D97727BE727ACA70`
	FOREIGN KEY (`parent_id`) REFERENCES `contrexx_access_user_attribute`(`id`);

ALTER TABLE `contrexx_access_user_attribute_name`ADD CONSTRAINT `FK_90502F6CB6E62EFA`
	FOREIGN KEY (`attribute_id`) REFERENCES `contrexx_access_user_attribute`(`id`);

ALTER TABLE `contrexx_access_user_attribute_value`
    ADD CONSTRAINT `FK_B0DEA323B6E62EFA`
	    FOREIGN KEY (`attribute_id`) REFERENCES `contrexx_access_user_attribute` (`id`),
    ADD CONSTRAINT `FK_B0DEA323A76ED395A76ED395A76ED395A76ED395`
	    FOREIGN KEY (`user_id`) REFERENCES `contrexx_access_users` (`id`);

ALTER TABLE contrexx_access_rel_user_group
    ADD CONSTRAINT FK_401DFD43FE54D947 FOREIGN KEY (group_id) REFERENCES contrexx_access_user_groups (group_id),
    ADD CONSTRAINT FK_401DFD43A76ED395 FOREIGN KEY (user_id) REFERENCES contrexx_access_users (id);

ALTER TABLE contrexx_access_user_attribute_name ADD PRIMARY KEY (lang_id, attribute_id);
ALTER TABLE contrexx_access_user_attribute_value ADD PRIMARY KEY (history_id, attribute_id, user_id);

/** Add indexes **/
CREATE UNIQUE INDEX UNIQ_7CD32875E7927C74 ON contrexx_access_users (email);
CREATE INDEX IDX_B0DEA323B6E62EFA ON contrexx_access_user_attribute_value (attribute_id);

/** Drop old tables and attributes **/
DROP TABLE contrexx_access_user_title;
DROP TABLE contrexx_access_user_core_attribute;
DROP TABLE contrexx_access_user_profile;
ALTER TABLE `contrexx_access_user_attribute` DROP `tmp_name`;