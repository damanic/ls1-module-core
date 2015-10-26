CREATE TABLE `core_metrics` (
  `id` int(11) NOT NULL auto_increment,
  `total_amount` decimal(15,2) default NULL,
  `total_order_num` int(11) default NULL,
  `page_views` int(11) default NULL,
  `updated` date default NULL,
  `update_lock` datetime default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

insert into core_metrics(id, total_amount, total_order_num, page_views, updated) values (1, 0, 0, 0, CURRENT_DATE());