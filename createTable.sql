CREATE TABLE IF NOT EXISTS `followers` (
  `id` bigint(20) NOT NULL,
  `username` text NOT NULL,
  `screen_name` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COMMENT='Twitter followers';
