<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *  @author   : Nicodemus Karisa
 *  @date   : 27th September, 2018
 *  Finance management system for NGOs
 *  https://techsysnow.com
 *  NKarisa@ke.ci.org
 */

class Fund_balance_summary_report_model extends MY_Model{

    public $table = 'fund_balance_summary_report'; 
    public $dependant_table = '';
    public $name_field = 'fund_balance_summary_report_name';
    public $create_date_field = "fund_balance_summary_report_created_date";
    public $created_by_field = "fund_balance_summary_report_created_by";
    public $last_modified_date_field = "fund_balance_summary_report_last_modified_date";
    public $last_modified_by_field = "fund_balance_summary_report_last_modified_by";
    public $deleted_at_field = "fund_balance_summary_report_deleted_at";

    private $selected_account_system_id = 0;
    
    function __construct(){
        parent::__construct();
        $this->load->database();

        // $this->set_selected_account_system_id();
    }

    function index(){}

    public function lookup_tables(){
        return array();
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}

    function financial_report_ids(array $office_ids, String $period, $data_column, $order_col = 'office_id', $order_dir = 'desc', $search_value = '', $data_column_value = ''): array {

      $start_month_date = date('Y-m-01', strtotime($period));

      $this->read_db->select(array('fk_office_id as office_id','financial_report_id','office_code'));
      $this->read_db->where_in('fk_office_id', $office_ids);
      $this->read_db->where(array('financial_report_month' => $start_month_date, 'financial_report_is_submitted' => 1));
      $this->read_db->join('office','office.office_id=financial_report.fk_office_id');
      if($order_col == 'office_id'){
        $this->read_db->order_by('office_code',$order_dir); 
      }
      
      if($data_column_value != ""){
        $this->read_db->like($data_column, $data_column_value,'both'); 
      }

      if($search_value){
        $this->read_db->like('office_code',$search_value,'both'); 
      }
      $obj = $this->read_db->get('financial_report');

      $records = [];

      if($obj->num_rows() > 0){
        $records_raw = $obj->result_array();

        $office_codes = array_column($records_raw,'office_code');
        // log_message('error', json_encode($office_codes));

        $o_ids = array_column($records_raw,'office_id');
        $financial_report_ids = array_column($records_raw,'financial_report_id');

        $records = array_combine($o_ids, $financial_report_ids);

      }

      return $records;
    }

    function set_selected_account_system_id(){
  
      if($this->input->post('account_system_id') > 0 && $this->session->system_admin){
        $this->selected_account_system_id = $this->input->post('account_system_id');
      }else{
        $this->selected_account_system_id = $this->session->user_account_system_id;
      }

      // log_message('error', json_encode($this->selected_account_system_id));
    }

    public function logged_cash_balance_report(String $period, int $offset = 0, int $limit = 10, String $order_col, String $order_dir, String $search_value, String $search_column = ""){
      $this->set_selected_account_system_id();

      $session_office_ids = array_column($this->session->hierarchy_offices,'office_id');
      $period = date('Y-m-01', strtotime($period));

      $financial_report_ids = $this->financial_report_ids($session_office_ids, $period, 'closing_total_cash_balance_data' ,$order_col ,$order_dir, $search_value, 'cash_breakdown');
      $office_ids = array_keys(array_slice($financial_report_ids, $offset, $limit,true)); // Get only the offices that have a report for th month specified

      $records = [
        'records' => [],
        'accounts' => []
      ];

      if(empty($office_ids)){
        return $records;
      }

      $this->read_db->select(array('financial_report_id','financial_report_month as voucher_month','office_id','office_code', 'closing_total_cash_balance_data'));

      $this->read_db->where_in('fk_office_id', $office_ids);
      $this->read_db->where(array('fk_account_system_id' => $this->selected_account_system_id, 'fk_context_definition_id' => 1));
      $this->read_db->where(array('financial_report_month' => $period));
      $this->read_db->like('closing_total_cash_balance_data','cash_breakdown','both');
      $this->read_db->join('office','office.office_id=financial_report.fk_office_id');

      // if($search_column > 0){
      //     $this->read_db->like('closing_fund_balance_data', '"'.$search_column.'"', 'both'); 
      // }

      $obj = $this->read_db->get('financial_report');
      
      $records['office_ids'] = $financial_report_ids;

      if($obj->num_rows() > 0){
          $recs = $obj->result_array();        
          foreach($recs as $row){
              $records['records'][$row['office_code']][$row['financial_report_id']] = json_decode($row['closing_total_cash_balance_data'],true);
          }
      }

      return $records;

    }

    public function logged_fund_balance_report(String $period, bool $show_income_account_balance, int $offset = 0, int $limit = 10, String $order_col, String $order_dir, String $search_value, String $search_column = ""): array {

      $this->set_selected_account_system_id();

      $session_office_ids = array_column($this->session->hierarchy_offices,'office_id');

      $period = date('Y-m-01', strtotime($period));
      // log_message('error', json_encode($period));
      $records = [
        'records' => [],
        'accounts' => []
      ];

      // log_message('error', json_encode($show_income_account_balance));

      // log_message('error', json_encode($show_income_account_balance));
      $data_column = $show_income_account_balance ? 'closing_fund_balance_data' : 'closing_project_balance_data';
      $summary_type = $show_income_account_balance ? ['closing_fund_balance_data <>' => NULL] : ['closing_project_balance_data <>' => NULL];

      $financial_report_ids = $this->financial_report_ids($session_office_ids, $period, $data_column, $order_col ,$order_dir, $search_value);
      $office_ids = array_keys(array_slice($financial_report_ids, $offset, $limit,true)); // Get only the offices that have a report for th month specified
      
      // log_message('error', json_encode($office_ids));

      // Give an empty return if no financial reports found for the period
      if(empty($office_ids)){
        return $records;
      }
      

      $this->read_db->select(array('financial_report_id','financial_report_month as voucher_month','office_id','office_code', $data_column));

      $this->read_db->where_in('fk_office_id', $office_ids);
      $this->read_db->where(array('fk_account_system_id' => $this->selected_account_system_id, 'fk_context_definition_id' => 1));
      $this->read_db->where(array('financial_report_month' => $period));
      $this->read_db->where($summary_type);
      $this->read_db->join('office','office.office_id=financial_report.fk_office_id');

      if($search_column > 0){
          if($show_income_account_balance){
            $this->read_db->like('closing_fund_balance_data', '"'.$search_column.'"', 'both'); 
          }else{
            $this->read_db->like('closing_project_balance_data', '"'.$search_column.'"', 'both'); 
          }
      }

      $obj = $this->read_db->get('financial_report');
      
      $records['office_ids'] = $financial_report_ids;

      if($obj->num_rows() > 0){
          $recs = $obj->result_array();        
          foreach($recs as $row){
              $records['records'][$row['office_code']][$row['financial_report_id']] = json_decode($row[$data_column],true);
          }
      }

      return $records;
  }
  
}
