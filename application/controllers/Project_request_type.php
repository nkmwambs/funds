<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Project_request_type extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('project_request_type_library');
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
      'project_request_type_id',
      'project_request_type_track_number',
      'project_request_type_name',
      'project_name',
      'request_type_name',
      'project_request_type_created_date'
  ];

   return $columns;
}

  function get_project_request_types(){

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
      $this->read_db->order_by('project_request_type_id DESC');
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
    $this->read_db->join('request_type','request_type.request_type_id=project_request_type.fk_request_type_id');
    $this->read_db->join('project','project.project_id=project_request_type.fk_project_id');
    
    if($this->session->master_table){
      $this->read_db->where(array('project_request_type.fk_project_id'=>$this->input->post('id')));
    }
    
    $result_obj = $this->read_db->get('project_request_type');
    
    $results = [];

    if($result_obj->num_rows() > 0){
      $results = $result_obj->result_array();
    }

    return $results;
  }

  function count_project_request_types(){

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
      $this->read_db->where(array('project_request_type.fk_project_id'=>$this->input->post('id')));
    }

    $this->read_db->join('request_type','request_type.request_type_id=project_request_type.fk_request_type_id');
    $this->read_db->join('project','project.project_id=project_request_type.fk_project_id');
    
    $this->read_db->from('project_request_type');
    $count_all_results = $this->read_db->count_all_results();

    return $count_all_results;
  }

  function show_list(){

    $draw =intval($this->input->post('draw'));
    $project_request_types = $this->get_project_request_types();
    $count_project_request_types = $this->count_project_request_types();

    $result = [];

    $cnt = 0;
    foreach($project_request_types as $project_request_type){
      $project_request_type_id = array_shift($project_request_type);
      $project_request_type_track_number = $project_request_type['project_request_type_track_number'];
      $project_request_type['project_request_type_track_number'] = '<a href="'.base_url().'project_request_type/view/'.hash_id($project_request_type_id).'">'.$project_request_type_track_number.'</a>';
      $row = array_values($project_request_type);

      $result[$cnt] = $row;

      $cnt++;
    }

    $response = [
      'draw'=>$draw,
      'recordsTotal'=>$count_project_request_types,
      'recordsFiltered'=>$count_project_request_types,
      'data'=>$result
    ];
    
    echo json_encode($response);
  }

  static function get_menu_list(){}

}