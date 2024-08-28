<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Role_group_model extends MY_Model{

    public $table = 'role_group'; 
    public $dependant_table = '';
    public $name_field = 'role_group_name';
    public $create_date_field = "role_group_created_date";
    public $created_by_field = "role_group_created_by";
    public $last_modified_date_field = "role_group_last_modified_date";
    public $last_modified_by_field = "role_group_last_modified_by";
    public $deleted_at_field = "role_group_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array('account_system');
    }

    public function detail_tables(){
        return ['permission_template','role_group_association'];
    }


    public function detail_multi_form_add_visible_columns(){}

    public function list_table_where()
    {
        if(!$this->session->system_admin){
            $this->read_db->where_in('fk_account_system_id',[1,$this->session->user_account_system_id]);
        }
    }
}