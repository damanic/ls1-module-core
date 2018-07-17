CREATE TABLE IF NOT EXISTS `core_cron_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `handler_name` varchar(100) DEFAULT NULL,
  `param_data` text,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `core_cron_table` (
	`record_code` varchar(50) NOT NULL,
	`updated_at` datetime DEFAULT NULL,
	PRIMARY KEY (`record_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;