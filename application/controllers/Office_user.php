<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Office_user extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('office_user_library');
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
      'office_user_id',
      'office_user_track_number',
      'user_name',
      'office_name',
      'office_user_is_active'
    ];

    return $columns;
  }


  function get_office_users(){

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
      $this->read_db->order_by('office_user_id DESC');
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
    $this->read_db->join('office','office.office_id=office_user.fk_office_id');
    $this->read_db->join('account_system','account_system.account_system_id=office.fk_account_system_id');
    $this->read_db->join('user','user.user_id=office_user.fk_user_id');
    $this->read_db->where(array('user.user_is_system_admin' => 0));

    $result_obj = $this->read_db->get('office_user');
    
    $results = [];

    if($result_obj->num_rows() > 0){
      $results = $result_obj->result_array();
    }

    return $results;
  }

  function count_office_users(){

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
      $this->read_db->where(array('office.fk_account_system_id'=>$this->session->user_account_system_id));
    }

    $this->read_db->join('office','office.office_id=office_user.fk_office_id');
    $this->read_db->join('account_system','account_system.account_system_id=office.fk_account_system_id');
    $this->read_db->join('user','user.user_id=office_user.fk_user_id');
    $this->read_db->where(array('user.user_is_system_admin' => 0));

    $this->read_db->from('office_user');
    $count_all_results = $this->read_db->count_all_results();

    return $count_all_results;
  }

  function show_list(){
   
    $draw =intval($this->input->post('draw'));
    $office_users = $this->get_office_users();
    $count_office_users = $this->count_office_users();

    $result = [];

    $cnt = 0;
    foreach($office_users as $office_user){
      $office_user_id = array_shift($office_user);
      $office_user_track_number = $office_user['office_user_track_number'];
      $office_user['office_user_track_number'] = '<a href="'.base_url().$this->controller.'/view/'.hash_id($office_user_id).'">'.$office_user_track_number.'</a>';
      $office_user['office_user_is_active'] = $office_user['office_user_is_active'] == 1 ? get_phrase('yes') : get_phrase('no');
      $row = array_values($office_user);

      $result[$cnt] = $row;

      $cnt++;
    }

    $response = [
      'draw'=>$draw,
      'recordsTotal'=>$count_office_users,
      'recordsFiltered'=>$count_office_users,
      'data'=>$result
    ];
    
    echo json_encode($response);
  }

  static function get_menu_list(){}

}