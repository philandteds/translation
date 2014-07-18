DROP TABLE IF EXISTS `translation_export_jobs`;
CREATE TABLE `translation_export_jobs` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `status` tinyint(1) unsigned NOT NULL,
  `file` varchar(255) NOT NULL,
  `parent_node_ids` varchar(255) default NULL,
  `exclude_parent_node_ids` varchar(255) default NULL,
  `direct_node_ids` varchar(255) default NULL,
  `classes` varchar(255) default NULL,
  `siteaccess` varchar(255) default NULL,
  `creator_id` int(11) unsigned NOT NULL,
  `created_at` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `translation_import_jobs`;
CREATE TABLE `translation_import_jobs` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `status` tinyint(1) unsigned NOT NULL,
  `file` varchar(255) NOT NULL,
  `language` char(6) NOT NULL,
  `creator_id` int(11) unsigned NOT NULL,
  `created_at` int(11) unsigned NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

