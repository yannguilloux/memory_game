CREATE DATABASE `memory_game` DEFAULT CHARACTER SET = `utf8`;

USE `memory_game`;

CREATE TABLE `memory_score` (
  `score_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL DEFAULT '',
  `score_pair_found` smallint(3) unsigned NOT NULL DEFAULT '0',
  `score_pair_total` smallint(3) unsigned NOT NULL DEFAULT '0',
  `score_elapsed_time` mediumint(4) NOT NULL DEFAULT '0',
  `score_created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `score_game_won` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`score_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `memory_user`;

CREATE TABLE `memory_user` (
  `uuid` varchar(36) NOT NULL DEFAULT '',
  `user_current_game` longtext NOT NULL,
  PRIMARY KEY (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;