<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Alter_account_system extends CI_Migration {

        public function up()
        {
                $sql = "ALTER TABLE `account_system`
                ADD `account_system_code` varchar(10) COLLATE 'latin1_swedish_ci' NOT NULL AFTER `account_system_name`;";

                $sql .= "UPDATE `account_system` SET `account_system_code` = 'global' WHERE `account_system_id` = '1';";
                
                $this->db->query($sql);
        }

        public function down()
        {
                $sql = "ALTER TABLE `account_system`
                DROP `account_system_code`;";

                $this->db->query($sql);
        }
}

//fk_account_system_id in bank table added