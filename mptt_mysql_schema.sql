DROP TABLE IF EXISTS `kohana_tree`;
CREATE TABLE IF NOT EXISTS `kohana_tree` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lft` int(11) NOT NULL,
  `rgt` int(11) NOT NULL,
  `lvl` int(11) NOT NULL,
  `parent` int(11) DEFAULT '0',
  `scope` int(11) DEFAULT '0',
  `alias` varchar(32) NOT NULL,
  `path` text NOT NULL
  PRIMARY KEY (`id`),
  KEY `kohana_tree_left` (`lft`),
  KEY `kohana_tree_right` (`rgt`),
  KEY `kohana_tree_level` (`lvl`)
) DEFAULT CHARSET=utf8;