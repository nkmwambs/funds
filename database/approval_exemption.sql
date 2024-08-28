DROP TABLE IF EXISTS `approval_exemption`;
CREATE TABLE `approval_exemption` (
  `approval_exemption_id` int NOT NULL AUTO_INCREMENT,
  `approval_exemption_track_number` varchar(100) NOT NULL,
  `approval_exemption_name` varchar(200) NOT NULL,
  `fk_office_id` int NOT NULL,
  `approval_exemption_status_id` int NOT NULL,
  `approval_exemption_is_active` int NOT NULL DEFAULT '1',
  `approval_exemption_created_date` date DEFAULT NULL,
  `approval_exemption_created_by` int NOT NULL,
  `approval_exemption_last_modified_by` int NOT NULL,
  `approval_exemption_last_modified_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_status_id` int NOT NULL,
  `fk_approval_id` int DEFAULT NULL,
  PRIMARY KEY (`approval_exemption_id`),
  KEY `fk_status_id` (`fk_status_id`),
  KEY `fk_office_id` (`fk_office_id`),
  KEY `approval_exemption_status_id` (`approval_exemption_status_id`),
  CONSTRAINT `approval_exemption_ibfk_1` FOREIGN KEY (`fk_status_id`) REFERENCES `status` (`status_id`),
  CONSTRAINT `approval_exemption_ibfk_2` FOREIGN KEY (`fk_office_id`) REFERENCES `office` (`office_id`),
  CONSTRAINT `approval_exemption_ibfk_3` FOREIGN KEY (`approval_exemption_status_id`) REFERENCES `status` (`status_id`),
  CONSTRAINT `approval_exemption_ibfk_4` FOREIGN KEY (`approval_exemption_status_id`) REFERENCES `status` (`status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


ALTER TABLE `approval_flow`
ADD `approval_flow_is_active` int(5) NOT NULL DEFAULT '1' AFTER `fk_account_system_id`;