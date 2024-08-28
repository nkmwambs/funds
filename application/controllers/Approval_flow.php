<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Approval_flow extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('approval_flow_library');
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
      'approval_flow_id',
      'approval_flow_track_number',
      'approval_flow_name',
      'approve_item_name',
      'account_system_name'
    ];

    return $columns;
  }


  function get_approval_flows(){

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
      $this->read_db->order_by('approval_flow_id DESC');
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
      $this->read_db->where(array('approval_flow.fk_account_system_id'=>$this->session->user_account_system_id));
    }

    $this->read_db->where(array('approve_item.approve_item_is_active'=> 1));

    $this->read_db->select($columns);
    // $this->read_db->join('status','status.status_id=approval_flow.fk_status_id');
    $this->read_db->join('account_system','account_system.account_system_id=approval_flow.fk_account_system_id');
    $this->read_db->join('approve_item','approve_item.approve_item_id=approval_flow.fk_approve_item_id');

    $result_obj = $this->read_db->get('approval_flow');
    
    $results = [];

    if($result_obj->num_rows() > 0){
      $results = $result_obj->result_array();
    }

    return $results;
  }

  function count_approval_flows(){

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
      $this->read_db->where(array('approval_flow.fk_account_system_id'=>$this->session->user_account_system_id));
    }

    $this->read_db->where(array('approve_item.approve_item_is_active'=> 1));

    // $this->read_db->join('status','status.status_id=approval_flow.fk_status_id');
    $this->read_db->join('account_system','account_system.account_system_id=approval_flow.fk_account_system_id');
    $this->read_db->join('approve_item','approve_item.approve_item_id=approval_flow.fk_approve_item_id');

    $this->read_db->from('approval_flow');
    $count_all_results = $this->read_db->count_all_results();

    return $count_all_results;
  }

  function show_list(){
   
    $draw =intval($this->input->post('draw'));
    $approval_flows = $this->get_approval_flows();
    $count_approval_flows = $this->count_approval_flows();

    $result = [];

    $cnt = 0;
    foreach($approval_flows as $approval_flow){
      $approval_flow_id = array_shift($approval_flow);
      $approval_flow_track_number = $approval_flow['approval_flow_track_number'];
      $approval_flow['approval_flow_track_number'] = '<a href="'.base_url().$this->controller.'/view/'.hash_id($approval_flow_id).'">'.$approval_flow_track_number.'</a>';
      $approval_flow['approve_item_name'] = ucwords(str_replace('_',' ',$approval_flow['approve_item_name']));
      $row = array_values($approval_flow);

      $result[$cnt] = $row;

      $cnt++;
    }

    $response = [
      'draw'=>$draw,
      'recordsTotal'=>$count_approval_flows,
      'recordsFiltered'=>$count_approval_flows,
      'data'=>$result
    ];
    
    echo json_encode($response);
  }

  static function get_menu_list(){}

}