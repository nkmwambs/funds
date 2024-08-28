<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Expense_account_office_association_model extends MY_Model{

    public $table = 'expense_account_office_association'; 
    public $dependant_table = '';
    public $name_field = 'expense_account_office_association_name';
    public $create_date_field = "expense_account_office_association_created_date";
    public $created_by_field = "expense_account_office_association_created_by";
    public $last_modified_date_field = "expense_account_office_association_last_modified_date";
    public $last_modified_by_field = "expense_account_office_association_last_modified_by";
    public $deleted_at_field = "expense_account_office_association_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array('office','expense_account');
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}

    function single_form_add_visible_columns()
    {
        return [
            "expense_account_name",
            "office_name"
        ];
    }

    function lookup_values()
    {
        $lookup_values = parent::lookup_values();

        if(!$this->session->system_admin){

            // Both the commented and uncommented code works

            // $office_ids = array_column($this->session->hierarchy_offices,'office_id');
            // $this->read_db->where_in('office_id',$office_ids);
            // $this->grants_model->not_exists_sub_query('office','expense_account_office_association');
            // $this->read_db->select(array('office_id','office_name'));
            // $lookup_values['office'] = $this->read_db->get('office')->result_array();

            $office_ids = array_column($this->session->hierarchy_offices,'office_id');
            $not_exist_string_condition = "AND fk_expense_account_id = ".hash_id($this->id,'decode');
            $this->read_db->where_in('office_id',$office_ids);
            $this->grants_model->get_unused_lookup_values($lookup_values, 'office','expense_account_office_association',$not_exist_string_condition);
        }

        return $lookup_values;
    }

    public function detail_list_table_visible_columns()
    {
        return [
            "expense_account_office_association_track_number",
            "expense_account_name",
            "office_name",
            "expense_account_office_association_created_date",
            "expense_account_office_association_last_modified_date"
        ];
    }
}