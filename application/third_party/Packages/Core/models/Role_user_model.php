<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Role_user_model extends MY_Model{

    public $table = 'role_user'; 
    public $dependant_table = '';
    public $name_field = 'role_user_name';
    public $create_date_field = "role_user_created_date";
    public $created_by_field = "role_user_created_by";
    public $last_modified_date_field = "role_user_last_modified_date";
    public $last_modified_by_field = "role_user_last_modified_by";
    public $deleted_at_field = "role_user_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array('role','user');
    }

    function multi_select_field(){
        return "user";
    }

    function single_form_add_visible_columns(){
        return ['role_name','user_name','role_user_expiry_date'];
    }

    function detail_list_table_visible_columns(){
        return ['role_user_track_number','role_name','user_name','role_user_expiry_date','role_user_is_active'];
    }

    function edit_visible_columns(){
        return ['role_name','user_name','role_user_is_active','role_user_expiry_date'];
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}
}