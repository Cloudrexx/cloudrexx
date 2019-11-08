INSERT INTO `contrexx_module_gallery_settings` (`name`, `value`) 
	VALUES ('drag_drop', 'off');


DELETE FROM `contrexx_module_shop_payment_processors` 
	WHERE `contrexx_module_shop_payment_processors`.`id` = 11;

DELETE FROM `contrexx_core_setting` WHERE name LIKE 'postfinance_mobile%';