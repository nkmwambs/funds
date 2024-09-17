<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Funder extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('funder_library');
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
    }elseif($this->action == 'view'){
    
      $result['header'] = $this->master_table();

      $detail_tables = $this->funder_model->detail_tables();

      if(!empty($detail_tables)){
        foreach($detail_tables as $detail_table){
          $this->load->model($detail_table.'_model');
          $result['detail'][$detail_table]['columns'] = $this->{$detail_table.'_model'}->list_table_visible_columns();
          $result['detail'][$detail_table]['has_details_table'] = true; 
          $result['detail'][$detail_table]['has_details_listing'] = false;
          $result['detail'][$detail_table]['is_multi_row'] = false;
          $result['detail'][$detail_table]['show_add_button'] = true;
        }
      }

      // $result['detail']['project']['columns'] = $this->project_model->columns();
      // $result['detail']['project']['has_details_table'] = true; 
      // $result['detail']['project']['has_details_listing'] = false;
      // $result['detail']['project']['is_multi_row'] = false;
      // $result['detail']['project']['show_add_button'] = true;

      return $result;
    }else{
      $result = parent::result($id);
    }

    return $result;
  }

  function master_table(){

    // $this->read_db->select(array('funder_track_number','funder_name',
    // 'funder_description','CONCAT(user_firstname," ", user_lastname) as funder_created_by',
    // 'funder_created_date','account_system_name'));
    $this->read_db->select($this->funder_model->master_table_visible_columns());
    $this->read_db->select(['CONCAT(user_firstname," ", user_lastname) as funder_created_by']);
    $this->read_db->join('account_system','account_system.account_system_id=funder.fk_account_system_id');
    $this->read_db->join('user','user.user_id=funder.funder_created_by');
    $this->read_db->where(array('funder_id'=>hash_id($this->id,'decode')));
    $result = $this->read_db->get('funder')->row_array();

    return $result;
  }

  function columns(){
    $columns = $this->funder_model->list_table_visible_columns();
    array_unshift($columns, 'funder_id');
    return $columns;
  }


  function get_funders(){

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
      $this->read_db->order_by('funder_id DESC');
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
      if($this->session->context_definition['context_definition_level'] == 1){
        $this->read_db->where_in('fk_office_id', array_column($this->session->hierarchy_offices,'office_id'));
      }
      $this->read_db->where(array('funder.fk_account_system_id'=>$this->session->user_account_system_id));
    }

    $this->read_db->select($columns);
    $this->read_db->join('office','office.office_id=funder.fk_office_id','left');
    $this->read_db->join('account_system','account_system.account_system_id=funder.fk_account_system_id');

    $result_obj = $this->read_db->get('funder');
    
    $results = [];

    if($result_obj->num_rows() > 0){
      $results = $result_obj->result_array();
    }

    return $results;
  }

  function count_funders(){

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
      if($this->session->context_definition['context_definition_level'] == 1){
        $this->read_db->where_in('fk_office_id', array_column($this->session->hierarchy_offices,'office_id'));
      }
      $this->read_db->where(array('funder.fk_account_system_id'=>$this->session->user_account_system_id));
    }

    $this->read_db->join('office','office.office_id=funder.fk_office_id','left');
    $this->read_db->join('account_system','account_system.account_system_id=funder.fk_account_system_id');
    

    $this->read_db->from('funder');
    $count_all_results = $this->read_db->count_all_results();

    return $count_all_results;
  }

  function show_list(){
   
    $draw =intval($this->input->post('draw'));
    $funders = $this->get_funders();
    $count_funders = $this->count_funders();

    $result = [];

    $cnt = 0;
    foreach($funders as $funder){
      $funder_id = array_shift($funder);
      $funder_track_number = $funder['funder_track_number'];
      $funder['funder_track_number'] = '<a href="'.base_url().$this->controller.'/view/'.hash_id($funder_id).'">'.$funder_track_number.'</a>';
      $funder['funder_is_active'] =  $funder['funder_is_active'] ? get_phrase('yes') : get_phrase('no');
      $row = array_values($funder);

      $result[$cnt] = $row;

      $cnt++;
    }

    $response = [
      'draw'=>$draw,
      'recordsTotal'=>$count_funders,
      'recordsFiltered'=>$count_funders,
      'data'=>$result
    ];
    
    echo json_encode($response);
  }

  static function get_menu_list(){}

}