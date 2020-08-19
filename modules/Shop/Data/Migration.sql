/** Create Tables **/
CREATE TABLE contrexx_module_shop_rel_category_pricelist (
  category_id INT UNSIGNED NOT NULL,
  pricelist_id INT UNSIGNED NOT NULL,
  INDEX IDX_B56E91A112469DE2 (category_id),
  INDEX IDX_B56E91A189045958 (pricelist_id),
  PRIMARY KEY(category_id, pricelist_id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

CREATE TABLE contrexx_module_shop_rel_category_product (
  category_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  INDEX IDX_DA4CA51112469DE2 (category_id),
  INDEX IDX_DA4CA5114584665A (product_id),
  PRIMARY KEY(category_id, product_id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

CREATE TABLE contrexx_module_shop_rel_product_user_group (
  product_id INT UNSIGNED NOT NULL,
  usergroup_id INT NOT NULL,
  INDEX IDX_32A4494A4584665A (product_id),
  INDEX IDX_32A4494AD2112630 (usergroup_id),
  PRIMARY KEY(product_id, usergroup_id)
) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;

ALTER TABLE contrexx_module_shop_pricelists ADD all_categories TINYINT NOT NULL DEFAULT 0;


/** Core Text **/
ALTER TABLE contrexx_module_shop_manufacturer
  ADD `uri` VARCHAR(255) DEFAULT '' NOT NULL,
  ADD `name` VARCHAR(255) DEFAULT '' NOT NULL;

ALTER TABLE contrexx_module_shop_products
  ADD `uri` VARCHAR(255) DEFAULT '' NOT NULL,
  ADD `short` TEXT NOT NULL,
  ADD `long` TEXT NOT NULL,
  ADD `name` VARCHAR(255) DEFAULT '' NOT NULL,
  ADD `keys` TEXT NOT NULL,
  ADD `code` VARCHAR(255) DEFAULT '' NOT NULL;

ALTER TABLE contrexx_module_shop_categories
  ADD `name` VARCHAR(255) DEFAULT '' NOT NULL,
  ADD `description` TEXT NOT NULL,
  ADD `short_description` TEXT NOT NULL;

ALTER TABLE contrexx_module_shop_discountgroup_count_name
  ADD `unit` VARCHAR(255) DEFAULT '' NOT NULL,
  ADD `name` VARCHAR(255) DEFAULT '' NOT NULL;

ALTER TABLE contrexx_module_shop_currencies ADD `name` VARCHAR(255) DEFAULT '' NOT NULL;

ALTER TABLE contrexx_module_shop_option ADD `name` VARCHAR(255) DEFAULT '' NOT NULL;

ALTER TABLE contrexx_module_shop_payment ADD `name` VARCHAR(255) DEFAULT '' NOT NULL;

ALTER TABLE contrexx_module_shop_shipper ADD `name` VARCHAR(255) DEFAULT '' NOT NULL;

ALTER TABLE contrexx_module_shop_vat ADD `class` VARCHAR(255) DEFAULT '' NOT NULL;

ALTER TABLE contrexx_module_shop_article_group ADD `name` VARCHAR(255) DEFAULT '' NOT NULL;

ALTER TABLE contrexx_module_shop_attribute ADD `name` VARCHAR(255) DEFAULT '' NOT NULL;

ALTER TABLE contrexx_module_shop_zones ADD `name` VARCHAR(255) DEFAULT '' NOT NULL;

ALTER TABLE contrexx_module_shop_customer_group ADD `name` VARCHAR(255) DEFAULT '' NOT NULL;


/** Structural adjustments  **/
ALTER TABLE contrexx_module_shop_orders
  CHANGE customer_id customer_id INT DEFAULT NULL,
  CHANGE date_time date_time DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
  CHANGE lang_id lang_id INT DEFAULT 0 NOT NULL,
  CHANGE modified_on modified_on DATETIME DEFAULT NULL,
  CHANGE payment_id payment_id INT UNSIGNED DEFAULT NULL,
  CHANGE status status INT UNSIGNED DEFAULT 0 NOT NULL;

ALTER TABLE contrexx_module_shop_products
  CHANGE date_start date_start DATETIME DEFAULT NULL,
  CHANGE date_end date_end DATETIME DEFAULT NULL;

ALTER TABLE contrexx_module_shop_attribute CHANGE `type` `type` INT(1) UNSIGNED DEFAULT 1 NOT NULL;

UPDATE `contrexx_module_shop_products` SET `date_start` = NULL WHERE `date_start` = '0000-00-00 00:00:00';
UPDATE `contrexx_module_shop_products` SET `date_end` = NULL WHERE `date_end` = '0000-00-00 00:00:00';

ALTER TABLE contrexx_module_shop_pricelists CHANGE lang_id lang_id INT DEFAULT 0 NOT NULL;

ALTER TABLE contrexx_module_shop_rel_payment
  CHANGE zone_id zone_id INT UNSIGNED NOT NULL,
  CHANGE payment_id payment_id INT UNSIGNED NOT NULL;

ALTER TABLE contrexx_module_shop_rel_shipper DROP PRIMARY KEY;
ALTER TABLE contrexx_module_shop_rel_shipper
  CHANGE shipper_id shipper_id INT UNSIGNED NOT NULL,
  CHANGE zone_id zone_id INT UNSIGNED NOT NULL;


/** Drop Primary Keys **/
ALTER TABLE contrexx_module_shop_discount_coupon DROP PRIMARY KEY;
ALTER TABLE contrexx_module_shop_rel_countries DROP PRIMARY KEY;
ALTER TABLE contrexx_module_shop_rel_customer_coupon DROP PRIMARY KEY;
ALTER TABLE contrexx_module_shop_rel_category_pricelist DROP PRIMARY KEY;


/** To insert relations without problems **/
ALTER TABLE contrexx_module_shop_discount_coupon
  ADD id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  CHANGE customer_id customer_id INT NULL,
  CHANGE payment_id payment_id INT UNSIGNED DEFAULT NULL,
  CHANGE product_id product_id INT UNSIGNED DEFAULT NULL;
UPDATE `contrexx_module_shop_discount_coupon` SET `payment_id`=NULL WHERE `payment_id`= 0;
UPDATE `contrexx_module_shop_discount_coupon` SET `product_id`=NULL WHERE `product_id`= 0;
UPDATE `contrexx_module_shop_discount_coupon` SET `customer_id`=NULL WHERE `customer_id`= 0;

UPDATE `contrexx_module_shop_products` SET `article_id`=NULL WHERE `article_id`= 0;
UPDATE `contrexx_module_shop_products` SET `group_id`=NULL WHERE `group_id`= 0;

ALTER TABLE contrexx_module_shop_categories CHANGE parent_id parent_id INT UNSIGNED DEFAULT NULL;
UPDATE `contrexx_module_shop_categories` SET `parent_id`=NULL WHERE `parent_id`= 0;

ALTER TABLE contrexx_module_shop_rel_customer_coupon
  ADD id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
  CHANGE customer_id customer_id INT DEFAULT NULL,
  CHANGE order_id order_id INT UNSIGNED DEFAULT NULL;


/** Correct data **/
UPDATE contrexx_module_shop_orders AS o
LEFT JOIN contrexx_module_shop_payment as p ON p.id = o.payment_id
SET payment_id = null  WHERE p.id IS NULL;

UPDATE contrexx_module_shop_orders AS o
LEFT JOIN contrexx_access_users as u ON u.id = o.customer_id
SET customer_id = null WHERE customer_id = 0 OR u.id IS NULL;

UPDATE contrexx_module_shop_products AS p
LEFT JOIN contrexx_module_shop_manufacturer as m ON m.id = p.manufacturer_id
SET manufacturer_id = null WHERE manufacturer_id = 0 OR m.id IS NULL;

DELETE a FROM `contrexx_module_shop_rel_product_attribute` AS a
LEFT JOIN contrexx_module_shop_products as p ON p.id = a.product_id
WHERE p.id IS NULL;

ALTER TABLE contrexx_module_shop_order_items
  CHANGE product_id product_id INT UNSIGNED DEFAULT NULL;
DELETE i FROM `contrexx_module_shop_order_items` AS i
LEFT JOIN contrexx_module_shop_orders as o ON o.id = i.order_id
WHERE o.id IS NULL;
UPDATE contrexx_module_shop_order_items AS i
LEFT JOIN contrexx_module_shop_products as p ON p.id = i.product_id
SET product_id = null WHERE product_id = 0 OR p.id IS NULL;

DELETE c FROM `contrexx_module_shop_rel_customer_coupon` AS c
LEFT JOIN contrexx_module_shop_orders as o ON o.id = c.order_id
WHERE o.id IS NULL;

DELETE o FROM `contrexx_module_shop_rel_payment` AS o
LEFT JOIN contrexx_module_shop_payment as p ON p.id = o.payment_id
WHERE p.id IS NULL;

DELETE a FROM `contrexx_module_shop_order_attributes` AS a
LEFT JOIN contrexx_module_shop_order_items as i ON i.id = a.item_id
WHERE i.id IS NULL;


/** Constraints **/
ALTER TABLE contrexx_module_shop_rel_category_pricelist
  ADD CONSTRAINT FK_B56E91A112469DE2 FOREIGN KEY (category_id) REFERENCES contrexx_module_shop_categories (id);
ALTER TABLE contrexx_module_shop_rel_category_pricelist
  ADD CONSTRAINT FK_B56E91A189045958 FOREIGN KEY (pricelist_id) REFERENCES contrexx_module_shop_pricelists (id);
ALTER TABLE contrexx_module_shop_rel_category_product
  ADD CONSTRAINT FK_DA4CA51112469DE2 FOREIGN KEY (category_id) REFERENCES contrexx_module_shop_categories (id);
ALTER TABLE contrexx_module_shop_rel_category_product
  ADD CONSTRAINT FK_DA4CA5114584665A FOREIGN KEY (product_id) REFERENCES contrexx_module_shop_products (id);

ALTER TABLE contrexx_module_shop_orders
  ADD CONSTRAINT FK_DA286BB1B213FA4 FOREIGN KEY (lang_id) REFERENCES contrexx_core_locale_locale (id);
ALTER TABLE contrexx_module_shop_orders
  ADD CONSTRAINT FK_DA286BB138248176 FOREIGN KEY (currency_id) REFERENCES contrexx_module_shop_currencies (id);
ALTER TABLE contrexx_module_shop_orders
  ADD CONSTRAINT FK_DA286BB17BE036FC FOREIGN KEY (shipment_id) REFERENCES contrexx_module_shop_shipper (id);
ALTER TABLE contrexx_module_shop_orders
  ADD CONSTRAINT FK_DA286BB14C3A3BB FOREIGN KEY (payment_id) REFERENCES contrexx_module_shop_payment (id);
ALTER TABLE contrexx_module_shop_orders
  ADD CONSTRAINT FK_DA286BB19395C3F3 FOREIGN KEY (customer_id) REFERENCES contrexx_access_users (id) ON DELETE SET NULL;

ALTER TABLE contrexx_module_shop_order_attributes
  ADD CONSTRAINT FK_273F59F6126F525E FOREIGN KEY (item_id) REFERENCES contrexx_module_shop_order_items (id);

ALTER TABLE contrexx_module_shop_option
  ADD CONSTRAINT FK_658196EFB6E62EFA FOREIGN KEY (attribute_id) REFERENCES contrexx_module_shop_attribute (id);

ALTER TABLE contrexx_module_shop_products
  ADD CONSTRAINT FK_97F512B7A23B42D FOREIGN KEY (manufacturer_id) REFERENCES contrexx_module_shop_manufacturer (id);
ALTER TABLE contrexx_module_shop_products
  ADD CONSTRAINT FK_97F512B7FE54D947 FOREIGN KEY (group_id)
  REFERENCES contrexx_module_shop_discountgroup_count_name (id);
ALTER TABLE contrexx_module_shop_products
  ADD CONSTRAINT FK_97F512B77294869C FOREIGN KEY (article_id) REFERENCES contrexx_module_shop_article_group (id);
ALTER TABLE contrexx_module_shop_products
  ADD CONSTRAINT FK_97F512B7B5B63A6B FOREIGN KEY (vat_id) REFERENCES contrexx_module_shop_vat (id);

ALTER TABLE contrexx_module_shop_payment
  ADD CONSTRAINT FK_96C3CFFE37BAC19A FOREIGN KEY (processor_id) REFERENCES contrexx_module_shop_payment_processors (id);

ALTER TABLE contrexx_module_shop_rel_product_attribute
  ADD CONSTRAINT FK_E17E240BA7C41D6F FOREIGN KEY (option_id) REFERENCES contrexx_module_shop_option (id);
ALTER TABLE contrexx_module_shop_rel_product_attribute
  ADD CONSTRAINT FK_E17E240B4584665A FOREIGN KEY (product_id) REFERENCES contrexx_module_shop_products (id);

ALTER TABLE contrexx_module_shop_order_items
  ADD CONSTRAINT FK_1D79476B8D9F6D38 FOREIGN KEY (order_id) REFERENCES contrexx_module_shop_orders (id);
ALTER TABLE contrexx_module_shop_order_items
  ADD CONSTRAINT FK_1D79476B4584665A FOREIGN KEY (product_id) REFERENCES contrexx_module_shop_products (id);

ALTER TABLE contrexx_module_shop_discount_coupon
  ADD CONSTRAINT FK_7E70AB1A4C3A3BB FOREIGN KEY (payment_id)
  REFERENCES contrexx_module_shop_payment (id) ON DELETE SET NULL;
ALTER TABLE contrexx_module_shop_discount_coupon
  ADD CONSTRAINT FK_7E70AB1A9395C3F3 FOREIGN KEY (customer_id)
  REFERENCES contrexx_access_users (id) ON DELETE SET NULL;
ALTER TABLE contrexx_module_shop_discount_coupon
  ADD CONSTRAINT FK_7E70AB1A4584665A FOREIGN KEY (product_id)
  REFERENCES contrexx_module_shop_products (id) ON DELETE SET NULL;

ALTER TABLE contrexx_module_shop_discountgroup_count_rate
  ADD CONSTRAINT FK_3F3DD477FE54D947 FOREIGN KEY (group_id)
  REFERENCES contrexx_module_shop_discountgroup_count_name (id);

ALTER TABLE contrexx_module_shop_pricelists
  ADD CONSTRAINT FK_BB867D48B213FA4 FOREIGN KEY (lang_id) REFERENCES contrexx_core_locale_locale (id);

ALTER TABLE contrexx_module_shop_lsv
  ADD CONSTRAINT FK_889921958D9F6D38 FOREIGN KEY (order_id) REFERENCES contrexx_module_shop_orders (id);

ALTER TABLE contrexx_module_shop_rel_customer_coupon
  ADD CONSTRAINT FK_6A7FBE248D9F6D38 FOREIGN KEY (order_id) REFERENCES contrexx_module_shop_orders (id);
ALTER TABLE contrexx_module_shop_rel_customer_coupon
  ADD CONSTRAINT FK_6A7FBE249395C3F3 FOREIGN KEY (customer_id) REFERENCES contrexx_access_users (id) ON DELETE SET NULL;

ALTER TABLE contrexx_module_shop_rel_discount_group
  ADD CONSTRAINT FK_93D6FD61D2919A68 FOREIGN KEY (customer_group_id) REFERENCES contrexx_module_shop_customer_group (id);
ALTER TABLE contrexx_module_shop_rel_discount_group
  ADD CONSTRAINT FK_93D6FD61ABBC2D2C FOREIGN KEY (article_group_id) REFERENCES contrexx_module_shop_article_group (id);

ALTER TABLE contrexx_module_shop_rel_payment
  ADD CONSTRAINT FK_43EB87989F2C3FAB FOREIGN KEY (zone_id) REFERENCES contrexx_module_shop_zones (id);
ALTER TABLE contrexx_module_shop_rel_payment
  ADD CONSTRAINT FK_43EB87984C3A3BB FOREIGN KEY (payment_id) REFERENCES contrexx_module_shop_payment (id);

ALTER TABLE contrexx_module_shop_rel_shipper
  ADD CONSTRAINT FK_87E5C9689F2C3FAB FOREIGN KEY (zone_id) REFERENCES contrexx_module_shop_zones (id);
ALTER TABLE contrexx_module_shop_rel_shipper
  ADD CONSTRAINT FK_87E5C96838459F23 FOREIGN KEY (shipper_id) REFERENCES contrexx_module_shop_shipper (id);

ALTER TABLE contrexx_module_shop_shipment_cost
  ADD CONSTRAINT FK_2329A4538459F23 FOREIGN KEY (shipper_id) REFERENCES contrexx_module_shop_shipper (id);

ALTER TABLE contrexx_module_shop_rel_product_user_group
  ADD CONSTRAINT FK_32A4494A4584665A FOREIGN KEY (product_id) REFERENCES contrexx_module_shop_products (id);
ALTER TABLE contrexx_module_shop_rel_product_user_group
  ADD CONSTRAINT FK_32A4494AD2112630 FOREIGN KEY (usergroup_id) REFERENCES contrexx_access_user_groups (group_id);

ALTER TABLE contrexx_module_shop_rel_countries
  ADD CONSTRAINT FK_C859EA8B9F2C3FAB FOREIGN KEY (zone_id) REFERENCES contrexx_module_shop_zones (id);

ALTER TABLE contrexx_module_shop_categories
  ADD CONSTRAINT FK_A9242624727ACA70 FOREIGN KEY (parent_id) REFERENCES contrexx_module_shop_categories (id);


/** Index **/
CREATE INDEX IDX_DA286BB1B213FA4 ON contrexx_module_shop_orders (lang_id);
CREATE INDEX IDX_DA286BB138248176 ON contrexx_module_shop_orders (currency_id);
CREATE INDEX IDX_DA286BB17BE036FC ON contrexx_module_shop_orders (shipment_id);
CREATE INDEX IDX_DA286BB14C3A3BB ON contrexx_module_shop_orders (payment_id);
CREATE INDEX IDX_DA286BB19395C3F3 ON contrexx_module_shop_orders (customer_id);

CREATE INDEX IDX_658196EFB6E62EFA ON contrexx_module_shop_option (attribute_id);

CREATE INDEX IDX_97F512B7A23B42D ON contrexx_module_shop_products (manufacturer_id);
CREATE INDEX IDX_97F512B7B5B63A6B ON contrexx_module_shop_products (vat_id);

CREATE INDEX IDX_96C3CFFE37BAC19A ON contrexx_module_shop_payment (processor_id);

CREATE INDEX IDX_E17E240B4584665A ON contrexx_module_shop_rel_product_attribute (product_id);
CREATE INDEX IDX_E17E240BA7C41D6F ON contrexx_module_shop_rel_product_attribute (option_id);

CREATE INDEX IDX_1D79476B4584665A ON contrexx_module_shop_order_items (product_id);

CREATE INDEX IDX_7E70AB1A4C3A3BB ON contrexx_module_shop_discount_coupon (payment_id);
CREATE INDEX IDX_7E70AB1A4584665A ON contrexx_module_shop_discount_coupon (product_id);
CREATE INDEX IDX_7E70AB1A9395C3F3 ON contrexx_module_shop_discount_coupon (customer_id);

CREATE INDEX IDX_3F3DD477FE54D947 ON contrexx_module_shop_discountgroup_count_rate (group_id);
CREATE INDEX fk_contrexx_module_shop_discountgroup_count_rate_contrexx_m_idx
  ON contrexx_module_shop_discountgroup_count_rate (count);

CREATE INDEX IDX_BB867D48B213FA4 ON contrexx_module_shop_pricelists (lang_id);

CREATE INDEX IDX_6A7FBE248D9F6D38 ON contrexx_module_shop_rel_customer_coupon (order_id);
CREATE INDEX IDX_6A7FBE249395C3F3 ON contrexx_module_shop_rel_customer_coupon (customer_id);

CREATE INDEX IDX_93D6FD61D2919A68 ON contrexx_module_shop_rel_discount_group (customer_group_id);
CREATE INDEX IDX_93D6FD61ABBC2D2C ON contrexx_module_shop_rel_discount_group (article_group_id);

CREATE INDEX IDX_43EB87989F2C3FAB ON contrexx_module_shop_rel_payment (zone_id);
CREATE INDEX IDX_43EB87984C3A3BB ON contrexx_module_shop_rel_payment (payment_id);

CREATE INDEX IDX_87E5C9689F2C3FAB ON contrexx_module_shop_rel_shipper (zone_id);
CREATE INDEX IDX_87E5C96838459F23 ON contrexx_module_shop_rel_shipper (shipper_id);

CREATE INDEX IDX_2329A4538459F23 ON contrexx_module_shop_shipment_cost (shipper_id);

CREATE INDEX IDX_C859EA8B9F2C3FAB ON contrexx_module_shop_rel_countries (zone_id);

CREATE INDEX IDX_A9242624727ACA70 ON contrexx_module_shop_categories (parent_id);

CREATE UNIQUE INDEX fk_module_shop_currency_unique_idx ON contrexx_module_shop_currencies (code);
CREATE UNIQUE INDEX fk_module_shop_discount_coupon_unique_idx
  ON contrexx_module_shop_discount_coupon (code, customer_id);
CREATE UNIQUE INDEX fk_module_shop_rel_customer_coupon_unique_idx
  ON contrexx_module_shop_rel_customer_coupon (code, customer_id, order_id);


/** Add Primary Keys **/
ALTER TABLE contrexx_module_shop_rel_shipper ADD PRIMARY KEY (zone_id, shipper_id);
ALTER TABLE contrexx_module_shop_rel_countries ADD PRIMARY KEY (zone_id, country_id);
ALTER TABLE contrexx_module_shop_rel_category_pricelist ADD PRIMARY KEY (category_id, pricelist_id);


/** Merge Data **/
INSERT INTO contrexx_module_shop_rel_category_product SELECT
    c.id AS category_id, d.id AS product_id
FROM
    contrexx_module_shop_categories c
    JOIN contrexx_module_shop_products d
        ON d.category_id REGEXP CONCAT('[[:<:]]', c.id, '[[:>:]]');

INSERT INTO contrexx_module_shop_rel_category_pricelist SELECT
    c.id AS category_id, d.id AS pricelist_id
FROM
    contrexx_module_shop_categories c
    JOIN contrexx_module_shop_pricelists d
        ON d.categories REGEXP CONCAT('[[:<:]]', c.id, '[[:>:]]') OR d.categories = '*';

INSERT INTO contrexx_module_shop_rel_product_user_group SELECT
    c.group_id AS usergroup_id, d.id AS product_id
FROM
    contrexx_access_user_groups c
    JOIN contrexx_module_shop_products d
        ON d.usergroup_ids REGEXP CONCAT('[[:<:]]', c.group_id, '[[:>:]]');

UPDATE contrexx_module_shop_pricelists
	SET all_categories = (
      SELECT (
          CASE
          WHEN categories = '*' THEN 1
          ELSE 0 END
      ) AS all_categories
  );

/** Merge core text attributes **/
INSERT INTO `contrexx_translations` (`locale`, `object_class`, `field`, `foreign_key`, `content`)
SELECT `l`.`iso_1` AS locale, CONCAT('Cx\\Modules\\Shop\\Model\\Entity\\', (
    CASE
      WHEN `t`.`key` LIKE 'vat%' THEN 'Vat'
    	WHEN `t`.`key` LIKE 'attribute%' THEN 'Attribute'
    	WHEN `t`.`key` LIKE 'category%' THEN 'Category'
    	WHEN `t`.`key` LIKE 'currency%' THEN 'Currency'
    	WHEN `t`.`key` = 'discount_group_unit' OR `t`.`key` = 'discount_group_name' THEN 'DiscountgroupCountName'
    	WHEN `t`.`key` = 'discount_group_article' THEN 'ArticleGroup'
    	WHEN `t`.`key` LIKE'discount_group_customer' THEN 'CustomerGroup'
    	WHEN `t`.`key` LIKE 'manufacturer%' THEN 'Manufacturer'
    	WHEN `t`.`key` LIKE 'option%' THEN 'Option'
    	WHEN `t`.`key` LIKE 'payment%' THEN 'Payment'
    	WHEN `t`.`key` LIKE 'product%' THEN 'Product'
    	WHEN `t`.`key` LIKE 'shipper%' THEN 'Shipper'
    	WHEN `t`.`key` LIKE 'zone%' THEN 'Zone'
    	ELSE ''
    END
)) AS object_class, (
    CASE
      WHEN `t`.`key` LIKE '%_name' THEN 'name'
      WHEN `t`.`key` LIKE '%_short_description' THEN 'short_description'
    	WHEN `t`.`key` LIKE '%_description' THEN 'description'
    	WHEN `t`.`key` LIKE '%_article' THEN 'name'
    	WHEN `t`.`key` LIKE '%_customer' THEN 'name'
    	WHEN `t`.`key` LIKE '%_unit' THEN 'unit'
    	WHEN `t`.`key` LIKE '%_uri' THEN 'uri'
    	WHEN `t`.`key` LIKE '%_code' THEN 'code'
    	WHEN `t`.`key` LIKE '%_keys' THEN 'keys'
    	WHEN `t`.`key` LIKE '%_long' THEN 'long'
    	WHEN `t`.`key` LIKE '%_short' THEN 'short'
    	WHEN `t`.`key` LIKE '%_class' THEN 'class'
    	ELSE ''
    END
) AS `field`, `t`.`id` AS `foreign_key`, `t`.`text` AS `content` FROM contrexx_core_text AS `t`
  LEFT JOIN `contrexx_core_locale_locale` AS `l` ON `t`.`lang_id` = `l`.`id`
	WHERE `section` LIKE 'Shop' AND `key` NOT LIKE '%core_mail_template%%';

ALTER TABLE contrexx_module_shop_products DROP category_id, DROP usergroup_ids;
ALTER TABLE contrexx_module_shop_pricelists DROP categories;