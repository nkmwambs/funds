-- Adminer 4.8.2-dev MySQL 8.0.28 dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `funds_transfer`;
CREATE TABLE `funds_transfer` (
  `funds_transfer_id` int NOT NULL AUTO_INCREMENT,
  `funds_transfer_track_number` varchar(100) NOT NULL,
  `funds_transfer_name` varchar(100) NOT NULL,
  `fk_office_id` int NOT NULL,
  `funds_transfer_source_account_id` int NOT NULL,
  `funds_transfer_target_account_id` int NOT NULL,
  `funds_transfer_source_project_allocation_id` int NOT NULL,
  `funds_transfer_target_project_allocation_id` int NOT NULL,
  `funds_transfer_type` int NOT NULL,
  `funds_transfer_amount` decimal(50,2) NOT NULL,
  `funds_transfer_description` longtext NOT NULL,
  `fk_voucher_id` int NOT NULL,
  `funds_transfer_deleted_at` date DEFAULT NULL,
  `fk_status_id` int NOT NULL,
  `funds_transfer_created_date` date NOT NULL,
  `funds_transfer_created_by` int NOT NULL,
  `funds_transfer_last_modified_by` int NOT NULL,
  `funds_transfer_last_modified_date` timestamp NOT NULL ON UPDATE CURRENT_TIMESTAMP,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`funds_transfer_id`),
  KEY `fk_office_id` (`fk_office_id`),
  KEY `fk_status_id` (`fk_status_id`),
  KEY `funds_transfer_source_project_allocation_id` (`funds_transfer_source_project_allocation_id`),
  KEY `funds_transfer_target_project_allocation_id` (`funds_transfer_target_project_allocation_id`),
  CONSTRAINT `funds_transfer_ibfk_1` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`),
  CONSTRAINT `funds_transfer_ibfk_2` FOREIGN KEY (`fk_status_id`) REFERENCES `status` (`status_id`),
  CONSTRAINT `funds_transfer_ibfk_3` FOREIGN KEY (`funds_transfer_source_project_allocation_id`) REFERENCES `project_allocation` (`project_allocation_id`),
  CONSTRAINT `funds_transfer_ibfk_4` FOREIGN KEY (`funds_transfer_target_project_allocation_id`) REFERENCES `project_allocation` (`project_allocation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- 2022-05-11 20:54:43
