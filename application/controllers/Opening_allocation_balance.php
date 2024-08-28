<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Opening_allocation_balance extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('opening_allocation_balance_library');
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
      'opening_allocation_balance_id',
      'opening_allocation_balance_track_number',
      'opening_allocation_balance_name',
      'project_allocation_name',
      'opening_allocation_balance_amount'
    ];

    return $columns;
}

  function get_opening_allocation_balances(){

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
      $this->read_db->order_by('opening_allocation_balance_id DESC');
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
    $this->read_db->join('status','status.status_id=opening_allocation_balance.fk_status_id');
    $this->read_db->join('project_allocation','project_allocation.project_allocation_id=opening_allocation_balance.fk_project_allocation_id');
    
    if($this->session->master_table){
      $this->read_db->where(array('opening_allocation_balance.fk_system_opening_balance_id'=>$this->input->post('id')));
    }
    
    $result_obj = $this->read_db->get('opening_allocation_balance');
    
    $results = [];

    if($result_obj->num_rows() > 0){
      $results = $result_obj->result_array();
    }

    return $results;
  }

  function count_opening_allocation_balances(){

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
      $this->read_db->where(array('opening_allocation_balance.fk_system_opening_balance_id'=>$this->input->post('id')));
    }

    $this->read_db->join('status','status.status_id=opening_allocation_balance.fk_status_id');
    $this->read_db->join('project_allocation','project_allocation.project_allocation_id=opening_allocation_balance.fk_project_allocation_id');
    
    $this->read_db->from('opening_allocation_balance');
    $count_all_results = $this->read_db->count_all_results();

    return $count_all_results;
  }

  function show_list(){

    $draw =intval($this->input->post('draw'));
    $opening_allocation_balances = $this->get_opening_allocation_balances();
    $count_opening_allocation_balances = $this->count_opening_allocation_balances();

    $result = [];

    $cnt = 0;
    foreach($opening_allocation_balances as $opening_allocation_balance){
      $opening_allocation_balance_id = array_shift($opening_allocation_balance);
      $opening_allocation_balance_track_number = $opening_allocation_balance['opening_allocation_balance_track_number'];
      $opening_allocation_balance['opening_allocation_balance_track_number'] = '<a href="'.base_url().'opening_allocation_balance/view/'.hash_id($opening_allocation_balance_id).'">'.$opening_allocation_balance_track_number.'</a>';
      $opening_allocation_balance['opening_allocation_balance_amount'] = number_format( $opening_allocation_balance['opening_allocation_balance_amount'],2);
      $row = array_values($opening_allocation_balance);

      $result[$cnt] = $row;

      $cnt++;
    }

    $response = [
      'draw'=>$draw,
      'recordsTotal'=>$count_opening_allocation_balances,
      'recordsFiltered'=>$count_opening_allocation_balances,
      'data'=>$result
    ];
    
    echo json_encode($response);
  }

  static function get_menu_list(){}

}