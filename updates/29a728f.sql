CREATE TABLE `core_eula_unread_users` (
  `user_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `core_eula_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agreement_text` text,
  `accepted_by` int(11) DEFAULT NULL,
  `accepted_on` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;