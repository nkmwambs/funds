<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Role_group_association_model extends MY_Model{

    public $table = 'role_group_association'; 
    public $dependant_table = '';
    public $name_field = 'role_group_association_name';
    public $create_date_field = "role_group_association_created_date";
    public $created_by_field = "role_group_association_created_by";
    public $last_modified_date_field = "role_group_association_last_modified_date";
    public $last_modified_by_field = "role_group_association_last_modified_by";
    public $deleted_at_field = "role_group_association_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array('role','role_group');
    }

    function detail_list_table_visible_columns(){
        return ['role_group_association_track_number','role_group_name','role_name','role_group_association_is_active','role_group_association_created_date','role_group_association_last_modified_date'];
    }

    function edit_visible_columns()
    {
        return ['role_group_name','role_name','role_group_association_is_active'];
    }
    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}

    public function single_form_add_visible_columns()
    {
        return [
            'role_name',
            'role_group_name'
        ];
    }

    function multi_select_field(){
        return 'role';
    }

    public function lookup_values()
    {
        $lookup_values = parent::lookup_values();

        if(!$this->session->system_admin){
            $this->read_db->select(array('role_group_id','role_group_name'));
            $this->read_db->where_in('fk_account_system_id',[1,$this->session->user_account_system_id]);
            $lookup_values['role_group'] = $this->read_db->get('role_group')->result_array();
        }

        return $lookup_values;
    }
}