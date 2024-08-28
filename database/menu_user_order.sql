-- Adminer 4.8.2-dev MySQL 8.0.28 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `menu_user_order`;
CREATE TABLE `menu_user_order` (
  `menu_user_order_id` int NOT NULL AUTO_INCREMENT,
  `fk_user_id` int NOT NULL,
  `menu_user_order_track_number` varchar(100) DEFAULT NULL,
  `menu_user_order_name` varchar(100) DEFAULT NULL,
  `fk_menu_id` int DEFAULT NULL,
  `menu_user_order_is_favorite` int DEFAULT '0',
  `menu_user_order_is_active` int NOT NULL DEFAULT '1',
  `menu_user_order_level` int NOT NULL DEFAULT '1',
  `menu_user_order_priority_item` int NOT NULL DEFAULT '1',
  `menu_user_order_created_date` date DEFAULT NULL,
  `menu_user_order_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `menu_user_order_created_by` int DEFAULT NULL,
  `menu_user_order_last_modified_by` int DEFAULT NULL,
  `fk_approval_id` int DEFAULT NULL,
  `fk_status_id` int DEFAULT NULL,
  PRIMARY KEY (`menu_user_order_id`),
  KEY `fk_menu_id` (`fk_menu_id`),
  CONSTRAINT `menu_user_order_ibfk_1` FOREIGN KEY (`fk_menu_id`) REFERENCES `menu` (`menu_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


-- 2022-07-18 18:15:03
