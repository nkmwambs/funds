<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Project_income_account_model extends MY_Model{

    public $table = 'project_income_account'; 
    public $dependant_table = '';
    public $name_field = 'project_income_account_name';
    public $create_date_field = "project_income_account_created_date";
    public $created_by_field = "project_income_account_created_by";
    public $last_modified_date_field = "project_income_account_last_modified_date";
    public $last_modified_by_field = "project_income_account_last_modified_by";
    public $deleted_at_field = "project_income_account_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array('project','income_account');
    }

    // function action_before_insert($post_array){

    //     return [];
    // }

    function single_form_add_visible_columns(){
        return ['project_name','income_account_name'];
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}

    public function detail_list_table_visible_columns(){
        return [
            'project_income_account_track_number',
            'income_account_name',
            'project_name',
            'project_income_account_created_date'
        ];
    }

    /**
   * get_project_expenses_account
   * 
   * This return list of account 
   * 
   * @return Array - Array
   * @author Onduso
   */
  function TEST($project_id)
  {

    $this->read_db->select(['fk_income_account_id']);
    $this->read_db->where(['fk_project_id'=>$project_id]);
    $project_income_account_ids=$this->read_db->get('project_income_account')->result_array();

    return $project_income_account_ids;
    
   
  }
    public function transaction_validate_duplicates_columns(){
        // return ['fk_project_id','fk_income_account_id'];
        return ['fk_project_id'];
      }

    // function multi_select_field(){
    //     return "income_account";
    // }  

    function lookup_values()
    {
        $lookup_values = parent::lookup_values();

        if(!$this->session->system_admin){
            $this->read_db->join('funder','funder.funder_id=project.fk_funder_id');
            $this->read_db->where(array('funder.fk_account_system_id'=>$this->session->user_account_system_id));
            $this->read_db->select(array('project_id','project_name'));
            $lookup_values['project'] = $this->read_db->get('project')->result_array();

            $this->read_db->where(array('fk_account_system_id'=>$this->session->user_account_system_id,'income_account_is_active' => 1));
            $this->read_db->select(array('income_account_id','income_account_name'));
            $lookup_values['income_account'] = $this->read_db->get('income_account')->result_array();
        }

        return $lookup_values;
    }

    function columns(){
        $columns = [
            'project_income_account_track_number',
            'income_account_name',
            'project_name',
            'project_income_account_created_date'
        ];

        return $columns;
    }
}