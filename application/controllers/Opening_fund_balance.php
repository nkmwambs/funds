<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Opening_fund_balance extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('opening_fund_balance_library');
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
      'opening_fund_balance_id',
      'opening_fund_balance_track_number',
      'opening_fund_balance_name',
      'income_account_name',
      'office_bank_name',
      'project_name',
      'opening_fund_balance_amount'
    ];

    return $columns;
}


function get_opening_fund_balances(){

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
    $this->read_db->order_by('opening_fund_balance_id DESC');
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
  $this->read_db->join('status','status.status_id=opening_fund_balance.fk_status_id');
  $this->read_db->join('office_bank','office_bank.office_bank_id=opening_fund_balance.fk_office_bank_id');
  $this->read_db->join('income_account','income_account.income_account_id=opening_fund_balance.fk_income_account_id');
  $this->read_db->join('project','opening_fund_balance.fk_project_id=project.project_id', 'LEFT');
  
  if($this->session->master_table){
    $this->read_db->where(array('opening_fund_balance.fk_system_opening_balance_id'=>$this->input->post('id')));
  }
  
  $result_obj = $this->read_db->get('opening_fund_balance');
  
  $results = [];

  if($result_obj->num_rows() > 0){
    $results = $result_obj->result_array();
  }

  return $results;
}

function count_opening_fund_balances(){

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
  
  if($this->session->master_table){
    $this->read_db->where(array('opening_fund_balance.fk_system_opening_balance_id'=>$this->input->post('id')));
  }

  $this->read_db->join('status','status.status_id=opening_fund_balance.fk_status_id');
  $this->read_db->join('office_bank','office_bank.office_bank_id=opening_fund_balance.fk_office_bank_id');
  $this->read_db->join('income_account','income_account.income_account_id=opening_fund_balance.fk_income_account_id');
  $this->read_db->join('project','opening_fund_balance.fk_project_id=project.project_id', 'LEFT');
  
  $this->read_db->from('opening_fund_balance');
  $count_all_results = $this->read_db->count_all_results();

  return $count_all_results;
}

function show_list(){

  $draw =intval($this->input->post('draw'));
  $opening_fund_balances = $this->get_opening_fund_balances();
  $count_opening_fund_balances = $this->count_opening_fund_balances();

  // $office_id

  $result = [];

  $cnt = 0;
  foreach($opening_fund_balances as $opening_fund_balance){
    $opening_fund_balance_id = array_shift($opening_fund_balance);
    $opening_fund_balance_track_number = $opening_fund_balance['opening_fund_balance_track_number'];
    $opening_fund_balance['opening_fund_balance_track_number'] = '<a href="'.base_url().'opening_fund_balance/view/'.hash_id($opening_fund_balance_id).'">'.$opening_fund_balance_track_number.'</a>';
    $opening_fund_balance['opening_fund_balance_amount'] = number_format($opening_fund_balance['opening_fund_balance_amount'],2);
    
    if($opening_fund_balance['project_name'] == ""){
      $opening_fund_balance['project_name'] = create_select_from_ids_and_names($this->get_project_for_income_account($opening_fund_balance_id), 'project_options', get_phrase('select_an_option'));
    }

    $row = array_values($opening_fund_balance);

    $result[$cnt] = $row;

    $cnt++;
  }

  $response = [
    'draw'=>$draw,
    'recordsTotal'=>$count_opening_fund_balances,
    'recordsFiltered'=>$count_opening_fund_balances,
    'data'=>$result
  ];
  
  echo json_encode($response);
}

function  get_project_for_income_account($opening_fund_balance_id){

  $income_account_id = 0;
  $office_id = 0;
  $projects = [];

  $this->read_db->select(array('fk_income_account_id as income_account_id', 'fk_office_id as office_id'));
  $this->read_db->where(array('opening_fund_balance_id' => $opening_fund_balance_id));
  $this->read_db->join('system_opening_balance','system_opening_balance.system_opening_balance_id=opening_fund_balance.fk_system_opening_balance_id');
  $opening_fund_balance_obj = $this->read_db->get('opening_fund_balance');

  if($opening_fund_balance_obj->num_rows() > 0){
    $result = $opening_fund_balance_obj->row();

    $income_account_id = $result->income_account_id;
    $office_id = $result->office_id;

    $this->read_db->select(array('project_id','project_name'));
    $this->read_db->where(array('fk_office_id' => $office_id, 'fk_income_account_id' => $income_account_id));
    $this->read_db->join('project_allocation','project_allocation.fk_project_id=project.project_id');
    $this->read_db->join('project_income_account','project_income_account.fk_project_id=project.project_id');
    $project_obj = $this->read_db->get('project');

    if($project_obj->num_rows() > 0){
      $projects_raw = $project_obj->result_array();

      foreach($projects_raw as $project){
        $projects[$opening_fund_balance_id.'-'.$project['project_id']] = $project['project_name'];
      }

      
    }
  }
  // log_message('error', json_encode($projects));

  return $projects;
}

function update_project_id(){
  $post = $this->input->post();
  $opening_fund_balance_id = $post['opening_fund_balance_id'];
  $project_id = $post['project_id'];

  $data['fk_project_id'] = $project_id;
  $this->write_db->where(array('opening_fund_balance_id' => $opening_fund_balance_id));
  $this->write_db->update('opening_fund_balance', $data);

  $message = get_phrase('update_unsuccessful');

  if($this->write_db->affected_rows() > 0){
    $message = get_phrase('update_successful');
  }

  echo $message;
}

function update_opening_fund_balance_project_id(){

  $count_of_updates = 0;

  $this->read_db->select(array('office_id'));
  $this->read_db->where(array('fk_context_definition_id' => 1, 'office_is_active' => 1));
  $offices_ids = $this->read_db->get('office')->result_array();

  foreach($offices_ids as $offices_id){
    $count_of_updates += $this->opening_fund_balance_model->get_office_project_id_for_an_income_account($offices_id['office_id']);
  }

  echo $count_of_updates;
}

  static function get_menu_list(){}

}