<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Budget_limit extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('budget_limit_library');
  }

  function index(){}

  function result($id = 0){

    $result = [];

    if($this->action == 'list'){
      $columns = $this->columns();
      array_shift($columns);
      $result['columns'] = $columns;
      $result['has_details_table'] = false; 
      $result['has_details_listing'] = false;
      $result['is_multi_row'] = false;
      $result['show_add_button'] = true;
    }else{
      $result = parent::result($id);
    }

    return $result;
  }

  function columns(){
    $columns = [
      'budget_limit_id',
      'budget_limit_track_number',
      'office_name',
      'budget_year',
      'month_name as budget_fy_start_month',
      'budget_tag_name',
      'income_account_name',
      'budget_limit_amount'
    ];

    return $columns;
  }


  function get_budget_limits(){

    $columns = $this->columns();
    $search_columns = $columns;

    // Limiting records
    $start = intval($this->input->post('start'));
    $length = intval($this->input->post('length'));

    $this->read_db->limit($length, $start);

    // Ordering records

    $order = $this->input->post('order');
    $col = '';
    $dir = 'desc';
    
    if(!empty($order)){
      $col = $order[0]['column'];
      $dir = $order[0]['dir'];
    }
          
    if( $col == ''){
      $this->read_db->order_by('budget_limit_id DESC');
    }else{
      $this->read_db->order_by($columns[$col],$dir); 
    }

    // Searching

    $search = $this->input->post('search');
    $value = $search['value'];

    array_shift($search_columns);

    if(!empty($value)){
      $this->read_db->group_start();
      $column_key = 0;
        foreach($search_columns as $column){
          if($column_key == 0) {
            $this->read_db->like($column,$value,'both'); 
          }else{
            $this->read_db->or_like($column,$value,'both');
        }
          $column_key++;				
      }
      $this->read_db->group_end();      
    }

    $this->read_db->select($columns);
    $this->read_db->join('budget','budget.budget_id=budget_limit.fk_budget_id');
    $this->read_db->join('budget_tag','budget_tag.budget_tag_id=budget.fk_budget_tag_id');
    $this->read_db->join('status','status.status_id=budget_limit.fk_status_id');
    $this->read_db->join('office','office.office_id=budget.fk_office_id');
    $this->read_db->join('income_account','income_account.income_account_id=budget_limit.fk_income_account_id');
    $this->read_db->join('custom_financial_year','custom_financial_year.custom_financial_year_id=budget.fk_custom_financial_year_id', 'LEFT');
    $this->read_db->join('month','month.month_number=custom_financial_year.custom_financial_year_start_month', 'LEFT');
    $this->read_db->where_in('budget.fk_office_id',array_column($this->session->hierarchy_offices,'office_id'));
    $result_obj = $this->read_db->get('budget_limit');
    
    $results = [];

    if($result_obj->num_rows() > 0){
      $results = $result_obj->result_array();
    }

    return $results;
  }

  function count_budget_limits(){

    $columns = $this->columns();
    $search_columns = $columns;

    // Searching

    $search = $this->input->post('search');
    $value = $search['value'];

    array_shift($search_columns);

    if(!empty($value)){
      $this->read_db->group_start();
      $column_key = 0;
        foreach($search_columns as $column){
          if($column_key == 0) {
            $this->read_db->like($column,$value,'both'); 
          }else{
            $this->read_db->or_like($column,$value,'both');
        }
          $column_key++;				
      }
      $this->read_db->group_end();
    }
    
    if(!$this->session->system_admin){
      $this->read_db->where_in('budget.fk_office_id',array_column($this->session->hierarchy_offices,'office_id'));
    }

    $this->read_db->join('budget','budget.budget_id=budget_limit.fk_budget_id');
    $this->read_db->join('budget_tag','budget_tag.budget_tag_id=budget.fk_budget_tag_id');
    $this->read_db->join('status','status.status_id=budget_limit.fk_status_id');
    $this->read_db->join('office','office.office_id=budget.fk_office_id');
    $this->read_db->join('income_account','income_account.income_account_id=budget_limit.fk_income_account_id');
    $this->read_db->join('custom_financial_year','custom_financial_year.custom_financial_year_id=budget.fk_custom_financial_year_id', 'LEFT');
    $this->read_db->join('month','month.month_number=custom_financial_year.custom_financial_year_start_month', 'LEFT');
    $this->read_db->where_in('budget.fk_office_id',array_column($this->session->hierarchy_offices,'office_id'));

    $this->read_db->from('budget_limit');
    $count_all_results = $this->read_db->count_all_results();

    return $count_all_results;
  }

  function show_list(){
   
    $draw =intval($this->input->post('draw'));
    $budget_limits = $this->get_budget_limits();
    $count_budget_limits = $this->count_budget_limits();

    $result = [];

    $cnt = 0;
    foreach($budget_limits as $budget_limit){
      $budget_limit_id = array_shift($budget_limit);
      $budget_limit_track_number = $budget_limit['budget_limit_track_number'];
      $budget_limit['budget_limit_track_number'] = '<a href="'.base_url().$this->controller.'/view/'.hash_id($budget_limit_id).'">'.$budget_limit_track_number.'</a>';
      $budget_limit['budget_year'] = "FY". $budget_limit['budget_year'];
      $budget_limit['budget_limit_amount'] = number_format($budget_limit['budget_limit_amount'],2);
      $row = array_values($budget_limit);

      $result[$cnt] = $row;

      $cnt++;
    }

    $response = [
      'draw'=>$draw,
      'recordsTotal'=>$count_budget_limits,
      'recordsFiltered'=>$count_budget_limits,
      'data'=>$result
    ];
    
    echo json_encode($response);
  }

  function get_budget_by_id(){
    $this->load->model('budget_model');

    $post = $this->input->post();
    $office_id = $post['budget_id'];
    $budgets = $this->budget_model->get_budget_by_id($office_id);

    echo json_encode($budgets);
  }

  function update_budget_limit_list($budget_id){
    echo $this->budget_limit_library->load_budget_list_view($budget_id);
  }

  function list_of_unused_income_accounts($budget_id = 0){
    // log_message('error', json_encode($budget_id));
    $income_accounts = [];
    $used_income_account_ids = [];

    if(!$budget_id){
      echo json_encode($income_accounts);
    }
    $this->read_db->select(array('fk_income_account_id'));
    $this->read_db->where(array('fk_budget_id' => $budget_id, 'income_account_is_budgeted' => 1, 'income_account_is_active' => 1));
    $this->read_db->join('income_account','income_account.income_account_id=budget_limit.fk_income_account_id');
    $used_income_account_ids_obj = $this->read_db->get('budget_limit');

    if($used_income_account_ids_obj->num_rows() > 0){
      $used_income_account_ids = array_column($used_income_account_ids_obj->result_array(),'fk_income_account_id');
      // log_message('error', json_encode($used_income_account_ids));
    }

    $this->read_db->select(array('income_account_id','CONCAT(account_system_code," - ",income_account_name) as income_account_name'));
    
    if(!empty($used_income_account_ids)){
      $this->read_db->where_not_in('income_account_id', $used_income_account_ids);
    }

    if(!$this->session->system_admin){
      $this->read_db->where(['fk_account_system_id'=>$this->session->user_account_system_id]);
    }

    $this->read_db->where(array('income_account_is_budgeted' => 1, 'income_account_is_active' => 1));
    $this->read_db->join('account_system','account_system.account_system_id=income_account.fk_account_system_id');
    $income_accounts_obj = $this->read_db->get('income_account');

    if($income_accounts_obj->num_rows() > 0){
      $income_accounts = $income_accounts_obj->result_array();
    } 

    echo json_encode($income_accounts);
  }

  function get_set_budget_limit(){
    $post = $this->input->post();

    $previous_budget_limits = $this->get_previous_budget_limits($post);

    echo json_encode($previous_budget_limits);
  }

  function get_previous_budget_limits($new_post){

    extract($new_post);

    $this->load->model('budget_model');

    $get_custom_financial_year = $this->budget_model->get_custom_financial_year($office_id,true);

    $custom_financial_year_id = isset($get_custom_financial_year['id']) ? $get_custom_financial_year['id'] : 0;
    $condition = array('budget_year' => $budget_year, 'fk_office_id' => $office_id);

    $this->read_db->select(array('budget_id','fk_office_id','fk_budget_tag_id'));
    $this->read_db->order_by('fk_budget_tag_id DESC');
    if($custom_financial_year_id > 0){
      $condition = array('budget_year' => $budget_year, 'fk_office_id' => $office_id, 'fk_custom_financial_year_id' => $custom_financial_year_id);
    }
    $this->read_db->where($condition);
    $years_office_budget_obj = $this->read_db->get('budget');

    $years_office_budget = [];

    if($years_office_budget_obj->num_rows() > 0){
      $years_office_budget = $years_office_budget_obj->result_array();
    }

    $latest_year_review = [];
    $budget_limits = [];

    if(!empty($years_office_budget)){
      $latest_year_review = $years_office_budget[0];
  
      $this->read_db->select(array('income_account_id','income_account_name','budget_limit_amount'));
      $this->read_db->where(array('fk_budget_id' => $latest_year_review['budget_id'], 'income_account_is_active' => 1, 'income_account_is_budgeted' => 1));
      $this->read_db->join('income_account','income_account.income_account_id=budget_limit.fk_income_account_id');
      $budget_limits_obj = $this->read_db->get('budget_limit');
  
      if($budget_limits_obj->num_rows() > 0){
        $budget_limits = $budget_limits_obj->result_array();
      }
    }

    return $budget_limits;
  }

  static function get_menu_list(){}

}