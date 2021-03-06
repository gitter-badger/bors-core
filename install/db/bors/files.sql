CREATE TABLE IF NOT EXISTS `files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `full_path` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `relative_path` varchar(255) NOT NULL,
  `mime_type` varchar(255) NOT NULL,
  `extension` varchar(255) NOT NULL,
  `actual_time` timestamp NULL DEFAULT NULL,
  `direct_url` varchar(255) DEFAULT NULL,
  `parent_class_name` varchar(64) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `parent_id` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL,
  `create_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modify_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `full_url` varchar(255) NOT NULL,
  `full_file_name` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `size` int(10) unsigned NOT NULL,
  `owner_id` int(10) unsigned NOT NULL,
  `last_editor_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `full_file_name` (`full_file_name`),
  KEY `create_time` (`create_time`),
  KEY `modify_time` (`modify_time`),
  KEY `size` (`size`),
  KEY `parent` (`parent_class_name`,`parent_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
