<?php

class As_Bank_model extends MY_Model{

    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    // function render_view_page_data(){
        
    //     $result = ['Hello','World'];

    //     return $result;
    // }

    // function render_single_form_add_page_data(){
        
    //     $result = ['Hello','World'];

    //     return $result;
    // }
}