<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Status extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('status_library');
  }

  function index(){}

  function get_status_roles(){

    $this->read_db->select(array('role_id','role_name'));
    $this->read_db->where(array('role_is_new_status_default'=>0));
    $status_roles = $this->read_db->get('role')->result_array();

    echo json_encode($status_roles);
  }

  function check_if_status_is_straight_jump($status_id){

    $is_straight_jump = 0;

    $this->read_db->select(array('status_id','approve_item_name','status_approval_direction'));
    $this->read_db->where(array('status_id'=>$status_id));
    $this->read_db->join('approval_flow','approval_flow.approval_flow_id=status.fk_approval_flow_id');
    $this->read_db->join('approve_item','approve_item.approve_item_id=approval_flow.fk_approve_item_id');
    $status_approval_direction_obj = $this->read_db->get('status')->row();

    $initial_item_status = $this->grants_model->initial_item_status($status_approval_direction_obj->approve_item_name);
    $max_approval_ids = $this->general_model->get_max_approval_status_id($status_approval_direction_obj->approve_item_name);
    $status_id = $status_approval_direction_obj->status_id;
    $status_approval_direction = $status_approval_direction_obj->status_approval_direction;

    if(($status_approval_direction == 1 || $status_approval_direction == 0) && $initial_item_status != $status_id){
      $is_straight_jump = 1;
    }

    echo json_encode(['is_straight_jump' => $is_straight_jump, 'initial_status' => $initial_item_status == $status_id, 'final_status' => in_array($status_id, $max_approval_ids )]);
  }

  function master_table(){

    $this->read_db->select(array('status_track_number','status_name',
    'status_button_label','status_decline_button_label','status_signatory_label','status_approval_sequence',
    'status_created_date','CONCAT(user_firstname," ", user_lastname) as status_created_by',
    'approval_flow_name','approval_flow_id','approve_item_name'));
    $this->read_db->join('approval_flow','approval_flow.approval_flow_id=status.fk_approval_flow_id');
    $this->read_db->join('approve_item','approve_item.approve_item_id=approval_flow.fk_approve_item_id');
    $this->read_db->join('user','user.user_id=status.status_created_by');
    $this->read_db->where(array('status_id'=>hash_id($this->id,'decode')));
    $result = $this->read_db->get('status')->row_array();

    $approval_flow_id = $result['approval_flow_id'];
    unset($result['approval_flow_id']);

    $result['approval_flow_name'] = '<a href="'.base_url().'approval_flow/view/'.hash_id($approval_flow_id).'">'.$result['approval_flow_name'].'</a>';

    return $result;
  }

  function result($id = 0){
    
    $this->load->model('status_role_model');
    $this->load->model('approval_exemption_model');

    if($this->action == 'view'){
      $this->load->model('office_model');

      $master_table = $this->master_table();

      $table_name = $master_table['approve_item_name'];
      unset($master_table['approve_item_name']);

      $result['header'] = $master_table;

      $result['detail']['status_role']['columns'] = $this->status_role_model->columns();
      $result['detail']['status_role']['has_details_table'] = true; 
      $result['detail']['status_role']['has_details_listing'] = false;
      $result['detail']['status_role']['is_multi_row'] = false;
      $result['detail']['status_role']['show_add_button'] = true;

      if($this->office_model->check_if_table_has_relationship_with_office($table_name)){ // Only show exemption section when the record type has an office relationship 
        $result['detail']['approval_exemption']['columns'] = $this->approval_exemption_model->columns();
        $result['detail']['approval_exemption']['has_details_table'] = true; 
        $result['detail']['approval_exemption']['has_details_listing'] = false;
        $result['detail']['approval_exemption']['is_multi_row'] = false;
        $result['detail']['approval_exemption']['show_add_button'] = true;
      }
      

      return $result;

    }else{
      return parent::result($id);
    }
  }

  static function get_menu_list(){}

}