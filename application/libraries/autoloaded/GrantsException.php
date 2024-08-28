<?php

class GrantsException extends Exception {

    private $CI = null;

    function __construct(){
        parent::__construct();
        $this->CI =& get_instance();
    }

    function db_error(){
        if($this->CI->db->error()){
            $message = "Database error occurred now";
            show_error($message,500);
        }
    }
}