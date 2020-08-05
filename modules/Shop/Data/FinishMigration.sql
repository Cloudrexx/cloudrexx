/** Merge Data **/
/** Zones **/
UPDATE contrexx_module_shop_zones AS z
	INNER JOIN contrexx_core_text AS t ON z.id = t.id
  SET z.name = t.text
  WHERE t.section = 'Shop' AND t.key = 'zone_name' AND t.lang_id = 1;

/** Shipper **/
UPDATE contrexx_module_shop_shipper AS z
	INNER JOIN contrexx_core_text AS t ON z.id = t.id
  SET z.name = t.text
  WHERE t.section = 'Shop' AND t.key = 'shipper_name' AND t.lang_id = 1;

/** Products **/
UPDATE contrexx_module_shop_products AS z
	INNER JOIN contrexx_core_text AS t ON z.id = t.id
  SET z.uri = t.text
  WHERE t.section = 'Shop' AND t.key = 'product_uri' AND t.lang_id = 1;

UPDATE contrexx_module_shop_products AS z
	INNER JOIN contrexx_core_text AS t ON z.id = t.id
  SET z.short = t.text
  WHERE t.section = 'Shop' AND t.key = 'product_short' AND t.lang_id = 1;

UPDATE contrexx_module_shop_products AS z
	INNER JOIN contrexx_core_text AS t ON z.id = t.id
  SET z.long = t.text
  WHERE t.section = 'Shop' AND t.key = 'product_long' AND t.lang_id = 1;

UPDATE contrexx_module_shop_products AS z
	INNER JOIN contrexx_core_text AS t ON z.id = t.id
  SET z.name = t.text
  WHERE t.section = 'Shop' AND t.key = 'product_name' AND t.lang_id = 1;

UPDATE contrexx_module_shop_products AS z
	INNER JOIN contrexx_core_text AS t ON z.id = t.id
  SET z.keys = t.text
  WHERE t.section = 'Shop' AND t.key = 'product_keys' AND t.lang_id = 1;

UPDATE contrexx_module_shop_products AS z
	INNER JOIN contrexx_core_text AS t ON z.id = t.id
  SET z.code = t.text
  WHERE t.section = 'Shop' AND t.key = 'product_code' AND t.lang_id = 1;

/** Attribute **/
UPDATE contrexx_module_shop_attribute AS z
	INNER JOIN contrexx_core_text AS t ON z.id = t.id
  SET z.name = t.text
  WHERE t.section = 'Shop' AND t.key = 'attribute_name' AND t.lang_id = 1;

/** Option **/
UPDATE contrexx_module_shop_option AS z
	INNER JOIN contrexx_core_text AS t ON z.id = t.id
  SET z.name = t.text
  WHERE t.section = 'Shop' AND t.key = 'option_name' AND t.lang_id = 1;

/** Category **/
UPDATE contrexx_module_shop_categories AS z
	INNER JOIN contrexx_core_text AS t ON z.id = t.id
  SET z.description = t.text
  WHERE t.section = 'Shop' AND t.key = 'category_description' AND t.lang_id = 1;

UPDATE contrexx_module_shop_categories AS z
	INNER JOIN contrexx_core_text AS t ON z.id = t.id
  SET z.name = t.text
  WHERE t.section = 'Shop' AND t.key = 'category_name' AND t.lang_id = 1;

/** Manufacturer **/
UPDATE contrexx_module_shop_manufacturer AS z
	INNER JOIN contrexx_core_text AS t ON z.id = t.id
  SET z.uri = t.text
  WHERE t.section = 'Shop' AND t.key = 'manufacturer_uri' AND t.lang_id = 1;

UPDATE contrexx_module_shop_manufacturer AS z
	INNER JOIN contrexx_core_text AS t ON z.id = t.id
  SET z.name = t.text
  WHERE t.section = 'Shop' AND t.key = 'manufacturer_name' AND t.lang_id = 1;

/** Payment **/
UPDATE contrexx_module_shop_payment AS z
	INNER JOIN contrexx_core_text AS t ON z.id = t.id
  SET z.name = t.text
  WHERE t.section = 'Shop' AND t.key = 'payment_name' AND t.lang_id = 1;

/** Currency **/
UPDATE contrexx_module_shop_currencies AS z
	INNER JOIN contrexx_core_text AS t ON z.id = t.id
  SET z.name = t.text
  WHERE t.section = 'Shop' AND t.key = 'currency_name' AND t.lang_id = 1;

/** Vat **/
UPDATE contrexx_module_shop_vat AS z
	INNER JOIN contrexx_core_text AS t ON z.id = t.id
  SET z.class = t.text
  WHERE t.section = 'Shop' AND t.key = 'vat_class' AND t.lang_id = 1;

/** Discountgroup Count Name **/
UPDATE contrexx_module_shop_discountgroup_count_name AS z
	INNER JOIN contrexx_core_text AS t ON z.id = t.id
  SET z.unit = t.text
  WHERE t.section = 'Shop' AND t.key = 'discount_group_unit' AND t.lang_id = 1;
UPDATE contrexx_module_shop_discountgroup_count_name AS z
	INNER JOIN contrexx_core_text AS t ON z.id = t.id
  SET z.name = t.text
  WHERE t.section = 'Shop' AND t.key = 'discount_group_name' AND t.lang_id = 1;

/** Article Group **/
UPDATE contrexx_module_shop_article_group AS z
	INNER JOIN contrexx_core_text AS t ON z.id = t.id
  SET z.name = t.text
  WHERE t.section = 'Shop' AND t.key = 'discount_group_article' AND t.lang_id = 1;

/** Customer Group **/
UPDATE contrexx_module_shop_customer_group AS z
	INNER JOIN contrexx_core_text AS t ON z.id = t.id
  SET z.name = t.text
  WHERE t.section = 'Shop' AND t.key = 'discount_group_customer' AND t.lang_id = 1;


/** Drop **/
DELETE FROM `contrexx_core_text` WHERE `key` = 'zone_name';
DELETE FROM `contrexx_core_text` WHERE `key` = 'shipper_name';
DELETE FROM `contrexx_core_text` WHERE `key` = 'product_uri';
DELETE FROM `contrexx_core_text` WHERE `key` = 'product_short';
DELETE FROM `contrexx_core_text` WHERE `key` = 'product_long';
DELETE FROM `contrexx_core_text` WHERE `key` = 'product_name';
DELETE FROM `contrexx_core_text` WHERE `key` = 'product_keys';
DELETE FROM `contrexx_core_text` WHERE `key` = 'product_code';
DELETE FROM `contrexx_core_text` WHERE `key` = 'attribute_name';
DELETE FROM `contrexx_core_text` WHERE `key` = 'option_name';
DELETE FROM `contrexx_core_text` WHERE `key` = 'category_description';
DELETE FROM `contrexx_core_text` WHERE `key` = 'category_name';
DELETE FROM `contrexx_core_text` WHERE `key` = 'manufacturer_uri';
DELETE FROM `contrexx_core_text` WHERE `key` = 'manufacturer_name';
DELETE FROM `contrexx_core_text` WHERE `key` = 'payment_name';
DELETE FROM `contrexx_core_text` WHERE `key` = 'currency_name';
DELETE FROM `contrexx_core_text` WHERE `key` = 'vat_class';
DELETE FROM `contrexx_core_text` WHERE `key` = 'discount_group_unit';
DELETE FROM `contrexx_core_text` WHERE `key` = 'discount_group_name';
DELETE FROM `contrexx_core_text` WHERE `key` = 'discount_group_article';
DELETE FROM `contrexx_core_text` WHERE `key` = 'discount_group_customer';