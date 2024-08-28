<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Status_role extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('Status_role_library');
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
      $result['show_add_button'] = false;
    }else{
      $result = parent::result($id);
    }

    return $result;
  }

  function columns(){
    $columns = [
      'status_role_id',
      'status_role_track_number',
      // 'status_role_name',
      'status_name',
      'role_name',
      'status_role_is_active',
      'status_role_created_date'
    ];

    return $columns;
  }


  function get_status_roles(){

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
      $this->read_db->order_by('status_role_id DESC');
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


    if($this->session->master_table){
      // $this->read_db->where(array('opening_bank_balance.fk_system_opening_balance_id'=>$this->input->post('id')));
       $this->read_db->where(array('status_role.status_role_status_id' => $this->input->post('id')));
    }

    $this->read_db->select($columns);
    $this->read_db->join('role','role.role_id=status_role.fk_role_id');
    $this->read_db->join('status','status.status_id=status_role.status_role_status_id');
    $this->read_db->join('approval_flow','approval_flow.approval_flow_id=status.fk_approval_flow_id');

    $result_obj = $this->read_db->get('status_role');
    
    $results = [];

    if($result_obj->num_rows() > 0){
      $results = $result_obj->result_array();
    }

    return $results;
  }

  function count_status_roles(){

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
    
    // $this->read_db->where(array('status_role.status_role_status_id'=>hash_id($this->id,'decode')));
    if($this->session->master_table){
      // $this->read_db->where(array('opening_bank_balance.fk_system_opening_balance_id'=>$this->input->post('id')));
       $this->read_db->where(array('status_role.status_role_status_id' => $this->input->post('id')));
    }

    $this->read_db->join('role','role.role_id=status_role.fk_role_id');
    $this->read_db->join('status','status.status_id=status_role.status_role_status_id');
    $this->read_db->join('approval_flow','approval_flow.approval_flow_id=status.fk_approval_flow_id');
    

    $this->read_db->from('status_role');
    $count_all_results = $this->read_db->count_all_results();

    return $count_all_results;
  }

  function show_list(){
   
    $draw =intval($this->input->post('draw'));
    $status_roles = $this->get_status_roles();
    $count_status_roles = $this->count_status_roles();

    $result = [];

    $user_has_update_status_role_permission = $this->user_model->check_role_has_permissions('status_role','update');

    $cnt = 0;
    foreach($status_roles as $status_role){
      $status_role_id = array_shift($status_role);
      $status_role_track_number = $status_role['status_role_track_number'];

      $edit_link = '';
      if($user_has_update_status_role_permission){
        $edit_link = '<a href= "'.base_url().$this->controller.'/edit/'.hash_id($status_role_id).'"><i class = "fa fa-pencil"></i></a>';
      }

      $status_role['status_role_track_number'] = $edit_link.' <a href="'.base_url().$this->controller.'/view/'.hash_id($status_role_id).'">'.$status_role_track_number.'</a>';
      $status_role['status_role_is_active'] = $status_role['status_role_is_active'] == 1 ? get_phrase('yes') : get_phrase('no');
      $row = array_values($status_role);

      $result[$cnt] = $row;

      $cnt++;
    }

    $response = [
      'draw'=>$draw,
      'recordsTotal'=>$count_status_roles,
      'recordsFiltered'=>$count_status_roles,
      'data'=>$result
    ];
    
    echo json_encode($response);
  }

  static function get_menu_list(){}

}