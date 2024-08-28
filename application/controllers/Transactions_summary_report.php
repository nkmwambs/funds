<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Transactions_summary_report extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('transactions_summary_report_library');
  }

  function index(){}

  function result($id = 0){
    $result = parent::result($id);

    if($this->action == 'list'){
      
      $transaction_type = 1;
      $start_date = date('Y-m-01');
      $end_date = date('Y-m-t');

      $data['transactions_summary'] = $this->transactions_summary($transaction_type, $start_date, $end_date);
      $result['transaction_summary'] = $this->load->view('transactions_summary_report/transactions_summary', $data, true);
    }

    return $result;
  }

  function transactions_summary ($transaction_type, $start_date, $end_date, $filter_civs = false) {

    $user_context_definition_level = $this->session->context_definition['context_definition_level'];

    // log_message('error', json_encode($context_definition));

    $office_ids = array_column($this->session->hierarchy_offices, 'office_id');
    $transactions = [];
    $result['accounts'] = [];
    $result['data'] = [];

    if($user_context_definition_level <= 4){ // Country Level Definion and Below

      $this->read_db->join('voucher_detail', 'voucher_detail.fk_voucher_id=voucher.voucher_id');
      $this->read_db->join('voucher_type','voucher.fk_voucher_type_id=voucher_type.voucher_type_id');
      $this->read_db->join('voucher_type_effect','voucher_type.fk_voucher_type_effect_id=voucher_type_effect.voucher_type_effect_id');
      $this->read_db->join('voucher_type_account','voucher_type.fk_voucher_type_account_id=voucher_type_account.voucher_type_account_id');
      $this->read_db->join('office','office.office_id=voucher.fk_office_id');
      $this->read_db->where_in('office_id', $office_ids);
  
      $group_by_array = [];
      $assorted_condition = [];
      $select_array = [];
  
      if($transaction_type == 1){ // Income
        $this->read_db->join('income_account','income_account.income_account_id=voucher_detail.fk_income_account_id');
        $assorted_condition = ['voucher_type_effect_code' => 'income'];
        $group_by_array = ['office_id','income_account_id'];
        $select_array = ['office_code','income_account_id as account_id','income_account_name as account_name','income_account_code as account_code'];

        // if($filter_civs){
        //   $select_array = ['office_code','project_id as account_id','project_name as account_name','CONCAT(project_code," (",income_account_code,")") as account_code'];
        // }

      }elseif ($transaction_type == 2) { //  Bank Expense
        $this->read_db->join('expense_account','expense_account.expense_account_id=voucher_detail.fk_expense_account_id');
        $assorted_condition = ['voucher_type_account_code' => 'bank', 'voucher_type_effect_code' => 'expense'];
        $group_by_array = ['office_id','expense_account_id'];
        $select_array = ['office_code','expense_account_id as account_id','expense_account_name as account_name','expense_account_code as account_code'];

        // if($filter_civs){
        //   $select_array = ['office_code','project_id as account_id','project_name as account_name','CONCAT(project_code," (",expense_account_code,")") as account_code'];
        // }

      }elseif ($transaction_type == 3) { // Cash Expenses
        $this->read_db->join('expense_account','expense_account.expense_account_id=voucher_detail.fk_expense_account_id');
        $assorted_condition = ['voucher_type_account_code' => 'cash', 'voucher_type_effect_code' => 'expense'];
        $group_by_array = ['office_id','expense_account_id'];
        $select_array = ['office_code','expense_account_id as account_id','expense_account_name as account_name','expense_account_code as account_code'];

        // if($filter_civs){
        //   $select_array = ['office_code','project_id as account_id','project_name as account_name','CONCAT(project_code," (",expense_account_code,")") as account_code'];
        // }

      }elseif ($transaction_type == 4) { // All Expenses
        $this->read_db->join('expense_account','expense_account.expense_account_id=voucher_detail.fk_expense_account_id');
        $group_by_array = ['office_id','expense_account_id'];
        $select_array = ['office_code','expense_account_id as account_id','expense_account_name as account_name','expense_account_code as account_code'];

        // if($filter_civs){
        //   $select_array = ['office_code','project_id as account_id','project_name as account_name','CONCAT(project_code," (",expense_account_code,")") as account_code'];
        // }
      }

      if($filter_civs){

        if($transaction_type == 1){
          $select_array = ['office_code','project_id as account_id','project_name as account_name','CONCAT(project_code," (",income_account_code,")") as account_code'];
        }else{
          $select_array = ['office_code','project_id as account_id','project_name as account_name','CONCAT(project_code," (",expense_account_code,")") as account_code'];
        }

        $this->read_db->join('project_allocation','voucher_detail.fk_project_allocation_id=project_allocation.project_allocation_id');
        $this->read_db->join('project','project_allocation.fk_project_id=project.project_id');

        $this->read_db->where('project.project_end_date NOT LIKE "0000-00-00"');
        $this->read_db->or_where('project.project_end_date IS NOT NULL'); //Added by Onduso Because of Nulling date  
  
      }
  
      $this->read_db->select($select_array);
      $this->read_db->select_sum('voucher_detail_total_cost');
      $this->read_db->group_by($group_by_array);
      $this->read_db->where($assorted_condition);
      $this->read_db->where(array('voucher_date >= ' => $start_date, 'voucher_date <= ' => $end_date));
  
      $transactions_obj = $this->read_db->get('voucher');
  
      if($transactions_obj->num_rows() > 0){
        $transactions = $transactions_obj->result_array();
  
        $account_ids = array_column( $transactions, 'account_id');
        $account_codes = array_column( $transactions, 'account_code');
  
        $accounts = array_combine($account_ids, $account_codes);
  
        $result['accounts'] = $accounts;
  
        foreach ($transactions as $office_transaction) {
          $result['data'][$office_transaction['office_code']][$office_transaction['account_id']]['account_name'] = $office_transaction['account_code'];
          $result['data'][$office_transaction['office_code']][$office_transaction['account_id']]['amount'] = $office_transaction['voucher_detail_total_cost'];
        }
        
      }
    }
    
    $result['parameters'] = ['transaction_type' => $transaction_type, 'start_date' => $start_date, 'end_date' => $end_date];
    
    return $result;
  }

  function refresh_report(){
    $post = $this->input->post();

    // log_message('error', json_encode($post));

    $date_range = explode('->',$post['date_range']);

    $transaction_type = $post['transaction_type'];
    $start_date = $date_range[0];
    $end_date = $date_range[1];

    if($this->days_difference($start_date, $end_date) > 366) {
      echo "<div class = 'well'>".get_phrase('out_year_date_range','You cannot run a report with range greater than a year')."</div>";
    }else{
      $data['transactions_summary'] = $this->transactions_summary($transaction_type, $start_date, $end_date, $post['filter_civs']);
      echo $this->load->view('transactions_summary_report/transactions_summary', $data, true);
    }

  }

  function days_difference($start_date, $end_date){

    $start_date = strtotime($start_date);
    $end_date = strtotime($end_date);

    $days = ($end_date - $start_date)/60/60/24;

    return $days;
  }


  static function get_menu_list(){}

}