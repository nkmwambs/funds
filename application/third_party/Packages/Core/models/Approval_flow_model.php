<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Approval_flow_model extends MY_Model{

    public $table = 'approval_flow'; 
    public $dependant_table = '';
    public $name_field = 'approval_flow_name';
    public $create_date_field = "approval_flow_created_date";
    public $created_by_field = "approval_flow_created_by";
    public $last_modified_date_field = "approval_flow_last_modified_date";
    public $last_modified_by_field = "approval_flow_last_modified_by";
    public $deleted_at_field = "approval_flow_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();

    }

    function index(){}

    public function lookup_tables(){
        return array('approve_item','account_system');
    }

    public function detail_tables(){
        return ['status'];
    }

    public function detail_multi_form_add_visible_columns(){}

    public function single_form_add_visible_columns(){
        return ['approval_flow_name','approve_item_name','account_system_name'];
    }

    function show_add_button(){
        // These items are automatically added by the system
        if($this->session->system_admin){
          return true;
        }
          
      }

      function lookup_values(){
          $lookup_values = parent::lookup_values();
        //print_r($lookup_values);exit;
          foreach($lookup_values['approve_item'] as $row_number => $lookup_record){
              if(in_array('approval_flow',$lookup_record)){
                unset($lookup_values['approve_item'][$row_number]);
              }

          }

          return $lookup_values;
      }

      function multi_select_field(){
          return "approve_item";
      }

      function list_table_where(){
          if(!$this->session->system_admin){
            $this->read_db->where(array('fk_account_system_id'=>$this->session->user_account_system_id,'approve_item_is_active'=>1));
          }
        }
}