<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Budget_tag_model extends MY_Model{

    public $table = 'budget_tag'; 
    public $dependant_table = '';
    public $name_field = 'budget_tag_name';
    public $create_date_field = "budget_tag_created_date";
    public $created_by_field = "budget_tag_created_by";
    public $last_modified_date_field = "budget_tag_last_modified_date";
    public $last_modified_by_field = "budget_tag_last_modified_by";
    public $deleted_at_field = "budget_tag_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array('month','account_system');
    }

    function list_table_where(){
        if(!$this->session->system_admin){
            $this->read_db->where(array('account_system_id'=>$this->session->user_account_system_id));
        }
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}

    function list_table_visible_columns(){
        return ['budget_tag_track_number','budget_tag_name','budget_tag_level','budget_tag_is_active','month_name','account_system_name','budget_tag_created_date','budget_tag_last_modified_date'];
    }

    function single_form_add_visible_columns(){
        return ['budget_tag_name','budget_tag_level','month_name','account_system_name'];
    }

    function edit_visible_columns(){
        return ['budget_tag_name','budget_tag_level','budget_tag_is_active','month_name','account_system_name'];
    }

    function get_budget_tag_id_based_on_reporting_month($office_id,$reporting_month, $custom_financial_year){
        
        $this->load->model('budget_review_count_model');
        $this->load->model('budget_model');
        $this->load->model('custom_financial_year_model');

        // $budget_tag_id = 0;
        $months = [];

        $report_month = date('n',strtotime($reporting_month));

        
        $custom_financial_year_id = isset($custom_financial_year['custom_financial_year_id']) ? $custom_financial_year['custom_financial_year_id'] : null ;
        
        if($custom_financial_year_id == null || (isset($custom_financial_year['custom_financial_year_is_active']) && $custom_financial_year['custom_financial_year_is_active'])){
            // log_message('error', 'There');

            $office_custom_financial_years = $this->custom_financial_year_model->office_custom_financial_years($office_id);

            $count_office_custom_fy = count($office_custom_financial_years);

            if($count_office_custom_fy > 1){
                $previous_custom_financial_year = $office_custom_financial_years[$count_office_custom_fy - 2];
                $months =  $this->custom_financial_year_model->get_months_order_for_custom_year($previous_custom_financial_year['custom_financial_year_id']);
            }else{
                $this->read_db->select(array('month_number'));
                $this->read_db->order_by('month_order ASC');
                $months_array = $this->read_db->get('month')->result_array();
                $months = array_column($months_array, 'month_number');  
            }
      
        }else{
            // log_message('error', 'Here');
            // $months = range(1,12);
            $months =  $this->custom_financial_year_model->get_months_order_for_custom_year($custom_financial_year_id);

        }

        $review_count = $this->budget_review_count_model->budget_review_count_by_office($office_id);

        // log_message('error', json_encode($review_count));

        $chunk_key_range = range(1, $review_count);

        $period_size = count($months) /  $review_count;
        $month_chunks = array_chunk($months, $period_size);

        $month_chunks_with_proper_keys = array_combine($chunk_key_range, $month_chunks);
        // log_message('error', json_encode($month_chunks_with_proper_keys));

        $level = 0;

        foreach($month_chunks_with_proper_keys as $period_key => $months_in_period){
            if(in_array($report_month, $months_in_period)){
                $level = $period_key;
            }
        }

        // log_message('error', json_encode($level));

        $this->read_db->select(array('budget_tag_id','budget_tag_name'));
        $this->read_db->where(array('office_id'=>$office_id, 'budget_tag_level' => $level));
        $this->read_db->join('account_system','account_system.account_system_id=budget_tag.fk_account_system_id');
        $this->read_db->join('office','office.fk_account_system_id=account_system.account_system_id');
        $budget_tag = $this->read_db->get('budget_tag')->row_array();

    
        return $budget_tag;
    }
}