<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Approval_exemption extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('approval_exemption_library');
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
      'approval_exemption_id',
      'approval_exemption_track_number',
      'approval_exemption_name',
      'approve_item_name',
      'office_name',
      'approval_exemption_is_active',
      'approval_exemption_created_date'
    ];

    return $columns;
  }


  function get_approval_exemptions(){

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
      $this->read_db->order_by('approval_exemption_id DESC');
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

    if($this->session->master_table){
       $this->read_db->where(array('approval_exemption.approval_exemption_status_id' => $this->input->post('id')));
    }

    $this->read_db->select($columns);
    $this->read_db->join('office','office.office_id=approval_exemption.fk_office_id');
    $this->read_db->join('status','status.status_id=approval_exemption.approval_exemption_status_id');
    $this->read_db->join('approval_flow','approval_flow.approval_flow_id=status.fk_approval_flow_id');
    $this->read_db->join('approve_item','approve_item.approve_item_id=approval_flow.fk_approve_item_id');

    $result_obj = $this->read_db->get('approval_exemption');
    
    $results = [];

    if($result_obj->num_rows() > 0){
      $results = $result_obj->result_array();
    }

    return $results;
  }

  function count_approval_exemptions(){

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

    if($this->session->master_table){
      $this->read_db->where(array('approval_exemption.approval_exemption_status_id' => $this->input->post('id')));
   }

    $this->read_db->join('office','office.office_id=approval_exemption.fk_office_id');
    $this->read_db->join('status','status.status_id=approval_exemption.approval_exemption_status_id');
    $this->read_db->join('approval_flow','approval_flow.approval_flow_id=status.fk_approval_flow_id');
    $this->read_db->join('approve_item','approve_item.approve_item_id=approval_flow.fk_approve_item_id');
    

    $this->read_db->from('approval_exemption');
    $count_all_results = $this->read_db->count_all_results();

    return $count_all_results;
  }

  function show_list(){
   
    $draw =intval($this->input->post('draw'));
    $approval_exemptions = $this->get_approval_exemptions();
    $count_approval_exemptions = $this->count_approval_exemptions();

    $result = [];

    $user_has_update_approval_exemption_permission = $this->user_model->check_role_has_permissions('approval_exemption','update');

    $cnt = 0;
    foreach($approval_exemptions as $approval_exemption){
      $approval_exemption_id = array_shift($approval_exemption);
      $approval_exemption_track_number = $approval_exemption['approval_exemption_track_number'];

      $edit_link = '';
      if($user_has_update_approval_exemption_permission){
        $edit_link = '<a href= "'.base_url().$this->controller.'/edit/'.hash_id($approval_exemption_id).'"><i class = "fa fa-pencil"></i></a>';
      }
      $approval_exemption['approval_exemption_track_number'] = $edit_link. ' <a href="'.base_url().$this->controller.'/view/'.hash_id($approval_exemption_id).'">'.$approval_exemption_track_number.'</a>';
      $approval_exemption['approval_exemption_is_active'] = $approval_exemption['approval_exemption_is_active'] == 1 ? get_phrase('yes') : get_phrase('no');
      $row = array_values($approval_exemption);

      $result[$cnt] = $row;

      $cnt++;
    }

    $response = [
      'draw'=>$draw,
      'recordsTotal'=>$count_approval_exemptions,
      'recordsFiltered'=>$count_approval_exemptions,
      'data'=>$result
    ];
    
    echo json_encode($response);
  }

  static function get_menu_list(){}

}