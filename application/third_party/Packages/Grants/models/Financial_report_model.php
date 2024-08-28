<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Financial_report_model extends MY_Model
{

    public $table = 'financial_report';
    public $dependant_table = '';
    public $name_field = 'financial_report_name';
    public $create_date_field = "financial_report_created_date";
    public $created_by_field = "financial_report_created_by";
    public $last_modified_date_field = "financial_report_last_modified_date";
    public $last_modified_by_field = "financial_report_last_modified_by";
    public $deleted_at_field = "financial_report_deleted_at";

    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    function index()
    {
    }

    public function lookup_tables()
    {
        return array('office', 'status', 'approval');
    }

    function show_add_button()
    {
        return false;
    }

    function list_table_visible_columns()
    {
        return ['financial_report_track_number', 'office_name', 'financial_report_month', 'financial_report_created_date', 'financial_report_is_submitted', 'status_name'];
    }

    function edit_visible_columns()
    {
        return ["financial_report_name", "financial_report_statement_date"];
    }

    /**
     * @todo - Find out why this method causes an error $this->user_model->get_lowest_office_context()->context_definition_id;
     */
    function list_table_where()
    {
        //$context_definition_level = $this->session->context_definition['context_definition_level'];

        if (!$this->session->system_admin) {
            $this->read_db->where_in('office_id', array_column($this->session->hierarchy_offices, 'office_id'));
        }
    }

    public function detail_tables()
    {
    }

    public function detail_multi_form_add_visible_columns()
    {
    }

    function financial_report_information($id, array $offices_ids = [], $reporting_month = '')
    {
        // log_message('error', json_encode(($offices_ids)));
        $report_id = hash_id($id, 'decode');

        $offices_information = [];

        $this->read_db->select(array('financial_report_month', 'fk_office_id as office_id', 'office_name', 'financial_report.fk_status_id as status_id', 'fk_account_system_id as account_system_id'));
        $this->read_db->join('office', 'office.office_id=financial_report.fk_office_id');

        if (count($offices_ids) > 0) {
            $this->read_db->where_in('fk_office_id', $offices_ids);

            if ($reporting_month != '') {
                $this->read_db->where(array('financial_report_month' => date('Y-m-01', strtotime($reporting_month))));
            }
        } else {
            $this->read_db->where(array('financial_report_id' => $report_id));
        }

        $offices_information =  $this->read_db->get('financial_report')->result_array();

        return $offices_information;
    }


    // function month_income_opening_balance($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    // {
       
    //     //print_r($income_accounts);exit;
    //     // $income_accounts = $this->income_accounts($office_ids, $project_ids, $office_bank_ids);
    //     // log_message('error', json_encode($office_bank_ids));

    //     $opening_balances = [];

    //     $opening_balances = $this->_get_to_date_account_opening_balance($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);

    //     // foreach ($income_accounts as $income_account) {

    //     //     $opening_balances[$income_account['income_account_id']] =  $to_date_account_opening_balance[$income_account['income_account_id']];
    //     // }

    //     //print_r($opening_balances);exit;
        
    //     return $opening_balances;
    // }

    function _initial_opening_account_balance($office_ids, $project_ids = [], $office_bank_ids = [])
    {
        $account_opening_balance = [];

        $this->read_db->select(array('opening_fund_balance.fk_income_account_id as fk_income_account_id'));
        $this->read_db->select_sum('opening_fund_balance_amount');
        $this->read_db->join('opening_fund_balance', 'opening_fund_balance.fk_system_opening_balance_id=system_opening_balance.system_opening_balance_id');

        if (count($office_bank_ids) > 0) {
            $this->read_db->where_in('opening_fund_balance.fk_office_bank_id', $office_bank_ids);
        }

        // log_message('error', json_encode($project_ids));

        if (count($project_ids) > 0) {
            $this->read_db->where_in('project.project_id', $project_ids);
            $this->read_db->join('income_account', 'income_account.income_account_id=opening_fund_balance.fk_income_account_id');
            $this->read_db->join('project_income_account', 'project_income_account.fk_income_account_id=income_account.income_account_id');
            $this->read_db->join('project', 'project.project_id=project_income_account.fk_project_id');
        }

        $this->read_db->group_by(array('fk_income_account_id'));
        $this->read_db->where_in('system_opening_balance.fk_office_id', $office_ids);
        $initial_account_opening_balance_obj = $this->read_db->get('system_opening_balance');


        if ($initial_account_opening_balance_obj->num_rows() > 0) {
            $account_opening_balance_array = $initial_account_opening_balance_obj->result_array();
            
            foreach($account_opening_balance_array as $row){
                $account_opening_balance[$row['fk_income_account_id']] = $row['opening_fund_balance_amount'];
            }
        }

        return $account_opening_balance;
    }

    // function _initial_opening_account_balance($office_ids, $income_account_id, $project_ids = [], $office_bank_ids = [])
    // {
    //     $account_opening_balance = 0;

    //     $this->read_db->select_sum('opening_fund_balance_amount');
    //     $this->read_db->join('opening_fund_balance', 'opening_fund_balance.fk_system_opening_balance_id=system_opening_balance.system_opening_balance_id');

    //     if (count($office_bank_ids) > 0) {
    //         $this->read_db->where_in('opening_fund_balance.fk_office_bank_id', $office_bank_ids);
    //     }

    //     if (count($project_ids) > 0) {
    //         $this->read_db->where_in('project.project_id', $project_ids);
    //         $this->read_db->join('income_account', 'income_account.income_account_id=opening_fund_balance.fk_income_account_id');
    //         $this->read_db->join('project_income_account', 'project_income_account.fk_income_account_id=income_account.income_account_id');
    //         $this->read_db->join('project', 'project.project_id=project_income_account.fk_project_id');
    //     }

    //     $this->read_db->group_by(array('fk_income_account_id'));
    //     $this->read_db->where_in('system_opening_balance.fk_office_id', $office_ids);
    //     $initial_account_opening_balance_obj = $this->read_db->get_where(
    //         'system_opening_balance',
    //         array('opening_fund_balance.fk_income_account_id' => $income_account_id)
    //     );


    //     if ($initial_account_opening_balance_obj->num_rows() == 1) {
    //         $account_opening_balance = $initial_account_opening_balance_obj->row()->opening_fund_balance_amount;
    //     }

    //     return $account_opening_balance;
    // }

    function compute_cash_at_hand($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [], $office_cash_id = 0, $retrieve_only_max_approved = true)
    {
      //return 15000;
      $cash_transactions_to_date = $this->cash_transactions_to_date($office_ids, $reporting_month, $project_ids, $office_bank_ids, $office_cash_id, $retrieve_only_max_approved); 

      $opening_cash_balance = $this->opening_cash_balance($office_ids, $reporting_month, $project_ids, $office_bank_ids, $office_cash_id)['cash'];
      $cash_income_to_date = isset($cash_transactions_to_date['cash']['income']) ? $cash_transactions_to_date['cash']['income'] : 0; 
      $cash_expenses_to_date = isset($cash_transactions_to_date['cash']['expense']) ? $cash_transactions_to_date['cash']['expense'] : 0; 
  
      //return $cash_expenses_to_date;
      return $opening_cash_balance + $cash_income_to_date - $cash_expenses_to_date;
    }

    // function compute_cash_at_bank($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [], $retrieve_only_max_approved = true)
    // {
    //   // log_message('error', json_encode($office_bank_ids));
  
    //   $office_ids = array_unique($office_ids); // Find out why office_ids come in duplicates
    
    //   $opening_bank_balance = $this->opening_cash_balance($office_ids, $reporting_month, $project_ids, $office_bank_ids)['bank'];
      
    //   $bank_income_to_date = $this->cash_transactions_to_date($office_ids, $reporting_month, 'income', 'bank', $project_ids, $office_bank_ids,0,$retrieve_only_max_approved); //$this->_cash_income_to_date($office_ids,$reporting_month);
    //   $bank_expenses_to_date = $this->cash_transactions_to_date($office_ids, $reporting_month, 'expense', 'bank', $project_ids, $office_bank_ids,0,$retrieve_only_max_approved); //$this->_cash_expense_to_date($office_ids,$reporting_month);
  
    //   return $opening_bank_balance + $bank_income_to_date - $bank_expenses_to_date;
    // }

    function bank_to_bank_contra_receipts(Array $office_bank_ids, String $reporting_month) : Array {
        $bank_to_bank_contra_received_amounts = [];

        $end_of_reporting_month = date('Y-m-t', strtotime($reporting_month));
       
        if(count($office_bank_ids) > 0){
            $this->read_db->select(array('income_account_id'));
            $this->read_db->select_sum('voucher_detail_total_cost');
            $this->read_db->group_by(array('income_account_id'));
            $this->read_db->where_in('office_bank_id', $office_bank_ids);
            $this->read_db->where(array('voucher_date <=' => $end_of_reporting_month));
            $voucher_detail_total_cost_obj = $this->read_db->get('bank_to_bank_contra_receipts');
    
    
            if($voucher_detail_total_cost_obj->num_rows() > 0){

                $income_account_grouped = $voucher_detail_total_cost_obj->result_array();

                foreach( $income_account_grouped as $row){
                    if($row['income_account_id'] != null &&  $row['voucher_detail_total_cost'] > 0){
                        $bank_to_bank_contra_received_amounts[$row['income_account_id']] =  $row['voucher_detail_total_cost'] ? $row['voucher_detail_total_cost'] : 0;
                    }
                }
 
            }
        }

        
        return $bank_to_bank_contra_received_amounts;
    }

    function bank_to_bank_contra_contributions(String $reporting_month, $office_bank_ids = []): Array {

        $bank_to_bank_contra_contributed_amounts = [];

        $end_of_reporting_month = date('Y-m-t', strtotime($reporting_month));

        if(count($office_bank_ids) > 0){
            $this->read_db->select(array('income_account_id'));
            $this->read_db->select_sum('voucher_detail_total_cost');
            $this->read_db->group_by(array('income_account_id'));
            $this->read_db->where_in('office_bank_id', $office_bank_ids);
            $this->read_db->where(array('voucher_date <=' => $end_of_reporting_month));
            $voucher_detail_total_cost_obj = $this->read_db->get('bank_to_bank_contra_contributions');

            if($voucher_detail_total_cost_obj->num_rows() > 0){

                $income_account_grouped = $voucher_detail_total_cost_obj->result_array();
         
                foreach( $income_account_grouped as $row){
                    if($row['income_account_id'] != null){
                        $bank_to_bank_contra_contributed_amounts[$row['income_account_id']] =  $row['voucher_detail_total_cost'] ? $row['voucher_detail_total_cost'] : 0;
                    }
                }
 
            }
        }

        return $bank_to_bank_contra_contributed_amounts;
    }

    function compute_cash_at_bank($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [], $retrieve_only_max_approved = true)
    {
      // log_message('error', json_encode($office_bank_ids));

      $to_date_cancelled_opening_oustanding_cheques = $this->financial_report_model->get_month_cancelled_opening_outstanding_cheques($office_ids, $reporting_month, $project_ids, $office_bank_ids, 'to_date');
    
      $office_ids = array_unique($office_ids); // Find out why office_ids come in duplicates
      
      $opening_bank_balance = $this->opening_cash_balance($office_ids, $reporting_month, $project_ids, $office_bank_ids)['bank'];
    //   log_message('error', json_encode($opening_bank_balance));
    //   log_message('error', json_encode($opening_bank_balance));

     $bank_to_bank_contra_receipts = $this->bank_to_bank_contra_receipts($office_bank_ids, $reporting_month);
     $bank_to_bank_contra_contributions = $this->bank_to_bank_contra_contributions($reporting_month, $office_bank_ids);

     $cash_transactions_to_date = $this->cash_transactions_to_date($office_ids, $reporting_month, $project_ids, $office_bank_ids,0,$retrieve_only_max_approved); 

      $bank_income_to_date = isset($cash_transactions_to_date['bank']['income']) ? $cash_transactions_to_date['bank']['income'] : 0; 
      $bank_expenses_to_date = isset($cash_transactions_to_date['bank']['expense']) ? $cash_transactions_to_date['bank']['expense'] : 0; 
 
      $computed_cash_at_bank = $opening_bank_balance + $bank_income_to_date - $bank_expenses_to_date;
    
    //   log_message('error', json_encode(['computed_cash_at_bank' => $computed_cash_at_bank, 'opening_bank_balance' => $opening_bank_balance, 'bank_income_to_date' => $bank_income_to_date, 'bank_expenses_to_date' => $bank_expenses_to_date]));

      if($bank_to_bank_contra_receipts > 0){
        $computed_cash_at_bank = $computed_cash_at_bank + array_sum($bank_to_bank_contra_receipts);
      }

      if($bank_to_bank_contra_contributions > 0){
        $computed_cash_at_bank = $computed_cash_at_bank - array_sum($bank_to_bank_contra_contributions);
      }

    //   log_message('error', json_encode($to_date_cancelled_opening_oustanding_cheques));

        $computed_cash_at_bank = $computed_cash_at_bank + $to_date_cancelled_opening_oustanding_cheques;

      return $computed_cash_at_bank; // $opening_bank_balance + $bank_income_to_date - $bank_expenses_to_date;
    }

    /**
     * @todo:
     * Awaiting documentation
     * Updated by Nicodemus Karisa
     */

    function opening_cash_balance($office_ids, $reporting_month, array $project_ids = [], $office_bank_ids = [], $office_cash_id = 0)
    {
        // log_message('error', json_encode($reporting_month));

        $bank_balance_amount = $this->financial_report_model->system_opening_bank_balance($office_ids, $project_ids, $office_bank_ids);

        //  log_message('error', json_encode($bank_balance_amount));

        if(!isset($_POST['reporting_month'])){
        $report = $this->financial_report_information($this->id);
        extract($report);
        $report_month=$reporting_month;
        }
        else{
        $report_month=$_POST['reporting_month'];
        }
        //If the mfr has been submitted. Adjust the child support fund by taking away exact amount of bounced opening chqs This code was added during enhancement for cancelling opening outstanding chqs

        if ($this->financial_report_model->check_if_financial_report_is_submitted($office_ids, $reporting_month) == true) {

            $sum_of_bounced_cheques = $this->financial_report_model->get_total_sum_of_bounced_opening_cheques($office_ids, $reporting_month, $project_ids, $office_bank_ids);

            // log_message('error', json_encode($sum_of_bounced_cheques));

            $mfr_report_month= date('Y-m-t', strtotime($reporting_month));

            $total_amount_bounced = isset($sum_of_bounced_cheques[0]['opening_outstanding_cheque_amount']) ? $sum_of_bounced_cheques[0]['opening_outstanding_cheque_amount'] : 0;
            $bounced_date = isset($sum_of_bounced_cheques[0]['opening_outstanding_cheque_cleared_date']) ? $sum_of_bounced_cheques[0]['opening_outstanding_cheque_cleared_date'] : NULL;

            if($total_amount_bounced > 0 &&  $bounced_date > $mfr_report_month ){
                $bank_balance_amount=$bank_balance_amount-$total_amount_bounced;
            }
        }

        // log_message('error', json_encode($bank_balance_amount));
        
        $balance =  [
        'bank' => $bank_balance_amount,
        'cash' => $this->financial_report_model->system_opening_cash_balance($office_ids, $project_ids, $office_bank_ids, $office_cash_id)
        ];

        // log_message('error', json_encode($balance));

        return $balance;
  }

    /**
     * Added by onduso on 18/2/2022
     * @todo:
     * Awaiting documentation
     * Updated by Nicodemus Karisa
     */

    public function get_total_sum_of_bounced_opening_cheques($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
    {
        
        $reporting_month = date('Y-m-t', strtotime($reporting_month));

        if(sizeof($office_bank_ids) == 0){
            $office_bank=$this->get_office_banks($office_ids);
            $office_bank_ids=array_column($office_bank,'office_bank_id');
        }
        $this->read_db->select_sum('opening_outstanding_cheque_amount');
        $this->read_db->select(array('opening_outstanding_cheque_cleared_date'));
        $this->read_db->where_in('fk_office_bank_id', $office_bank_ids);
        $this->read_db->where(array('opening_outstanding_cheque_bounced_flag' => 1));
        $this->read_db->where(array('opening_outstanding_cheque_cleared_date' => $reporting_month));
        $this->read_db->group_by(array('fk_office_bank_id','opening_outstanding_cheque_cleared_date')); // Modified by Nicodemus Karisa on 13th May 2022
        return $this->read_db->get('opening_outstanding_cheque')->result_array();
    }
    function get_office_banks($office_ids, $project_ids = [], $office_bank_ids = []){


        $this->read_db->select(array('DISTINCT(office_bank_id)', 'office_bank_name'));
        $this->read_db->where_in('fk_office_id' ,$office_ids);
        $this->read_db->join('office_bank', 'office_bank.office_bank_id=office_bank_project_allocation.fk_office_bank_id');

        if (!empty($office_bank_ids)) {
            $this->read_db->where_in('fk_office_bank_id', $office_bank_ids);
        }

        $office_banks = $this->read_db->get('office_bank_project_allocation')->result_array();
        
    
        return $office_banks;
    }

    /*End of Onduso addition*/

    function month_income_opening_balance($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    {
        // log_message('error', json_encode($project_ids));

        $max_approval_status_ids = $this->general_model->get_max_approval_status_id('voucher', $office_ids);

        $initial_account_opening_balance = $this->_initial_opening_account_balance($office_ids, $project_ids, $office_bank_ids);     

        $account_last_month_income_to_date = $this->_get_account_last_month_income_to_date($office_ids, $start_date_of_month, $max_approval_status_ids, $project_ids, $office_bank_ids);
        
        $account_last_month_expense_to_date = $this->_get_account_last_month_expense_to_date($office_ids, $start_date_of_month, $max_approval_status_ids, $project_ids, $office_bank_ids);
        
        $income_account_ids = array_unique(array_merge(array_keys($initial_account_opening_balance), array_keys($account_last_month_income_to_date), array_keys($account_last_month_expense_to_date)));
        
        $account_opening_balance=[];
        
        foreach($income_account_ids as $income_account_id){
            $opening = isset($initial_account_opening_balance[$income_account_id]) ? $initial_account_opening_balance[$income_account_id] : 0;
            $income = isset($account_last_month_income_to_date[$income_account_id]) ? $account_last_month_income_to_date[$income_account_id] : 0;
            $expense = isset($account_last_month_expense_to_date[$income_account_id]) ? $account_last_month_expense_to_date[$income_account_id] : 0;

            $account_opening_balance[$income_account_id] = $opening  + ($income - $expense);
        }

        return $account_opening_balance;
    }

    /**
     * @todo:
     * Awaiting documentation
     */

    function get_month_cancelled_opening_outstanding_cheques($office_ids, $start_date_of_month, $project_ids, $office_bank_ids, $aggregation_period = 'current_month'){ // Options: current_month, past_months, to_date

        $sum_cancelled_cheques = 0;
        
        $first_month_date = date('Y-m-01', strtotime($start_date_of_month));
        $end_month_date = date('Y-m-t', strtotime($start_date_of_month));
        
        $this->read_db->select_sum('opening_outstanding_cheque_amount');
        $this->read_db->where_in('fk_office_id', $office_ids);
        $this->read_db->where(['opening_outstanding_cheque_bounced_flag' => 1]);

        $condition = ['opening_outstanding_cheque_cleared_date' =>  $end_month_date];

        if($aggregation_period == 'past_months'){
            $condition = ['opening_outstanding_cheque_cleared_date < ' =>  $first_month_date];
        }

        if($aggregation_period == 'to_date'){
            $condition = ['opening_outstanding_cheque_cleared_date <= ' =>  $end_month_date];
        }

        // $condition = 'opening_outstanding_cheque_cleared_date <> LAST_DAY(office_start_date)'; 

        $this->read_db->where($condition); 

        if(!empty($office_bank_ids)){
            $this->read_db->where_in('fk_office_bank_id', $office_bank_ids);
        }

        $this->read_db->group_by(array('fk_system_opening_balance_id'));
        $this->read_db->join('system_opening_balance','system_opening_balance.system_opening_balance_id=opening_outstanding_cheque.fk_system_opening_balance_id');
        $this->read_db->join('office','office.office_id=system_opening_balance.fk_office_id');
        $opening_outstanding_cheque_obj = $this->read_db->get('opening_outstanding_cheque');

        if($opening_outstanding_cheque_obj->num_rows() > 0){

            $sum_cancelled_cheques = $opening_outstanding_cheque_obj->row()->opening_outstanding_cheque_amount;
        }

        // log_message('error', json_encode([$sum_cancelled_cheques]));

        return $sum_cancelled_cheques;
    }

    function _get_account_last_month_income_to_date($office_ids, $start_date_of_month, $max_approval_status_ids, $project_ids = [], $office_bank_ids = [])
    {

        $previous_months_income_to_date = [];

        $this->read_db->select(array('income_account_id'));
        $this->read_db->select_sum('amount');
        $this->read_db->where_in('fk_office_id', $office_ids);

        if (!empty($office_bank_ids)) {
            $this->read_db->where_in('fk_office_bank_id', $office_bank_ids);
        }

        $this->read_db->where(array('voucher_month < ' => $start_date_of_month));
        $this->read_db->group_by(array('income_account_id'));
        $this->read_db->where_in('fk_status_id', $max_approval_status_ids);
        $previous_months_income_obj = $this->read_db->get('monthly_sum_income_per_center');

        if ($previous_months_income_obj->num_rows() > 0) {
            $previous_months_income_to_date_arr = $previous_months_income_obj->result_array(); //->row()->voucher_detail_total_cost;
            
            foreach($previous_months_income_to_date_arr as $row){
                $previous_months_income_to_date[$row['income_account_id']] = $row['amount'];
            }
        }

        return $previous_months_income_to_date;
    }

    // function _get_account_last_month_income_to_date($office_ids, $income_account_id, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    // {

    //     $previous_months_income_to_date = 0;
    //     $get_office_bank_project_allocation = !empty($project_ids) ? $project_ids : $this->get_office_bank_project_allocation($office_bank_ids);

    //     $this->read_db->select_sum('voucher_detail_total_cost');
    //     $this->read_db->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
    //     $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
    //     $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
    //     $this->read_db->group_by('voucher_type_effect_code');
    //     $this->read_db->where_in('voucher.fk_office_id', $office_ids);

    //     if (count($project_ids) > 0) {
    //         $this->read_db->where_in('fk_project_id', $project_ids);
    //         $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');
    //     }

    //     if (!empty($office_bank_ids)) {

    //         $this->read_db->group_start();
    //         $this->read_db->where_in('voucher.fk_office_bank_id', $office_bank_ids);
    //         $this->read_db->where_in('voucher_detail.fk_project_allocation_id', $get_office_bank_project_allocation);
    //         $this->read_db->group_end();
    //     }

    //     $previous_months_income_obj = $this->read_db->get_where(
    //         'voucher_detail',
    //         array(
    //             'voucher_date<' => $start_date_of_month,
    //             'voucher_detail.fk_income_account_id' => $income_account_id, 'voucher_type_effect_code' => 'income'
    //         )
    //     );

    //     if ($previous_months_income_obj->num_rows() > 0) {
    //         $previous_months_income_to_date = $previous_months_income_obj->row()->voucher_detail_total_cost;
    //     }

    //     return $previous_months_income_to_date;
    // }

    function _get_account_last_month_expense_to_date($office_ids, $start_date_of_month,$max_approval_status_ids, $project_ids = [], $office_bank_ids = [])
    {

        $previous_months_expense_to_date = [];
    
        $this->read_db->select(array('income_account_id'));
        $this->read_db->select_sum('amount');

        $this->read_db->where_in('fk_office_id', $office_ids);

        if (!empty($office_bank_ids)) {
            $this->read_db->where_in('fk_office_bank_id', $office_bank_ids);
        }

        $this->read_db->where(array('voucher_month < ' => $start_date_of_month));
        $this->read_db->group_by(array('income_account_id'));
        $this->read_db->where_in('fk_status_id', $max_approval_status_ids);
        $previous_months_expense_obj = $this->read_db->get('monthly_sum_income_expense_per_center');

        if ($previous_months_expense_obj->num_rows() > 0) {
            $previous_months_expense_to_date_arr = $previous_months_expense_obj->result_array(); //->row()->voucher_detail_total_cost;
            
            foreach($previous_months_expense_to_date_arr as $row){
                $previous_months_expense_to_date[$row['income_account_id']] = $row['amount'];
            }
        }

        return $previous_months_expense_to_date;
    }

    // function _get_account_last_month_expense_to_date($office_ids, $income_account_id, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    // {

    //     $previous_months_expense_to_date = 0;
    //     $get_office_bank_project_allocation = !empty($project_ids) ? $project_ids : $this->get_office_bank_project_allocation($office_bank_ids);

    //     $this->read_db->select_sum('voucher_detail_total_cost');
    //     $this->read_db->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
    //     $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
    //     $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
    //     $this->read_db->join('expense_account', 'expense_account.expense_account_id=voucher_detail.fk_expense_account_id');
    //     $this->read_db->join('income_account', 'income_account.income_account_id=expense_account.fk_income_account_id');

    //     if (count($project_ids) > 0) {
    //         $this->read_db->where_in('fk_project_id', $project_ids);
    //         $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');
    //     }

    //     $this->read_db->group_by('voucher_type_effect_code');
    //     $this->read_db->where_in('voucher.fk_office_id', $office_ids);

    //     if (!empty($office_bank_ids)) {
    //         $this->read_db->group_start();
    //         $this->read_db->where_in('voucher.fk_office_bank_id', $office_bank_ids);
    //         $this->read_db->where_in('voucher_detail.fk_project_allocation_id', $get_office_bank_project_allocation);
    //         $this->read_db->group_end();
    //     }

    //     $previous_months_expense_obj = $this->read_db->get_where(
    //         'voucher_detail',
    //         array(
    //             'voucher_date<' => $start_date_of_month,
    //             'income_account_id' => $income_account_id, 'voucher_type_effect_code' => 'expense'
    //         )
    //     );

    //     if ($previous_months_expense_obj->num_rows() > 0) {
    //         $previous_months_expense_to_date = $previous_months_expense_obj->row()->voucher_detail_total_cost;
    //     }

    //     return $previous_months_expense_to_date;
    // }

    // function month_income_account_receipts($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    // {
    //     //print_r($project_ids);exit;
        
    //     $income_accounts = $this->income_accounts($office_ids, $project_ids);

    //     $month_income = [];

    //     foreach ($income_accounts as $income_account) {
    //         $month_income[$income_account['income_account_id']] = $this->_get_account_month_income($office_ids, $income_account['income_account_id'], $start_date_of_month, $project_ids, $office_bank_ids);
    //     }
    //     //print_r($month_income);exit;
    //     return $month_income;
    // }

    function month_income_account_receipts($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    {
        
        // log_message('error', json_encode([
        //     'office_ids' => $office_ids, 
        //     'start_date_of_month' => $start_date_of_month, 
        //     'project_ids' => $project_ids, 
        //     'office_bank_ids' => $office_bank_ids
        // ]));

        $income_accounts = $this->income_accounts($office_ids, $project_ids);

        $month_income = [];

        $bank_to_bank_contra_receipts = $this->bank_to_bank_contra_receipts($office_bank_ids, $start_date_of_month);
        $bank_to_bank_contra_contributions = $this->bank_to_bank_contra_contributions($start_date_of_month, $office_bank_ids);

        $account_month_income = $this->_get_account_month_income($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);

        // log_message('error', json_encode(['income_accounts' => $income_accounts, 'account_month_income' => $account_month_income]));

        foreach ($income_accounts as $income_account) {
            $month_income[$income_account['income_account_id']] = isset($account_month_income[$income_account['income_account_id']]) ? $account_month_income[$income_account['income_account_id']] : 0;

            if(isset($this->bank_to_bank_contra_receipts($office_bank_ids, $start_date_of_month)[$income_account['income_account_id']])){
                $month_income[$income_account['income_account_id']] = $month_income[$income_account['income_account_id']] + $bank_to_bank_contra_receipts[$income_account['income_account_id']];
            }

            if(isset($this->bank_to_bank_contra_contributions($start_date_of_month, $office_bank_ids)[$income_account['income_account_id']])){
                $month_income[$income_account['income_account_id']] = $month_income[$income_account['income_account_id']] - $bank_to_bank_contra_contributions[$income_account['income_account_id']];
            }
        }
        
        return $month_income;
    }

    // function _get_account_month_income($office_ids, $income_account_id, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    // {
    //     //echo $income_account_id;exit;
    //     $max_approval_status_ids = $this->general_model->get_max_approval_status_id('voucher');

    //     $last_date_of_month = date('Y-m-t', strtotime($start_date_of_month));
    //     $get_office_bank_project_allocation = !empty($project_ids) ? $project_ids : $this->get_office_bank_project_allocation($office_bank_ids);

    //     $month_income = 0;

    //     $this->read_db->select_sum('voucher_detail_total_cost');
    //     $this->read_db->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
    //     $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
    //     $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
    //     $this->read_db->group_by(array('fk_income_account_id'));
    //     $this->read_db->where_in('voucher.fk_office_id', $office_ids);

    //     $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');

    //     if (count($project_ids) > 0) {
    //         $this->read_db->where_in('project_allocation.fk_project_id', $project_ids);
    //     }

    //     if (count($office_bank_ids) > 0) {
    //         $this->read_db->group_start();
    //         $this->read_db->where_in('voucher.fk_office_bank_id', $office_bank_ids);
    //         $this->read_db->where_in('voucher_detail.fk_project_allocation_id', $get_office_bank_project_allocation);
    //         $this->read_db->group_end();
    //     }

    //     $this->read_db->where_in('voucher.fk_status_id', $max_approval_status_ids);

    //     $month_income_obj = $this->read_db->get_where(
    //         'voucher_detail',
    //         array(
    //             'voucher_type_effect_code' => 'income',
    //             'fk_income_account_id' => $income_account_id, 'voucher_date>=' => $start_date_of_month,
    //             'voucher_date<=' => $last_date_of_month
    //         )
    //     );

    //     if ($month_income_obj->num_rows() > 0) {
    //         // log_message('error', json_encode($month_income_obj->row()));
    //         $month_income = $month_income_obj->row()->voucher_detail_total_cost;
    //     }

    //     return $month_income;
    // }

    function _get_account_month_income($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = []){

        // log_message('error', json_encode([
        //     'office_ids' => $office_ids,
        //     'start_date_of_month' => $start_date_of_month,
        //     'project_ids' => $project_ids,
        //     'office_bank_ids' => $office_bank_ids
        // ]));
        
        $max_approval_status_ids = $this->general_model->get_max_approval_status_id('voucher', $office_ids);
        $month_income = [];

        $this->read_db->select(array('income_account_id'));
        $this->read_db->select_sum('amount');

        if (count($project_ids) > 0) {
            $this->read_db->where_in('fk_project_id', $project_ids);
        }

        if (count($office_bank_ids) > 0) {
            $this->read_db->where_in('fk_office_bank_id', $office_bank_ids);
        }
        
        $this->read_db->where_in('fk_status_id', $max_approval_status_ids);
        $this->read_db->where_in('fk_office_id', $office_ids);
        $condition_array = array(
             'voucher_month' => $start_date_of_month,
        );
        $this->read_db->where($condition_array);
        $this->read_db->group_by(array('income_account_id'));
        $month_income_obj = $this->read_db->get('monthly_sum_income_per_center');

        if ($month_income_obj->num_rows() > 0) {
            $month_income_arr = $month_income_obj->result_array();
            
            // log_message('error', json_encode($month_income_arr));

            foreach($month_income_arr as $row){
                $month_income[$row['income_account_id']] = $row['amount'];
            }
        }
        // log_message('error', json_encode($month_income));

        return $month_income;
    }

    function month_income_account_expenses($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    {

        $income_accounts = $this->income_accounts($office_ids, $project_ids);

        $expense_income = [];

        $income_account_month_expense = $this->_get_income_account_month_expense($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);

        foreach ($income_accounts as $income_account) {
            $expense_income[$income_account['income_account_id']] = isset($income_account_month_expense[$income_account['income_account_id']]) ? $income_account_month_expense[$income_account['income_account_id']] : 0;
        }

        return $expense_income;
    }

    function _get_income_account_month_expense($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    {
        $expense_income = [];

        $max_approval_status_ids = $this->general_model->get_max_approval_status_id('voucher', $office_ids);

        $this->read_db->select(array('income_account_id'));
        $this->read_db->select_sum('amount');
        $this->read_db->where_in('fk_status_id', $max_approval_status_ids);

        if (count($office_bank_ids) > 0) {
            $this->read_db->where_in('fk_office_bank_id', $office_bank_ids);
        }

        $this->read_db->where(array('voucher_month' => $start_date_of_month));
        $this->read_db->group_by(array('income_account_id'));
        $this->read_db->where_in('fk_office_id', $office_ids);
        $expense_income_obj = $this->read_db->get('monthly_sum_income_expense_per_center');


        if ($expense_income_obj->num_rows() > 0) {
            $expense_income_arr = $expense_income_obj->result_array();

            foreach($expense_income_arr as $row){
                $expense_income[$row['income_account_id']] = $row['amount'];
            }
        }

        return $expense_income;
    }

    // function _get_income_account_month_expense($office_ids, $income_account_id, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    // {
    //     $last_date_of_month = date('Y-m-t', strtotime($start_date_of_month));
    //     $get_office_bank_project_allocation = !empty($project_ids) ? $project_ids : $this->get_office_bank_project_allocation($office_bank_ids);
    //     $expense_income = 0;

    //     $max_approval_status_ids = $this->general_model->get_max_approval_status_id('voucher');

    //     $this->read_db->select_sum('voucher_detail_total_cost');
    //     $this->read_db->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
    //     $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
    //     $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
    //     $this->read_db->join('expense_account', 'expense_account.expense_account_id=voucher_detail.fk_expense_account_id');
    //     $this->read_db->join('income_account', 'income_account.income_account_id=expense_account.fk_income_account_id');
    //     $this->read_db->group_by('voucher_type_effect_code');
    //     $this->read_db->where_in('voucher.fk_office_id', $office_ids);

    //     $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');

    //     if (count($project_ids) > 0) {
    //         $this->read_db->where_in('project_allocation.fk_project_id', $project_ids);
    //     }

    //     if (count($office_bank_ids) > 0) {

           
    //         $this->read_db->group_start();
    //         $this->read_db->where_in('voucher.fk_office_bank_id', $office_bank_ids);
    //         $this->read_db->where_in('voucher_detail.fk_project_allocation_id', $get_office_bank_project_allocation);
    //         $this->read_db->group_end();
    //     }

    //     $this->read_db->where_in('voucher.fk_status_id', $max_approval_status_ids);

    //     $expense_income_obj = $this->read_db->get_where(
    //         'voucher_detail',
    //         array(
    //             'voucher_date>=' => $start_date_of_month, 'voucher_date<=' => $last_date_of_month,
    //             'income_account_id' => $income_account_id, 'voucher_type_effect_code' => 'expense'
    //         )
    //     );

    //     if ($expense_income_obj->num_rows() > 0) {
    //         $expense_income = $expense_income_obj->row()->voucher_detail_total_cost;
    //     }

    //     return $expense_income;
    // }

    function income_accounts($office_ids, $project_ids = [], $office_bank_ids = [])
    {

        // log_message('error', json_encode([
        //     'office_ids' => $office_ids,
        //     'project_ids' => $project_ids,
        //     'office_bank_ids' => $office_bank_ids
        // ]));

        // Array of account system
        $this->read_db->select('fk_account_system_id');
        $this->read_db->where_in('office_id', $office_ids);
        $office_account_system_ids = $this->read_db->get('office')->result_array();

        if (count($project_ids) > 0) {
            $this->read_db->where_in('project.project_id', $project_ids);
            $this->read_db->join('project_income_account', 'project_income_account.fk_income_account_id=income_account.income_account_id');
            $this->read_db->join('project', 'project.project_id=project_income_account.fk_project_id');
        }

        if (count($office_bank_ids) > 0) {
            $this->read_db->join('project_income_account', 'project_income_account.fk_income_account_id=income_account.income_account_id');
            $this->read_db->join('project', 'project.project_id=project_income_account.fk_project_id');

            $this->read_db->join('project_allocation', 'project_allocation.fk_project_id=project.project_id');
            $this->read_db->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_project_allocation_id=project_allocation.project_allocation_id');
            $this->read_db->where_in('fk_office_bank_id', $office_bank_ids);
        }
        
        $this->read_db->where_in('income_account.fk_account_system_id', array_column($office_account_system_ids, 'fk_account_system_id'));
        $this->read_db->group_by(array('income_account_id'));
        $result = $this->read_db->select(array('income_account_id', 'income_account_name'))->get('income_account')->result_array();
        
        // log_message('error', json_encode($result));
        
        return $result;
    }

    function month_utilized_income_accounts($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = []){

        $income_accounts =  $this->income_accounts($office_ids, $project_ids, $office_bank_ids);
        
        // log_message('error', json_encode($project_ids));

        $all_accounts_month_opening_balance = $this->month_income_opening_balance($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);
        $all_accounts_month_income = $this->month_income_account_receipts($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);
        $all_accounts_month_expense = $this->month_income_account_expenses($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);

        $report = array();

        foreach ($income_accounts as $account) {

        $month_opening_balance = isset($all_accounts_month_opening_balance[$account['income_account_id']]) ? $all_accounts_month_opening_balance[$account['income_account_id']] : 0;
        $month_income = isset($all_accounts_month_income[$account['income_account_id']]) ? $all_accounts_month_income[$account['income_account_id']] : 0;
        $month_expense = isset($all_accounts_month_expense[$account['income_account_id']]) ? $all_accounts_month_expense[$account['income_account_id']] : 0;

        if ($month_opening_balance == 0 && $month_income == 0 && $month_expense == 0) {
            continue;
        }

        $report[] = [
            'income_account_id' => $account['income_account_id'],
            'income_account_name' => $account['income_account_name'],
            'month_opening_balance' => $month_opening_balance,
            'month_income' => $month_income,
            'month_expense' => $month_expense,
        ];
        }

        return $report;
    }


    function system_opening_bank_balance($office_ids, array $project_ids = [], $office_bank_ids = [])
    {

        $this->read_db->select_sum('opening_bank_balance_amount');
        $this->read_db->join('system_opening_balance', 'system_opening_balance.system_opening_balance_id=opening_bank_balance.fk_system_opening_balance_id');
        $this->read_db->join('office_bank', 'office_bank.office_bank_id=opening_bank_balance.fk_office_bank_id');
        $this->read_db->where_in('system_opening_balance.fk_office_id', $office_ids);

        if (!empty($project_ids)) {
            $this->read_db->where_in('project_allocation.fk_project_id', $project_ids);
            $this->read_db->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_office_bank_id=office_bank.office_bank_id');
            $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=office_bank_project_allocation.fk_project_allocation_id');
        }

        if (!empty($office_bank_ids)) {
            $this->read_db->where_in('opening_bank_balance.fk_office_bank_id', $office_bank_ids);
        }

        $opening_bank_balance_obj = $this->read_db->get('opening_bank_balance');

        $opening_bank_balance = $opening_bank_balance_obj->num_rows() > 0 ? $opening_bank_balance_obj->row()->opening_bank_balance_amount : 0;
        
        // log_message('error', json_encode($opening_bank_balance));

        return $opening_bank_balance;
    }

    function system_opening_cash_balance($office_ids, $project_ids = [], $office_bank_ids = [], $office_cash_id = 0)
    {
        $balance = 0;

        $this->read_db->select_sum('opening_cash_balance_amount');
        $this->read_db->join('system_opening_balance', 'system_opening_balance.system_opening_balance_id=opening_cash_balance.fk_system_opening_balance_id');
        $this->read_db->where_in('system_opening_balance.fk_office_id', $office_ids);

        if (count($project_ids) > 0) {
            $this->read_db->where_in('project_allocation.fk_project_id', $project_ids);
            $this->read_db->join('office_bank', 'office_bank.office_bank_id=opening_cash_balance.fk_office_bank_id');
            $this->read_db->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_office_bank_id=office_bank.office_bank_id');
            $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=office_bank_project_allocation.fk_project_allocation_id');
        }

        if (!empty($office_bank_ids)) {
            $this->read_db->where_in('opening_cash_balance.fk_office_bank_id', $office_bank_ids);
        }

        if($office_cash_id > 0){
            $this->read_db->where(array('opening_cash_balance.fk_office_cash_id' => $office_cash_id));
        }

        $opening_cash_balance_obj = $this->read_db->get('opening_cash_balance');

        if ($opening_cash_balance_obj->num_rows() > 0) {
            $balance = $opening_cash_balance_obj->row()->opening_cash_balance_amount;
        }

        return $balance;
    }

    function get_office_bank_project_allocation($office_bank_ids)
    {

        if (!empty($office_bank_ids)) {
            $this->read_db->select(array('fk_project_allocation_id'));
            $this->read_db->where_in('fk_office_bank_id', $office_bank_ids);
            $result =  $this->read_db->get('office_bank_project_allocation')->result_array();

            return array_column($result, 'fk_project_allocation_id');
        } else {
            return [];
        }
    }

    function cash_transactions_to_date($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [], $office_cash_id = 0, $retrieve_only_max_approved = true)
    {
        $cash_transactions_to_date = [];
    
        $max_approval_status_ids = $this->general_model->get_max_approval_status_id('voucher', $office_ids);

        $start_of_reporting_month = date('Y-m-01', strtotime($reporting_month));

        if (!empty($office_bank_ids)) {
            $this->read_db->where_in('fk_office_bank_id', $office_bank_ids);
        }

        if($office_cash_id > 0){
            $this->read_db->where(array('fk_office_cash_id' => $office_cash_id));
        }

        $this->read_db->where(array('voucher_month <=' => $start_of_reporting_month));

        $this->read_db->select(array('voucher_type_account_code','voucher_type_effect_code', 'amount'));
        $this->read_db->select_sum('amount');

        $this->read_db->where_in('fk_office_id', $office_ids);

        if($retrieve_only_max_approved){
            $this->read_db->where_in('fk_status_id', $max_approval_status_ids);
        }

        $this->read_db->group_by(array('voucher_type_account_code','voucher_type_effect_code','fk_office_bank_id'));

        $cash_transactions_to_date_obj = $this->read_db->get('monthly_sum_transactions_by_account_effect');

        if ($cash_transactions_to_date_obj->num_rows() > 0) {
            $cash_transactions_to_date_arr = $cash_transactions_to_date_obj->result_array(); 

            $cash_transactions_to_date['bank']['income'] = 0;
            $cash_transactions_to_date['bank']['expense'] = 0;
            $cash_transactions_to_date['cash']['income'] = 0;
            $cash_transactions_to_date['cash']['expense'] = 0;

            foreach($cash_transactions_to_date_arr as $row){

                if(($row['voucher_type_account_code'] == 'bank' && $row['voucher_type_effect_code'] == 'income') || ($row['voucher_type_account_code'] == 'cash' && $row['voucher_type_effect_code'] == 'cash_contra')){
                    $cash_transactions_to_date['bank']['income'] += $row['amount'];
                }
                
                if(($row['voucher_type_account_code'] == 'bank' && $row['voucher_type_effect_code'] == 'expense') || ($row['voucher_type_account_code'] == 'bank' && $row['voucher_type_effect_code'] == 'bank_contra')){
                    $cash_transactions_to_date['bank']['expense'] += $row['amount'];
                }
                
                if(($row['voucher_type_account_code'] == 'cash' && $row['voucher_type_effect_code'] == 'income')  ||($row['voucher_type_account_code'] == 'bank' && $row['voucher_type_effect_code'] == 'bank_contra')){
                    $cash_transactions_to_date['cash']['income'] += $row['amount'];

                    if($office_cash_id > 0){
                        $cash_transactions_to_date['cash']['income'] = $cash_transactions_to_date['cash']['income'] + $this->income_from_other_boxes($office_cash_id, $start_of_reporting_month);
                    }
                }
                
                if(($row['voucher_type_account_code'] == 'cash' && $row['voucher_type_effect_code'] == 'expense') || ($row['voucher_type_account_code'] == 'cash' && $row['voucher_type_effect_code'] == 'cash_to_cash_contra') || ($row['voucher_type_account_code'] == 'cash' && $row['voucher_type_effect_code'] == 'cash_contra')){
                    $cash_transactions_to_date['cash']['expense'] += $row['amount'];
                }
                
            }
        }

        return $cash_transactions_to_date;
    }

    function income_from_other_boxes($office_cash_id, $reporting_month){

        $this->read_db->select(array('amount'));
        $this->read_db->where(array('source_office_cash_id' => $office_cash_id,'voucher_month <= ' => date('Y-m-t',strtotime($reporting_month)), 'voucher_month >= ' => date('Y-m-01',strtotime($reporting_month))));
        $sumObj = $this->read_db->get('month_cash_recipient_sum_amount');

        $amount = 0;

        if($sumObj->num_rows() > 0){
            $amount = $sumObj->row()->amount;
        }

        return $amount;
    }


    // function cash_transactions_to_date($office_ids, $reporting_month, $transaction_type, $voucher_type_account, $project_ids = [], $office_bank_ids = [], $office_cash_id = 0, $retrieve_only_max_approved = true)
    // {
    //     // bank_income = voucher of voucher_type_effect_code == income or cash_contra and voucher_type_account_code == bank 
    //     // bank_expense = voucher of voucher_type_effect_code == expense or bank_contra and voucher_type_account_code == bank 
    //     // cash_income = voucher of voucher_type_effect_code == income or bank_contra and voucher_type_account_code == cash 
    //     // cash_expense = voucher of voucher_type_effect_code == expense or cash_contra and voucher_type_account_code == cash 

    //     $max_approval_status_ids = $this->general_model->get_max_approval_status_id('voucher');

    //     $voucher_detail_total_cost = 0;
    //     $end_of_reporting_month = date('Y-m-t', strtotime($reporting_month));

    //     if (!empty($office_bank_ids)) {

    //         $get_office_bank_project_allocation = !empty($project_ids) ? $project_ids : $this->get_office_bank_project_allocation($office_bank_ids);

    //         $this->read_db->group_start();
    //         $this->read_db->where_in('voucher.fk_office_bank_id', $office_bank_ids);
    //         $this->read_db->where_in('voucher_detail.fk_project_allocation_id', $get_office_bank_project_allocation);
    //         $this->read_db->group_end();
    //     }

    //     if (count($project_ids) > 0) {
    //         $this->read_db->where_in('fk_project_id', $project_ids);
    //         $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');
    //     }

    //     $this->read_db->where(array('voucher_date<=' => $end_of_reporting_month));

    //     //$cond_string = "(voucher_type_account_code = '".$voucher_type_account."' AND  voucher_type_effect_code = '".$transaction_type."') OR (voucher_type_account_code = '".$voucher_type_account."' AND voucher_type_effect_code = 'contra' )";

    //     if ($voucher_type_account == 'bank' && $transaction_type == 'income') {
    //         $cond_string = "((voucher_type_account_code = 'bank' AND  voucher_type_effect_code = '" . $transaction_type . "') OR (voucher_type_account_code = 'cash' AND  voucher_type_effect_code = 'cash_contra'))";
    //         $this->read_db->where($cond_string);
    //     } elseif ($voucher_type_account == 'bank' && $transaction_type == 'expense') {
    //         $cond_string = "((voucher_type_account_code = 'bank' AND  voucher_type_effect_code = '" . $transaction_type . "') OR (voucher_type_account_code = 'bank' AND  voucher_type_effect_code = 'bank_contra'))";
    //         $this->read_db->where($cond_string);
    //     } elseif ($voucher_type_account == 'cash' && $transaction_type == 'income') {
    //         $cond_string = "((voucher_type_account_code = 'cash' AND  voucher_type_effect_code = '" . $transaction_type . "') OR (voucher_type_account_code = 'bank' AND  voucher_type_effect_code = 'bank_contra'))";
    //         $this->read_db->where($cond_string);
    //     } elseif ($voucher_type_account == 'cash' && $transaction_type == 'expense') {
    //         $cond_string = "((voucher_type_account_code = 'cash' AND  voucher_type_effect_code = '" . $transaction_type . "') OR (voucher_type_account_code = 'cash' AND  voucher_type_effect_code = 'cash_contra'))";
    //         $this->read_db->where($cond_string);
    //     }



    //     $this->read_db->select_sum('voucher_detail_total_cost');
    //     //Where clause for a fully approved voucher
    //    // $voucher_max_approval_id = $this->general_model->get_max_approval_status_id('voucher')[0];

    //     //$this->read_db->where(['voucher.fk_status_id'=>$voucher_max_approval_id]);

    //     $this->read_db->where_in('voucher.fk_office_id', $office_ids);
    //     $this->read_db->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
    //     $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
    //     $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
    //     $this->read_db->join('voucher_type_account', 'voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');

    //     //$this->read_db->where(['voucher.fk_status_id'=>  $max_approval_status_ids[0]]);
    //     if($retrieve_only_max_approved){
    //         $this->read_db->where_in('voucher.fk_status_id', $max_approval_status_ids);
    //     }

    //     if($office_cash_id > 0){
    //         $this->read_db->where(array('voucher.fk_office_cash_id' => $office_cash_id));
    //     }

    //     $voucher_detail_total_cost_obj = $this->read_db->get('voucher_detail');

    //     if ($voucher_detail_total_cost_obj->num_rows() > 0) {
    //         $voucher_detail_total_cost = $voucher_detail_total_cost_obj->row()->voucher_detail_total_cost;
    //     }

    //     return $voucher_detail_total_cost == null ? 0 : $voucher_detail_total_cost;
    // }

   
    function get_month_active_projects($office_ids, $reporting_month, $show_active_only = false)
    {

        $date_condition_string = "(project_end_date >= '" . $reporting_month . "' OR  project_allocation_extended_end_date >= '" . $reporting_month . "')";

        $this->read_db->select(array('project_id', 'project_name'));

        if ($show_active_only) {
            $this->read_db->where($date_condition_string);
        }

        $this->read_db->where_in('fk_office_id', $office_ids);
        $this->read_db->join('project_allocation', 'project_allocation.fk_project_id=project.project_id');
        $projects = $this->read_db->get('project')->result_array();

        return $projects;
    }


    function month_expense_by_expense_account($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
    {

        $max_approval_status_ids = $this->general_model->get_max_approval_status_id('voucher', $office_ids);

        $start_date_of_reporting_month = date('Y-m-01', strtotime($reporting_month));
        $end_date_of_reporting_month = date('Y-m-t', strtotime($reporting_month));
        $get_office_bank_project_allocation = $this->get_office_bank_project_allocation($office_bank_ids);

        $this->read_db->select_sum('voucher_detail_total_cost');
        $this->read_db->select(array('income_account_id', 'expense_account_id'));
        $this->read_db->group_by('expense_account_id');
        $this->read_db->where_in('voucher.fk_office_id', $office_ids);
        $this->read_db->where(array(
            'voucher_type_effect_code' => 'expense', 'voucher_date>=' => $start_date_of_reporting_month,
            'voucher_date<=' => $end_date_of_reporting_month
        ));

        $this->read_db->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
        $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $this->read_db->join('expense_account', 'expense_account.expense_account_id=voucher_detail.fk_expense_account_id');
        $this->read_db->join('income_account', 'income_account.income_account_id=expense_account.fk_income_account_id');

        if (count($project_ids) > 0) {
            $this->read_db->where_in('fk_project_id', $project_ids);
            $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');
        }

        if (!empty($office_bank_ids)) {
            $this->read_db->group_start();
            $this->read_db->where_in('voucher.fk_office_bank_id', $office_bank_ids);
            $this->read_db->where_in('voucher_detail.fk_project_allocation_id', $get_office_bank_project_allocation);
            $this->read_db->group_end();
        }

        $this->read_db->where_in('voucher.fk_status_id', $max_approval_status_ids);

        $result = $this->read_db->get('voucher_detail');

        $order_array = [];

        if ($result->num_rows() > 0) {
            $rows = $result->result_array();

            foreach ($rows as $record) {
                $order_array[$record['income_account_id']][$record['expense_account_id']] = $record['voucher_detail_total_cost'];
            }
        }

        return $order_array;
    }

    function expense_to_date_by_expense_account($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
    {

        $this->load->model('custom_financial_year_model');

        // $budget_by_office_current_transaction_date = $this->budget_model->get_a_budget_by_office_current_transaction_date($office_ids[0]);
        $custom_financial_year = $this->custom_financial_year_model->get_default_custom_financial_year_id_by_office($office_ids[0], true);

        // $max_approval_status_ids = $this->general_model->get_max_approval_status_id('voucher');
        $max_approval_status_ids = $this->general_model->get_max_approval_status_id('voucher', $office_ids);
        
        $fy_start_date = fy_start_date($reporting_month, $custom_financial_year);
        $end_date_of_reporting_month = date('Y-m-t', strtotime($reporting_month));
        $get_office_bank_project_allocation = $this->get_office_bank_project_allocation($office_bank_ids);

        $this->read_db->select_sum('voucher_detail_total_cost');
        $this->read_db->select(array('income_account_id', 'expense_account_id'));
        $this->read_db->group_by('expense_account_id');
        $this->read_db->where_in('voucher.fk_office_id', $office_ids);
        $this->read_db->where(array(
            'voucher_type_effect_code' => 'expense', 'voucher_date>=' => $fy_start_date,
            'voucher_date<=' => $end_date_of_reporting_month
        ));

        $this->read_db->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
        $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $this->read_db->join('expense_account', 'expense_account.expense_account_id=voucher_detail.fk_expense_account_id');
        $this->read_db->join('income_account', 'income_account.income_account_id=expense_account.fk_income_account_id');

        if (count($project_ids) > 0) {
            $this->read_db->where_in('fk_project_id', $project_ids);
            $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');
        }

        if (!empty($office_bank_ids)) {
            $this->read_db->group_start();
            $this->read_db->where_in('voucher.fk_office_bank_id', $office_bank_ids);
            $this->read_db->where_in('voucher_detail.fk_project_allocation_id', $get_office_bank_project_allocation);
            $this->read_db->group_end();
        }

        $this->read_db->where_in('voucher.fk_status_id', $max_approval_status_ids);

        $result = $this->read_db->get('voucher_detail');

        $order_array = [];

        if ($result->num_rows() > 0) {
            $rows = $result->result_array();

            foreach ($rows as $record) {
                $order_array[$record['income_account_id']][$record['expense_account_id']] = $record['voucher_detail_total_cost'];
            }
        }

        return $order_array;
    }

    //   function list_of_month_order($reporting_month){
    //       return [1,2];
    //   }

    public function get_budget_tag_based_on_month($reporting_month, $office_ids = [])
    {

        $month_number = date('n', strtotime($reporting_month));

        $this->read_db->select(array('budget_tag_id', 'fk_month_id'));
        $this->read_db->order_by('budget_tag_level ASC');
        $this->read_db->join('account_system', 'account_system.account_system_id=budget_tag.fk_account_system_id');
        $this->read_db->join('office', 'office.fk_account_system_id=account_system.account_system_id');
        $this->read_db->where_in('office_id', $office_ids);
        $budget_tags_start_month = $this->read_db->get('budget_tag')->result_array();

        $budget_tag_id_array = array_column($budget_tags_start_month, 'budget_tag_id');
        $budget_tag_based_on_month_array = array_column($budget_tags_start_month, 'fk_month_id');

        $budget_tag_id_based_on_month_values_array = array_combine($budget_tag_based_on_month_array, $budget_tag_id_array);

        ksort($budget_tag_id_based_on_month_values_array);

        $budget_tag_id = 0;

        if (array_key_exists($month_number, $budget_tag_id_based_on_month_values_array)) {
            $budget_tag_id = $budget_tag_id_based_on_month_values_array[$month_number];
        } else {
            $original_budget_tag_id = $budget_tag_id;
            foreach ($budget_tag_id_based_on_month_values_array as $_month_number => $_budget_tag_id) {
                if ($month_number > $_month_number) {
                    $original_budget_tag_id = $_budget_tag_id;
                } else {
                    break;
                }
            }

            $budget_tag_id = $original_budget_tag_id;
        }

        return $budget_tag_id;
    }

    //   function get_expense_account_comment($office_ids,$reporting_month){

    //         $variance_comments_array = [];

    //         $this->read_db->select(array('fk_income_account_id as income_account_id','fk_expense_account_id as expense_account_id','variance_comment_text'));
    //         $this->read_db->where_in('budget.fk_office_id',$office_ids);
    //         $this->read_db->join('budget','budget.budget_id=variance_comment.fk_budget_id');
    //         $this->read_db->join('expense_account','expense_account.expense_account_id=variance_comment.fk_expense_account_id');
    //         $variance_comment_obj = $this->read_db->get('variance_comment');

    //         if($variance_comment_obj->num_rows() > 0){
    //             $variance_comments = $variance_comment_obj->result_array();

    //             foreach($variance_comments as $variance_comment){
    //                 $variance_comments_array[$variance_comment['income_account_id']][$variance_comment['expense_account_id']] = $variance_comment['variance_comment_text'];
    //             }
    //         }

    //         return $variance_comments_array;
    //   }

    function budget_to_date_by_expense_account($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = []){

        $max_approval_status_ids = $this->general_model->get_max_approval_status_id('budget_item', $office_ids);

        $budget_ids = [];

        $this->read_db->select(array('fk_budget_id'));
        $this->read_db->where_in('fk_office_id', $office_ids);
        $this->read_db->where(array('financial_report_month' => date('Y-m-01',strtotime($reporting_month))));
        $financial_report_obj = $this->read_db->get('financial_report');

        if($financial_report_obj->num_rows() > 0){
            $budget_ids = array_column($financial_report_obj->result_array(),'fk_budget_id');
        }

        $month_number = date('m', strtotime($reporting_month));
        $custom_financial_year = $this->custom_financial_year_model->get_default_custom_financial_year_id_by_office($office_ids[0], true);
        $month_list = year_month_order($custom_financial_year);

        $listed_months = [];

        for($i = 0; $i < count($month_list); $i++){
            $listed_months[$i] = $month_list[$i];
            if($month_list[$i] == $month_number){
                break;
            }
        }

        $get_office_bank_project_allocation = $this->get_office_bank_project_allocation($office_bank_ids);

        $this->read_db->select_sum('budget_item_detail_amount');
        $this->read_db->select(array(
            'income_account.income_account_id as income_account_id',
            'expense_account.expense_account_id as expense_account_id'
        ));

        $this->read_db->group_by('expense_account.expense_account_id');
        $this->read_db->where_in('budget.fk_office_id', $office_ids);

        // $this->read_db->where(array('month_order<=' => $month_order));
        $this->read_db->where_in('month_id',  $listed_months);
        
        $this->read_db->where_in('budget_id', $budget_ids);

        $this->read_db->join('budget_item', 'budget_item.budget_item_id=budget_item_detail.fk_budget_item_id');
        $this->read_db->join('budget', 'budget.budget_id=budget_item.fk_budget_id');
        $this->read_db->join('month', 'month.month_id=budget_item_detail.fk_month_id');
        $this->read_db->join('expense_account', 'expense_account.expense_account_id=budget_item.fk_expense_account_id');
        $this->read_db->join('income_account', 'income_account.income_account_id=expense_account.fk_income_account_id');

        if (count($project_ids) > 0) {
            $this->read_db->where_in('project_allocation.fk_project_id', $project_ids);
            $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=budget_item.fk_project_allocation_id');
        }

        if (!empty($office_bank_ids)) {
            $this->read_db->where_in('budget_item.fk_project_allocation_id', $get_office_bank_project_allocation);
        }


        $this->read_db->where_in('budget_item.fk_status_id', $max_approval_status_ids);

        $result = $this->read_db->get('budget_item_detail');

        $order_array = [];

        if ($result->num_rows() > 0) {
            $rows = $result->result_array();

            foreach ($rows as $record) {
                $order_array[$record['income_account_id']][$record['expense_account_id']] = $record['budget_item_detail_amount'];
            }
        }
        
        return $order_array;
        
    }

    function bugdet_to_date_by_expense_account($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
    {

        $max_approval_status_ids = $this->general_model->get_max_approval_status_id('budget_item', $office_ids);

        $financial_year = get_fy($reporting_month);
        $month_number = date('m', strtotime($reporting_month));
        $month_order = $this->read_db->get_where('month', array('month_number' => $month_number))->row()->month_order;
        //$list_of_month_order = $this->list_of_month_order($reporting_month);
        $get_budget_tag_based_on_month = $this->get_budget_tag_based_on_month($reporting_month, $office_ids);

        $get_office_bank_project_allocation = $this->get_office_bank_project_allocation($office_bank_ids);

        //echo json_encode($get_budget_tag_based_on_month);exit;


        $this->read_db->select_sum('budget_item_detail_amount');
        $this->read_db->select(array(
            'income_account.income_account_id as income_account_id',
            'expense_account.expense_account_id as expense_account_id'
        ));

        $this->read_db->group_by('expense_account.expense_account_id');
        $this->read_db->where_in('budget.fk_office_id', $office_ids);

        $this->read_db->where(array('month_order<=' => $month_order));

        $this->read_db->where(array('fk_budget_tag_id' => $get_budget_tag_based_on_month));
        // log_message('error', json_encode($month_order));
        $this->read_db->where(array('budget_year' => $financial_year));

        $this->read_db->join('budget_item', 'budget_item.budget_item_id=budget_item_detail.fk_budget_item_id');
        $this->read_db->join('budget', 'budget.budget_id=budget_item.fk_budget_id');
        $this->read_db->join('month', 'month.month_id=budget_item_detail.fk_month_id');
        $this->read_db->join('expense_account', 'expense_account.expense_account_id=budget_item.fk_expense_account_id');
        $this->read_db->join('income_account', 'income_account.income_account_id=expense_account.fk_income_account_id');

        if (count($project_ids) > 0) {
            $this->read_db->where_in('project_allocation.fk_project_id', $project_ids);
            $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=budget_item.fk_project_allocation_id');
        }

        if (!empty($office_bank_ids)) {
            $this->read_db->where_in('budget_item.fk_project_allocation_id', $get_office_bank_project_allocation);
        }


        $this->read_db->where_in('budget_item.fk_status_id', $max_approval_status_ids);

        $result = $this->read_db->get('budget_item_detail');

        $order_array = [];

        if ($result->num_rows() > 0) {
            $rows = $result->result_array();

            foreach ($rows as $record) {
                $order_array[$record['income_account_id']][$record['expense_account_id']] = $record['budget_item_detail_amount'];
            }
        }

        // log_message('error', json_encode($order_array));
        
        return $order_array;
    }

    function list_oustanding_cheques_and_deposits($office_ids, $reporting_month, $transaction_type, $contra_type, $voucher_type_account_code, $project_ids = [], $office_bank_ids = [])
    {
        $max_voucher_approval_ids = $this->general_model->get_max_approval_status_id('voucher', $office_ids);

        if (count($project_ids) > 0) {
            $this->read_db->select(array('office_bank.office_bank_id'));
            $this->read_db->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_office_bank_id=office_bank.office_bank_id');
            $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=office_bank_project_allocation.fk_project_allocation_id');
            $this->dread_db->where_in('fk_project_id', $project_ids);
        }

        if (!empty($office_bank_ids)) {
            $this->read_db->where_in('voucher.fk_office_bank_id', $office_bank_ids);
        }


        $list_oustanding_cheques_and_deposit = [];

        $this->read_db->select_sum('voucher_detail_total_cost');
        $this->read_db->select(array(
            'voucher_id', 'voucher_number', 'voucher_cheque_number','voucher_vendor',
            'voucher_description', 'voucher_cleared', 'office_code', 'office_name', 'voucher_date',
            'voucher_cleared', 'fk_office_bank_id', 'office_bank_name'
        ));

        $this->read_db->group_by(array('voucher_id'));


        $this->read_db->where_in('voucher.fk_office_id', $office_ids);

        if ($transaction_type == 'expense') {
            $this->read_db->where_in('voucher_type_effect_code', [$transaction_type, $contra_type]); // contra, expense , income
            $this->read_db->where(array('voucher_type_account_code' => $voucher_type_account_code)); // bank, cash
        } elseif (($contra_type == 'cash_contra' || $contra_type = 'bank_contra') && $transaction_type == 'income') {
            $this->read_db->where_in('voucher_type_effect_code', [$transaction_type, $contra_type]);
        } else {
            $this->read_db->where_in('voucher_type_effect_code', [$transaction_type, $contra_type]); // contra, expense , income
            $this->read_db->where(array('voucher_type_account_code' => $voucher_type_account_code)); // bank, cash
        }


        $this->read_db->group_start();
        $this->read_db->where(array(
            'voucher_cleared' => 0,
            'voucher_date <=' => date('Y-m-t', strtotime($reporting_month))
            //'voucher_date <='=>date('Y-m-t',strtotime($reporting_month))    
        ));
        $this->read_db->or_group_start();
        $this->read_db->where(array(
            'voucher_cleared' => 1,
            'voucher_date <=' => date('Y-m-t', strtotime($reporting_month)),
            'voucher_cleared_month > ' => date('Y-m-t', strtotime($reporting_month))
        ));
        $this->read_db->group_end();
        $this->read_db->group_end();

        $this->read_db->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
        $this->read_db->join('office', 'office.office_id=voucher.fk_office_id');
        $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $this->read_db->join('voucher_type_account', 'voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
        $this->read_db->join('office_bank', 'office_bank.office_bank_id=voucher.fk_office_bank_id');

        $this->read_db->where_in('voucher.fk_status_id', $max_voucher_approval_ids);

        $list_oustanding_cheques_and_deposit = $this->read_db->get('voucher_detail')->result_array();

        if ($transaction_type == 'expense') {
            $cleared_and_uncleared_opening_outstanding_cheques = $this->get_uncleared_and_cleared_opening_outstanding_cheques($office_ids, $reporting_month, 'uncleared', $office_bank_ids);
            $list_oustanding_cheques_and_deposit = array_merge($list_oustanding_cheques_and_deposit, $cleared_and_uncleared_opening_outstanding_cheques);
        } else {
            $cleared_and_uncleared_deposit_in_transit = $this->get_uncleared_and_cleared_deposit_in_transit($office_ids, $reporting_month, $office_bank_ids, 'uncleared');
            $list_oustanding_cheques_and_deposit = array_merge($list_oustanding_cheques_and_deposit, $cleared_and_uncleared_deposit_in_transit);
        }


        return $list_oustanding_cheques_and_deposit;
    }

    private function get_uncleared_and_cleared_deposit_in_transit($office_ids, $reporting_month, $office_bank_ids, $state = 'uncleared')
    {
        $opening_deposit_in_transits = [];

        $this->read_db->select(
            [
                'opening_deposit_transit_amount as voucher_detail_total_cost',
                //'opening_outstanding_cheque_number as voucher_cheque_number',
                'opening_deposit_transit_description as voucher_description',
                'opening_deposit_transit_date as voucher_date',
                'fk_office_bank_id',
                'opening_deposit_transit_is_cleared as voucher_cleared',
                'opening_deposit_transit_cleared_date as voucher_cleared_month',
                'office_bank_name',
                'opening_deposit_transit_id'
            ]
        );

        $this->read_db->where_in('system_opening_balance.fk_office_id', $office_ids);

        if ($state == 'uncleared') {
            $this->read_db->group_start();
            $this->read_db->where(array('opening_deposit_transit_is_cleared' => 0));

            $this->read_db->or_group_start();
            $this->read_db->where(array(
                'opening_deposit_transit_is_cleared' => 1,
                'opening_deposit_transit_cleared_date > ' => date('Y-m-t', strtotime($reporting_month))
            ));
            $this->read_db->group_end();
            $this->read_db->group_end();
        } else {

            //$this->read_db->group_start();
            //$this->read_db->where(array('opening_outstanding_cheque_is_cleared'=>1));

            //$this->read_db->or_group_start();
            $this->read_db->where(array(
                'opening_deposit_transit_is_cleared' => 1,
                'opening_deposit_transit_cleared_date ' => date('Y-m-t', strtotime($reporting_month))
            ));
            //$this->read_db->group_end();
            // $this->read_db->group_end();

        }

        if (!empty($office_bank_ids)) {
            $this->read_db->where_in('opening_deposit_transit.fk_office_bank_id', $office_bank_ids);
        }

        $this->read_db->join('system_opening_balance', 'system_opening_balance.system_opening_balance_id=opening_deposit_transit.fk_system_opening_balance_id');
        $this->read_db->join('office_bank', 'office_bank.office_bank_id=opening_deposit_transit.fk_office_bank_id');
        $opening_deposit_in_transits_obj = $this->read_db->get('opening_deposit_transit');

        if ($opening_deposit_in_transits_obj->num_rows() > 0) {
            $opening_deposit_in_transits = $opening_deposit_in_transits_obj->result_array();
        }

        $modified_opening_deposit_in_transits = [];

        foreach ($opening_deposit_in_transits as $opening_deposit_in_transit) {
            $modified_opening_deposit_in_transits[] = array_merge($opening_deposit_in_transit, ['voucher_id' => 0]);
        }

        return $modified_opening_deposit_in_transits;
    }

    function get_uncleared_and_cleared_opening_outstanding_cheques($office_ids, $reporting_month, $state = 'uncleared', $office_bank_ids = [])
    {
        $opening_outstanding_cheques = [];

        $this->read_db->select(
            [
                'opening_outstanding_cheque_amount as voucher_detail_total_cost',
                'opening_outstanding_cheque_number as voucher_cheque_number',
                'opening_outstanding_cheque_description as voucher_description',
                'opening_outstanding_cheque_bounced_flag as bounce_flag',
                'opening_outstanding_cheque_date as voucher_date',
                'fk_office_bank_id',
                'opening_outstanding_cheque_is_cleared as voucher_cleared',
                'opening_outstanding_cheque_cleared_date as voucher_cleared_month',
                'office_bank_name',
                'opening_outstanding_cheque_id'
            ]
        );

        //$this->read_db->where(array('opening_outstanding_cheque_bounce_flag '=>0));

        $this->read_db->where_in('system_opening_balance.fk_office_id', $office_ids);

        if ($state == 'uncleared') {
            $this->read_db->group_start();
            $this->read_db->where(array('opening_outstanding_cheque_is_cleared' => 0));

        

            $this->read_db->or_group_start();
            $this->read_db->where(array(
                'opening_outstanding_cheque_is_cleared' => 1,
                'opening_outstanding_cheque_cleared_date > ' => date('Y-m-t', strtotime($reporting_month))
            ));
            $this->read_db->group_end();
            $this->read_db->group_end();
        } else {

            //$this->read_db->group_start();
            //$this->read_db->where(array('opening_outstanding_cheque_is_cleared'=>1));

            //$this->read_db->or_group_start();
            $this->read_db->where(array(
                'opening_outstanding_cheque_is_cleared' => 1,
                'opening_outstanding_cheque_cleared_date ' => date('Y-m-t', strtotime($reporting_month))
            ));
            //$this->read_db->group_end();
            // $this->read_db->group_end();

        }

        if (!empty($office_bank_ids)) {
            $this->read_db->where_in('opening_outstanding_cheque.fk_office_bank_id', $office_bank_ids);
        }

        $this->read_db->join('system_opening_balance', 'system_opening_balance.system_opening_balance_id=opening_outstanding_cheque.fk_system_opening_balance_id');
        $this->read_db->join('office_bank', 'office_bank.office_bank_id=opening_outstanding_cheque.fk_office_bank_id');
        $opening_outstanding_cheque_obj = $this->read_db->get('opening_outstanding_cheque');

       
        if ($opening_outstanding_cheque_obj->num_rows() > 0) {
            $opening_outstanding_cheques = $opening_outstanding_cheque_obj->result_array();
        }

        $modified_opening_outstanding_cheques = [];

        foreach ($opening_outstanding_cheques as $opening_outstanding_cheque) {

            //$opening_outstanding_cheque['voucher_detail_total_cost']=$opening_outstanding_cheque['voucher_detail_total_cost']<0 && $opening_outstanding_cheque['voucher_cleared']==1?0:$opening_outstanding_cheque['voucher_detail_total_cost'];
            
            $modified_opening_outstanding_cheques[] = array_merge($opening_outstanding_cheque, ['voucher_id' => 0]);

        }

        return $modified_opening_outstanding_cheques;
    }

    // Added by Onduso on 2/8/2022

    private function get_opening_oustanding_cheque($cheque_id)
    {

        $this->read_db->select(array('opening_outstanding_cheque_amount','opening_outstanding_cheque_id','opening_outstanding_cheque_bounced_flag','opening_outstanding_cheque_description','opening_outstanding_cheque_is_cleared','opening_outstanding_cheque_cleared_date','opening_outstanding_cheque_number'));
        $this->read_db->where(array('opening_outstanding_cheque_id' => $cheque_id));
        $bounced_chq_record = $this->read_db->get('opening_outstanding_cheque')->row_array();


        return $bounced_chq_record;
    }
    function get_openning_child_support_balance($office_bank_id)
    {

        $this->read_db->select(array('opening_fund_balance_id', 'opening_fund_balance_amount'));
        $this->read_db->where(array('fk_office_bank_id' => $office_bank_id, 'project_is_default' => 1));
        $this->read_db->join('project','project.project_id=opening_fund_balance.fk_project_id');
        return $this->read_db->get('opening_fund_balance')->row_array();
    }

    function get_openning_child_support_balance_test($office_bank_id)
    {

        $this->read_db->select(array('opening_fund_balance_id', 'opening_fund_balance_amount'));
        $this->read_db->where(array('fk_office_bank_id' => $office_bank_id));

        return $this->read_db->get('opening_fund_balance')->row_array();
    }

    function get_openning_bank_balance($office_bank_id)
    {

        $this->read_db->select(array('opening_bank_balance_id', 'opening_bank_balance_amount'));
        $this->read_db->where(array('fk_office_bank_id' => $office_bank_id));

        return $this->read_db->get('opening_bank_balance')->row_array();
    }

    function update_bank_support_funds_and_oustanding_cheque_opening_balances($office_bank_id, $cheque_id,$reporting_month, $bounce_chq)
    {


        //Query table opening_fund_balance to get the Child Support record especially opening fund and amount
        // $openning_funds_first_record_always_child_support = $this->get_openning_child_support_balance($office_bank_id);

        //Get the a given selected outstanding cheque and compute the the total amount for funds
        $bounced_chq_record = $this->get_opening_oustanding_cheque($cheque_id);

        // $opening_fund_balance_id = $openning_funds_first_record_always_child_support['opening_fund_balance_id'];

        // $opening_fund_balance_amount =$openning_funds_first_record_always_child_support['opening_fund_balance_amount'];

        // if($bounce_chq==1){
        //     $update_openning_child_support_funds=$opening_fund_balance_amount + $bounced_chq_record['opening_outstanding_cheque_amount'];
            
        // }else if($bounce_chq==0){
        //     $update_openning_child_support_funds=$opening_fund_balance_amount - $bounced_chq_record['opening_outstanding_cheque_amount'];
        // }

        $this->write_db->trans_begin();



        //Update the opening_fund_balance with the computed amount Openning Fund child support+ openning chq amount
        //origin amount child support=50992880.74
        //opening outsatnding chq 160,000 for TZ0114

        // $opening_fund_balance_data = array(
        //     'opening_fund_balance_amount' => $update_openning_child_support_funds
        // );

        // $this->write_db->where('opening_fund_balance_id', $opening_fund_balance_id);
        // $this->write_db->update('opening_fund_balance',  $opening_fund_balance_data);

        // //Get openning balance in the bank and update the openning bank balance

        // $openning_bank_balance = $this->get_openning_bank_balance($office_bank_id);

        // $updated_openning_bank_balance=0;

        // // $updated_openning_bank_balance = $bounce_chq==true? $openning_bank_balance['opening_bank_balance_amount'] + $bounced_chq_record['opening_outstanding_cheque_amount']:$openning_bank_balance['opening_bank_balance_amount'] - $bounced_chq_record['opening_outstanding_cheque_amount'];

        // if($bounce_chq==1){
        //     $updated_openning_bank_balance = $openning_bank_balance['opening_bank_balance_amount'] + $bounced_chq_record['opening_outstanding_cheque_amount'];

        // }elseif($bounce_chq==0){
        //     $updated_openning_bank_balance=$openning_bank_balance['opening_bank_balance_amount'] - $bounced_chq_record['opening_outstanding_cheque_amount'];
        // }
        // $opening_bank_balance_data = array(
        //     'opening_bank_balance_amount' => $updated_openning_bank_balance
        // );

        // $this->write_db->where('opening_bank_balance_id', $openning_bank_balance['opening_bank_balance_id']);
        // $this->write_db->update('opening_bank_balance',  $opening_bank_balance_data);

        //Update Openning outstanding balance
        
        $cheque_cleared=$bounced_chq_record['opening_outstanding_cheque_is_cleared']==1?0:1;
        $bounce_flag=$bounced_chq_record['opening_outstanding_cheque_bounced_flag']==1?0:1;       
        $cheque_cleared_date = $bounced_chq_record['opening_outstanding_cheque_cleared_date']== '0000-00-00' || $bounced_chq_record['opening_outstanding_cheque_cleared_date'] == NULL ? date('Y-m-t', strtotime($reporting_month)): NULL;

        $opening_oustanding_chq_balance_data = array(
            'opening_outstanding_cheque_is_cleared'=>$cheque_cleared,
            'opening_outstanding_cheque_cleared_date' => $cheque_cleared_date,
            'opening_outstanding_cheque_bounced_flag'=>$bounce_flag
        );

        $this->write_db->where('opening_outstanding_cheque_id', $cheque_id);
        $this->write_db->update('opening_outstanding_cheque',  $opening_oustanding_chq_balance_data);

        $this->write_db->trans_complete();

        if ($this->write_db->trans_status() == FALSE) {
            $this->write_db->trans_rollback();
             return false;
        } else {

            $this->write_db->trans_commit();
            return true;
        }
    }
    //End of Onduso Additon on 2/8/2022



    /**
     * list_cleared_effects + list_oustanding_cheques_and_deposits can be normalized
     */

    function list_cleared_effects($office_ids, $reporting_month, $transaction_type, $contra_type, $voucher_type_account_code, $project_ids = [], $office_bank_ids = [])
    {

        if (count($project_ids) > 0) {
            $this->read_db->select(array('office_bank.office_bank_id'));
            $this->read_db->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_office_bank_id=office_bank.office_bank_id');
            $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=office_bank_project_allocation.fk_project_allocation_id');
            $this->read_db->where_in('fk_project_id', $project_ids);
            $office_bank_ids = array_column($this->read_db->get('office_bank')->result_array(), 'office_bank_id');
        }

        if (!empty($office_bank_ids)) {
            $this->read_db->where_in('office_bank_id', $office_bank_ids);
        }


        $list_cleared_effects = [];

        //return 145890.00;
        //$cleared_condition = " `voucher_cleared` = 1 AND `voucher_cleared_month` = '".date('Y-m-t',strtotime($reporting_month))."' ";
        $this->read_db->select_sum('voucher_detail_total_cost');
        $this->read_db->where(array('voucher_type_is_hidden' => 0));
        $this->read_db->select(array(
            'voucher_id', 'voucher_number', 'voucher_cheque_number', 'voucher_description','voucher_vendor',
            'voucher_cleared', 'office_code', 'office_name', 'voucher_date', 'voucher_cleared',
            'office_bank_id', 'office_bank_name', 'voucher_is_reversed'
        ));
        $this->read_db->group_by('voucher_id');
        $this->read_db->where_in('voucher.fk_office_id', $office_ids);
        
        $this->read_db->where_in('voucher.fk_office_id', $office_ids);

    
        if ($voucher_type_account_code == 'bank' && $transaction_type == 'income') {
            $cond_string = "((voucher_type_account_code = 'bank' AND  voucher_type_effect_code = '" . $transaction_type . "') OR (voucher_type_account_code = 'cash' AND  voucher_type_effect_code = 'cash_contra'))";
            $this->read_db->where($cond_string);
        } elseif ($voucher_type_account_code == 'bank' && $transaction_type == 'expense') {
            $cond_string = "((voucher_type_account_code = 'bank' AND  voucher_type_effect_code = '" . $transaction_type . "') OR (voucher_type_account_code = 'bank' AND  voucher_type_effect_code = 'bank_contra'))";
            $this->read_db->where($cond_string);
        } elseif ($voucher_type_account_code == 'cash' && $transaction_type == 'income') {
            $cond_string = "((voucher_type_account_code = 'cash' AND  voucher_type_effect_code = '" . $transaction_type . "') OR (voucher_type_account_code = 'bank' AND  voucher_type_effect_code = 'bank_contra'))";
            $this->read_db->where($cond_string);
        } elseif ($voucher_type_account_code == 'cash' && $transaction_type == 'expense') {
            $cond_string = "((voucher_type_account_code = 'cash' AND  voucher_type_effect_code = '" . $transaction_type . "') OR (voucher_type_account_code = 'cash' AND  voucher_type_effect_code = 'cash_contra'))";
            $this->read_db->where($cond_string);
        }
        
        $this->read_db->where(array('voucher_cleared' => 1 , 'voucher_date<=' => date('Y-m-t', strtotime($reporting_month)), 'voucher_cleared_month' => date('Y-m-t', strtotime($reporting_month))));

        $this->read_db->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
        $this->read_db->join('office', 'office.office_id=voucher.fk_office_id');
        $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $this->read_db->join('voucher_type_account', 'voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
        $this->read_db->join('office_bank', 'office_bank.office_bank_id=voucher.fk_office_bank_id');

        if (count($project_ids) > 0) {
            $this->read_db->where_in('voucher.fk_office_bank_id', $office_bank_ids);
        }

        $list_cleared_effects = $this->read_db->get('voucher_detail')->result_array();

        if ($transaction_type == 'expense') {
            $list_cleared_effects = array_merge($list_cleared_effects, $this->get_uncleared_and_cleared_opening_outstanding_cheques($office_ids, $reporting_month, 'cleared', $office_bank_ids));
        } else {
            $list_cleared_effects = array_merge($list_cleared_effects, $this->get_uncleared_and_cleared_deposit_in_transit($office_ids, $reporting_month, $office_bank_ids, 'cleared'));
        }

        return $list_cleared_effects;
    }

    function check_if_financial_report_is_submitted($office_ids, $reporting_month)
    {
        // log_message('error', $reporting_month);
        $report_is_submitted = false;

        if (is_array($office_ids) && count($office_ids) == 1) {

            $financial_report_is_submitted_obj = $this->read_db->get_where(
                'financial_report',
                array(
                    'fk_office_id' => $office_ids[0],
                    'financial_report_month' => date('Y-m-01', strtotime($reporting_month))
                )
                );
            
            

            if ($financial_report_is_submitted_obj->num_rows() > 0) {

                if ($financial_report_is_submitted_obj->row()->financial_report_is_submitted) {
                    $report_is_submitted = true;
                }
                
            }
        }

        return $report_is_submitted;
    }


    // function _get_account_month_income_test($office_ids, $income_account_id, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    // {
    //     //echo $income_account_id;exit;
    //     $last_date_of_month = date('Y-m-t', strtotime($start_date_of_month));

    //     $month_income = 0;

    //     $this->read_db->select_sum('voucher_detail_total_cost');
    //     $this->read_db->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
    //     $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
    //     $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
    //     $this->read_db->group_by(array('fk_income_account_id'));
    //     $this->read_db->where_in('voucher.fk_office_id', $office_ids);

    //     $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');

    //     if (count($project_ids) > 0) {
    //         $this->read_db->where_in('project_allocation.fk_project_id', $project_ids);
    //     }

    //     if (count($office_bank_ids) > 0) {
    //         $this->read_db->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_project_allocation_id=project_allocation.project_allocation_id');
    //         $this->read_db->where_in('office_bank_project_allocation.fk_office_bank_id', $office_bank_ids);
    //     }

    //     $this->read_db->where(array(
    //         'voucher_type_effect_code' => 'income',
    //         'fk_income_account_id' => $income_account_id, 'voucher_date>=' => $start_date_of_month,
    //         'voucher_date<=' => $last_date_of_month
    //     ));

    //     $month_income_obj = $this->read_db->get_compiled_select('voucher_detail', false);

    //     // if($month_income_obj->num_rows() > 0){
    //     //     $month_income = $month_income_obj->row()->voucher_detail_total_cost;
    //     // }    

    //     //return $month_income;

    //     return $month_income_obj;
    // }

    function office_bank_has_more_than_one_financial_report(array $office_bank_ids)
    {

        // Check if a office has a financial report submitted
        $has_more_than_one_financial_report = true;

        if (!empty($office_bank_ids)) {
            $this->read_db->join('office', 'office.office_id=financial_report.fk_office_id');
            $this->read_db->join('office_bank', 'office_bank.fk_office_id=office.office_id');
            $this->read_db->where_in('office_bank.office_bank_id', $office_bank_ids);
            $this->read_db->limit(2);
            $has_more_than_one_financial_report_check = $this->read_db->get('financial_report')->num_rows();

            // log_message('error', json_encode($has_more_than_one_financial_report_check));

            $has_more_than_one_financial_report = $has_more_than_one_financial_report_check < 2 ? false : true; 
        }

        return $has_more_than_one_financial_report;
    }

    function count_of_submitted_financial_reports(array $office_bank_ids)
    {

        // Check if a office has a financial report submitted
        $count_financial_reports = 0;

        if (!empty($office_bank_ids)) {
            $this->read_db->join('office', 'office.office_id=financial_report.fk_office_id');
            $this->read_db->join('office_bank', 'office_bank.fk_office_id=office.office_id');
            $this->read_db->where_in('office_bank.office_bank_id', $office_bank_ids);
            $this->read_db->where(array('financial_report_is_submitted' => 1));
            $count_financial_reports = $this->read_db->get('financial_report')->num_rows();
        }

        return $count_financial_reports;
    }


    function post_approval_action_event($event_payload){
    
        //log_message('error', json_encode($event_payload));
        // Check if the status is a decline step
        $status_approval_direction = 0; // Zero mean the status is a reinstating status
    
        $this->read_db->select(array('status_approval_direction'));
        $this->read_db->join('approval_flow','approval_flow.approval_flow_id=status.fk_approval_flow_id');
        $this->read_db->join('approve_item','approve_item.approve_item_id=approval_flow.fk_approve_item_id');
        $this->read_db->where(array('approve_item_name' => $event_payload['item'], 'status_id' => $event_payload['post']['next_status'] ));
        $status_obj = $this->read_db->get('status');
    
        if($status_obj->num_rows() > 0){
          $status_approval_direction = $status_obj->row()->status_approval_direction;
        }
        
        // Perform actions here if the record is being declined
        if($status_approval_direction == -1){ // -1 mean that it is a decline status
            //log_message('error',"You are declining");
            
            // Decline subsequent submitted financial reports
            $this->decline_subsequent_financial_reports($event_payload['post']['item_id'], $event_payload['post']['next_status']);
        }
        
      }

      function decline_subsequent_financial_reports($financial_report_id, $decline_status){
        // $this->read_db->reset_query(); 
        $this->read_db->select(array('financial_report_month','fk_office_id'));
        $this->read_db->where(array('financial_report_id' => $financial_report_id));
        $financial_report_obj = $this->read_db->get('financial_report');

        if($financial_report_obj->num_rows() > 0){

            $financial_report = $financial_report_obj->row_array(); 

            // log_message('error', json_encode(['fk_office_id' => $financial_report['fk_office_id'], 
            // 'financial_report_month > ' => $financial_report['financial_report_month']]));

            // Check if we have subsequent submit reports and unsubmit them and reset their approval status to step 1
            $subsequent_mfr_data = [
                'fk_status_id' => $this->grants_model->initial_item_status('financial_report'),//$decline_status, // Immediate Decline status
                'financial_report_is_submitted' => 0,
            ];
            $this->write_db->where([
                'fk_office_id' => $financial_report['fk_office_id'], 
                'financial_report_month > ' => $financial_report['financial_report_month']
            ]);

            $this->write_db->where_not_in('fk_status_id', [$this->grants_model->initial_item_status('financial_report'), $decline_status]);

            $this->write_db->update('financial_report',$subsequent_mfr_data);



            $declined_mfr_data = [
                'financial_report_is_submitted' => 0,
            ];
            $this->write_db->where([
                'fk_office_id' => $financial_report['fk_office_id'], 
                'financial_report_month' => $financial_report['financial_report_month']
            ]);

            $this->write_db->update('financial_report',$declined_mfr_data);
            
        }
      }

    function fund_balance_report($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
      {
        
        // log_message('error', json_encode([
        //     'office_ids' => $office_ids,
        //     'start_date_of_month' => $start_date_of_month,
        //     'project_ids' => $project_ids,
        //     'office_bank_ids' => $office_bank_ids
        // ]));

        $income_accounts =  $this->income_accounts($office_ids, $project_ids, $office_bank_ids);
        //print_r($income_accounts);exit;
        $all_accounts_month_opening_balance = $this->month_income_opening_balance($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);
        $all_accounts_month_income = $this->month_income_account_receipts($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);
        $all_accounts_month_expense = $this->month_income_account_expenses($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);
    
        $report = array();

        // log_message('error', json_encode([
        //     'income_accounts' => $income_accounts,
        //     'all_accounts_month_opening_balance' => $all_accounts_month_opening_balance,
        //     'all_accounts_month_income' => $all_accounts_month_income,
        //     'all_accounts_month_expense' => $all_accounts_month_expense
        // ]));
    
        foreach ($income_accounts as $account) {
    
          $month_opening_balance = isset($all_accounts_month_opening_balance[$account['income_account_id']]) ? $all_accounts_month_opening_balance[$account['income_account_id']] : 0;
          $month_income = isset($all_accounts_month_income[$account['income_account_id']]) ? $all_accounts_month_income[$account['income_account_id']] : 0;
          $month_expense = isset($all_accounts_month_expense[$account['income_account_id']]) ? $all_accounts_month_expense[$account['income_account_id']] : 0;
    
          if ($month_opening_balance == 0 && $month_income == 0 && $month_expense == 0) {
            continue;
          }
    
          $report[$account['income_account_id']] = [
            'account_name' => $account['income_account_name'],
            'month_opening_balance' => $month_opening_balance,
            'month_income' => $month_income,
            'month_expense' => $month_expense,
          ];
        }

        // log_message('error', json_encode($report));
    
        //If the mfr has been submitted. Adjust the child support fund by taking away exact amount of bounced opening chqs This code was added during enhancement for cancelling opening outstanding chqs
    
        if ($this->check_if_financial_report_is_submitted($office_ids, $start_date_of_month) == true) {
    
          $sum_of_bounced_cheques=$this->get_total_sum_of_bounced_opening_cheques($office_ids, $start_date_of_month);
    
          $total_amount_bounced=$sum_of_bounced_cheques[0]['opening_outstanding_cheque_amount'];
          $bounced_date=$sum_of_bounced_cheques[0]['opening_outstanding_cheque_cleared_date'];
          $mfr_report_month= date('Y-m-t', strtotime($start_date_of_month));
          
          if($total_amount_bounced>0 &&  $bounced_date > $mfr_report_month ){
    
            $month_opening=$report[0]['month_opening_balance'];
          
            $report[0]['month_opening_balance']=$month_opening-$total_amount_bounced;
          }
         
        }
        
        return $report;
      }

      function get_fund_balance_by_account($office_id, $income_account_id,$reporting_month, $project_id = 0){

        // log_message('error', json_encode([
        //     'office_id' => $office_id, 
        //     'income_account_id' => $income_account_id,
        //     'reporting_month' => $reporting_month, 
        //     'project_allocation_id' => $project_allocation_id
        // ]));
        
        $null_balances = ['month_opening_balance' => 0, 'month_income' => 0, 'month_expense' => 0];

        $fund_balance_report = $this->fund_balance_report([$office_id],$reporting_month, [$project_id]);
        
        // log_message('error', json_encode($fund_balance_report));

        $income_account_balances = isset($fund_balance_report[$income_account_id]) ? $fund_balance_report[$income_account_id] : $null_balances;

        $income_account_month_opening_balance = $income_account_balances['month_opening_balance'];
        $income_account_month_sum_income = $income_account_balances['month_income'];
        $income_account_month_sum_expense = $income_account_balances['month_expense'];
        $income_account_month_closing_balance = $income_account_month_opening_balance + $income_account_month_sum_income - $income_account_month_sum_expense;

        return $income_account_month_closing_balance;
      }

      public function overdue_transit_deposit(){
        $list_of_overdue_transit_deposit = [];

        $this->read_db->where_in('office_id', array_column($this->session->hierarchy_offices, 'office_id'));
        $this->read_db->where(array('amount > ' => 0));
        $list_of_overdue_transit_deposit_obj = $this->read_db->get('overdue_transit_deposit');

        if($list_of_overdue_transit_deposit_obj->num_rows() > 0){
            $list_of_overdue_transit_deposit = $list_of_overdue_transit_deposit_obj->result_array();
        }

        return $list_of_overdue_transit_deposit;
      }

      public function stale_cheques(){
        $list_of_cheques = [];

        $this->read_db->where_in('office_id', array_column($this->session->hierarchy_offices, 'office_id'));
        $this->read_db->where(array('amount > ' => 0));
        $list_of_cheques_obj = $this->read_db->get('stale_cheques');

        if($list_of_cheques_obj->num_rows() > 0){
            $list_of_cheques = $list_of_cheques_obj->result_array();
        }

        return $list_of_cheques;
      }

      public function last_month_submitted_financial_reports(){
        $list_of_fcps = [];

        $this->read_db->where_in('office_id', array_column($this->session->hierarchy_offices, 'office_id'));
        $list_of_fcps_obj = $this->read_db->get('offices_missing_last_month_financial_report');

        if($list_of_fcps_obj->num_rows() > 0){
            $list_of_fcps = $list_of_fcps_obj->result_array();
        }

        return $list_of_fcps;
      }


public function create_financial_report($financial_report_date, $office_id)
  {
    // Check if MFR exists

    $initial_status = $this->grants->initial_item_status('financial_report');

    $financial_report_date = date('Y-m-01', strtotime($financial_report_date));

    // Check if a journal for the same month and FCP exists
    $this->write_db->where(array('fk_office_id' => $office_id, 'financial_report_month' => $financial_report_date));
    $count_financial_report = $this->write_db->get('financial_report')->num_rows();

    if ($count_financial_report == 0) {
      $new_mfr['financial_report_month'] = $financial_report_date;
      $new_mfr['fk_office_id'] = $office_id;
      $new_mfr['fk_status_id'] = $initial_status; //$this->grants->initial_item_status('financial_report');

      $new_mfr_to_insert = $this->grants_model->merge_with_history_fields('financial_report', $new_mfr);

      $this->write_db->insert('financial_report', $new_mfr_to_insert);

      $report_id = $this->write_db->insert_id(); 

      $this->load->model('budget_model');

      $current_budget = $this->budget_model->get_a_budget_by_office_current_transaction_date($office_id);

      // Update the budget id for the newly created MFR
      $update_data['fk_budget_id'] = $current_budget['budget_id'];
      $this->write_db->where(array('financial_report_id' => $report_id));
      $this->write_db->update('financial_report',$update_data);
    }
  }

  public function all_office_financial_report_submitted($office_id){

    $this->read_db->where(array('fk_office_id' => $office_id));
    $this->read_db->where(array('financial_report_is_submitted' => 0));
    $not_submitted_mfrs_count = $this->read_db->get('financial_report')->num_rows();

    return $not_submitted_mfrs_count > 0 ? false : true;
  }


}
