<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Custom_financial_year extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('custom_financial_year_library');

    // $this->load->model('voucher_model');
  }

  function index(){}

  function get_custom_financial_year_reset_date($office_id){
    $this->load->model('voucher_model');
    $next_voucher_date = $this->voucher_model->get_voucher_date($office_id);

    echo date('Y-m-01', strtotime($next_voucher_date));
  }

  function result($id = 0){

    $result = [];

    if($this->action == 'list'){
      $columns = alias_columns($this->columns());
      array_shift($columns);
      $result['columns'] = array_column($columns,'list_columns');
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
      'custom_financial_year_id',
      'custom_financial_year_track_number as track_number',
      'office_name',
      'custom_financial_year_start_month as start_month',
      'custom_financial_year_reset_date as reset_date',
      'custom_financial_year_is_active as is_active',
      'custom_financial_year_is_default as is_default',
      'custom_financial_year_created_date as created_date',
      // 'custom_financial_year_last_modified_date as modified_date'
    ];

    return $columns;
  }


  function get_custom_financial_year(){

    $columns = $this->columns();
    $search_columns = $search_columns = array_column(alias_columns($columns),'query_columns'); // $columns;

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
      $this->read_db->order_by('custom_financial_year_id DESC');
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
    
    if(!$this->session->system_admin){
      $this->read_db->where(array('office.fk_account_system_id'=>$this->session->user_account_system_id));
    }

    $this->read_db->select($columns);
    $this->read_db->join('office','office.office_id=custom_financial_year.fk_office_id');
    $this->read_db->join('account_system','account_system.account_system_id=office.fk_account_system_id');
    $this->read_db->where_in('custom_financial_year.fk_office_id',array_column($this->session->hierarchy_offices,'office_id'));

    $result_obj = $this->read_db->get('custom_financial_year');
    
    $results = [];

    if($result_obj->num_rows() > 0){
      $results = $result_obj->result_array();
    }

    return $results;
  }

  function count_custom_financial_year(){

    $columns = $this->columns();
    $search_columns = $search_columns = array_column(alias_columns($columns),'query_columns'); // $columns;

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
      $this->read_db->where(array('office.fk_account_system_id'=>$this->session->user_account_system_id));
    }

    $this->read_db->select($columns);
    $this->read_db->join('office','office.office_id=custom_financial_year.fk_office_id');
    $this->read_db->join('account_system','account_system.account_system_id=office.fk_account_system_id');    

    $this->read_db->from('custom_financial_year');
    $count_all_results = $this->read_db->count_all_results();

    return $count_all_results;
  }

  function show_list(){
   
    $draw =intval($this->input->post('draw'));
    $custom_financial_years = $this->get_custom_financial_year();
    $count_custom_financial_years = $this->count_custom_financial_year();

    $this->read_db->select(array('month_number', 'month_name'));
    $months = $this->read_db->get('month')->result_array();

    $month_numbers = array_column($months, 'month_number');
    $month_names = array_column($months, 'month_name');

    $months = array_combine($month_numbers, $month_names);

    $result = [];

    $cnt = 0;
    foreach($custom_financial_years as $year){
      $year_id = array_shift($year);
      $year_track_number = $year['track_number'];
      $year['track_number'] = '<a href="'.base_url().$this->controller.'/view/'.hash_id($year_id).'">'.$year_track_number.'</a>';
      $year['is_active'] = $year['is_active'] == 1 ? get_phrase('yes') : get_phrase('no');
      $year['is_default'] = $year['is_default'] == 1 ? get_phrase('yes') : get_phrase('no');
      $year['start_month'] = $months[$year['start_month']];
      $row = array_values($year);

      $result[$cnt] = $row;

      $cnt++;
    }

    $response = [
      'draw'=>$draw,
      'recordsTotal'=>$count_custom_financial_years,
      'recordsFiltered'=>$count_custom_financial_years,
      'data'=>$result
    ];
    
    echo json_encode($response);
  }

  static function get_menu_list(){}

}