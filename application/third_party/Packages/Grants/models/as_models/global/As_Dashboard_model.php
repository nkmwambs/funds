<?php

class As_Dashboard_model extends MY_Model{

    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function testing(){
        
        $result = ['Hello','World'];

        return $result;
    }
}