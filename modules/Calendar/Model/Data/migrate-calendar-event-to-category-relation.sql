-- Run the migration queries first:
DROP TABLE IF EXISTS `contrexx_module_calendar_events_categories`;
CREATE TABLE `contrexx_module_calendar_events_categories` (
  `event_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  PRIMARY KEY (`event_id`,`category_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
INSERT INTO `contrexx_module_calendar_events_categories` (
  `event_id`, `category_id`
)
SELECT `id`, `catid`
FROM `contrexx_module_calendar_event`;
-- Migration end

-- If the above completed successfully, also run:
ALTER TABLE `contrexx_module_calendar_event`
  DROP `catid`;
