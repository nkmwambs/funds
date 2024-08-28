<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Budget_review_count_model extends MY_Model{

    public $table = 'budget_review_count'; 
    public $dependant_table = '';
    public $name_field = 'budget_review_count_name';
    public $create_date_field = "budget_review_count_created_date";
    public $created_by_field = "budget_review_count_created_by";
    public $last_modified_date_field = "budget_review_count_last_modified_date";
    public $last_modified_by_field = "budget_review_count_last_modified_by";
    public $deleted_at_field = "budget_review_count_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array('account_system');
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}

    function single_form_add_visible_columns(){
        return ['budget_review_count_number','account_system_name'];
    }

    function edit_visible_columns(){
        return ['budget_review_count_number','account_system_name'];
    }

    function list_table_visible_columns(){
        return ['budget_review_count_track_number','budget_review_count_number','account_system_name','budget_review_count_created_date','budget_review_count_last_modified_date'];
    }

    function budget_review_count_by_office($office_id){
        // log_message('error', json_encode([$office_id]));
        $this->read_db->join('account_system','account_system.account_system_id=budget_review_count.fk_account_system_id');
        $this->read_db->join('office','office.fk_account_system_id=account_system.account_system_id');
        $this->read_db->where(array('office_id' => $office_id));
        $budget_review_count = $this->read_db->get('budget_review_count')->row()->budget_review_count_number;
    
        return $budget_review_count;
      }
}