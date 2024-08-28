<?php

require_once('Output_template.php');

class Output_base{

    protected $CI = null;

    public static $output = null;

    protected $db = null;

    function __construct(){
        $this->CI =& get_instance();
        $this->db = $this->CI->db;
    }


    static function load($page_action,...$args){

        $class_filename  = ucfirst($page_action).'_output';

        require_once(__DIR__.DIRECTORY_SEPARATOR.ucfirst($page_action).DIRECTORY_SEPARATOR.$class_filename.'.php');
        
        return self::$output;
    }



}