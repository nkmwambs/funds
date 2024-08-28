<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Role extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('role_library');
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

  function get_roles(){

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
      $this->read_db->order_by('role_id DESC');
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
    $this->read_db->join('account_system','account_system.account_system_id=role.fk_account_system_id');
    $this->read_db->join('status','status.status_id=role.fk_status_id');
    $this->read_db->join('context_definition','context_definition.context_definition_id=role.fk_context_definition_id');

    if (!$this->session->system_admin) {
      $this->read_db->where_in('role.fk_account_system_id',$this->session->user_account_system_id);
      $this->read_db->where_not_in('role_id', $this->session->role_ids);
    }
    
    $result_obj = $this->read_db->get('role');
    
    $results = [];

    if($result_obj->num_rows() > 0){
      $results = $result_obj->result_array();
    }

    return $results;
  }

  function count_roles(){

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
    
    $this->read_db->join('account_system','account_system.account_system_id=role.fk_account_system_id');
    $this->read_db->join('status','status.status_id=role.fk_status_id');
    $this->read_db->join('context_definition','context_definition.context_definition_id=role.fk_context_definition_id');

    if (!$this->session->system_admin) {
      $this->read_db->where_in('role.fk_account_system_id',$this->session->user_account_system_id);
      $this->read_db->where_not_in('role_id', $this->session->role_ids);
    }

    $this->read_db->from('role');
    $count_all_results = $this->read_db->count_all_results();

    return $count_all_results;
  }

  function columns(){
    $columns = [
      'role_id',
      'role_track_number',
      'role_name',
      'role_description',
      'context_definition_name',
      'role_is_active',
      'account_system_name'
    ];

    return $columns;

  }

  function show_list(){
   
    $draw =intval($this->input->post('draw'));
    $roles = $this->get_roles();
    $count_roles = $this->count_roles();

    $result = [];

    $cnt = 0;
    foreach($roles as $role){
      $role_id = array_shift($role);
      $role_track_number = $role['role_track_number'];
      $role['role_track_number'] = '<a href="'.base_url().$this->controller.'/view/'.hash_id($role_id).'">'.$role_track_number.'</a>';
      $role['role_is_active'] = $role['role_is_active'] == 1 ? get_phrase('yes') : get_phrase('no');
      $row = array_values($role);

      $result[$cnt] = $row;

      $cnt++;
    }

    $response = [
      'draw'=>$draw,
      'recordsTotal'=>$count_roles,
      'recordsFiltered'=>$count_roles,
      'data'=>$result
    ];
    
    echo json_encode($response);
  }

  static function get_menu_list(){}

}
