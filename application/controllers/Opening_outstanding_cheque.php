<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Opening_outstanding_cheque extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('opening_outstanding_cheque_library');
  }

  function index(){}

  function get_office_start_date($system_opening_balance_id){
    
    $this->read_db->where(array('system_opening_balance_id' => $system_opening_balance_id));
    $this->read_db->join('office','office.office_id=system_opening_balance.fk_office_id');
    $office_start_date = $this->read_db->get('system_opening_balance')->row()->office_start_date;

    $end_date = date('Y-m-t', strtotime('- 1 month', strtotime($office_start_date)));
    $start_date = date('Y-m-01',strtotime('- 6 months', strtotime($end_date)));

    echo json_encode(compact('start_date', 'end_date'));
  }

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
      'opening_outstanding_cheque_id',
      'opening_outstanding_cheque_track_number',
      'opening_outstanding_cheque_description',
      'opening_outstanding_cheque_date',
      'opening_outstanding_cheque_cleared_date',
      'office_bank_name',
      'opening_outstanding_cheque_number',
      'opening_outstanding_cheque_amount'
    ];

    return $columns;
}


function get_opening_outstanding_cheques(){

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
    $this->read_db->order_by('opening_outstanding_cheque_id DESC');
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
  $this->read_db->join('status','status.status_id=opening_outstanding_cheque.fk_status_id');
  $this->read_db->join('office_bank','office_bank.office_bank_id=opening_outstanding_cheque.fk_office_bank_id');
  
  if($this->session->master_table){
    $this->read_db->where(array('opening_outstanding_cheque.fk_system_opening_balance_id'=>$this->input->post('id')));
  }
  
  $result_obj = $this->read_db->get('opening_outstanding_cheque');
  
  $results = [];

  if($result_obj->num_rows() > 0){
    $results = $result_obj->result_array();
  }

  return $results;
}

function count_opening_outstanding_cheques(){

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
    $this->read_db->where(array('opening_outstanding_cheque.fk_system_opening_balance_id'=>$this->input->post('id')));
  }

  $this->read_db->join('status','status.status_id=opening_outstanding_cheque.fk_status_id');
  $this->read_db->join('office_bank','office_bank.office_bank_id=opening_outstanding_cheque.fk_office_bank_id');
  
  $this->read_db->from('opening_outstanding_cheque');
  $count_all_results = $this->read_db->count_all_results();

  return $count_all_results;
}

function show_list(){

  $draw =intval($this->input->post('draw'));
  $opening_outstanding_cheques = $this->get_opening_outstanding_cheques();
  $count_opening_outstanding_cheques = $this->count_opening_outstanding_cheques();

  $result = [];

  $cnt = 0;
  foreach($opening_outstanding_cheques as $opening_outstanding_cheque){
    $opening_outstanding_cheque_id = array_shift($opening_outstanding_cheque);
    $opening_outstanding_cheque_track_number = $opening_outstanding_cheque['opening_outstanding_cheque_track_number'];
    $opening_outstanding_cheque['opening_outstanding_cheque_track_number'] = '<a href="'.base_url().'opening_outstanding_cheque/view/'.hash_id($opening_outstanding_cheque_id).'">'.$opening_outstanding_cheque_track_number.'</a>';
    $opening_outstanding_cheque['opening_outstanding_cheque_amount'] = number_format($opening_outstanding_cheque['opening_outstanding_cheque_amount'],2);
    $row = array_values($opening_outstanding_cheque);

    $result[$cnt] = $row;

    $cnt++;
  }

  $response = [
    'draw'=>$draw,
    'recordsTotal'=>$count_opening_outstanding_cheques,
    'recordsFiltered'=>$count_opening_outstanding_cheques,
    'data'=>$result
  ];
  
  echo json_encode($response);
}

  static function get_menu_list(){}

}