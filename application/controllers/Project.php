<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Project extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('project_library');
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
      $this->load->model('project_allocation_model');
      $this->load->model('project_income_account_model');
      $this->load->model('project_request_type_model');

      $result['header'] = $this->master_table();

      $result['detail']['project_allocation']['columns'] = $this->project_allocation_model->columns();
      $result['detail']['project_allocation']['has_details_table'] = true; 
      $result['detail']['project_allocation']['has_details_listing'] = false;
      $result['detail']['project_allocation']['is_multi_row'] = false;
      $result['detail']['project_allocation']['show_add_button'] = true;

      $result['detail']['project_income_account']['columns'] = $this->project_income_account_model->columns();
      $result['detail']['project_income_account']['has_details_table'] = true; 
      $result['detail']['project_income_account']['has_details_listing'] = false;
      $result['detail']['project_income_account']['is_multi_row'] = false;
      $result['detail']['project_income_account']['show_add_button'] = true;

      $result['detail']['project_request_type']['columns'] = $this->project_request_type_model->columns();
      $result['detail']['project_request_type']['has_details_table'] = true; 
      $result['detail']['project_request_type']['has_details_listing'] = false;
      $result['detail']['project_request_type']['is_multi_row'] = false;
      $result['detail']['project_request_type']['show_add_button'] = true;

      //$result['status_id'] = 1;
    }else{
      $result = parent::result($id);
    }

    return $result;
  }

  function master_table(){  

    $this->read_db->select(array('project_track_number','project_name','project_code','project_description','project_start_date',
    'project_end_date','project_is_default','project_created_by','funder_name','funder_id','funding_status_name','CONCAT(user_firstname," ", user_lastname) as project_created_by'));
    $this->read_db->where(array('project_id'=>hash_id($this->id,'decode')));
    $this->read_db->join('funder','funder.funder_id=project.fk_funder_id');
    $this->read_db->join('user','user.user_id=project.project_created_by');
    $this->read_db->join('funding_status','funding_status.funding_status_id=project.fk_funding_status_id');
    $result = $this->read_db->get('project')->row_array();

    $funder_id = $result['funder_id'];
    unset($result['funder_id']);

    $result['project_is_default'] = $result['project_is_default'] == 1 ? get_phrase('yes') : get_phrase('no');
    $result['funder_name'] = '<a href="'.base_url().'funder/view/'.hash_id($funder_id,'encode').'">'.$result['funder_name'].'</a>';

    return $result;
  }

  function columns(){
    $columns = [
      'project_id',
      'project_track_number',
      'project_name',
      'project_code',
      'project_start_date',
      'project_end_date',
      'project_created_date',
      'funder_name'
   ];

   return $columns;
}

  function get_projects(){

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
      $this->read_db->order_by('project_id DESC');
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
    //$this->read_db->join('status','status.status_id=opening_allocation_balance.fk_status_id');
    $this->read_db->join('funder','funder.funder_id=project.fk_funder_id');
    
    if($this->session->master_table){
      $this->read_db->where(array('project.fk_funder_id'=>$this->input->post('id')));
      
      if($this->session->context_definition['context_definition_level'] == 1){
        $this->read_db->join('project_allocation','project_allocation.fk_project_id=project.project_id');
        $office_ids = array_column($this->session->hierarchy_offices, 'office_id');
        $this->read_db->where_in('project_allocation.fk_office_id',$office_ids);
      }
    }
    
    $result_obj = $this->read_db->get('project');
    
    $results = [];

    if($result_obj->num_rows() > 0){
      $results = $result_obj->result_array();
    }

    return $results;
  }

  function count_projects(){

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
    
    // if($this->session->master_table){
    //   $this->read_db->where(array('project.fk_funder_id'=>$this->input->post('id')));
    // }

    // //$this->read_db->join('status','status.status_id=opening_allocation_balance.fk_status_id');
    // $this->read_db->join('funder','funder.funder_id=project.fk_funder_id');

    $this->read_db->join('funder','funder.funder_id=project.fk_funder_id');
    
    if($this->session->master_table){
      $this->read_db->where(array('project.fk_funder_id'=>$this->input->post('id')));
      
      if($this->session->context_definition['context_definition_level'] == 1){
        $this->read_db->join('project_allocation','project_allocation.fk_project_id=project.project_id');
        $office_ids = array_column($this->session->hierarchy_offices, 'office_id');
        $this->read_db->where_in('project_allocation.fk_office_id',$office_ids);
      }
    }
    
    $this->read_db->from('project');
    $count_all_results = $this->read_db->count_all_results();

    return $count_all_results;
  }

  function show_list(){

    $draw =intval($this->input->post('draw'));
    $projects = $this->get_projects();
    $count_projects = $this->count_projects();

    $result = [];

    $cnt = 0;
    foreach($projects as $project){
      $project_id = array_shift($project);
      $project_track_number = $project['project_track_number'];
      $project['project_track_number'] = '<a href="'.base_url().'project/view/'.hash_id($project_id).'">'.$project_track_number.'</a>';
      $row = array_values($project);

      $result[$cnt] = $row;

      $cnt++;
    }

    $response = [
      'draw'=>$draw,
      'recordsTotal'=>$count_projects,
      'recordsFiltered'=>$count_projects,
      'data'=>$result
    ];
    
    echo json_encode($response);
  }

  static function get_menu_list(){}

}