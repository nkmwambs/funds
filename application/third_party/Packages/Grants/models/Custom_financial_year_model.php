<?php

use SebastianBergmann\Type\TrueType;

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Custom_financial_year_model extends MY_Model{

    public $table = 'custom_financial_year'; 
    public $dependant_table = '';
    public $name_field = 'custom_financial_year_name';
    public $create_date_field = "custom_financial_year_created_date";
    public $created_by_field = "custom_financial_year_created_by";
    public $last_modified_date_field = "custom_financial_year_last_modified_date";
    public $last_modified_by_field = "custom_financial_year_last_modified_by";
    public $deleted_at_field = "custom_financial_year_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array('office');
    }

    // Update any default custom_financial_year record to 0
    function action_before_insert($post_array){
        $office_id = $post_array['header']['fk_office_id'];
        // Check if all vouchers and mfrs are submitted for the office
        // $are_office_vouchers_are_approved = $this->check_if_all_office_vouchers_are_approved($office_id);
        // $are_office_mfrs_are_submitted = $this->check_if_all_office_mfrs_are_submitted($office_id);

        // if(!$are_office_vouchers_are_approved || !$are_office_mfrs_are_submitted){
        //     return ['message' => get_phrase('unsubmitted_vouchers_and_mfrs','You have either unapproved vouchers or unsubmitted financial report')];
        // }

        // Check if there is an existing default custom financial year. Turn it to non default it exists
        $checkExistingDefaultCustomFY = $this->checkExistingDefaultCustomFY($office_id);

        if($checkExistingDefaultCustomFY){
            $this->set_custom_fy_as_non_default($office_id);
        }

        return $post_array;
    }

    private function set_custom_fy_as_non_default($office_id){
        $this->write_db->where(array('custom_financial_year_is_default' => 1,'fk_office_id' => $office_id));
        $this->write_db->update('custom_financial_year', ['custom_financial_year_is_active' => 0, 'custom_financial_year_is_default' => 0]);
    }

    private function checkExistingDefaultCustomFY($office_id){
        $checkExistingDefaultCustomFY = false;

        $this->read_db->where(array('custom_financial_year_is_default' => 1,'fk_office_id' => $office_id));
        $count = $this->read_db->get('custom_financial_year')->num_rows();

        if($count > 0){
            $checkExistingDefaultCustomFY = true;
        }

        return $checkExistingDefaultCustomFY;
    }

    public function check_if_all_office_vouchers_are_approved($office_id){

        $max_approval_ids = $this->general_model->get_max_approval_status_id('voucher');

        $this->read_db->where_not_in('voucher.fk_status_id', $max_approval_ids);
        $this->read_db->where(array('fk_office_id' => $office_id));
        $count_of_unapproved_vouchers = $this->read_db->get('voucher')->num_rows();

        $check = true;

        if($count_of_unapproved_vouchers > 0){
            $check = false;
        }

        return $check;
    }

    public function check_if_all_office_mfrs_are_submitted($office_id){

        $initial_status = $this->grants->initial_item_status('financial_report');

        $this->read_db->where(array('financial_report.fk_status_id'=> $initial_status, 'financial_report.fk_office_id' => $office_id));
        $count_unsubmitted_mfrs = $this->read_db->get('financial_report')->num_rows();

        $check = true;

        if($count_unsubmitted_mfrs > 0){
            $check = false;
        }

        return $check;
    }

    // Do not edit if there is a budget already created for this custom financial year record 
    function action_before_edit($post_array){
        $custom_financial_year_id = hash_id($this->id, 'decode');

        $this->read_db->where(array('fk_custom_financial_year_id' => $custom_financial_year_id));
        $count_budgets_with_custom_financial_year_id = $this->read_db->get('budget')->num_rows();

        $custom_financial_year = ['error' => get_phrase('disallow_custom_fY_edit', 'You are not allowed to edit custom financial year after being assigned to a budget')];

        if($count_budgets_with_custom_financial_year_id == 0){
            $custom_financial_year = $post_array;
        }
        return $custom_financial_year;
    }

    function single_form_add_visible_columns()
    {
        return [
            'office_name',
            'custom_financial_year_start_month',
            'custom_financial_year_reset_date'
        ];
    }

    function edit_visible_columns()
    {
        return [
            'office_name',
            'custom_financial_year_start_month',
            // 'custom_financial_year_reset_date'
        ];
    }

    function lookup_values()
    {
        $lookup_values = parent::lookup_values();

        $this->read_db->select(array('office_id','office_name'));
        $this->read_db->where_in('office_id',array_column($this->session->hierarchy_offices,'office_id'));
        $this->read_db->where(array('fk_context_definition_id' => 1));
        $lookup_values['office'] = $this->read_db->get('office')->result_array();

        return  $lookup_values;
    }

    function get_months_order_for_custom_year($custom_financial_year_id){

        $start_month = 7; 
        
        $this->read_db->select(array('custom_financial_year_start_month'));
        $this->read_db->where(array('custom_financial_year_id' => $custom_financial_year_id));
        $start_month_obj = $this->read_db->get('custom_financial_year');

        if($start_month_obj->num_rows() > 0){
            $start_month =  $start_month_obj->row()->custom_financial_year_start_month;
        }
        
        $months = range($start_month, 12);

        if(count($months) < 12){
            $months_in_next_year = range(1, (12 - count($months)));
            $months = array_merge($months,$months_in_next_year);
        }

        return $months;
    }

    function office_custom_financial_years($office_id){

        $custom_financial_years = [];

        $this->read_db->where(array('fk_office_id' => $office_id));
        $this->read_db->order_by('custom_financial_year_id ASC');
        $custom_financial_years_obj = $this->read_db->get('custom_financial_year');

        if($custom_financial_years_obj->num_rows() > 0){
            $custom_financial_years = $custom_financial_years_obj->result_array();
        }

        return $custom_financial_years;
    }

    function get_custom_financial_year_by_id($custom_financial_year_id){
        $custom_financial_year = [];
    
        $this->read_db->select(array('custom_financial_year_id','custom_financial_year_start_month', 'custom_financial_year_is_active'));
        $this->read_db->where(array('custom_financial_year_id' => $custom_financial_year_id));
        $custom_financial_year_obj = $this->read_db->get('custom_financial_year');
    
        if($custom_financial_year_obj->num_rows() > 0){
          $custom_financial_year = $custom_financial_year_obj->row_array();
        }
    
        return $custom_financial_year;
    }

    public function get_default_custom_financial_year_id_by_office($office_id){

        // $custom_financial_year_start_month = 7;
        $custom_financial_year = ['custom_financial_year_start_month' => 7, 'custom_financial_year_id' => NULL, 'custom_financial_year_is_active' => 0, 'custom_financial_year_reset_date' => NULL];
    
        $this->read_db->select(array('custom_financial_year_start_month','custom_financial_year_id', 'custom_financial_year_is_active', 'custom_financial_year_reset_date'));
        $this->read_db->where(array('custom_financial_year_is_default'=> 1,'fk_office_id' => $office_id));
        $custom_financial_year_obj = $this->read_db->get('custom_financial_year');
        
        if($custom_financial_year_obj->num_rows() > 0){
          // log_message('error', json_encode($custom_financial_year_obj->row()));
          $custom_financial_year = $custom_financial_year_obj->row_array();
        }
    
        return $custom_financial_year;
      }

      function transaction_period_behind_default_custom_fy_reset_date($next_vouching_date,$custom_financial_year){

        $transaction_period_behind_default_custom_fy_reset_date = false;
        // log_message('error', json_encode(['office_id' => $office_id, 'next_vouching_date' => $next_vouching_date, 'custom_financial_year' => $custom_financial_year]));
      
        $custom_financial_year_id = $custom_financial_year['custom_financial_year_id'];
    
        if($custom_financial_year_id != null){
          $next_vouching_date_stamp = strtotime('first day of this  month', strtotime($next_vouching_date));
          $custom_financial_year_reset_date_stamp = strtotime('first day of this month', strtotime($custom_financial_year['custom_financial_year_reset_date']));
    
          // log_message('error', json_encode([$next_vouching_date_stamp, $custom_financial_year_reset_date_stamp]));
    
          if($custom_financial_year_reset_date_stamp > $next_vouching_date_stamp){
            $transaction_period_behind_default_custom_fy_reset_date = true;
          }
        }
    
        return $transaction_period_behind_default_custom_fy_reset_date;
      }
    
      function get_previous_custom_financial_year_by_current_id($office_id, $current_custom_financial_year_id){
    
        $this->read_db->select(array('custom_financial_year_id','custom_financial_year_start_month','custom_financial_year_is_active','custom_financial_year_reset_date'));
        $this->read_db->where(array('fk_office_id' => $office_id, 'custom_financial_year_id <> ' => $current_custom_financial_year_id));
        $this->read_db->order('custom_financial_year_id ASC');
        $custom_financial_year_obj = $this->read_db->get('custom_financial_year');
    
        $previous_custom_financial_year = ['custom_financial_year_start_month' => 7, 'custom_financial_year_id' => NULL, 'custom_financial_year_is_active' => 0, 'custom_financial_year_reset_date' => NULL];
    
        if($custom_financial_year_obj->num_rows() > 0){
          $previous_custom_financial_year = $custom_financial_year_obj->row_array();
        }
    
        return $previous_custom_financial_year;
      }
    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}
}