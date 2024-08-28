<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class System_opening_balance extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('system_opening_balance_library');
  }

  function index(){}

  function result($id = 0){
    $this->load->model('opening_allocation_balance_model');
    $this->load->model('opening_bank_balance_model');
    $this->load->model('opening_cash_balance_model');
    $this->load->model('opening_fund_balance_model');
    $this->load->model('opening_outstanding_cheque_model');
    $this->load->model('opening_deposit_transit_model');
    $this->load->model('financial_report_model');

    if($this->action == 'view'){
     
      $office_bank_ids = $this->system_opening_balance_model->get_system_opening_balance_office_bank_ids( hash_id($this->id,'decode'));

      $office_has_financial_report_submitted = $this->financial_report_model->count_of_submitted_financial_reports($office_bank_ids) > 0 ? true : false;

      $result['office_has_financial_report_submitted'] = $office_has_financial_report_submitted;

      $result['header'] = $this->master_table();

      $result['detail']['opening_allocation_balance']['columns'] = $this->opening_allocation_balance_model->columns();
      $result['detail']['opening_allocation_balance']['has_details_table'] = true; 
      $result['detail']['opening_allocation_balance']['has_details_listing'] = false;
      $result['detail']['opening_allocation_balance']['is_multi_row'] = false;
      $result['detail']['opening_allocation_balance']['show_add_button'] = true;

      $result['detail']['opening_bank_balance']['columns'] = $this->opening_bank_balance_model->columns();
      $result['detail']['opening_bank_balance']['has_details_table'] = true; 
      $result['detail']['opening_bank_balance']['has_details_listing'] = false;
      $result['detail']['opening_bank_balance']['is_multi_row'] = false;
      $result['detail']['opening_bank_balance']['show_add_button'] = true;

      $result['detail']['opening_cash_balance']['columns'] = $this->opening_cash_balance_model->columns();
      $result['detail']['opening_cash_balance']['has_details_table'] = true; 
      $result['detail']['opening_cash_balance']['has_details_listing'] = false;
      $result['detail']['opening_cash_balance']['is_multi_row'] = false;
      $result['detail']['opening_cash_balance']['show_add_button'] = true;

      $result['detail']['opening_fund_balance']['columns'] = $this->opening_fund_balance_model->columns();
      $result['detail']['opening_fund_balance']['has_details_table'] = true; 
      $result['detail']['opening_fund_balance']['has_details_listing'] = false;
      $result['detail']['opening_fund_balance']['is_multi_row'] = false;
      $result['detail']['opening_fund_balance']['show_add_button'] = true;

      $result['detail']['opening_outstanding_cheque']['columns'] = $this->opening_outstanding_cheque_model->columns();
      $result['detail']['opening_outstanding_cheque']['has_details_table'] = true; 
      $result['detail']['opening_outstanding_cheque']['has_details_listing'] = false;
      $result['detail']['opening_outstanding_cheque']['is_multi_row'] = false;
      $result['detail']['opening_outstanding_cheque']['show_add_button'] = true;

      $result['detail']['opening_deposit_transit']['columns'] = $this->opening_deposit_transit_model->columns();
      $result['detail']['opening_deposit_transit']['has_details_table'] = true; 
      $result['detail']['opening_deposit_transit']['has_details_listing'] = false;
      $result['detail']['opening_deposit_transit']['is_multi_row'] = false;
      $result['detail']['opening_deposit_transit']['show_add_button'] = true;
     
      return $result;//parent::result($id = '');;
    }elseif($this->action == 'list'){
      $columns = $this->columns();
      array_shift($columns);
      $result['columns'] = $columns;
      $result['has_details_table'] = false; 
      $result['has_details_listing'] = false;
      $result['is_multi_row'] = false;
      $result['show_add_button'] = false;

      return $result;
    }else{
      return parent::result($id);
    }
  }

  function master_table(){

    $this->read_db->select(array('system_opening_balance_track_number','system_opening_balance_name',
    'system_opening_balance_created_date','CONCAT(user_firstname," ", user_lastname) as system_opening_balance_created_by',
    'system_opening_balance_last_modified_date','office_name'));
    $this->read_db->join('office','office.office_id=system_opening_balance.fk_office_id');
    $this->read_db->join('user','user.user_id=system_opening_balance.system_opening_balance_created_by');
    $this->read_db->where(array('system_opening_balance_id'=>hash_id($this->id,'decode')));
    $result = $this->read_db->get('system_opening_balance')->row_array();

    return $result;
  }

  function columns(){
    $columns = [
      'system_opening_balance_id',
      'system_opening_balance_track_number',
      'system_opening_balance_name',
      'office_name',
      'system_opening_balance_created_date'
    ];

    return $columns;

  }


  function get_system_opening_balances(){

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
      $this->read_db->order_by('system_opening_balance_id DESC');
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
    $this->read_db->join('status','status.status_id=system_opening_balance.fk_status_id');
    $this->read_db->join('office','office.office_id=system_opening_balance.fk_office_id');

    if(!$this->session->system_admin){
      $this->read_db->where_in('system_opening_balance.fk_office_id',array_column($this->session->hierarchy_offices,'office_id'));
    }

    $this->read_db->where(array('office_is_readonly'=>0,'office_is_active'=>1));

    $result_obj = $this->read_db->get('system_opening_balance');
    
    $results = [];

    if($result_obj->num_rows() > 0){
      $results = $result_obj->result_array();
    }

    return $results;
  }

  function count_system_opening_balances(){

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
      $this->read_db->where_in('system_opening_balance.fk_office_id',array_column($this->session->hierarchy_offices,'office_id'));
    }

    $this->read_db->where(array('office_is_readonly'=>0,'office_is_active'=>1));

    $this->read_db->join('status','status.status_id=system_opening_balance.fk_status_id');
    $this->read_db->join('office','office.office_id=system_opening_balance.fk_office_id');
    $this->read_db->from('system_opening_balance');
    $count_all_results = $this->read_db->count_all_results();

    return $count_all_results;
  }

  function show_list(){

    $draw =intval($this->input->post('draw'));
    $system_opening_balances = $this->get_system_opening_balances();
    $count_system_opening_balances = $this->count_system_opening_balances();

    $result = [];

    $cnt = 0;
    foreach($system_opening_balances as $system_opening_balance){
      $system_opening_balance_id = array_shift($system_opening_balance);
      $system_opening_balance_track_number = $system_opening_balance['system_opening_balance_track_number'];
      $system_opening_balance['system_opening_balance_track_number'] = '<a href="'.base_url().$this->controller.'/view/'.hash_id($system_opening_balance_id).'">'.$system_opening_balance_track_number.'</a>';
      $row = array_values($system_opening_balance);

      $result[$cnt] = $row;

      $cnt++;
    }

    $response = [
      'draw'=>$draw,
      'recordsTotal'=>$count_system_opening_balances,
      'recordsFiltered'=>$count_system_opening_balances,
      'data'=>$result
    ];
    
    echo json_encode($response);
  }


  static function get_menu_list(){}

}