-- Adminer 4.6.3 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `account_system`;
CREATE TABLE `account_system` (
  `account_system_id` int(100) NOT NULL AUTO_INCREMENT,
  `account_system_track_number` varchar(100) NOT NULL,
  `account_system_name` varchar(100) NOT NULL,
  `account_system_code` varchar(10) NOT NULL,
  `account_system_is_allocation_linked_to_account` int(5) NOT NULL,
  `account_system_is_active` int(5) NOT NULL DEFAULT '1',
  `account_system_created_date` date DEFAULT NULL,
  `account_system_created_by` int(100) DEFAULT NULL,
  `account_system_last_modified_by` int(100) DEFAULT NULL,
  `account_system_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`account_system_id`),
  UNIQUE KEY `account_system_code` (`account_system_code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `account_system_setting`;
CREATE TABLE `account_system_setting` (
  `account_system_setting_id` int(100) NOT NULL AUTO_INCREMENT,
  `account_system_setting_name` varchar(100) NOT NULL,
  `account_system_setting_track_number` varchar(100) NOT NULL,
  `account_system_setting_value` varchar(50) NOT NULL,
  `fk_account_system_id` int(100) NOT NULL,
  `fk_approve_item_id` int(100) NOT NULL,
  `account_system_setting_created_by` int(100) NOT NULL,
  `account_system_setting_created_date` date NOT NULL,
  `account_system_setting_last_modified_by` int(100) NOT NULL,
  `account_system_setting_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_status_id` int(100) NOT NULL,
  `fk_approval_id` int(100) NOT NULL,
  PRIMARY KEY (`account_system_setting_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  KEY `fk_approve_item_id` (`fk_approve_item_id`),
  CONSTRAINT `account_system_setting_ibfk_1` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`),
  CONSTRAINT `account_system_setting_ibfk_2` FOREIGN KEY (`fk_approve_item_id`) REFERENCES `approve_item` (`approve_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `approval`;
CREATE TABLE `approval` (
  `approval_id` int(11) NOT NULL AUTO_INCREMENT,
  `approval_track_number` varchar(100) NOT NULL,
  `approval_name` varchar(100) NOT NULL,
  `fk_approve_item_id` int(11) NOT NULL,
  `fk_status_id` int(11) NOT NULL,
  `approval_created_by` int(100) NOT NULL,
  `approval_created_date` date NOT NULL,
  `approval_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `approval_last_modified_by` int(100) NOT NULL,
  PRIMARY KEY (`approval_id`),
  KEY `fk_approve_item_id` (`fk_approve_item_id`),
  KEY `fk_status_id` (`fk_status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `approval_flow`;
CREATE TABLE `approval_flow` (
  `approval_flow_id` int(100) NOT NULL AUTO_INCREMENT,
  `approval_flow_name` varchar(100) NOT NULL,
  `approval_flow_track_number` varchar(100) NOT NULL,
  `fk_approve_item_id` int(100) NOT NULL,
  `fk_account_system_id` int(100) NOT NULL,
  `approval_flow_created_by` int(100) NOT NULL,
  `approval_flow_created_date` date NOT NULL,
  `approval_flow_last_modified_by` int(100) NOT NULL,
  `approval_flow_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`approval_flow_id`),
  KEY `fk_approve_item_id` (`fk_approve_item_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  CONSTRAINT `approval_flow_ibfk_1` FOREIGN KEY (`fk_approve_item_id`) REFERENCES `approve_item` (`approve_item_id`),
  CONSTRAINT `approval_flow_ibfk_2` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `approve_item`;
CREATE TABLE `approve_item` (
  `approve_item_id` int(100) NOT NULL AUTO_INCREMENT,
  `approve_item_track_number` varchar(100) NOT NULL,
  `approve_item_name` varchar(100) NOT NULL,
  `approve_item_is_active` int(5) NOT NULL,
  `approve_item_created_date` date NOT NULL,
  `approve_item_created_by` int(100) NOT NULL,
  `approve_item_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `approve_item_last_modified_by` int(100) NOT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`approve_item_id`),
  UNIQUE KEY `approve_item_name` (`approve_item_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `attachment`;
CREATE TABLE `attachment` (
  `attachment_id` int(100) NOT NULL AUTO_INCREMENT,
  `attachment_name` varchar(100) NOT NULL,
  `attachment_track_number` varchar(100) NOT NULL,
  `attachment_size` int(100) NOT NULL,
  `attachment_file_type` varchar(100) NOT NULL,
  `attachment_url` longtext NOT NULL,
  `fk_approve_item_id` int(100) NOT NULL,
  `attachment_primary_id` int(100) NOT NULL,
  `attachment_is_s3_upload` int(5) NOT NULL DEFAULT '0',
  `attachment_created_date` date NOT NULL,
  `attachment_created_by` int(100) NOT NULL,
  `attachment_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `attachment_last_modified_by` int(100) NOT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`attachment_id`),
  KEY `fk_approve_item_id` (`fk_approve_item_id`),
  CONSTRAINT `attachment_ibfk_1` FOREIGN KEY (`fk_approve_item_id`) REFERENCES `approve_item` (`approve_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `bank`;
CREATE TABLE `bank` (
  `bank_id` int(100) NOT NULL AUTO_INCREMENT,
  `bank_track_number` varchar(100) DEFAULT NULL,
  `bank_name` varchar(45) DEFAULT NULL,
  `bank_swift_code` varchar(45) DEFAULT NULL,
  `bank_is_active` int(5) NOT NULL DEFAULT '1',
  `fk_account_system_id` int(100) NOT NULL DEFAULT '1',
  `bank_created_date` date DEFAULT NULL,
  `bank_created_by` int(100) DEFAULT NULL,
  `bank_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `bank_last_modified_by` int(100) DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`bank_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='This table list all the banks for centers';


DROP TABLE IF EXISTS `budget`;
CREATE TABLE `budget` (
  `budget_id` int(100) NOT NULL AUTO_INCREMENT,
  `budget_track_number` varchar(45) DEFAULT NULL,
  `budget_name` varchar(100) DEFAULT NULL,
  `fk_office_id` int(100) DEFAULT NULL,
  `fk_budget_tag_id` int(100) DEFAULT NULL,
  `fk_approval_id` int(11) DEFAULT '0',
  `fk_status_id` int(11) DEFAULT '0',
  `budget_year` int(5) DEFAULT NULL,
  `budget_created_by` int(100) DEFAULT NULL,
  `budget_created_date` date DEFAULT NULL,
  `budget_last_modified_by` int(100) DEFAULT NULL,
  `budget_last_modified_date` date DEFAULT NULL,
  PRIMARY KEY (`budget_id`),
  KEY `fk_budget_center1_idx` (`fk_office_id`),
  KEY `fk_budget_tag_id` (`fk_budget_tag_id`),
  CONSTRAINT `budget_ibfk_1` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`),
  CONSTRAINT `budget_ibfk_2` FOREIGN KEY (`fk_budget_tag_id`) REFERENCES `budget_tag` (`budget_tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='This table holds the budget items by activity';


DROP TABLE IF EXISTS `budget_item`;
CREATE TABLE `budget_item` (
  `budget_item_id` int(100) NOT NULL AUTO_INCREMENT,
  `budget_item_track_number` varchar(100) DEFAULT NULL,
  `budget_item_name` varchar(100) DEFAULT NULL,
  `fk_budget_id` int(100) DEFAULT NULL,
  `budget_item_total_cost` int(50) DEFAULT NULL,
  `fk_expense_account_id` int(100) DEFAULT NULL,
  `budget_item_description` longtext,
  `budget_item_quantity` int(10) NOT NULL DEFAULT '0',
  `budget_item_unit_cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `budget_item_often` int(10) NOT NULL DEFAULT '1',
  `fk_status_id` int(11) DEFAULT '0',
  `fk_approval_id` int(11) DEFAULT '0',
  `fk_project_allocation_id` int(100) DEFAULT NULL,
  `budget_item_created_by` int(100) DEFAULT NULL,
  `budget_item_last_modified_by` int(11) DEFAULT NULL,
  `budget_item_created_date` date DEFAULT NULL,
  `budget_item_last_modified_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`budget_item_id`),
  KEY `fk_budget_detail_id_expense_account_id_idx` (`fk_expense_account_id`),
  KEY `fk_budget_detail_budget_id_idx` (`fk_budget_id`),
  KEY `fk_project_allocation_id` (`fk_project_allocation_id`),
  KEY `fk_status_id` (`fk_status_id`),
  KEY `fk_approval_id` (`fk_approval_id`),
  CONSTRAINT `budget_item_ibfk_1` FOREIGN KEY (`fk_project_allocation_id`) REFERENCES `project_allocation` (`project_allocation_id`),
  CONSTRAINT `budget_item_ibfk_2` FOREIGN KEY (`fk_status_id`) REFERENCES `status` (`status_id`),
  CONSTRAINT `budget_item_ibfk_3` FOREIGN KEY (`fk_approval_id`) REFERENCES `approval` (`approval_id`),
  CONSTRAINT `fk_budget_detail_budget_id` FOREIGN KEY (`fk_budget_id`) REFERENCES `budget` (`budget_id`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `fk_budget_detail_id_expense_account_id` FOREIGN KEY (`fk_expense_account_id`) REFERENCES `expense_account` (`expense_account_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='This hold activties and their budgeted cost';


DROP TABLE IF EXISTS `budget_item_detail`;
CREATE TABLE `budget_item_detail` (
  `budget_item_detail_id` int(100) NOT NULL AUTO_INCREMENT,
  `budget_item_detail_track_number` varchar(100) DEFAULT NULL,
  `budget_item_detail_name` varchar(100) DEFAULT NULL,
  `fk_budget_item_id` int(100) DEFAULT NULL,
  `fk_month_id` int(5) DEFAULT NULL,
  `fk_status_id` int(11) DEFAULT '0',
  `fk_approval_id` int(11) DEFAULT '0',
  `budget_item_detail_amount` decimal(10,2) DEFAULT NULL,
  `budget_item_detail_created_date` date DEFAULT NULL,
  `budget_item_detail_created_by` int(100) DEFAULT NULL,
  `budget_item_detail_last_modified_by` int(100) DEFAULT NULL,
  `budget_item_detail_last_modified_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`budget_item_detail_id`),
  KEY `fk_budget_month_spread_budget_detail1_idx` (`fk_budget_item_id`),
  KEY `fk_status_id` (`fk_status_id`),
  KEY `fk_approval_id` (`fk_approval_id`),
  CONSTRAINT `budget_item_detail_ibfk_1` FOREIGN KEY (`fk_status_id`) REFERENCES `status` (`status_id`),
  CONSTRAINT `fk_budget_month_spread_budget_detail1` FOREIGN KEY (`fk_budget_item_id`) REFERENCES `budget_item` (`budget_item_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='This table distributes budget allocations by month';


DROP TABLE IF EXISTS `budget_limit`;
CREATE TABLE `budget_limit` (
  `budget_limit_id` int(100) NOT NULL AUTO_INCREMENT,
  `budget_limit_track_number` varchar(100) NOT NULL,
  `budget_limit_name` varchar(100) NOT NULL,
  `fk_office_id` int(100) NOT NULL,
  `budget_limit_year` int(100) NOT NULL,
  `fk_budget_tag_id` int(100) NOT NULL,
  `fk_income_account_id` int(11) NOT NULL,
  `budget_limit_amount` decimal(10,2) NOT NULL,
  `budget_limit_created_date` date DEFAULT NULL,
  `budget_limit_created_by` int(100) DEFAULT NULL,
  `budget_limit_last_modified_by` int(100) DEFAULT NULL,
  `budget_limit_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`budget_limit_id`),
  KEY `fk_office_id` (`fk_office_id`),
  KEY `budget_limit_year` (`budget_limit_year`),
  KEY `fk_budget_tag_id` (`fk_budget_tag_id`),
  KEY `fk_income_account_id` (`fk_income_account_id`),
  CONSTRAINT `budget_limit_ibfk_1` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`),
  CONSTRAINT `budget_limit_ibfk_2` FOREIGN KEY (`budget_limit_year`) REFERENCES `budget` (`budget_id`),
  CONSTRAINT `budget_limit_ibfk_3` FOREIGN KEY (`fk_budget_tag_id`) REFERENCES `budget_tag` (`budget_tag_id`),
  CONSTRAINT `budget_limit_ibfk_4` FOREIGN KEY (`fk_income_account_id`) REFERENCES `income_account` (`income_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `budget_projection`;
CREATE TABLE `budget_projection` (
  `budget_projection_id` int(100) NOT NULL AUTO_INCREMENT,
  `budget_projection_name` varchar(100) NOT NULL,
  `budget_projection_track_number` varchar(100) NOT NULL,
  `fk_budget_id` int(100) NOT NULL,
  `budget_projection_created_by` int(100) NOT NULL,
  `budget_projection_created_date` date NOT NULL,
  `budget_projection_last_modified_by` int(100) NOT NULL,
  `budget_projection_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int(11) NOT NULL,
  `fk_status_id` int(11) NOT NULL,
  PRIMARY KEY (`budget_projection_id`),
  KEY `fk_budget_id` (`fk_budget_id`),
  KEY `fk_approval_id` (`fk_approval_id`),
  KEY `fk_status_id` (`fk_status_id`),
  CONSTRAINT `budget_projection_ibfk_2` FOREIGN KEY (`fk_budget_id`) REFERENCES `budget` (`budget_id`),
  CONSTRAINT `budget_projection_ibfk_3` FOREIGN KEY (`fk_approval_id`) REFERENCES `approval` (`approval_id`),
  CONSTRAINT `budget_projection_ibfk_4` FOREIGN KEY (`fk_status_id`) REFERENCES `status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `budget_projection_income_account`;
CREATE TABLE `budget_projection_income_account` (
  `budget_projection_income_account_id` int(100) NOT NULL AUTO_INCREMENT,
  `budget_projection_income_account_name` varchar(100) NOT NULL,
  `budget_projection_income_account_track_number` varchar(100) NOT NULL,
  `fk_budget_projection_id` int(100) NOT NULL,
  `fk_income_account_id` int(11) NOT NULL,
  `budget_projection_income_account_amount` decimal(10,2) NOT NULL,
  `budget_projection_income_account_created_by` int(100) NOT NULL,
  `budget_projection_income_account_created_date` date NOT NULL,
  `budget_projection_income_account_last_modified_by` int(100) NOT NULL,
  `budget_projection_income_account_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int(11) NOT NULL,
  `fk_status_id` int(11) NOT NULL,
  PRIMARY KEY (`budget_projection_income_account_id`),
  KEY `fk_budget_projection_id` (`fk_budget_projection_id`),
  KEY `fk_income_account_id` (`fk_income_account_id`),
  KEY `fk_approval_id` (`fk_approval_id`),
  KEY `fk_status_id` (`fk_status_id`),
  CONSTRAINT `budget_projection_income_account_ibfk_1` FOREIGN KEY (`fk_budget_projection_id`) REFERENCES `budget_projection` (`budget_projection_id`),
  CONSTRAINT `budget_projection_income_account_ibfk_2` FOREIGN KEY (`fk_income_account_id`) REFERENCES `income_account` (`income_account_id`),
  CONSTRAINT `budget_projection_income_account_ibfk_3` FOREIGN KEY (`fk_approval_id`) REFERENCES `approval` (`approval_id`),
  CONSTRAINT `budget_projection_income_account_ibfk_4` FOREIGN KEY (`fk_status_id`) REFERENCES `status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `budget_review_count`;
CREATE TABLE `budget_review_count` (
  `budget_review_count_id` int(100) NOT NULL AUTO_INCREMENT,
  `budget_review_count_track_number` varchar(100) NOT NULL,
  `budget_review_count_name` varchar(100) NOT NULL,
  `budget_review_count_number` int(5) NOT NULL,
  `fk_account_system_id` int(100) NOT NULL,
  `budget_review_count_created_date` date NOT NULL,
  `budget_review_count_created_by` int(100) NOT NULL,
  `budget_review_count_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `budget_review_count_last_modified_by` int(100) NOT NULL,
  `fk_status_id` int(11) NOT NULL,
  `fk_approval_id` int(11) NOT NULL,
  PRIMARY KEY (`budget_review_count_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  CONSTRAINT `budget_review_count_ibfk_1` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `budget_tag`;
CREATE TABLE `budget_tag` (
  `budget_tag_id` int(100) NOT NULL AUTO_INCREMENT,
  `budget_tag_track_number` varchar(100) NOT NULL,
  `budget_tag_name` varchar(100) NOT NULL,
  `fk_month_id` int(11) NOT NULL,
  `budget_tag_level` int(5) NOT NULL,
  `budget_tag_is_active` int(5) NOT NULL DEFAULT '1',
  `fk_account_system_id` int(100) NOT NULL,
  `budget_tag_created_date` date NOT NULL,
  `budget_tag_created_by` int(100) NOT NULL,
  `budget_tag_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `budget_tag_last_modified_by` int(1) NOT NULL,
  `fk_approval_id` int(11) DEFAULT NULL,
  `fk_status_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`budget_tag_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  KEY `fk_month_id` (`fk_month_id`),
  CONSTRAINT `budget_tag_ibfk_1` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`),
  CONSTRAINT `budget_tag_ibfk_2` FOREIGN KEY (`fk_month_id`) REFERENCES `month` (`month_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `cash_recipient_account`;
CREATE TABLE `cash_recipient_account` (
  `cash_recipient_account_id` int(100) NOT NULL AUTO_INCREMENT,
  `cash_recipient_account_name` varchar(100) NOT NULL,
  `cash_recipient_account_track_number` varchar(100) NOT NULL,
  `fk_voucher_id` int(100) NOT NULL,
  `fk_office_bank_id` int(11) NOT NULL,
  `fk_office_cash_id` int(11) NOT NULL,
  `cash_recipient_account_created_date` date DEFAULT NULL,
  `cash_recipient_account_created_by` int(100) DEFAULT NULL,
  `cash_recipient_account_last_modified_by` int(100) DEFAULT NULL,
  `cash_recipient_account_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`cash_recipient_account_id`),
  KEY `fk_voucher_id` (`fk_voucher_id`),
  CONSTRAINT `cash_recipient_account_ibfk_1` FOREIGN KEY (`fk_voucher_id`) REFERENCES `voucher` (`voucher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `cheque_book`;
CREATE TABLE `cheque_book` (
  `cheque_book_id` int(11) NOT NULL AUTO_INCREMENT,
  `cheque_book_track_number` varchar(100) NOT NULL,
  `cheque_book_name` varchar(100) NOT NULL,
  `fk_office_bank_id` int(100) DEFAULT NULL,
  `cheque_book_is_active` int(5) DEFAULT '0',
  `cheque_book_start_serial_number` int(100) DEFAULT NULL,
  `cheque_book_count_of_leaves` int(100) DEFAULT NULL,
  `cheque_book_use_start_date` date DEFAULT NULL,
  `cheque_book_created_date` date DEFAULT NULL,
  `cheque_book_created_by` int(100) DEFAULT NULL,
  `cheque_book_last_modified_by` int(100) DEFAULT NULL,
  `cheque_book_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`cheque_book_id`),
  KEY `fk_office_bank_id` (`fk_office_bank_id`),
  CONSTRAINT `cheque_book_ibfk_1` FOREIGN KEY (`fk_office_bank_id`) REFERENCES `office_bank` (`office_bank_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `cheque_injection`;
CREATE TABLE `cheque_injection` (
  `cheque_injection_id` int(100) NOT NULL AUTO_INCREMENT,
  `cheque_injection_track_number` varchar(100) NOT NULL,
  `cheque_injection_name` varchar(100) NOT NULL,
  `fk_office_bank_id` int(100) NOT NULL,
  `cheque_injection_number` int(10) NOT NULL,
  `cheque_injection_created_date` date NOT NULL,
  `cheque_injection_created_by` int(100) NOT NULL,
  `cheque_injection_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cheque_injection_last_modified_by` int(100) NOT NULL,
  `fk_status_id` int(100) NOT NULL,
  `fk_approval_id` int(100) NOT NULL,
  PRIMARY KEY (`cheque_injection_id`),
  KEY `fk_office_bank_id` (`fk_office_bank_id`),
  CONSTRAINT `cheque_injection_ibfk_1` FOREIGN KEY (`fk_office_bank_id`) REFERENCES `office_bank` (`office_bank_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `ci_sessions`;
CREATE TABLE `ci_sessions` (
  `id` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` int(10) unsigned NOT NULL DEFAULT '0',
  `data` blob NOT NULL,
  `ci_sessions_created_date` date DEFAULT NULL,
  `ci_sessions_created_by` int(100) DEFAULT NULL,
  `ci_sessions_last_modified_by` int(100) DEFAULT NULL,
  `ci_sessions_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ci_sessions_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `context_center`;
CREATE TABLE `context_center` (
  `context_center_id` int(100) NOT NULL AUTO_INCREMENT,
  `context_center_track_number` varchar(100) DEFAULT NULL,
  `context_center_name` varchar(100) DEFAULT NULL,
  `context_center_description` varchar(100) DEFAULT NULL,
  `fk_office_id` int(100) NOT NULL,
  `fk_context_definition_id` int(100) DEFAULT NULL,
  `fk_context_cluster_id` int(100) DEFAULT NULL,
  `context_center_created_date` date DEFAULT '0000-00-00',
  `context_center_created_by` int(100) DEFAULT NULL,
  `context_center_last_modified_date` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `context_center_last_modified_by` int(100) DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`context_center_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `context_center_user`;
CREATE TABLE `context_center_user` (
  `context_center_user_id` int(100) NOT NULL AUTO_INCREMENT,
  `context_center_user_track_number` varchar(100) DEFAULT NULL,
  `context_center_user_name` varchar(100) DEFAULT NULL,
  `fk_context_center_id` int(100) NOT NULL,
  `fk_user_id` int(100) NOT NULL,
  `fk_designation_id` int(100) NOT NULL,
  `context_center_user_is_active` int(100) NOT NULL,
  `context_center_user_created_by` int(100) NOT NULL,
  `context_center_user_created_date` date DEFAULT '0000-00-00',
  `context_center_user_last_modified_date` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `context_center_user_last_modified_by` int(100) NOT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`context_center_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `context_cluster`;
CREATE TABLE `context_cluster` (
  `context_cluster_id` int(100) NOT NULL AUTO_INCREMENT,
  `context_cluster_track_number` varchar(100) DEFAULT NULL,
  `context_cluster_name` varchar(100) DEFAULT NULL,
  `context_cluster_description` varchar(100) DEFAULT NULL,
  `fk_office_id` varchar(100) NOT NULL,
  `fk_context_definition_id` varchar(100) DEFAULT NULL,
  `fk_context_cohort_id` int(100) DEFAULT NULL,
  `context_cluster_created_date` date DEFAULT '0000-00-00',
  `context_cluster_created_by` int(100) DEFAULT NULL,
  `context_cluster_last_modified_date` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `context_cluster_last_modified_by` int(100) DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`context_cluster_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `context_cluster_user`;
CREATE TABLE `context_cluster_user` (
  `context_cluster_user_id` int(100) NOT NULL AUTO_INCREMENT,
  `context_cluster_user_track_number` varchar(100) DEFAULT NULL,
  `context_cluster_user_name` varchar(100) DEFAULT NULL,
  `fk_context_cluster_id` int(100) NOT NULL,
  `fk_user_id` int(100) NOT NULL,
  `fk_designation_id` int(100) NOT NULL,
  `context_cluster_user_is_active` int(100) NOT NULL,
  `context_cluster_user_created_by` int(100) NOT NULL,
  `context_cluster_user_created_date` date DEFAULT '0000-00-00',
  `context_cluster_user_last_modified_date` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `context_cluster_user_last_modified_by` int(100) NOT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`context_cluster_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `context_cohort`;
CREATE TABLE `context_cohort` (
  `context_cohort_id` int(100) NOT NULL AUTO_INCREMENT,
  `context_cohort_track_number` varchar(100) DEFAULT NULL,
  `context_cohort_name` varchar(100) DEFAULT NULL,
  `context_cohort_description` varchar(100) DEFAULT NULL,
  `fk_office_id` varchar(100) NOT NULL,
  `fk_context_definition_id` varchar(100) DEFAULT NULL,
  `fk_context_country_id` int(100) DEFAULT NULL,
  `context_cohort_created_date` date DEFAULT '0000-00-00',
  `context_cohort_created_by` int(100) DEFAULT NULL,
  `context_cohort_last_modified_date` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `context_cohort_last_modified_by` int(100) DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`context_cohort_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `context_cohort_user`;
CREATE TABLE `context_cohort_user` (
  `context_cohort_user_id` int(100) NOT NULL AUTO_INCREMENT,
  `context_cohort_user_track_number` varchar(100) DEFAULT NULL,
  `context_cohort_user_name` varchar(100) DEFAULT NULL,
  `fk_context_cohort_id` int(100) NOT NULL,
  `fk_user_id` int(100) NOT NULL,
  `fk_designation_id` int(100) NOT NULL,
  `context_cohort_user_is_active` int(100) NOT NULL,
  `context_cohort_user_created_by` int(100) NOT NULL,
  `context_cohort_user_created_date` date DEFAULT '0000-00-00',
  `context_cohort_user_last_modified_date` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `context_cohort_user_last_modified_by` int(100) NOT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`context_cohort_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `context_country`;
CREATE TABLE `context_country` (
  `context_country_id` int(100) NOT NULL AUTO_INCREMENT,
  `context_country_track_number` varchar(100) DEFAULT NULL,
  `context_country_name` varchar(100) DEFAULT NULL,
  `context_country_description` varchar(100) DEFAULT NULL,
  `fk_office_id` int(11) NOT NULL,
  `fk_context_definition_id` int(11) DEFAULT NULL,
  `fk_context_region_id` int(100) DEFAULT NULL,
  `context_country_created_date` date DEFAULT '0000-00-00',
  `context_country_created_by` int(100) DEFAULT NULL,
  `context_country_last_modified_date` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `context_country_last_modified_by` int(100) DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`context_country_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `context_country_user`;
CREATE TABLE `context_country_user` (
  `context_country_user_id` int(100) NOT NULL AUTO_INCREMENT,
  `context_country_user_track_number` varchar(100) DEFAULT NULL,
  `context_country_user_name` varchar(100) DEFAULT NULL,
  `fk_context_country_id` int(100) NOT NULL,
  `fk_user_id` int(100) NOT NULL,
  `fk_designation_id` int(100) NOT NULL,
  `context_country_user_is_active` int(100) NOT NULL,
  `context_country_user_created_by` int(100) NOT NULL,
  `context_country_user_created_date` date DEFAULT '0000-00-00',
  `context_country_user_last_modified_date` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `context_country_user_last_modified_by` int(100) NOT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`context_country_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `context_definition`;
CREATE TABLE `context_definition` (
  `context_definition_id` int(100) NOT NULL AUTO_INCREMENT,
  `context_definition_track_number` varchar(100) DEFAULT NULL,
  `context_definition_name` varchar(100) DEFAULT NULL,
  `context_definition_level` int(5) DEFAULT NULL,
  `context_definition_is_implementing` int(5) DEFAULT NULL,
  `context_definition_is_active` int(5) NOT NULL DEFAULT '1',
  `context_definition_created_date` date DEFAULT NULL,
  `context_definition_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `context_definition_created_by` int(100) DEFAULT NULL,
  `context_definition_last_modified_by` int(100) DEFAULT NULL,
  `context_definition_deleted_at` date DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`context_definition_id`),
  UNIQUE KEY `center_group_hierarchy_level` (`context_definition_level`),
  KEY `fk_status_id` (`fk_status_id`),
  KEY `fk_approval_id` (`fk_approval_id`),
  CONSTRAINT `context_definition_ibfk_1` FOREIGN KEY (`fk_status_id`) REFERENCES `status` (`status_id`),
  CONSTRAINT `context_definition_ibfk_2` FOREIGN KEY (`fk_approval_id`) REFERENCES `approval` (`approval_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `context_global`;
CREATE TABLE `context_global` (
  `context_global_id` int(100) NOT NULL AUTO_INCREMENT,
  `context_global_track_number` varchar(100) NOT NULL,
  `context_global_name` varchar(100) NOT NULL,
  `context_global_description` longtext NOT NULL,
  `fk_office_id` int(100) NOT NULL,
  `fk_context_definition_id` int(100) NOT NULL,
  `context_global_created_date` date NOT NULL,
  `context_global_created_by` int(100) NOT NULL,
  `context_global_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `context_global_last_modified_by` int(100) NOT NULL,
  `fk_approval_id` int(11) NOT NULL,
  `fk_status_id` int(11) NOT NULL,
  PRIMARY KEY (`context_global_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `context_global_user`;
CREATE TABLE `context_global_user` (
  `context_global_user_id` int(100) NOT NULL AUTO_INCREMENT,
  `context_global_user_track_number` varchar(100) NOT NULL,
  `context_global_user_name` varchar(100) NOT NULL,
  `fk_user_id` int(100) NOT NULL,
  `fk_context_global_id` int(100) NOT NULL,
  `fk_designation_id` int(100) NOT NULL,
  `context_global_user_is_active` int(5) NOT NULL DEFAULT '1',
  `context_global_user_created_date` date NOT NULL,
  `context_global_user_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `context_global_user_created_by` int(100) NOT NULL,
  `context_global_user_last_modified_by` int(100) NOT NULL,
  `fk_approval_id` int(100) NOT NULL,
  `fk_status_id` int(100) NOT NULL,
  PRIMARY KEY (`context_global_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `context_region`;
CREATE TABLE `context_region` (
  `context_region_id` int(100) NOT NULL AUTO_INCREMENT,
  `context_region_track_number` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `context_region_name` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `context_region_description` varchar(100) CHARACTER SET latin1 DEFAULT NULL,
  `fk_office_id` int(11) NOT NULL,
  `fk_context_definition_id` int(11) NOT NULL,
  `fk_context_global_id` int(100) NOT NULL,
  `context_region_created_date` date DEFAULT '0000-00-00',
  `context_region_created_by` int(100) DEFAULT NULL,
  `context_region_last_modified_date` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `context_region_last_modified_by` int(100) DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`context_region_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `context_region_user`;
CREATE TABLE `context_region_user` (
  `context_region_user_id` int(100) NOT NULL AUTO_INCREMENT,
  `context_region_user_track_number` varchar(100) DEFAULT NULL,
  `context_region_user_name` varchar(100) DEFAULT NULL,
  `fk_context_region_id` int(100) NOT NULL,
  `fk_user_id` int(100) NOT NULL,
  `fk_designation_id` int(100) NOT NULL,
  `context_region_user_is_active` int(100) NOT NULL,
  `context_region_user_created_by` int(100) NOT NULL,
  `context_region_user_created_date` date DEFAULT '0000-00-00',
  `context_region_user_last_modified_date` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `context_region_user_last_modified_by` int(100) NOT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`context_region_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `contra_account`;
CREATE TABLE `contra_account` (
  `contra_account_id` int(100) NOT NULL AUTO_INCREMENT,
  `contra_account_track_number` varchar(100) NOT NULL,
  `contra_account_name` varchar(100) NOT NULL,
  `contra_account_code` varchar(20) NOT NULL,
  `contra_account_description` varchar(100) NOT NULL,
  `fk_voucher_type_account_id` int(100) NOT NULL,
  `fk_voucher_type_effect_id` int(100) NOT NULL,
  `fk_office_bank_id` int(100) NOT NULL,
  `fk_account_system_id` int(100) NOT NULL,
  `contra_account_created_date` date DEFAULT NULL,
  `contra_account_created_by` int(100) DEFAULT NULL,
  `contra_account_last_modified_by` int(100) DEFAULT NULL,
  `contra_account_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`contra_account_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  KEY `fk_voucher_type_account_id` (`fk_voucher_type_account_id`),
  KEY `fk_office_bank_id` (`fk_office_bank_id`),
  CONSTRAINT `contra_account_ibfk_1` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`),
  CONSTRAINT `contra_account_ibfk_2` FOREIGN KEY (`fk_voucher_type_account_id`) REFERENCES `voucher_type_account` (`voucher_type_account_id`),
  CONSTRAINT `contra_account_ibfk_3` FOREIGN KEY (`fk_office_bank_id`) REFERENCES `office_bank` (`office_bank_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `country_currency`;
CREATE TABLE `country_currency` (
  `country_currency_id` int(100) NOT NULL AUTO_INCREMENT,
  `country_currency_name` varchar(100) NOT NULL,
  `country_currency_track_number` varchar(100) NOT NULL,
  `country_currency_code` varchar(10) NOT NULL,
  `country_currency_created_by` int(100) NOT NULL,
  `country_currency_created_date` date NOT NULL,
  `country_currency_last_modified_by` int(100) NOT NULL,
  `country_currency_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`country_currency_id`),
  UNIQUE KEY `country_currency_code` (`country_currency_code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `currency_conversion`;
CREATE TABLE `currency_conversion` (
  `currency_conversion_id` int(100) NOT NULL AUTO_INCREMENT,
  `currency_conversion_name` varchar(100) NOT NULL,
  `currency_conversion_track_number` varchar(100) NOT NULL,
  `currency_conversion_month` date NOT NULL,
  `currency_conversion_created_date` date NOT NULL,
  `currency_conversion_created_by` int(100) NOT NULL,
  `currency_conversion_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `currency_conversion_last_modified_by` int(100) NOT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`currency_conversion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `currency_conversion_detail`;
CREATE TABLE `currency_conversion_detail` (
  `currency_conversion_detail_id` int(100) NOT NULL AUTO_INCREMENT,
  `currency_conversion_detail_name` varchar(100) NOT NULL,
  `currency_conversion_detail_track_number` varchar(100) NOT NULL,
  `fk_currency_conversion_id` int(100) NOT NULL,
  `fk_country_currency_id` int(100) NOT NULL,
  `currency_conversion_detail_rate` double(10,2) NOT NULL,
  `currency_conversion_detail_created_by` int(100) NOT NULL,
  `currency_conversion_detail_created_date` date NOT NULL,
  `currency_conversion_detail_last_modified_by` int(100) NOT NULL,
  `currency_conversion_detail_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`currency_conversion_detail_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `dashboard`;
CREATE TABLE `dashboard` (
  `dashboard_id` int(100) NOT NULL AUTO_INCREMENT,
  `dashboard_name` varchar(100) DEFAULT NULL,
  `dashboard_created_date` date DEFAULT NULL,
  `dashboard_created_by` int(100) DEFAULT NULL,
  `dashboard_last_modified_by` int(100) DEFAULT NULL,
  `dashboard_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`dashboard_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `department`;
CREATE TABLE `department` (
  `department_id` int(100) NOT NULL AUTO_INCREMENT,
  `department_track_number` varchar(100) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `department_description` longtext NOT NULL,
  `department_is_active` int(5) NOT NULL DEFAULT '1',
  `department_created_date` date NOT NULL,
  `department_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `department_created_by` int(100) NOT NULL,
  `department_last_modified_by` int(100) NOT NULL,
  `fk_approval_id` int(11) NOT NULL,
  `fk_status_id` int(11) NOT NULL,
  PRIMARY KEY (`department_id`),
  KEY `fk_approval_id` (`fk_approval_id`),
  KEY `fk_status_id` (`fk_status_id`),
  CONSTRAINT `department_ibfk_1` FOREIGN KEY (`fk_approval_id`) REFERENCES `approval` (`approval_id`),
  CONSTRAINT `department_ibfk_2` FOREIGN KEY (`fk_status_id`) REFERENCES `status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `department_user`;
CREATE TABLE `department_user` (
  `department_user_id` int(100) NOT NULL AUTO_INCREMENT,
  `department_user_track_number` varchar(100) NOT NULL,
  `department_user_name` varchar(100) NOT NULL,
  `fk_user_id` int(100) NOT NULL,
  `fk_department_id` int(100) NOT NULL,
  `department_user_created_date` date DEFAULT NULL,
  `department_user_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `department_user_created_by` int(100) DEFAULT NULL,
  `department_user_last_modified_by` int(100) DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`department_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `designation`;
CREATE TABLE `designation` (
  `designation_id` int(100) NOT NULL AUTO_INCREMENT,
  `designation_track_number` varchar(100) NOT NULL,
  `designation_name` varchar(100) NOT NULL,
  `fk_context_definition_id` int(100) NOT NULL,
  `designation_created_date` date NOT NULL,
  `designation_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `designation_created_by` int(100) NOT NULL,
  `designation_last_modified_by` int(100) NOT NULL,
  `fk_approval_id` int(11) NOT NULL,
  `fk_status_id` int(11) NOT NULL,
  PRIMARY KEY (`designation_id`),
  KEY `fk_approval_id` (`fk_approval_id`),
  KEY `fk_status_id` (`fk_status_id`),
  KEY `fk_center_group_hierarchy_id` (`fk_context_definition_id`),
  CONSTRAINT `designation_ibfk_1` FOREIGN KEY (`fk_approval_id`) REFERENCES `approval` (`approval_id`),
  CONSTRAINT `designation_ibfk_2` FOREIGN KEY (`fk_status_id`) REFERENCES `status` (`status_id`),
  CONSTRAINT `designation_ibfk_3` FOREIGN KEY (`fk_context_definition_id`) REFERENCES `context_definition` (`context_definition_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `event`;
CREATE TABLE `event` (
  `event_id` int(100) NOT NULL AUTO_INCREMENT,
  `event_track_number` varchar(100) NOT NULL,
  `event_name` varchar(100) NOT NULL,
  `fk_approve_item_id` int(100) NOT NULL,
  `event_action` int(5) NOT NULL COMMENT '1 = data, 2 = access',
  `event_json_string` longtext NOT NULL,
  `fk_user_id` int(100) NOT NULL,
  `event_created_by` int(100) NOT NULL,
  `event_created_date` date NOT NULL,
  `event_last_modified_by` int(100) NOT NULL,
  `event_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int(100) NOT NULL,
  `fk_status_id` int(100) NOT NULL,
  PRIMARY KEY (`event_id`),
  KEY `fk_approve_item_id` (`fk_approve_item_id`),
  KEY `fk_user_id` (`fk_user_id`),
  CONSTRAINT `event_ibfk_1` FOREIGN KEY (`fk_approve_item_id`) REFERENCES `approve_item` (`approve_item_id`),
  CONSTRAINT `event_ibfk_2` FOREIGN KEY (`fk_user_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `expense_account`;
CREATE TABLE `expense_account` (
  `expense_account_id` int(100) NOT NULL AUTO_INCREMENT,
  `expense_account_track_number` varchar(100) DEFAULT NULL,
  `expense_account_name` varchar(100) DEFAULT NULL,
  `expense_account_description` varchar(100) DEFAULT NULL,
  `expense_account_code` varchar(10) DEFAULT NULL,
  `expense_account_is_admin` int(5) DEFAULT NULL,
  `expense_account_is_active` int(5) DEFAULT NULL,
  `expense_account_is_budgeted` int(5) DEFAULT NULL,
  `fk_income_account_id` int(100) DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  `expense_account_created_date` date DEFAULT NULL,
  `expense_account_last_modified_date` date DEFAULT NULL,
  `expense_account_created_by` int(100) DEFAULT NULL,
  `expense_account_last_modified_by` int(100) DEFAULT NULL,
  PRIMARY KEY (`expense_account_id`),
  KEY `fk_expense_account_income_account_idx` (`fk_income_account_id`),
  CONSTRAINT `fk_expense_account_income_account` FOREIGN KEY (`fk_income_account_id`) REFERENCES `income_account` (`income_account_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='This table holds the expense accounts';


DROP TABLE IF EXISTS `expense_account_office_association`;
CREATE TABLE `expense_account_office_association` (
  `expense_account_office_association_id` int(100) NOT NULL AUTO_INCREMENT,
  `expense_account_office_association_name` varchar(100) NOT NULL,
  `expense_account_office_association_track_number` varchar(100) NOT NULL,
  `fk_expense_account_id` int(100) NOT NULL,
  `fk_office_id` int(100) NOT NULL,
  `expense_account_office_association_created_date` date NOT NULL,
  `expense_account_office_association_created_by` int(100) NOT NULL,
  `expense_account_office_association_last_modified_by` int(100) NOT NULL,
  `expense_account_office_association_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_status_id` int(11) DEFAULT NULL,
  `fk_approval_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`expense_account_office_association_id`),
  KEY `fk_expense_account_id` (`fk_expense_account_id`),
  KEY `fk_office_id` (`fk_office_id`),
  CONSTRAINT `expense_account_office_association_ibfk_1` FOREIGN KEY (`fk_expense_account_id`) REFERENCES `expense_account` (`expense_account_id`),
  CONSTRAINT `expense_account_office_association_ibfk_2` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `financial_report`;
CREATE TABLE `financial_report` (
  `financial_report_id` int(100) NOT NULL AUTO_INCREMENT,
  `financial_report_track_number` varchar(100) NOT NULL,
  `financial_report_name` varchar(100) NOT NULL,
  `financial_report_month` date NOT NULL,
  `fk_office_id` int(100) NOT NULL,
  `financial_report_statement_date` date NOT NULL DEFAULT '0000-00-00',
  `financial_report_is_submitted` int(5) NOT NULL DEFAULT '0',
  `financial_report_created_date` date DEFAULT NULL,
  `financial_report_created_by` int(100) DEFAULT NULL,
  `financial_report_last_modified_by` int(100) DEFAULT NULL,
  `financial_report_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`financial_report_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `funder`;
CREATE TABLE `funder` (
  `funder_id` int(100) NOT NULL AUTO_INCREMENT,
  `funder_track_number` varchar(100) DEFAULT NULL,
  `funder_name` varchar(45) DEFAULT NULL,
  `funder_description` varchar(45) DEFAULT NULL,
  `fk_account_system_id` int(100) DEFAULT NULL,
  `funder_created_date` date DEFAULT NULL,
  `funder_last_modified_date` date DEFAULT NULL,
  `funder_created_by` int(100) DEFAULT NULL,
  `funder_last_modified_by` int(100) DEFAULT NULL,
  `funder_deleted_at` datetime DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`funder_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='This table holds donor (funders) bio-information';


DROP TABLE IF EXISTS `funding_status`;
CREATE TABLE `funding_status` (
  `funding_status_id` int(100) NOT NULL AUTO_INCREMENT,
  `funding_status_track_number` varchar(100) DEFAULT NULL,
  `funding_status_name` varchar(100) DEFAULT NULL,
  `funding_status_is_active` int(5) DEFAULT NULL,
  `fk_account_system_id` int(100) DEFAULT NULL,
  `funding_status_created_date` date DEFAULT NULL,
  `funding_status_created_by` int(100) DEFAULT NULL,
  `funding_status_last_modified_by` int(100) DEFAULT NULL,
  `funding_status_last_modified_date` date DEFAULT NULL,
  `funding_status_is_available` int(5) NOT NULL DEFAULT '0',
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`funding_status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `history`;
CREATE TABLE `history` (
  `history_id` int(100) NOT NULL AUTO_INCREMENT,
  `reference_table` varchar(45) DEFAULT NULL,
  `user_id` varchar(45) DEFAULT NULL,
  `table_action` varchar(45) DEFAULT NULL,
  `history_created_date` date DEFAULT NULL,
  `history_created_by` int(100) DEFAULT NULL,
  `history_last_modified_by` int(100) DEFAULT NULL,
  `history_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`history_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `income_account`;
CREATE TABLE `income_account` (
  `income_account_id` int(11) NOT NULL AUTO_INCREMENT,
  `income_account_track_number` varchar(100) NOT NULL,
  `income_account_name` varchar(100) NOT NULL,
  `income_account_description` varchar(100) DEFAULT NULL,
  `income_account_code` varchar(10) DEFAULT NULL,
  `income_account_is_active` int(5) DEFAULT NULL,
  `income_account_is_budgeted` int(5) DEFAULT NULL,
  `income_account_is_donor_funded` int(5) DEFAULT NULL,
  `fk_account_system_id` int(100) DEFAULT NULL,
  `income_account_created_date` date DEFAULT NULL,
  `income_account_last_modified_date` date DEFAULT NULL,
  `income_account_created_by` int(100) DEFAULT NULL,
  `income_account_last_modified_by` int(100) DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`income_account_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  CONSTRAINT `income_account_ibfk_1` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='This table contains the income accounts. ';


DROP TABLE IF EXISTS `journal`;
CREATE TABLE `journal` (
  `journal_id` int(11) NOT NULL AUTO_INCREMENT,
  `journal_track_number` varchar(100) NOT NULL,
  `journal_name` varchar(100) NOT NULL,
  `journal_month` date NOT NULL,
  `fk_office_id` int(100) NOT NULL,
  `journal_created_date` date DEFAULT NULL,
  `journal_created_by` int(100) DEFAULT NULL,
  `journal_last_modified_by` int(100) DEFAULT NULL,
  `journal_last_modified_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`journal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `language`;
CREATE TABLE `language` (
  `language_id` int(100) NOT NULL AUTO_INCREMENT,
  `language_track_number` varchar(100) NOT NULL,
  `language_name` varchar(100) DEFAULT NULL,
  `language_code` varchar(10) DEFAULT NULL,
  `language_created_date` date DEFAULT NULL,
  `language_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `language_deleted_at` date DEFAULT NULL,
  `language_created_by` int(100) DEFAULT NULL,
  `language_last_modified_by` int(100) DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`language_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `language_phrase`;
CREATE TABLE `language_phrase` (
  `language_phrase_id` int(11) NOT NULL AUTO_INCREMENT,
  `phrase` longtext,
  `created_date` date DEFAULT NULL,
  `last_modified_date` date DEFAULT NULL,
  `deleted_date` date DEFAULT NULL,
  `created_by` int(100) DEFAULT NULL,
  `last_modified_by` int(100) DEFAULT NULL,
  `language_phrase_created_date` date DEFAULT NULL,
  `language_phrase_created_by` int(100) DEFAULT NULL,
  `language_phrase_last_modified_by` int(100) DEFAULT NULL,
  `language_phrase_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`language_phrase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `menu`;
CREATE TABLE `menu` (
  `menu_id` int(100) NOT NULL AUTO_INCREMENT,
  `menu_name` varchar(100) DEFAULT NULL,
  `menu_derivative_controller` varchar(100) DEFAULT NULL,
  `menu_is_active` int(5) NOT NULL DEFAULT '1',
  `menu_created_date` date DEFAULT NULL,
  `menu_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `menu_created_by` int(100) DEFAULT NULL,
  `menu_last_modified_by` int(100) DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`menu_id`),
  UNIQUE KEY `menu_derivative_controller` (`menu_derivative_controller`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `menu_user_order`;
CREATE TABLE `menu_user_order` (
  `menu_user_order_id` int(100) NOT NULL AUTO_INCREMENT,
  `fk_user_id` int(100) NOT NULL,
  `menu_user_order_track_number` varchar(100) DEFAULT NULL,
  `menu_user_order_name` varchar(100) DEFAULT NULL,
  `fk_menu_id` int(100) DEFAULT NULL,
  `menu_user_order_is_active` int(5) NOT NULL DEFAULT '1',
  `menu_user_order_level` int(100) NOT NULL DEFAULT '1',
  `menu_user_order_priority_item` int(5) NOT NULL DEFAULT '1',
  `menu_user_order_created_date` date DEFAULT NULL,
  `menu_user_order_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `menu_user_order_created_by` int(100) DEFAULT NULL,
  `menu_user_order_last_modified_by` int(100) DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`menu_user_order_id`),
  KEY `fk_menu_id` (`fk_menu_id`),
  CONSTRAINT `menu_user_order_ibfk_1` FOREIGN KEY (`fk_menu_id`) REFERENCES `menu` (`menu_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `message`;
CREATE TABLE `message` (
  `message_id` int(100) NOT NULL AUTO_INCREMENT,
  `message_track_number` varchar(100) DEFAULT NULL,
  `message_name` varchar(100) DEFAULT NULL,
  `fk_approve_item_id` int(100) DEFAULT NULL,
  `message_record_key` int(100) DEFAULT NULL,
  `message_created_by` int(100) DEFAULT NULL,
  `message_last_modified_by` int(100) DEFAULT NULL,
  `message_created_date` date DEFAULT NULL,
  `message_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `message_deleted_date` date DEFAULT NULL,
  `message_is_thread_open` int(5) DEFAULT '1',
  `fk_status_id` int(100) DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`message_id`),
  KEY `fk_approve_item_id` (`fk_approve_item_id`),
  CONSTRAINT `message_ibfk_1` FOREIGN KEY (`fk_approve_item_id`) REFERENCES `approve_item` (`approve_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `message_detail`;
CREATE TABLE `message_detail` (
  `message_detail_id` int(100) NOT NULL AUTO_INCREMENT,
  `message_detail_track_number` varchar(100) NOT NULL,
  `message_detail_name` varchar(100) NOT NULL,
  `fk_user_id` int(100) DEFAULT NULL,
  `message_detail_content` longtext,
  `fk_message_id` int(100) DEFAULT NULL,
  `message_detail_created_date` datetime DEFAULT NULL,
  `message_detail_last_modified_date` date DEFAULT NULL,
  `message_detail_deleted_date` date DEFAULT NULL,
  `message_detail_created_by` int(100) DEFAULT NULL,
  `message_detail_last_modified_by` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `message_detail_is_reply` int(5) DEFAULT '0',
  `message_detail_replied_message_key` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`message_detail_id`),
  KEY `fk_message_detail_message1_idx` (`fk_message_id`),
  CONSTRAINT `fk_message_detail_message1` FOREIGN KEY (`fk_message_id`) REFERENCES `message` (`message_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `month`;
CREATE TABLE `month` (
  `month_id` int(11) NOT NULL AUTO_INCREMENT,
  `month_track_number` varchar(100) NOT NULL,
  `month_number` int(5) NOT NULL,
  `month_name` varchar(50) NOT NULL,
  `month_order` int(5) NOT NULL DEFAULT '0',
  `fk_status_id` int(100) NOT NULL,
  `month_created_by` int(100) NOT NULL,
  `month_last_modified_by` int(100) NOT NULL,
  `month_created_date` date NOT NULL,
  `month_last_modified_date` date NOT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`month_id`),
  UNIQUE KEY `month_number` (`month_number`),
  UNIQUE KEY `month_order` (`month_order`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `office`;
CREATE TABLE `office` (
  `office_id` int(100) NOT NULL AUTO_INCREMENT,
  `office_track_number` varchar(100) DEFAULT NULL,
  `office_name` varchar(45) NOT NULL,
  `office_description` longtext NOT NULL,
  `office_code` varchar(45) NOT NULL,
  `fk_context_definition_id` int(100) NOT NULL,
  `office_start_date` date NOT NULL,
  `office_end_date` date DEFAULT '0000-00-00',
  `office_is_active` int(5) NOT NULL DEFAULT '1',
  `office_is_readonly` int(5) NOT NULL DEFAULT '0',
  `fk_account_system_id` int(100) NOT NULL DEFAULT '1',
  `fk_country_currency_id` int(100) NOT NULL,
  `office_created_by` int(100) NOT NULL,
  `office_created_date` date NOT NULL,
  `office_last_modified_date` date NOT NULL,
  `office_last_modified_by` int(100) NOT NULL,
  `fk_approval_id` int(11) DEFAULT NULL,
  `fk_status_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`office_id`),
  UNIQUE KEY `office_code` (`office_code`),
  KEY `fk_context_definition_id` (`fk_context_definition_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  KEY `fk_country_currency_id` (`fk_country_currency_id`),
  CONSTRAINT `office_ibfk_1` FOREIGN KEY (`fk_context_definition_id`) REFERENCES `context_definition` (`context_definition_id`),
  CONSTRAINT `office_ibfk_2` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`),
  CONSTRAINT `office_ibfk_3` FOREIGN KEY (`fk_country_currency_id`) REFERENCES `country_currency` (`country_currency_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='This table list all the remote sites for the organization';


DROP TABLE IF EXISTS `office_bank`;
CREATE TABLE `office_bank` (
  `office_bank_id` int(100) NOT NULL AUTO_INCREMENT,
  `office_bank_track_number` varchar(100) DEFAULT NULL,
  `office_bank_name` varchar(100) DEFAULT NULL,
  `office_bank_account_number` varchar(100) DEFAULT NULL,
  `fk_office_id` int(100) DEFAULT NULL,
  `fk_bank_id` int(100) DEFAULT NULL,
  `office_bank_is_active` int(5) DEFAULT '1',
  `office_bank_is_default` int(5) DEFAULT '0',
  `office_bank_created_date` date DEFAULT NULL,
  `office_bank_created_by` int(100) DEFAULT NULL,
  `office_bank_last_modified_date` timestamp NULL DEFAULT NULL,
  `office_bank_last_modified_by` int(100) DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`office_bank_id`),
  KEY `fk_office_id` (`fk_office_id`),
  KEY `fk_bank_id` (`fk_bank_id`),
  CONSTRAINT `office_bank_ibfk_1` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`),
  CONSTRAINT `office_bank_ibfk_2` FOREIGN KEY (`fk_bank_id`) REFERENCES `bank` (`bank_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `office_bank_project_allocation`;
CREATE TABLE `office_bank_project_allocation` (
  `office_bank_project_allocation_id` int(100) NOT NULL AUTO_INCREMENT,
  `office_bank_project_allocation_name` varchar(100) NOT NULL,
  `office_bank_project_allocation_track_number` varchar(100) NOT NULL,
  `fk_office_bank_id` int(100) NOT NULL,
  `fk_project_allocation_id` int(100) NOT NULL,
  `office_bank_project_allocation_created_date` date NOT NULL,
  `office_bank_project_allocation_created_by` int(100) NOT NULL,
  `office_bank_project_allocation_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `office_bank_project_allocation_last_modified_by` int(100) NOT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`office_bank_project_allocation_id`),
  KEY `fk_office_bank_id` (`fk_office_bank_id`),
  KEY `fk_project_allocation_id` (`fk_project_allocation_id`),
  CONSTRAINT `office_bank_project_allocation_ibfk_1` FOREIGN KEY (`fk_office_bank_id`) REFERENCES `office_bank` (`office_bank_id`),
  CONSTRAINT `office_bank_project_allocation_ibfk_2` FOREIGN KEY (`fk_project_allocation_id`) REFERENCES `project_allocation` (`project_allocation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `office_cash`;
CREATE TABLE `office_cash` (
  `office_cash_id` int(100) NOT NULL AUTO_INCREMENT,
  `office_cash_name` varchar(100) NOT NULL,
  `office_cash_track_number` varchar(100) NOT NULL,
  `fk_account_system_id` int(100) NOT NULL,
  `office_cash_is_active` int(5) NOT NULL DEFAULT '1',
  `office_cash_created_by` int(100) NOT NULL,
  `office_cash_created_date` date NOT NULL,
  `office_cash_last_modified_by` int(100) NOT NULL,
  `office_cash_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`office_cash_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `office_group`;
CREATE TABLE `office_group` (
  `office_group_id` int(100) NOT NULL AUTO_INCREMENT,
  `office_group_track_number` varchar(100) NOT NULL,
  `office_group_name` varchar(100) NOT NULL,
  `fk_account_system_id` int(100) NOT NULL,
  `office_group_created_by` int(100) NOT NULL,
  `office_group_created_date` date NOT NULL,
  `office_group_last_modified_by` int(100) NOT NULL,
  `office_group_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int(11) NOT NULL,
  `fk_status_id` int(11) NOT NULL,
  PRIMARY KEY (`office_group_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  KEY `fk_approval_id` (`fk_approval_id`),
  KEY `fk_status_id` (`fk_status_id`),
  CONSTRAINT `office_group_ibfk_1` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`),
  CONSTRAINT `office_group_ibfk_2` FOREIGN KEY (`fk_approval_id`) REFERENCES `approval` (`approval_id`),
  CONSTRAINT `office_group_ibfk_3` FOREIGN KEY (`fk_status_id`) REFERENCES `status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `office_group_association`;
CREATE TABLE `office_group_association` (
  `office_group_association_id` int(11) NOT NULL AUTO_INCREMENT,
  `office_group_association_name` varchar(100) NOT NULL,
  `office_group_association_track_number` varchar(100) NOT NULL,
  `fk_office_group_id` int(100) NOT NULL,
  `fk_office_id` int(100) NOT NULL,
  `office_group_association_is_lead` int(5) NOT NULL DEFAULT '0',
  `office_group_association_created_by` int(100) NOT NULL,
  `office_group_association_created_date` date NOT NULL,
  `office_group_association_last_modified_by` int(100) NOT NULL,
  `office_group_association_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int(11) NOT NULL,
  `fk_status_id` int(11) NOT NULL,
  PRIMARY KEY (`office_group_association_id`),
  KEY `fk_office_id` (`fk_office_id`),
  KEY `fk_approval_id` (`fk_approval_id`),
  KEY `fk_status_id` (`fk_status_id`),
  KEY `fk_office_group_id` (`fk_office_group_id`),
  CONSTRAINT `office_group_association_ibfk_1` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`),
  CONSTRAINT `office_group_association_ibfk_2` FOREIGN KEY (`fk_approval_id`) REFERENCES `approval` (`approval_id`),
  CONSTRAINT `office_group_association_ibfk_3` FOREIGN KEY (`fk_status_id`) REFERENCES `status` (`status_id`),
  CONSTRAINT `office_group_association_ibfk_4` FOREIGN KEY (`fk_office_group_id`) REFERENCES `office_group` (`office_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `opening_allocation_balance`;
CREATE TABLE `opening_allocation_balance` (
  `opening_allocation_balance_id` int(100) NOT NULL AUTO_INCREMENT,
  `fk_system_opening_balance_id` int(100) NOT NULL,
  `opening_allocation_balance_track_number` varchar(100) NOT NULL,
  `opening_allocation_balance_name` varchar(100) NOT NULL,
  `fk_project_allocation_id` int(100) NOT NULL,
  `opening_allocation_balance_amount` decimal(10,2) NOT NULL,
  `opening_allocation_balance_created_date` date DEFAULT NULL,
  `opening_allocation_balance_created_by` int(100) DEFAULT NULL,
  `opening_allocation_balance_last_modified_by` int(100) DEFAULT NULL,
  `opening_allocation_balance_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`opening_allocation_balance_id`),
  KEY `system_opening_balance_id` (`fk_system_opening_balance_id`),
  KEY `fk_project_allocation_id` (`fk_project_allocation_id`),
  CONSTRAINT `opening_allocation_balance_ibfk_1` FOREIGN KEY (`fk_system_opening_balance_id`) REFERENCES `system_opening_balance` (`system_opening_balance_id`),
  CONSTRAINT `opening_allocation_balance_ibfk_2` FOREIGN KEY (`fk_project_allocation_id`) REFERENCES `project_allocation` (`project_allocation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `opening_bank_balance`;
CREATE TABLE `opening_bank_balance` (
  `opening_bank_balance_id` int(100) NOT NULL AUTO_INCREMENT,
  `fk_system_opening_balance_id` int(100) NOT NULL,
  `opening_bank_balance_track_number` varchar(100) NOT NULL,
  `opening_bank_balance_name` varchar(100) NOT NULL,
  `opening_bank_balance_amount` decimal(10,2) NOT NULL,
  `fk_office_bank_id` int(100) NOT NULL,
  `opening_bank_balance_created_date` date NOT NULL,
  `opening_bank_balance_created_by` int(100) NOT NULL,
  `opening_bank_balance_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `opening_bank_balance_last_modified_by` int(100) NOT NULL,
  `fk_status_id` int(100) NOT NULL,
  `fk_approval_id` int(100) NOT NULL,
  PRIMARY KEY (`opening_bank_balance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `opening_cash_balance`;
CREATE TABLE `opening_cash_balance` (
  `opening_cash_balance_id` int(100) NOT NULL AUTO_INCREMENT,
  `opening_cash_balance_track_number` varchar(100) NOT NULL,
  `opening_cash_balance_name` varchar(100) NOT NULL,
  `fk_system_opening_balance_id` int(100) NOT NULL,
  `fk_office_bank_id` int(100) NOT NULL,
  `fk_office_cash_id` int(100) NOT NULL,
  `opening_cash_balance_amount` decimal(10,2) NOT NULL,
  `opening_cash_balance_created_date` date DEFAULT NULL,
  `opening_cash_balance_created_by` int(100) DEFAULT NULL,
  `opening_cash_balance_last_modified_by` int(100) DEFAULT NULL,
  `opening_cash_balance_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`opening_cash_balance_id`),
  KEY `fk_system_opening_balance_id` (`fk_system_opening_balance_id`),
  KEY `fk_office_bank_id` (`fk_office_bank_id`),
  KEY `fk_office_cash_id` (`fk_office_cash_id`),
  CONSTRAINT `opening_cash_balance_ibfk_1` FOREIGN KEY (`fk_system_opening_balance_id`) REFERENCES `system_opening_balance` (`system_opening_balance_id`),
  CONSTRAINT `opening_cash_balance_ibfk_2` FOREIGN KEY (`fk_office_bank_id`) REFERENCES `office_bank` (`office_bank_id`),
  CONSTRAINT `opening_cash_balance_ibfk_3` FOREIGN KEY (`fk_office_cash_id`) REFERENCES `office_cash` (`office_cash_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `opening_deposit_transit`;
CREATE TABLE `opening_deposit_transit` (
  `opening_deposit_transit_id` int(100) NOT NULL AUTO_INCREMENT,
  `opening_deposit_transit_track_number` varchar(100) NOT NULL,
  `opening_deposit_transit_name` varchar(100) NOT NULL,
  `fk_system_opening_balance_id` int(100) NOT NULL,
  `fk_office_bank_id` int(100) NOT NULL,
  `opening_deposit_transit_date` date NOT NULL,
  `opening_deposit_transit_description` longtext NOT NULL,
  `opening_deposit_transit_amount` decimal(10,2) NOT NULL,
  `opening_deposit_transit_is_cleared` int(5) NOT NULL DEFAULT '0',
  `opening_deposit_transit_cleared_date` date NOT NULL DEFAULT '0000-00-00',
  `opening_deposit_transit_created_date` date DEFAULT NULL,
  `opening_deposit_transit_created_by` int(100) DEFAULT NULL,
  `opening_deposit_transit_last_modified_by` int(100) DEFAULT NULL,
  `opening_deposit_transit_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`opening_deposit_transit_id`),
  KEY `fk_system_opening_balance_id` (`fk_system_opening_balance_id`),
  CONSTRAINT `opening_deposit_transit_ibfk_1` FOREIGN KEY (`fk_system_opening_balance_id`) REFERENCES `system_opening_balance` (`system_opening_balance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `opening_fund_balance`;
CREATE TABLE `opening_fund_balance` (
  `opening_fund_balance_id` int(100) NOT NULL AUTO_INCREMENT,
  `fk_system_opening_balance_id` int(100) NOT NULL,
  `opening_fund_balance_track_number` varchar(100) NOT NULL,
  `opening_fund_balance_name` varchar(100) NOT NULL,
  `fk_income_account_id` int(11) NOT NULL,
  `fk_office_bank_id` int(11) NOT NULL,
  `opening_fund_balance_amount` decimal(10,2) NOT NULL,
  `opening_fund_balance_created_date` date DEFAULT NULL,
  `opening_fund_balance_created_by` int(100) DEFAULT NULL,
  `opening_fund_balance_last_modified_by` int(100) DEFAULT NULL,
  `opening_fund_balance_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`opening_fund_balance_id`),
  KEY `fk_system_opening_balance_id` (`fk_system_opening_balance_id`),
  KEY `fk_income_account_id` (`fk_income_account_id`),
  CONSTRAINT `opening_fund_balance_ibfk_1` FOREIGN KEY (`fk_system_opening_balance_id`) REFERENCES `system_opening_balance` (`system_opening_balance_id`),
  CONSTRAINT `opening_fund_balance_ibfk_2` FOREIGN KEY (`fk_income_account_id`) REFERENCES `income_account` (`income_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `opening_outstanding_cheque`;
CREATE TABLE `opening_outstanding_cheque` (
  `opening_outstanding_cheque_id` int(100) NOT NULL AUTO_INCREMENT,
  `opening_outstanding_cheque_name` varchar(100) NOT NULL,
  `opening_outstanding_cheque_track_number` varchar(100) NOT NULL,
  `opening_outstanding_cheque_description` longtext NOT NULL,
  `opening_outstanding_cheque_date` date NOT NULL,
  `fk_system_opening_balance_id` int(100) NOT NULL,
  `fk_office_bank_id` int(100) NOT NULL,
  `opening_outstanding_cheque_number` int(50) NOT NULL,
  `opening_outstanding_cheque_amount` decimal(10,2) NOT NULL,
  `opening_outstanding_cheque_is_cleared` int(5) NOT NULL DEFAULT '0',
  `opening_outstanding_cheque_cleared_date` date NOT NULL DEFAULT '0000-00-00',
  `opening_outstanding_cheque_created_date` date DEFAULT NULL,
  `opening_outstanding_cheque_created_by` int(100) DEFAULT NULL,
  `opening_outstanding_cheque_last_modified_by` int(100) DEFAULT NULL,
  `opening_outstanding_cheque_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`opening_outstanding_cheque_id`),
  KEY `fk_system_opening_balance_id` (`fk_system_opening_balance_id`),
  CONSTRAINT `opening_outstanding_cheque_ibfk_1` FOREIGN KEY (`fk_system_opening_balance_id`) REFERENCES `system_opening_balance` (`system_opening_balance_id`),
  CONSTRAINT `opening_outstanding_cheque_ibfk_2` FOREIGN KEY (`fk_system_opening_balance_id`) REFERENCES `system_opening_balance` (`system_opening_balance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `page_view`;
CREATE TABLE `page_view` (
  `page_view_id` int(100) NOT NULL AUTO_INCREMENT,
  `page_view_track_number` varchar(100) NOT NULL,
  `page_view_name` varchar(100) NOT NULL,
  `page_view_description` longtext NOT NULL,
  `fk_menu_id` int(100) NOT NULL,
  `page_view_is_default` int(5) NOT NULL DEFAULT '0' COMMENT 'System Admin default ',
  `page_view_created_date` date NOT NULL,
  `page_view_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `page_view_created_by` int(100) NOT NULL,
  `page_view_last_modified_by` int(100) NOT NULL,
  `fk_approval_id` int(11) NOT NULL,
  `fk_status_id` int(11) NOT NULL,
  PRIMARY KEY (`page_view_id`),
  KEY `fk_approval_id` (`fk_approval_id`),
  KEY `fk_status_id` (`fk_status_id`),
  KEY `fk_menu_id` (`fk_menu_id`),
  CONSTRAINT `page_view_ibfk_1` FOREIGN KEY (`fk_approval_id`) REFERENCES `approval` (`approval_id`),
  CONSTRAINT `page_view_ibfk_2` FOREIGN KEY (`fk_status_id`) REFERENCES `status` (`status_id`),
  CONSTRAINT `page_view_ibfk_3` FOREIGN KEY (`fk_menu_id`) REFERENCES `menu` (`menu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `page_view_condition`;
CREATE TABLE `page_view_condition` (
  `page_view_condition_id` int(100) NOT NULL AUTO_INCREMENT,
  `page_view_condition_track_number` varchar(100) NOT NULL,
  `page_view_condition_name` varchar(100) DEFAULT NULL,
  `page_view_condition_field` varchar(100) NOT NULL,
  `page_view_condition_operator` varchar(50) NOT NULL,
  `page_view_condition_value` varchar(100) NOT NULL,
  `fk_page_view_id` int(100) NOT NULL,
  `page_view_condition_created_date` date NOT NULL,
  `page_view_condition_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `page_view_condition_created_by` int(100) NOT NULL,
  `page_view_condition_last_modified_by` int(100) NOT NULL,
  `fk_approval_id` int(11) DEFAULT NULL,
  `fk_status_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`page_view_condition_id`),
  KEY `fk_approval_id` (`fk_approval_id`),
  KEY `fk_status_id` (`fk_status_id`),
  KEY `fk_page_view_id` (`fk_page_view_id`),
  CONSTRAINT `page_view_condition_ibfk_3` FOREIGN KEY (`fk_page_view_id`) REFERENCES `page_view` (`page_view_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `page_view_role`;
CREATE TABLE `page_view_role` (
  `page_view_role_id` int(100) NOT NULL AUTO_INCREMENT,
  `page_view_role_track_number` varchar(100) NOT NULL,
  `page_view_role_name` varchar(100) DEFAULT NULL,
  `page_view_role_is_default` int(5) DEFAULT '0',
  `fk_page_view_id` int(100) DEFAULT NULL,
  `fk_role_id` int(100) DEFAULT NULL,
  `page_view_role_created_date` date NOT NULL,
  `page_view_role_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `page_view_role_created_by` int(100) NOT NULL,
  `page_view_role_last_modified_by` int(100) NOT NULL,
  `fk_approval_id` int(11) NOT NULL,
  `fk_status_id` int(11) NOT NULL,
  PRIMARY KEY (`page_view_role_id`),
  KEY `fk_page_view_id` (`fk_page_view_id`),
  KEY `fk_role_id` (`fk_role_id`),
  KEY `fk_approval_id` (`fk_approval_id`),
  KEY `fk_status_id` (`fk_status_id`),
  CONSTRAINT `page_view_role_ibfk_1` FOREIGN KEY (`fk_page_view_id`) REFERENCES `page_view` (`page_view_id`),
  CONSTRAINT `page_view_role_ibfk_2` FOREIGN KEY (`fk_role_id`) REFERENCES `role` (`role_id`),
  CONSTRAINT `page_view_role_ibfk_3` FOREIGN KEY (`fk_approval_id`) REFERENCES `approval` (`approval_id`),
  CONSTRAINT `page_view_role_ibfk_4` FOREIGN KEY (`fk_status_id`) REFERENCES `status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `permission`;
CREATE TABLE `permission` (
  `permission_id` int(11) NOT NULL AUTO_INCREMENT,
  `permission_track_number` varchar(100) NOT NULL,
  `permission_name` varchar(100) NOT NULL,
  `permission_description` longtext NOT NULL,
  `permission_is_active` int(5) NOT NULL,
  `fk_permission_label_id` int(100) NOT NULL,
  `permission_type` int(5) NOT NULL DEFAULT '1' COMMENT 'Type 1 = Page Access, 2 = Field Access',
  `permission_field` varchar(100) NOT NULL,
  `permission_is_global` int(5) NOT NULL DEFAULT '1',
  `fk_menu_id` int(100) DEFAULT NULL,
  `fk_approval_id` int(11) DEFAULT NULL,
  `fk_status_id` int(11) DEFAULT NULL,
  `permission_created_date` date DEFAULT NULL,
  `permission_created_by` int(100) DEFAULT NULL,
  `permission_deleted_at` date DEFAULT NULL,
  `permission_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `permission_last_modified_by` int(100) DEFAULT NULL,
  PRIMARY KEY (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `permission_label`;
CREATE TABLE `permission_label` (
  `permission_label_id` int(100) NOT NULL AUTO_INCREMENT,
  `permission_label_track_number` varchar(100) NOT NULL,
  `permission_label_name` varchar(100) NOT NULL,
  `permission_label_description` varchar(100) NOT NULL,
  `permission_label_depth` int(10) NOT NULL,
  `fk_approval_id` int(100) NOT NULL,
  `fk_status_id` int(100) NOT NULL,
  `permission_label_created_date` date NOT NULL,
  `permission_label_created_by` int(100) NOT NULL,
  `permission_label_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `permission_label_last_modified_by` int(100) NOT NULL,
  PRIMARY KEY (`permission_label_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `permission_template`;
CREATE TABLE `permission_template` (
  `permission_template_id` int(100) NOT NULL AUTO_INCREMENT,
  `permission_template_track_number` varchar(100) NOT NULL,
  `permission_template_name` varchar(100) NOT NULL,
  `fk_role_group_id` int(100) NOT NULL,
  `fk_permission_id` int(11) NOT NULL,
  `permission_template_created_date` date NOT NULL,
  `permission_template_created_by` int(100) NOT NULL,
  `permission_template_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `permission_template_last_modified_by` int(100) NOT NULL,
  `fk_status_id` int(100) NOT NULL,
  `fk_approval_id` int(100) NOT NULL,
  PRIMARY KEY (`permission_template_id`),
  KEY `fk_role_group_id` (`fk_role_group_id`),
  KEY `fk_permission_id` (`fk_permission_id`),
  CONSTRAINT `permission_template_ibfk_2` FOREIGN KEY (`fk_role_group_id`) REFERENCES `role_group` (`role_group_id`),
  CONSTRAINT `permission_template_ibfk_3` FOREIGN KEY (`fk_permission_id`) REFERENCES `permission` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `project`;
CREATE TABLE `project` (
  `project_id` int(100) NOT NULL AUTO_INCREMENT,
  `project_track_number` varchar(100) DEFAULT NULL,
  `project_name` varchar(100) DEFAULT NULL,
  `project_code` varchar(10) NOT NULL,
  `project_description` longtext,
  `project_start_date` date NOT NULL,
  `project_end_date` date DEFAULT '0000-00-00',
  `fk_funder_id` int(100) NOT NULL,
  `project_cost` double(10,2) DEFAULT '0.00',
  `fk_funding_status_id` int(100) DEFAULT NULL,
  `project_is_default` int(5) DEFAULT '0',
  `project_created_by` int(100) NOT NULL,
  `project_last_modified_by` int(100) NOT NULL,
  `project_created_date` date NOT NULL,
  `project_last_modified_date` date NOT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`project_id`),
  KEY `fk_funder_id` (`fk_funder_id`),
  KEY `fk_funding_status_id` (`fk_funding_status_id`),
  CONSTRAINT `project_ibfk_2` FOREIGN KEY (`fk_funder_id`) REFERENCES `funder` (`funder_id`),
  CONSTRAINT `project_ibfk_3` FOREIGN KEY (`fk_funding_status_id`) REFERENCES `funding_status` (`funding_status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='A project is a single funded proposal that need to be implemented and reported as a unit. It''s related to single funder ';


DROP TABLE IF EXISTS `project_allocation`;
CREATE TABLE `project_allocation` (
  `project_allocation_id` int(100) NOT NULL AUTO_INCREMENT,
  `project_allocation_track_number` varchar(100) DEFAULT NULL,
  `fk_project_id` int(100) DEFAULT NULL,
  `project_allocation_name` varchar(100) DEFAULT NULL,
  `project_allocation_amount` int(100) DEFAULT '0',
  `project_allocation_is_active` int(5) DEFAULT '1',
  `fk_office_id` int(100) DEFAULT NULL,
  `fk_status_id` int(11) DEFAULT NULL,
  `fk_approval_id` int(11) DEFAULT NULL,
  `project_allocation_extended_end_date` date DEFAULT '0000-00-00',
  `project_allocation_created_date` date DEFAULT NULL,
  `project_allocation_last_modified_date` varchar(45) DEFAULT NULL,
  `project_allocation_created_by` int(100) DEFAULT NULL,
  `project_allocation_last_modified_by` int(100) DEFAULT NULL,
  PRIMARY KEY (`project_allocation_id`),
  KEY `fk_project_id` (`fk_project_id`),
  KEY `fk_office_id` (`fk_office_id`),
  CONSTRAINT `project_allocation_ibfk_1` FOREIGN KEY (`fk_project_id`) REFERENCES `project` (`project_id`),
  CONSTRAINT `project_allocation_ibfk_2` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `project_allocation_detail`;
CREATE TABLE `project_allocation_detail` (
  `project_allocation_detail_id` int(11) NOT NULL,
  `project_allocation_detail_track_number` varchar(100) NOT NULL,
  `project_allocation_detail_name` varchar(100) NOT NULL,
  `fk_project_allocation_id` int(100) NOT NULL,
  `project_allocation_detail_month` date NOT NULL,
  `project_allocation_detail_amount` decimal(10,2) NOT NULL,
  `project_allocation_detail_created_date` date DEFAULT NULL,
  `project_allocation_detail_created_by` int(100) DEFAULT NULL,
  `project_allocation_detail_last_modified_by` int(100) DEFAULT NULL,
  `project_allocation_detail_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  KEY `fk_project_allocation_id` (`fk_project_allocation_id`),
  CONSTRAINT `project_allocation_detail_ibfk_1` FOREIGN KEY (`fk_project_allocation_id`) REFERENCES `project_allocation` (`project_allocation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `project_cost_proportion`;
CREATE TABLE `project_cost_proportion` (
  `project_cost_proportion_id` int(11) NOT NULL,
  `voucher_detail_id` int(100) DEFAULT NULL,
  `amount` varchar(45) DEFAULT NULL,
  `created_by` varchar(45) DEFAULT NULL,
  `created_date` varchar(45) DEFAULT NULL,
  `last_modified_by` varchar(45) DEFAULT NULL,
  `last_modified_date` varchar(45) DEFAULT NULL,
  `center_project_allocation_id` int(100) DEFAULT NULL,
  `project_cost_proportion_created_date` date DEFAULT NULL,
  `project_cost_proportion_created_by` int(100) DEFAULT NULL,
  `project_cost_proportion_last_modified_by` int(100) DEFAULT NULL,
  `project_cost_proportion_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`project_cost_proportion_id`),
  KEY `voucher_detail_id` (`voucher_detail_id`),
  CONSTRAINT `project_cost_proportion_ibfk_1` FOREIGN KEY (`voucher_detail_id`) REFERENCES `voucher_detail` (`voucher_detail_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `project_income_account`;
CREATE TABLE `project_income_account` (
  `project_income_account_id` int(100) NOT NULL AUTO_INCREMENT,
  `project_income_account_name` varchar(100) NOT NULL,
  `project_income_account_track_number` varchar(100) NOT NULL,
  `fk_project_id` int(100) NOT NULL,
  `fk_income_account_id` int(11) NOT NULL,
  `project_income_account_created_date` date NOT NULL,
  `project_income_account_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `project_income_account_created_by` int(100) NOT NULL,
  `project_income_account_last_modified_by` int(100) NOT NULL,
  `fk_approval_id` int(100) NOT NULL,
  `fk_status_id` int(100) NOT NULL,
  PRIMARY KEY (`project_income_account_id`),
  KEY `fk_project_id` (`fk_project_id`),
  KEY `fk_income_account_id` (`fk_income_account_id`),
  CONSTRAINT `project_income_account_ibfk_1` FOREIGN KEY (`fk_project_id`) REFERENCES `project` (`project_id`),
  CONSTRAINT `project_income_account_ibfk_2` FOREIGN KEY (`fk_income_account_id`) REFERENCES `income_account` (`income_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `project_request_type`;
CREATE TABLE `project_request_type` (
  `project_request_type_id` int(100) NOT NULL AUTO_INCREMENT,
  `project_request_type_track_number` varchar(100) NOT NULL,
  `project_request_type_name` varchar(100) NOT NULL,
  `fk_project_id` int(100) NOT NULL,
  `fk_request_type_id` int(11) NOT NULL,
  `project_request_type_created_by` int(100) NOT NULL,
  `project_request_type_created_date` date NOT NULL,
  `project_request_type_last_modified_by` int(100) NOT NULL,
  `project_request_type_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_status_id` int(11) NOT NULL,
  `fk_approval_id` int(11) NOT NULL,
  PRIMARY KEY (`project_request_type_id`),
  KEY `fk_request_type_id` (`fk_request_type_id`),
  KEY `fk_project_id` (`fk_project_id`),
  CONSTRAINT `project_request_type_ibfk_2` FOREIGN KEY (`fk_request_type_id`) REFERENCES `request_type` (`request_type_id`),
  CONSTRAINT `project_request_type_ibfk_3` FOREIGN KEY (`fk_project_id`) REFERENCES `project` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `reconciliation`;
CREATE TABLE `reconciliation` (
  `reconciliation_id` int(100) NOT NULL AUTO_INCREMENT,
  `reconciliation_track_number` varchar(100) DEFAULT NULL,
  `reconciliation_name` varchar(100) DEFAULT NULL,
  `fk_financial_report_id` int(100) DEFAULT NULL,
  `fk_office_bank_id` int(100) DEFAULT NULL,
  `reconciliation_statement_balance` decimal(10,2) DEFAULT NULL,
  `fk_status_id` int(5) DEFAULT NULL,
  `reconciliation_suspense_amount` decimal(10,2) DEFAULT NULL,
  `reconciliation_created_by` int(100) DEFAULT NULL,
  `reconciliation_created_date` date DEFAULT NULL,
  `reconciliation_last_modified_by` int(100) DEFAULT NULL,
  `reconciliation_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`reconciliation_id`),
  KEY `fk_reconciliation_center1_idx` (`fk_financial_report_id`),
  KEY `fk_office_bank_id` (`fk_office_bank_id`),
  CONSTRAINT `reconciliation_ibfk_1` FOREIGN KEY (`fk_financial_report_id`) REFERENCES `financial_report` (`financial_report_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `reconciliation_ibfk_2` FOREIGN KEY (`fk_office_bank_id`) REFERENCES `office_bank` (`office_bank_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `request`;
CREATE TABLE `request` (
  `request_id` int(100) NOT NULL AUTO_INCREMENT,
  `request_track_number` varchar(100) DEFAULT NULL,
  `request_name` varchar(100) DEFAULT NULL,
  `fk_request_type_id` int(11) DEFAULT '1',
  `fk_status_id` int(11) DEFAULT '0',
  `fk_office_id` int(100) DEFAULT NULL,
  `fk_approval_id` int(11) DEFAULT NULL,
  `request_date` date DEFAULT NULL,
  `request_description` varchar(100) DEFAULT NULL,
  `fk_department_id` int(100) NOT NULL,
  `request_is_fully_vouched` int(5) NOT NULL DEFAULT '0',
  `request_created_date` date DEFAULT NULL,
  `request_created_by` varchar(45) DEFAULT NULL,
  `request_last_modified_by` varchar(45) DEFAULT NULL,
  `request_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `request_deleted_at` date DEFAULT NULL,
  PRIMARY KEY (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `request_conversion`;
CREATE TABLE `request_conversion` (
  `request_conversion_id` int(100) NOT NULL AUTO_INCREMENT,
  `request_conversion_name` varchar(100) NOT NULL,
  `request_conversion_track_number` varchar(100) NOT NULL,
  `fk_account_system_id` int(100) NOT NULL,
  `conversion_status_id` int(100) NOT NULL,
  `request_conversion_created_date` date DEFAULT NULL,
  `request_conversion_created_by` int(100) DEFAULT NULL,
  `request_conversion_last_modified_by` int(100) DEFAULT NULL,
  `request_conversion_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`request_conversion_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  KEY `conversion_status_id` (`conversion_status_id`),
  CONSTRAINT `request_conversion_ibfk_1` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`),
  CONSTRAINT `request_conversion_ibfk_2` FOREIGN KEY (`conversion_status_id`) REFERENCES `status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `request_detail`;
CREATE TABLE `request_detail` (
  `request_detail_id` int(100) NOT NULL AUTO_INCREMENT,
  `request_detail_track_number` varchar(100) DEFAULT NULL,
  `request_detail_name` varchar(100) DEFAULT NULL,
  `fk_request_id` int(100) DEFAULT NULL,
  `request_detail_description` varchar(45) DEFAULT NULL,
  `request_detail_quantity` int(10) DEFAULT NULL,
  `request_detail_unit_cost` decimal(10,2) DEFAULT NULL,
  `request_detail_total_cost` decimal(10,2) DEFAULT NULL,
  `fk_expense_account_id` int(100) DEFAULT NULL,
  `fk_project_allocation_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `request_detail_conversion_set` int(5) DEFAULT '0',
  `fk_voucher_id` int(100) NOT NULL DEFAULT '0',
  `request_detail_created_date` date DEFAULT NULL,
  `request_detail_created_by` int(100) DEFAULT NULL,
  `request_detail_last_modified_by` int(100) DEFAULT NULL,
  `request_detail_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`request_detail_id`),
  KEY `fk_request_detail_request1_idx` (`fk_request_id`),
  KEY `fk_request_detail_expense_account1_idx` (`fk_expense_account_id`),
  CONSTRAINT `fk_request_detail_expense_account1` FOREIGN KEY (`fk_expense_account_id`) REFERENCES `expense_account` (`expense_account_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_request_detail_request1` FOREIGN KEY (`fk_request_id`) REFERENCES `request` (`request_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `request_type`;
CREATE TABLE `request_type` (
  `request_type_id` int(11) NOT NULL AUTO_INCREMENT,
  `request_type_track_number` varchar(100) NOT NULL,
  `request_type_name` varchar(100) NOT NULL,
  `request_type_is_active` int(5) NOT NULL DEFAULT '1',
  `fk_account_system_id` int(100) NOT NULL DEFAULT '1',
  `request_type_created_date` date DEFAULT NULL,
  `request_type_created_by` int(100) DEFAULT NULL,
  `request_type_last_modified_by` int(100) DEFAULT NULL,
  `request_type_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`request_type_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  CONSTRAINT `request_type_ibfk_1` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `request_type_department`;
CREATE TABLE `request_type_department` (
  `request_type_department_id` int(100) NOT NULL AUTO_INCREMENT,
  `request_type_department_track_number` varchar(100) NOT NULL,
  `request_type_department_name` varchar(100) NOT NULL,
  `fk_request_type_id` int(11) NOT NULL,
  `fk_department_id` int(100) NOT NULL,
  `request_type_department_created_by` int(100) NOT NULL,
  `request_type_department_created_date` date NOT NULL,
  `request_type_department_last_modified_by` int(100) NOT NULL,
  `request_type_department_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_status_id` int(11) NOT NULL,
  `fk_approval_id` int(11) NOT NULL,
  PRIMARY KEY (`request_type_department_id`),
  KEY `fk_request_type_id` (`fk_request_type_id`),
  KEY `fk_department_id` (`fk_department_id`),
  CONSTRAINT `request_type_department_ibfk_1` FOREIGN KEY (`fk_request_type_id`) REFERENCES `request_type` (`request_type_id`),
  CONSTRAINT `request_type_department_ibfk_2` FOREIGN KEY (`fk_department_id`) REFERENCES `department` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `role`;
CREATE TABLE `role` (
  `role_id` int(100) NOT NULL AUTO_INCREMENT,
  `role_track_number` varchar(100) DEFAULT NULL,
  `role_name` varchar(100) DEFAULT NULL,
  `role_shortname` varchar(50) NOT NULL,
  `role_description` longtext,
  `role_is_active` int(5) DEFAULT NULL,
  `role_is_new_status_default` int(5) DEFAULT '0',
  `role_is_department_strict` int(5) DEFAULT '0',
  `fk_account_system_id` int(100) DEFAULT NULL,
  `role_created_by` int(100) DEFAULT NULL,
  `role_created_date` date DEFAULT NULL,
  `role_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `role_last_modified_by` varchar(45) DEFAULT NULL,
  `role_deleted_at` date DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`role_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  CONSTRAINT `role_ibfk_1` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `role_group`;
CREATE TABLE `role_group` (
  `role_group_id` int(100) NOT NULL AUTO_INCREMENT,
  `role_group_name` varchar(100) NOT NULL,
  `role_group_track_number` varchar(100) NOT NULL,
  `role_group_description` longtext NOT NULL,
  `role_group_is_active` int(5) NOT NULL DEFAULT '1',
  `fk_account_system_id` int(100) NOT NULL,
  `role_group_created_date` date NOT NULL,
  `role_group_created_by` int(100) NOT NULL,
  `role_group_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `role_group_last_modified_by` int(100) NOT NULL,
  `fk_status_id` int(100) NOT NULL,
  `fk_approval_id` int(100) NOT NULL,
  PRIMARY KEY (`role_group_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  CONSTRAINT `role_group_ibfk_1` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `role_group_association`;
CREATE TABLE `role_group_association` (
  `role_group_association_id` int(100) NOT NULL AUTO_INCREMENT,
  `role_group_association_name` varchar(100) NOT NULL,
  `role_group_association_track_number` varchar(100) NOT NULL,
  `fk_role_group_id` int(100) NOT NULL,
  `fk_role_id` int(100) NOT NULL,
  `role_group_association_created_date` date NOT NULL,
  `role_group_association_created_by` int(100) NOT NULL,
  `role_group_association_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `role_group_association_last_modified_by` int(100) NOT NULL,
  `fk_status_id` int(11) NOT NULL,
  `fk_approval_id` int(11) NOT NULL,
  PRIMARY KEY (`role_group_association_id`),
  KEY `fk_role_group_id` (`fk_role_group_id`),
  KEY `fk_role_id` (`fk_role_id`),
  CONSTRAINT `role_group_association_ibfk_1` FOREIGN KEY (`fk_role_group_id`) REFERENCES `role_group` (`role_group_id`),
  CONSTRAINT `role_group_association_ibfk_2` FOREIGN KEY (`fk_role_id`) REFERENCES `role` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `role_permission`;
CREATE TABLE `role_permission` (
  `role_permission_id` int(100) NOT NULL AUTO_INCREMENT,
  `role_permission_track_number` varchar(100) NOT NULL,
  `role_permission_name` varchar(100) NOT NULL,
  `role_permission_is_active` int(5) NOT NULL DEFAULT '1',
  `fk_role_id` int(100) NOT NULL,
  `fk_permission_id` int(11) NOT NULL,
  `fk_approval_id` int(100) NOT NULL,
  `fk_status_id` int(100) NOT NULL,
  `role_permission_created_date` date NOT NULL,
  `role_permission_created_by` int(100) NOT NULL,
  `role_permission_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `role_permission_last_modified_by` int(100) NOT NULL,
  PRIMARY KEY (`role_permission_id`),
  KEY `fk_role_id` (`fk_role_id`),
  KEY `fk_permission_id` (`fk_permission_id`),
  CONSTRAINT `role_permission_ibfk_4` FOREIGN KEY (`fk_role_id`) REFERENCES `role` (`role_id`) ON DELETE CASCADE,
  CONSTRAINT `role_permission_ibfk_5` FOREIGN KEY (`fk_permission_id`) REFERENCES `permission` (`permission_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `setting`;
CREATE TABLE `setting` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(100) DEFAULT NULL,
  `description` varchar(100) DEFAULT NULL,
  `setting_created_date` date DEFAULT NULL,
  `setting_created_by` int(100) DEFAULT NULL,
  `setting_last_modified_by` int(100) DEFAULT NULL,
  `setting_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`setting_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `status`;
CREATE TABLE `status` (
  `status_id` int(11) NOT NULL AUTO_INCREMENT,
  `status_track_number` varchar(100) NOT NULL,
  `status_name` varchar(50) NOT NULL,
  `fk_approval_flow_id` int(100) NOT NULL,
  `status_approval_sequence` int(10) NOT NULL,
  `status_backflow_sequence` int(10) NOT NULL,
  `status_approval_direction` int(5) NOT NULL COMMENT '1-straight jumps, 0 - return jumps, -1 - reverse jump',
  `status_is_requiring_approver_action` int(5) NOT NULL DEFAULT '1',
  `status_created_date` date NOT NULL,
  `status_created_by` int(100) NOT NULL,
  `status_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status_last_modified_by` int(100) NOT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`status_id`),
  KEY `fk_approval_flow_id` (`fk_approval_flow_id`),
  CONSTRAINT `status_ibfk_1` FOREIGN KEY (`fk_approval_flow_id`) REFERENCES `approval_flow` (`approval_flow_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `status_role`;
CREATE TABLE `status_role` (
  `status_role_id` int(100) NOT NULL AUTO_INCREMENT,
  `status_role_track_number` varchar(100) NOT NULL,
  `status_role_name` varchar(100) NOT NULL,
  `fk_role_id` int(100) NOT NULL,
  `fk_status_id` int(100) NOT NULL,
  `status_role_status_id` int(100) NOT NULL,
  `status_role_is_active` int(5) NOT NULL DEFAULT '1',
  `status_role_created_by` int(100) NOT NULL,
  `status_role_created_date` date NOT NULL DEFAULT '0000-00-00',
  `status_role_last_modified_by` int(100) NOT NULL,
  `status_role_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `fk_approval_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`status_role_id`),
  KEY `fk_role_id` (`fk_role_id`),
  CONSTRAINT `status_role_ibfk_4` FOREIGN KEY (`fk_role_id`) REFERENCES `role` (`role_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `system_opening_balance`;
CREATE TABLE `system_opening_balance` (
  `system_opening_balance_id` int(100) NOT NULL AUTO_INCREMENT,
  `system_opening_balance_track_number` varchar(100) NOT NULL,
  `system_opening_balance_name` varchar(100) NOT NULL,
  `fk_office_id` int(100) NOT NULL,
  `month` date NOT NULL,
  `system_opening_balance_created_date` date DEFAULT NULL,
  `system_opening_balance_created_by` int(100) DEFAULT NULL,
  `system_opening_balance_last_modified_by` int(100) DEFAULT NULL,
  `system_opening_balance_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`system_opening_balance_id`),
  KEY `fk_office_id` (`fk_office_id`),
  CONSTRAINT `system_opening_balance_ibfk_1` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `translation`;
CREATE TABLE `translation` (
  `translation_id` int(100) NOT NULL AUTO_INCREMENT,
  `language_phrase_id` int(100) DEFAULT NULL,
  `language_id` int(100) DEFAULT NULL,
  `translate` longtext,
  `created_date` date DEFAULT NULL,
  `last_modified_date` date DEFAULT NULL,
  `deleted_date` date DEFAULT NULL,
  `created_by` int(100) DEFAULT NULL,
  `last_modified_by` int(100) DEFAULT NULL,
  `translation_created_date` date DEFAULT NULL,
  `translation_created_by` int(100) DEFAULT NULL,
  `translation_last_modified_by` int(100) DEFAULT NULL,
  `translation_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`translation_id`),
  KEY `fk_translation_language1_idx` (`language_id`),
  KEY `fk_translation_language_phrase1_idx` (`language_phrase_id`),
  CONSTRAINT `fk_translation_language1` FOREIGN KEY (`language_id`) REFERENCES `language` (`language_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_translation_language_phrase1` FOREIGN KEY (`language_phrase_id`) REFERENCES `language_phrase` (`language_phrase_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `user_id` int(100) NOT NULL AUTO_INCREMENT,
  `user_track_number` varchar(100) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `user_firstname` varchar(100) NOT NULL,
  `user_lastname` varchar(100) NOT NULL,
  `user_email` varchar(100) NOT NULL,
  `fk_context_definition_id` int(100) NOT NULL,
  `user_is_context_manager` int(5) NOT NULL DEFAULT '0',
  `user_is_system_admin` int(5) NOT NULL DEFAULT '0',
  `fk_language_id` int(100) DEFAULT NULL COMMENT 'User''s default language',
  `fk_country_currency_id` int(100) DEFAULT NULL,
  `user_is_active` int(5) NOT NULL DEFAULT '1',
  `fk_role_id` int(100) DEFAULT NULL,
  `fk_account_system_id` int(100) DEFAULT NULL,
  `user_password` varchar(100) NOT NULL,
  `user_created_date` date NOT NULL,
  `user_created_by` int(100) NOT NULL,
  `user_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_last_modifed_by` int(100) DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  `user_last_modified_by` int(100) DEFAULT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `variance_note`;
CREATE TABLE `variance_note` (
  `variance_note_id` int(100) NOT NULL AUTO_INCREMENT,
  `reconciliation_id` int(100) DEFAULT NULL,
  `expense_account_id` int(100) DEFAULT NULL,
  `created_by` varchar(45) DEFAULT NULL,
  `created_date` varchar(45) DEFAULT NULL,
  `last_modified_date` varchar(45) DEFAULT NULL,
  `last_modified_by` varchar(45) DEFAULT NULL,
  `variance_note_detail` longtext,
  `variance_note_created_date` date DEFAULT NULL,
  `variance_note_created_by` int(100) DEFAULT NULL,
  `variance_note_last_modified_by` int(100) DEFAULT NULL,
  `variance_note_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`variance_note_id`),
  KEY `fk_variance_comment_reconciliation1_idx` (`reconciliation_id`),
  KEY `fk_variance_comment_expense_account1_idx` (`expense_account_id`),
  CONSTRAINT `fk_variance_comment_expense_account1` FOREIGN KEY (`expense_account_id`) REFERENCES `expense_account` (`expense_account_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_variance_comment_reconciliation1` FOREIGN KEY (`reconciliation_id`) REFERENCES `reconciliation` (`reconciliation_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `voucher`;
CREATE TABLE `voucher` (
  `voucher_id` int(100) NOT NULL AUTO_INCREMENT,
  `voucher_track_number` varchar(50) DEFAULT NULL,
  `voucher_name` varchar(100) DEFAULT NULL,
  `voucher_number` int(10) DEFAULT NULL,
  `fk_office_id` int(100) DEFAULT NULL,
  `voucher_date` date DEFAULT NULL,
  `fk_voucher_type_id` int(100) DEFAULT NULL,
  `voucher_cleared` int(5) DEFAULT '0',
  `voucher_cleared_month` date DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  `fk_office_bank_id` int(100) DEFAULT NULL,
  `fk_office_cash_id` int(100) DEFAULT NULL,
  `voucher_cheque_number` varchar(50) DEFAULT NULL,
  `voucher_transaction_cleared_date` date DEFAULT '0000-00-00',
  `voucher_transaction_cleared_month` date DEFAULT '0000-00-00',
  `voucher_vendor` varchar(100) DEFAULT NULL,
  `voucher_vendor_address` varchar(100) DEFAULT NULL,
  `voucher_description` varchar(200) DEFAULT NULL,
  `voucher_allow_edit` int(5) DEFAULT '0',
  `voucher_is_reversed` int(5) DEFAULT '0',
  `voucher_reversal_from` int(100) NOT NULL DEFAULT '0',
  `voucher_reversal_to` int(100) NOT NULL DEFAULT '0',
  `voucher_created_by` int(100) DEFAULT NULL,
  `voucher_created_date` date DEFAULT NULL,
  `voucher_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `voucher_last_modified_by` int(100) DEFAULT NULL,
  PRIMARY KEY (`voucher_id`),
  KEY `fk_office_id` (`fk_office_id`),
  KEY `fk_voucher_type_id` (`fk_voucher_type_id`),
  CONSTRAINT `voucher_ibfk_1` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`),
  CONSTRAINT `voucher_ibfk_2` FOREIGN KEY (`fk_voucher_type_id`) REFERENCES `voucher_type` (`voucher_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='This holds transactions ';


DROP TABLE IF EXISTS `voucher_detail`;
CREATE TABLE `voucher_detail` (
  `voucher_detail_id` int(100) NOT NULL AUTO_INCREMENT,
  `voucher_detail_track_number` varchar(100) DEFAULT NULL,
  `voucher_detail_name` varchar(100) DEFAULT NULL,
  `fk_voucher_id` int(100) DEFAULT NULL,
  `voucher_detail_description` varchar(45) DEFAULT NULL,
  `voucher_detail_quantity` int(10) DEFAULT NULL,
  `voucher_detail_unit_cost` decimal(10,2) DEFAULT NULL,
  `voucher_detail_total_cost` decimal(10,2) DEFAULT NULL,
  `fk_expense_account_id` int(100) NOT NULL DEFAULT '0' COMMENT 'Can be income_account_id or expense_account_id depending on the selected voucher type',
  `fk_income_account_id` int(100) NOT NULL DEFAULT '0',
  `fk_contra_account_id` int(100) NOT NULL DEFAULT '0',
  `fk_approval_id` int(100) NOT NULL DEFAULT '0',
  `fk_status_id` int(100) NOT NULL DEFAULT '0',
  `fk_request_detail_id` int(100) NOT NULL DEFAULT '0',
  `fk_project_allocation_id` int(100) NOT NULL DEFAULT '0',
  `voucher_detail_last_modified_date` date DEFAULT NULL,
  `voucher_detail_last_modified_by` varchar(45) DEFAULT NULL,
  `voucher_detail_created_by` int(100) DEFAULT NULL,
  `voucher_detail_created_date` date DEFAULT NULL,
  PRIMARY KEY (`voucher_detail_id`),
  KEY `fk_voucher_detail_voucher1_idx` (`fk_voucher_id`),
  KEY `fk_voucher_detail_request_detail1_idx` (`fk_approval_id`),
  CONSTRAINT `voucher_detail_ibfk_3` FOREIGN KEY (`fk_voucher_id`) REFERENCES `voucher` (`voucher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `voucher_signatory`;
CREATE TABLE `voucher_signatory` (
  `voucher_signatory_id` int(100) NOT NULL AUTO_INCREMENT,
  `voucher_signatory_name` varchar(100) NOT NULL,
  `voucher_signatory_track_number` varchar(100) NOT NULL,
  `fk_account_system_id` int(100) NOT NULL,
  `voucher_signatory_is_active` int(5) NOT NULL DEFAULT '1',
  `voucher_signatory_created_date` date DEFAULT NULL,
  `voucher_signatory_created_by` int(100) DEFAULT NULL,
  `voucher_signatory_last_modified_by` int(100) DEFAULT NULL,
  `voucher_signatory_last_modified_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`voucher_signatory_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  CONSTRAINT `voucher_signatory_ibfk_1` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `voucher_type`;
CREATE TABLE `voucher_type` (
  `voucher_type_id` int(100) NOT NULL AUTO_INCREMENT,
  `voucher_type_track_number` varchar(100) NOT NULL,
  `voucher_type_name` varchar(45) DEFAULT NULL,
  `voucher_type_is_active` int(5) DEFAULT NULL,
  `voucher_type_abbrev` varchar(5) DEFAULT NULL,
  `fk_voucher_type_account_id` int(100) DEFAULT NULL COMMENT 'Can be bank, cash or contra',
  `fk_voucher_type_effect_id` int(100) DEFAULT NULL COMMENT 'Can be income or expense',
  `voucher_type_is_cheque_referenced` int(5) DEFAULT '0',
  `fk_account_system_id` int(100) DEFAULT NULL,
  `voucher_type_created_by` int(100) DEFAULT NULL,
  `voucher_type_created_date` date DEFAULT NULL,
  `voucher_type_last_modified_by` int(100) DEFAULT NULL,
  `voucher_type_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`voucher_type_id`),
  KEY `fk_voucher_type_voucher_type_transaction_effect1_idx` (`fk_voucher_type_effect_id`),
  KEY `voucher_type_account_id` (`fk_voucher_type_account_id`),
  KEY `fk_account_system_id` (`fk_account_system_id`),
  CONSTRAINT `voucher_type_ibfk_1` FOREIGN KEY (`fk_voucher_type_account_id`) REFERENCES `voucher_type_account` (`voucher_type_account_id`),
  CONSTRAINT `voucher_type_ibfk_2` FOREIGN KEY (`fk_voucher_type_effect_id`) REFERENCES `voucher_type_effect` (`voucher_type_effect_id`),
  CONSTRAINT `voucher_type_ibfk_3` FOREIGN KEY (`fk_account_system_id`) REFERENCES `account_system` (`account_system_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `voucher_type_account`;
CREATE TABLE `voucher_type_account` (
  `voucher_type_account_id` int(100) NOT NULL AUTO_INCREMENT,
  `voucher_type_account_track_number` varchar(100) NOT NULL,
  `voucher_type_account_name` varchar(100) NOT NULL,
  `voucher_type_account_code` varchar(10) NOT NULL COMMENT 'cash or bank',
  `voucher_type_account_created_date` date DEFAULT NULL,
  `voucher_type_account_created_by` int(100) DEFAULT NULL,
  `voucher_type_account_last_modified_by` int(100) DEFAULT NULL,
  `voucher_type_account_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`voucher_type_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `voucher_type_effect`;
CREATE TABLE `voucher_type_effect` (
  `voucher_type_effect_id` int(100) NOT NULL AUTO_INCREMENT,
  `voucher_type_effect_track_number` varchar(100) NOT NULL,
  `voucher_type_effect_name` varchar(100) NOT NULL,
  `voucher_type_effect_code` varchar(50) NOT NULL,
  `voucher_type_effect_created_date` date DEFAULT NULL,
  `voucher_type_effect_created_by` int(100) DEFAULT NULL,
  `voucher_type_effect_last_modified_by` int(100) DEFAULT NULL,
  `voucher_type_effect_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`voucher_type_effect_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `workplan`;
CREATE TABLE `workplan` (
  `workplan_id` int(100) NOT NULL,
  `workplan_track_number` varchar(100) DEFAULT NULL,
  `workplan_name` varchar(100) DEFAULT NULL,
  `fk_budget_id` int(100) DEFAULT NULL,
  `workplan_description` longtext,
  `workplan_start_date` date DEFAULT NULL,
  `workplan_end_date` date DEFAULT NULL,
  `workplan_created_date` date DEFAULT NULL,
  `workplan_created_by` int(100) DEFAULT NULL,
  `workplan_last_modified_date` date DEFAULT NULL,
  `workplan_last_modified_by` int(100) DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`workplan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `workplan_task`;
CREATE TABLE `workplan_task` (
  `workplan_task_id` int(100) NOT NULL AUTO_INCREMENT,
  `fk_workplan_id` int(100) NOT NULL,
  `workplan_task_track_number` varchar(100) NOT NULL,
  `workplan_task_name` varchar(100) NOT NULL,
  `workplan_task_description` longtext NOT NULL,
  `workplan_task_start_date` date NOT NULL,
  `workplan_taskend_date` date NOT NULL,
  `workplan_task_user` int(100) NOT NULL,
  `workplan_task_status` int(5) NOT NULL DEFAULT '1',
  `workplan_task_note` longtext NOT NULL,
  `workplan_task_created_date` date DEFAULT NULL,
  `workplan_task_created_by` int(100) DEFAULT NULL,
  `workplan_task_last_modified_by` int(100) DEFAULT NULL,
  `workplan_task_last_modified_date` date DEFAULT NULL,
  `fk_approval_id` int(100) DEFAULT NULL,
  `fk_status_id` int(100) DEFAULT NULL,
  PRIMARY KEY (`workplan_task_id`),
  KEY `fk_workplan_id` (`fk_workplan_id`),
  KEY `workplan_detail_task_user` (`workplan_task_user`),
  CONSTRAINT `workplan_task_ibfk_1` FOREIGN KEY (`fk_workplan_id`) REFERENCES `workplan` (`workplan_id`),
  CONSTRAINT `workplan_task_ibfk_2` FOREIGN KEY (`workplan_task_user`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


-- 2021-01-29 14:17:58
