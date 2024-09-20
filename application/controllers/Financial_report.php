<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org/ Londuso@ke.ci.org.....
 */


class Financial_report extends MY_Controller
{

  function __construct()
  {
    parent::__construct();
    $this->load->library('financial_report_library');
    $this->load->model('financial_report_model');
    $this->load->model('variance_comment_model');
    $this->load->model('budget_model');
    $this->load->library('Aws_attachment_library');
    $this->load->model('voucher_model');
  }

  function index()
  {
  }

  private function _income_accounts($office_ids, $project_ids = [])
  {

    // Should be moved to Income accounts library
    return $this->financial_report_library->income_accounts($office_ids, $project_ids);
  }

  private function month_income_account_receipts($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
  {
    return $this->financial_report_library->month_income_account_receipts($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);
  }

  private function month_income_account_expenses($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
  {
    return $this->financial_report_library->month_income_account_expenses($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);
  }

  private function month_income_opening_balance($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
  {
    return $this->financial_report_library->month_income_opening_balance($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);
  }

  private function _fund_balance_report($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
  {

    // log_message('error', json_encode($office_bank_ids));

    $income_accounts =  $this->financial_report_model->income_accounts($office_ids, $project_ids, $office_bank_ids);
    // log_message('error', json_encode($income_accounts));
    $all_accounts_month_opening_balance = $this->month_income_opening_balance($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);
    $all_accounts_month_income = $this->month_income_account_receipts($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);
    $all_accounts_month_expense = $this->month_income_account_expenses($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);

    $report = array();

    $month_cancelled_opening_oustanding_cheques = $this->financial_report_model->get_month_cancelled_opening_outstanding_cheques($office_ids, $start_date_of_month, [], $project_ids, $office_bank_ids);
    $past_months_cancelled_opening_oustanding_cheques = $this->financial_report_model->get_month_cancelled_opening_outstanding_cheques($office_ids, $start_date_of_month, [], $project_ids, $office_bank_ids, 'past_months');

    $itr = 0;

    foreach ($income_accounts as $account) {

      $month_opening_balance = isset($all_accounts_month_opening_balance[$account['income_account_id']]) ? $all_accounts_month_opening_balance[$account['income_account_id']] : 0;
      $month_income = isset($all_accounts_month_income[$account['income_account_id']]) ? $all_accounts_month_income[$account['income_account_id']] : 0;
      $month_expense = isset($all_accounts_month_expense[$account['income_account_id']]) ? $all_accounts_month_expense[$account['income_account_id']] : 0;

      if ($month_opening_balance == 0 && $month_income == 0 && $month_expense == 0) {
        continue;
      }

      if ($itr == 0) {
        $month_opening_balance = $month_opening_balance + $past_months_cancelled_opening_oustanding_cheques;
        $month_income = $month_income + $month_cancelled_opening_oustanding_cheques;
      }

      $report[] = [
        'account_id' => $account['income_account_id'],
        'account_name' => $account['income_account_name'],
        'month_opening_balance' => $month_opening_balance,
        'month_income' => $month_income,
        'month_expense' => $month_expense,
        'month_closing_balance' => ($month_opening_balance + $month_income - $month_expense)
      ];

      $itr++;
    }

    //If the mfr has been submitted. Adjust the child support fund by taking away exact amount of bounced opening chqs This code was added during enhancement for cancelling opening outstanding chqs

    if ($this->financial_report_model->check_if_financial_report_is_submitted($office_ids, $start_date_of_month) == true) {

      $sum_of_bounced_cheques = $this->financial_report_model->get_total_sum_of_bounced_opening_cheques($office_ids, $start_date_of_month);

      $total_amount_bounced = isset($sum_of_bounced_cheques[0]['opening_outstanding_cheque_amount']) ? $sum_of_bounced_cheques[0]['opening_outstanding_cheque_amount'] : 0;
      $bounced_date = isset($sum_of_bounced_cheques[0]['opening_outstanding_cheque_cleared_date']) ? $sum_of_bounced_cheques[0]['opening_outstanding_cheque_cleared_date'] : NULL;
      $mfr_report_month = date('Y-m-t', strtotime($start_date_of_month));

      if ($total_amount_bounced > 0 &&  $bounced_date > $mfr_report_month && sizeof($report) > 0) {

        $month_opening = $report[0]['month_opening_balance'];

        $report[0]['month_opening_balance'] = $month_opening - $total_amount_bounced;
      }

      // print_r($report);
      // exit;
    }

    return $report;
  }

  /**
   * @todo:
   * Awaiting documentation
   */

  private function _proof_of_cash($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
  {
    // log_message('error', json_encode(['office_ids' => $office_ids, 'reporting_month' => $reporting_month, 'project_ids' => $project_ids, 'office_bank_ids' => $office_bank_ids]));

    $cash_at_bank = $this->financial_report_model->compute_cash_at_bank($office_ids, $reporting_month, $project_ids, $office_bank_ids);
    $cash_at_hand = $this->financial_report_model->compute_cash_at_hand($office_ids, $reporting_month, $project_ids, $office_bank_ids);

    $proof_of_cash = ['cash_at_bank' => $cash_at_bank, 'cash_at_hand' => $cash_at_hand];

    // log_message('error', json_encode($proof_of_cash));

    return $proof_of_cash;
  }

  private function _bank_reconciliation($office_ids, $reporting_month, $multiple_offices_report, $multiple_projects_report, $project_ids = [], $office_bank_ids = [])
  {

    $bank_statement_date = $this->_bank_statement_date($office_ids, $reporting_month, $multiple_offices_report, $multiple_projects_report);
    $bank_statement_balance = $this->_bank_statement_balance($office_ids, $reporting_month, $project_ids, $office_bank_ids);

    $book_closing_balance = $this->financial_report_model->compute_cash_at_bank($office_ids, $reporting_month, $project_ids, $office_bank_ids); //$this->_book_closing_balance($office_ids,$reporting_month);

    $month_outstanding_cheques = $this->_sum_of_outstanding_cheques_and_transits($office_ids, $reporting_month, 'expense', 'bank_contra', 'bank', $project_ids, $office_bank_ids);



    $month_transit_deposit = $this->_sum_of_outstanding_cheques_and_transits($office_ids, $reporting_month, 'income', 'cash_contra', 'bank', $project_ids, $office_bank_ids); //$this->_deposit_in_transit($office_ids,$reporting_month);
    $bank_reconciled_balance = $bank_statement_balance - $month_outstanding_cheques + $month_transit_deposit;

    $is_book_reconciled = false;

    if (round($bank_reconciled_balance, 2) == round($book_closing_balance, 2)) {
      $is_book_reconciled = true;
    }

    return [
      'bank_statement_date' => $bank_statement_date,
      'bank_statement_balance' => $bank_statement_balance,
      'book_closing_balance' => $book_closing_balance,
      'month_outstanding_cheques' => $month_outstanding_cheques,
      'month_transit_deposit' => $month_transit_deposit,
      'bank_reconciled_balance' => $bank_reconciled_balance,
      'is_book_reconciled' => $is_book_reconciled
    ];
  }

  function _bank_statement_date($office_ids, $reporting_month, $multiple_offices_report, $multiple_projects_report)
  {

    $reconciliation_reporting_month = date('Y-m-t', strtotime($reporting_month));

    if (!$multiple_offices_report || !$multiple_projects_report) {
      $this->read_db->select(array('financial_report_month'));
      $this->read_db->where(array(
        'fk_office_id' => $office_ids[0],
        'financial_report_month' => date('Y-m-t', strtotime($reporting_month))
      ));
      $this->read_db->join('financial_report', 'financial_report.financial_report_id=reconciliation.fk_financial_report_id');
      $reconciliation_reporting_month_obj = $this->read_db->get('reconciliation');

      if ($reconciliation_reporting_month_obj->num_rows() > 0) {
        $reconciliation_reporting_month = $reconciliation_reporting_month_obj->row()->financial_report_month;
      }
    } else {
      $reconciliation_reporting_month = "This field cannot be populated for multiple offices or bank accounts report";
    }

    return $reconciliation_reporting_month;
  }

  function _bank_statement_balance($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
  {

    $financial_report_statement_amount = 0;

    $this->read_db->select_sum('reconciliation_statement_balance');
    $this->read_db->where_in('financial_report.fk_office_id', $office_ids);
    $this->read_db->where(array('financial_report_month' => date('Y-m-01', strtotime($reporting_month))));
    $this->read_db->join('reconciliation', 'reconciliation.fk_financial_report_id=financial_report.financial_report_id');

    $this->read_db->group_by(array('financial_report_month'));

    if (count($project_ids) > 0) {
      $this->read_db->where_in('project_allocation.fk_project_id', $project_ids);
      $this->read_db->join('office_bank', 'office_bank.office_bank_id=reconciliation.fk_office_bank_id');
      $this->read_db->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_office_bank_id=office_bank.office_bank_id');
      $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=office_bank_project_allocation.fk_project_allocation_id');
    }

    if (!empty($office_bank_ids)) {
      $this->read_db->where_in('reconciliation.fk_office_bank_id', $office_bank_ids);
    }

    $financial_report_statement_amount_obj = $this->read_db->get('financial_report');

    if ($financial_report_statement_amount_obj->num_rows() > 0) {
      $financial_report_statement_amount = $financial_report_statement_amount_obj->row()->reconciliation_statement_balance;
    }

    return $financial_report_statement_amount;
  }

  // function _book_closing_balance($office_ids,$reporting_month){
  //   return 245980.12;
  // }


  private function bank_statements()
  {
  }

  function _sum_of_outstanding_cheques_and_transits($office_ids, $reporting_month, $transaction_type, $contra_type, $voucher_type_account_code, $project_ids = [], $office_bank_ids = [])
  {

    // return array_sum(array_column($this->financial_report_model->list_oustanding_cheques_and_deposits($office_ids, $reporting_month, $transaction_type, $contra_type, $voucher_type_account_code, $project_ids, $office_bank_ids), 'voucher_detail_total_cost'));

    $all_outsanding_chqs = $this->financial_report_model->list_oustanding_cheques_and_deposits($office_ids, $reporting_month, $transaction_type, $contra_type, $voucher_type_account_code, $project_ids, $office_bank_ids);

    // log_message('error', json_encode($all_outsanding_chqs));

    $removed_bounced_openning_oustanding_cheques = [];

    /*Check if the mfr has been submitted and if so treat the all oustanding cheques as before the adjustemnt of 
        opening oustanding cheques after cancelled. Othewise consider the undjustment by reseting the amount to zero*/

    $mfr_submitted_check = $this->financial_report_model->check_if_financial_report_is_submitted($office_ids, $reporting_month);

    if ($mfr_submitted_check == true) {

      $removed_bounced_openning_oustanding_cheques  = $all_outsanding_chqs;
    } else {
      foreach ($all_outsanding_chqs as $all_outsanding_chq) {

        if ($all_outsanding_chq['voucher_id'] == 0 && ($all_outsanding_chq['bounce_flag'] == 1 && $all_outsanding_chq['voucher_cleared_month'] < $reporting_month)) {

          $all_outsanding_chq['voucher_detail_total_cost'] = 0;

          $removed_bounced_openning_oustanding_cheques[] = $all_outsanding_chq;
        } else {
          $removed_bounced_openning_oustanding_cheques[] = $all_outsanding_chq;
        }
      }
    }

    //return $removed_bounced_openning_oustanding_cheques;
    return array_sum(array_column($removed_bounced_openning_oustanding_cheques, 'voucher_detail_total_cost'));
  }




  private function _list_cleared_effects($office_ids, $reporting_month, $transaction_type, $contra_type, $voucher_type_account_code, $project_ids = [], $office_bank_ids = [])
  {

    return $this->financial_report_model->list_cleared_effects($office_ids, $reporting_month, $transaction_type, $contra_type, $voucher_type_account_code, $project_ids, $office_bank_ids);
  }

  // private function cleared_oustanding_cheques(){

  // }

  // private function cleared_deposit_in_transit(){

  // }

  private function _expense_report($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
  {

    $expense_account_grid = [];

    $income_grouped_expense_accounts = $this->_income_grouped_expense_accounts($office_ids);
    $month_expense = $this->financial_report_model->month_expense_by_expense_account($office_ids, $reporting_month, $project_ids, $office_bank_ids);
    $month_expense_to_date = $this->financial_report_model->expense_to_date_by_expense_account($office_ids, $reporting_month, $project_ids, $office_bank_ids);
    $budget_to_date = $this->financial_report_model->budget_to_date_by_expense_account($office_ids, $reporting_month, $project_ids, $office_bank_ids);

    $expense_account_comment = $this->_expense_account_comment($office_ids, $reporting_month);

    // log_message('error', json_encode($budget_to_date));
    // $budget_variance = $this->_budget_variance_by_expense_account($office_ids,$reporting_month);
    // $budget_variance_percent = $this->_budget_variance_percent_by_expense_account($office_ids,$reporting_month);   


    foreach ($income_grouped_expense_accounts as $income_account_id => $income_account) {
      $check_sum = 0;
      foreach ($income_account['expense_accounts'] as $expense_account) {
        $income_account_id =  $income_account['income_account']['income_account_id'];
        $expense_account_id = $expense_account['expense_account_id'];

        $expense_account_grid[$income_account_id]['income_account'] = $income_account['income_account'];
        $expense_account_grid[$income_account_id]['expense_accounts'][$expense_account['expense_account_id']]['expense_account'] = $expense_account;
        $expense_account_grid[$income_account_id]['expense_accounts'][$expense_account['expense_account_id']]['month_expense'] = isset($month_expense[$income_account_id][$expense_account_id]) ? $month_expense[$income_account_id][$expense_account_id] : 0;
        $expense_account_grid[$income_account_id]['expense_accounts'][$expense_account['expense_account_id']]['month_expense_to_date'] = isset($month_expense_to_date[$income_account_id][$expense_account_id]) ? $month_expense_to_date[$income_account_id][$expense_account_id] : 0;
        $expense_account_grid[$income_account_id]['expense_accounts'][$expense_account['expense_account_id']]['budget_to_date'] = isset($budget_to_date[$income_account_id][$expense_account_id]) ? $budget_to_date[$income_account_id][$expense_account_id] : 0;
        //$expense_account_grid[$income_account_id]['expense_accounts'][$expense_account['expense_account_id']]['budget_variance'] = $budget_variance;
        //$expense_account_grid[$income_account_id]['expense_accounts'][$expense_account['expense_account_id']]['budget_variance_percent'] = $budget_variance_percent;
        $expense_account_grid[$income_account_id]['expense_accounts'][$expense_account['expense_account_id']]['expense_account_comment'] = isset($expense_account_comment[$income_account_id][$expense_account_id]) ? $expense_account_comment[$income_account_id][$expense_account_id] : ''; //$expense_account_comment;

        $check_sum += $expense_account_grid[$income_account_id]['expense_accounts'][$expense_account['expense_account_id']]['month_expense_to_date'] +  $expense_account_grid[$income_account_id]['expense_accounts'][$expense_account['expense_account_id']]['budget_to_date'];
      }
      $expense_account_grid[$income_account_id]['check_sum'] = $check_sum;
    }

    return $expense_account_grid;
  }

  function post_expense_account_comment()
  {
    echo $this->variance_comment_model->add();
  }

  function get_expense_account_comment()
  {

    $post = $this->input->post();

    // log_message('error', json_encode($post));

    $comment = "";

    if (isset($post['expense_account_id'])) {

      $expense_account_id = $post['expense_account_id'];
      $office_id = $post['office_id'];
      $reporting_month = $post['reporting_month'];
      $report_id = $post['report_id'];

      $budget_id = $this->budget_model->get_budget_id_based_on_month($office_id, $reporting_month);
      $comment =  $this->variance_comment_model->get_expense_account_comment($expense_account_id, $budget_id, $report_id);
    }

    echo $comment;
  }

  function _income_grouped_expense_accounts($office_ids)
  {
    $income_accounts = $this->_income_accounts($office_ids);

    $expense_accounts = [];

    foreach ($income_accounts as $income_account) {

      $expense_accounts[$income_account['income_account_id']]['income_account'] = $income_account;

      $this->read_db->select(array('expense_account_id', 'expense_account_code', 'expense_account_name'));
      $expense_accounts[$income_account['income_account_id']]['expense_accounts'] = $this->read_db->get_where(
        'expense_account',
        array('fk_income_account_id' => $income_account['income_account_id'])
      )->result_array();
    }

    return $expense_accounts;
  }



  function _budget_variance_by_expense_account($office_ids, $reporting_month)
  {
    return 150;
  }

  function _budget_variance_percent_by_expense_account($office_ids, $reporting_month)
  {
    return 0.65;
  }

  function _expense_account_comment($office_ids, $reporting_month)
  {

    $office_id = $office_ids[0];
    $budget_id = $this->budget_model->get_budget_id_based_on_month($office_id, $reporting_month);
    $report_id = hash_id($this->id, 'decode');
    return $this->variance_comment_model->get_all_expense_account_comment($budget_id, $report_id);
  }


  function financial_report_office_hierarchy($reporting_month)
  {
    $user_office_hierarchy = $this->user_model->user_hierarchy_offices($this->session->user_id, true);

    // Remove offices with a financial reporting in the selected reporting month

    $user_hierarchy_offices_with_report = $this->_user_hierarchy_offices_with_financial_report_for_selected_month($reporting_month);
    //print_r($user_hierarchy_offices_with_report);exit;
    foreach ($user_office_hierarchy as $office_context => $offices) {
      foreach ($offices as $key => $office) {
        if (is_array($office) && isset($office['office_id']) && !in_array($office['office_id'], $user_hierarchy_offices_with_report)) {
          unset($user_office_hierarchy[$office_context][$key]);
        }
      }
    }


    if ($this->config->item('only_combined_center_financial_reports')) {
      $centers = $user_office_hierarchy[$this->user_model->get_lowest_office_context()->context_definition_name];
      unset($user_office_hierarchy);
      $user_office_hierarchy[$this->user_model->get_lowest_office_context()->context_definition_name] = $centers;
    }

    return $user_office_hierarchy;
  }

  private function _user_hierarchy_offices_with_financial_report_for_selected_month($reporting_month)
  {
    $context_ungrouped_user_hierarchy_offices = $this->user_model->user_hierarchy_offices($this->session->user_id);

    $offices_ids = array_column($context_ungrouped_user_hierarchy_offices, 'office_id');

    $this->read_db->select('fk_office_id');
    $this->read_db->where_in('fk_office_id', $offices_ids);
    $office_ids_with_report = $this->read_db->get_where('financial_report', array('financial_report_month' => $reporting_month))->result_array();

    return array_column($office_ids_with_report, 'fk_office_id');
  }

  function financial_report_information($report_id)
  {

    $additional_information = $this->financial_report_library->financial_report_information($report_id);
    // print_r($additional_information);exit;

    if ((isset($_POST['office_ids']) && isset($_POST['reporting_month']) && count($_POST['office_ids']) > 0)) {
      $additional_information = $this->financial_report_library->financial_report_information($report_id, $_POST['office_ids'], $_POST['reporting_month']);
      // log_message('error', json_encode(['Hello world']));
    }
    //print_r($additional_information); exit;

    // log_message('error', json_encode($additional_information));

    // $this->voucher_model->get_voucher_date($office_id)

    $reporting_month = $additional_information[0]['financial_report_month'];

    $account_system_id = $additional_information[0]['account_system_id'];

    $office_ids = array_column($additional_information, 'office_id');

    // log_message('error', json_encode($office_ids));

    $multiple_offices_report = false;
    $multiple_projects_report = false;

    if (count($office_ids) == 1) {
      $count_of_office_banks = $this->read_db->get_where('office_bank', array('fk_office_id', $office_ids[0]))->num_rows();

      if ((isset($_POST['project_ids']) && count($_POST['project_ids']) == $count_of_office_banks) || ($count_of_office_banks > 1 && !isset($_POST['project_ids']))) {
        $multiple_projects_report = true;
      }
    }

    $office_names = implode(', ', array_column($additional_information, 'office_name'));

    if (count($additional_information) > 1) {
      // Multiple Office
      $multiple_offices_report = true;
    }

    return [
      'office_names' => $office_names,
      'reporting_month' => $reporting_month,
      'office_ids' => $office_ids,
      'multiple_offices_report' => $multiple_offices_report,
      'multiple_projects_report' => $multiple_projects_report,
      'status_id' => $additional_information[0]['status_id'],
      'account_system_id' => $account_system_id
      //'test'=>$additional_information,
    ];
  }

  function get_month_active_projects($office_ids, $reporting_month, $show_active_only = false)
  {

    return $this->financial_report_library->get_month_active_projects($office_ids, $reporting_month);
  }

  function get_office_banks($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
  {

    $this->load->model('office_bank_model');

    $office_banks = $this->financial_report_model->get_office_banks($office_ids, $project_ids, $office_bank_ids);
  
    // log_message('error', json_encode($office_banks));
    $office_banks_array = [];

    $cnt = 0;
    for($i = 0; $i < count($office_banks); $i++){
      $is_office_bank_obselete = $this->office_bank_model->is_office_bank_obselete($office_banks[$i]['office_bank_id'], $reporting_month);
      
      if(!$is_office_bank_obselete){
        // unset($office_banks[$i]);
        $office_banks_array[$cnt] = $office_banks[$i];
        $cnt++;
      }
    }

    
    
    return $office_banks_array;
  }

  function has_submitted_report_ahead($report)
  {

    $reporting_month = $report['reporting_month'];
    $office_id = $report['office_ids'][0];

    // log_message('error', json_encode($reporting_month));

    $has_submitted_report_ahead = false;
    $financial_report_initial_status = $this->grants_model->initial_item_status('financial_report');

    $this->read_db->where(array(
      'financial_report_month > ' => $reporting_month,
      'fk_status_id<>' => $financial_report_initial_status, 'fk_office_id' => $office_id
    ));
    $this->read_db->from('financial_report');
    $count_all_results = $this->read_db->count_all_results();

    if ($count_all_results > 0) {
      $has_submitted_report_ahead = true;
    }

    return  $has_submitted_report_ahead;
  }

  function update_financial_report_budget_id($report_id, $office_id)
  {

    $budget_id = 0;

    $this->read_db->where(array('financial_report_id' => $report_id));
    $budget_id = $this->read_db->get('financial_report')->row()->fk_budget_id;

    if ($budget_id == NULL || $budget_id == 0) {
      $this->load->model('budget_model');
      $current_budget = $this->budget_model->get_a_budget_by_office_current_transaction_date($office_id);

      if(!empty($current_budget)){
        $budget_id = $current_budget['budget_id'];
        $this->write_db->where(array('financial_report_id' => $report_id));
        $this->write_db->update('financial_report',['fk_budget_id' => $budget_id]);
      }
    }

    return $budget_id == NULL ? 0 : $budget_id;

  }

  function result($id = '')
  {

    if ($this->action == 'view') {

      $report = $this->financial_report_information($this->id);
      extract($report);


      // check if report has budget id if not update it
      $budget_id = $this->update_financial_report_budget_id(hash_id($this->id,'decode'), $office_ids[0]);
      $budget_tag_name = '';

      if($budget_id == 0){
        $this->load->model('budget_tag_model');
        $this->load->model('custom_financial_year_model');

        $custom_financial_year = $this->custom_financial_year_model->get_default_custom_financial_year_id_by_office($office_ids[0], true);
        $budget_tag_name = $this->budget_tag_model->get_budget_tag_id_based_on_reporting_month($office_ids[0],$reporting_month, $custom_financial_year)['budget_tag_name'];
      }
      // log_message('error', json_encode($budget_id));

      return array_merge([
        'test' => [],
        'report_id' => hash_id($this->id,'decode'),
        'budget_id' => $budget_id,
        'budget_tag_name' => $budget_tag_name,
        'allow_mfr_reconciliation' => ($multiple_offices_report || $multiple_projects_report || count($this->get_office_banks($office_ids, $reporting_month)) > 1) ? false : true,
        'month_active_projects' => $this->get_month_active_projects($office_ids, $reporting_month),
        'office_banks' => $this->get_office_banks($office_ids, $reporting_month),
        'multiple_offices_report' => $multiple_offices_report,
        'multiple_projects_report' => $multiple_projects_report,
        'financial_report_submitted' => $this->_check_if_financial_report_is_submitted($office_ids, $reporting_month),
        'has_submitted_report_ahead' => $this->has_submitted_report_ahead($report),
        'user_office_hierarchy' => $this->financial_report_office_hierarchy($reporting_month),
        'office_names' => $office_names,
        'office_ids' => $office_ids,
        'reporting_month' => $reporting_month,
        'fund_balance_report' => $this->_fund_balance_report($office_ids, $reporting_month),
        'projects_balance_report' => $this->_projects_balance_report($office_ids, $reporting_month),
        'proof_of_cash' => $this->_proof_of_cash($office_ids, $reporting_month),
        'financial_ratios' => $this->financial_ratios(),
        'bank_statements_uploads' => $this->_bank_statements_uploads($office_ids, $reporting_month),
        'bank_reconciliation' => $this->_bank_reconciliation($office_ids, $reporting_month, $multiple_offices_report, $multiple_projects_report),
        'outstanding_cheques' => $this->financial_report_model->list_oustanding_cheques_and_deposits($office_ids, $reporting_month, 'expense', 'bank_contra', 'bank'),
        'clear_outstanding_cheques' => $this->_list_cleared_effects($office_ids, $reporting_month, 'expense', 'bank_contra', 'bank'),
        'deposit_in_transit' => $this->financial_report_model->list_oustanding_cheques_and_deposits($office_ids, $reporting_month, 'income', 'cash_contra', 'bank'), //$this->_deposit_in_transit($office_ids,$reporting_month),
        'cleared_deposit_in_transit' => $this->_list_cleared_effects($office_ids, $reporting_month, 'income', 'cash_contra', 'bank'),
        'expense_report' => $this->_expense_report($office_ids, $reporting_month),
        'logged_role_id' => $this->session->role_ids,
        'table' => 'financial_report',
        'primary_key' => hash_id($this->id, 'decode'),
        'financial_report_status' => $status_id,
        'funds_transfers' => $this->voucher_model->month_funds_transfer_vouchers($office_ids, $reporting_month),
      ], $this->general_model->action_button_data($this->controller, $account_system_id));
    } elseif ($this->action == 'list') {
      $columns = $this->columns();
      array_shift($columns);
      $result['columns'] = $columns;
      $result['has_details_table'] = false;
      $result['has_details_listing'] = false;
      $result['is_multi_row'] = false;
      $result['show_add_button'] = false;

      return $result;
    } else {
      return parent::result($id);
    }
  }

  function columns()
  {
    $columns = [
      'financial_report_id',
      'financial_report_track_number',
      'office_name',
      'financial_report_month',
      'financial_report_created_date',
      'financial_report_is_submitted',
      'status_name'
    ];

    return $columns;
  }

  function financial_ratios(){
    
  }

  function get_financial_reports()
  {

    $columns = $this->columns();
    array_push($columns, 'status_id');
    $search_columns = $columns;

    // Limiting records
    $start = intval($this->input->post('start'));
    $length = intval($this->input->post('length'));

    $this->read_db->limit($length, $start);

    // Ordering records

    $order = $this->input->post('order');
    $col = '';
    $dir = 'desc';

    if (!empty($order)) {
      $col = $order[0]['column'];
      $dir = $order[0]['dir'];
    }

    if ($col == '') {
      $this->read_db->order_by('financial_report_id DESC');
    } else {
      $this->read_db->order_by($columns[$col], $dir);
    }

    // Searching

    // $search = $this->input->post('search');
    // $value = $search['value'];

    // array_shift($search_columns);

    // if(!empty($value)){
    //   $this->read_db->group_start();
    //   $column_key = 0;
    //     foreach($search_columns as $column){
    //       if($column_key == 0) {
    //         $this->read_db->like($column,$value,'both'); 
    //       }else{
    //         $this->read_db->or_like($column,$value,'both');
    //     }
    //       $column_key++;				
    //   }
    //   $this->read_db->group_end();       
    // }
    $this->searchbuilder->searchbuilder_query_group($this->columns());

    $this->read_db->select($columns);
    $this->read_db->join('status', 'status.status_id=financial_report.fk_status_id');
    $this->read_db->join('office', 'office.office_id=financial_report.fk_office_id');

    if (!$this->session->system_admin) {
      $this->read_db->where_in('financial_report.fk_office_id', array_column($this->session->hierarchy_offices, 'office_id'));
    }

    $result_obj = $this->read_db->get('financial_report');

    $results = [];

    if ($result_obj->num_rows() > 0) {
      $results = $result_obj->result_array();
    }

    return $results;
  }

  function count_financial_reports()
  {

    $columns = $this->columns();
    $search_columns = $columns;

    // Searching

    // $search = $this->input->post('search');
    // $value = $search['value'];

    // array_shift($search_columns);

    // if(!empty($value)){
    //   $this->read_db->group_start();
    //   $column_key = 0;
    //     foreach($search_columns as $column){
    //       if($column_key == 0) {
    //         $this->read_db->like($column,$value,'both'); 
    //       }else{
    //         $this->read_db->or_like($column,$value,'both');
    //     }
    //       $column_key++;				
    //   }
    //   $this->read_db->group_end();
    // }

    $this->searchbuilder->searchbuilder_query_group($this->columns());

    if (!$this->session->system_admin) {
      $this->read_db->where_in('financial_report.fk_office_id', array_column($this->session->hierarchy_offices, 'office_id'));
    }

    $this->read_db->join('status', 'status.status_id=financial_report.fk_status_id');
    $this->read_db->join('office', 'office.office_id=financial_report.fk_office_id');
    $this->read_db->from('financial_report');
    $count_all_results = $this->read_db->count_all_results();

    return $count_all_results;
  }

  function show_list()
  {

    $draw = intval($this->input->post('draw'));
    $financial_reports = $this->get_financial_reports();
    $count_financial_reports = $this->count_financial_reports();

    $status_data = $this->general_model->action_button_data($this->controller);
    extract($status_data);

    $result = [];

    $cnt = 0;
    foreach ($financial_reports as $financial_report) {
      $financial_report_id = array_shift($financial_report);
      $financial_status = array_pop($financial_report);

      $financial_report_track_number = $financial_report['financial_report_track_number'];
      $financial_report['financial_report_track_number'] = '<a target="__blank" href="' . base_url() . $this->controller . '/view/' . hash_id($financial_report_id) . '">' . $financial_report_track_number . '</a>';
      $financial_report['financial_report_is_submitted'] = $financial_report['financial_report_is_submitted'] == 1 ? get_phrase('yes') :  get_phrase('no');
      $row = array_values($financial_report);

      $action = ''; //approval_action_button($this->controller, $item_status, $financial_report_id, $financial_status, $item_initial_item_status_id, $item_max_approval_status_ids);

      array_unshift($row, $action);

      $result[$cnt] = $row;

      $cnt++;
    }

    $response = [
      'draw' => $draw,
      'recordsTotal' => $count_financial_reports,
      'recordsFiltered' => $count_financial_reports,
      'data' => $result
    ];

    echo json_encode($response);
  }

  function result_array($report_id, $office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
  {
    // log_message('error', json_encode($office_bank_ids));
    extract($this->financial_report_information($report_id));

    return [
      //'test1'=>$this->financial_report_information($report_id),
      //'test'=>[$office_ids,$reporting_month,'expense','bank_contra','bank',$project_ids,$office_bank_ids],
      'month_active_projects' => $this->get_month_active_projects($office_ids, $reporting_month),
      'allow_mfr_reconciliation' => ($multiple_offices_report || $multiple_projects_report || count($this->get_office_banks($office_ids, $reporting_month, $project_ids, $office_bank_ids)) > 1) ? false : true,
      'office_banks' => $this->get_office_banks($office_ids, $reporting_month, $project_ids, $office_bank_ids),
      'multiple_offices_report' => $multiple_offices_report,
      'multiple_projects_report' => $multiple_projects_report,
      'financial_report_submitted' => $this->_check_if_financial_report_is_submitted($office_ids, $reporting_month),
      'user_office_hierarchy' => $this->financial_report_office_hierarchy($reporting_month),
      'office_names' => $office_names,
      'office_ids' => $office_ids,
      'reporting_month' => $reporting_month,
      'fund_balance_report' => $this->_fund_balance_report($office_ids, $reporting_month, $project_ids, $office_bank_ids),
      'projects_balance_report' => $this->_projects_balance_report($office_ids, $reporting_month, $project_ids, $office_bank_ids),
      'proof_of_cash' => $this->_proof_of_cash($office_ids, $reporting_month, $project_ids, $office_bank_ids),
      //'financial_ratios'=>$this->financial_ratios(),
      'bank_statements_uploads' => $this->_bank_statements_uploads($office_ids, $reporting_month, $project_ids, $office_bank_ids),
      'bank_reconciliation' => $this->_bank_reconciliation($office_ids, $reporting_month, $multiple_offices_report, $multiple_projects_report, $project_ids, $office_bank_ids),
      'outstanding_cheques' => $this->financial_report_model->list_oustanding_cheques_and_deposits($office_ids, $reporting_month, 'expense', 'bank_contra', 'bank', $project_ids, $office_bank_ids),
      'clear_outstanding_cheques' => $this->_list_cleared_effects($office_ids, $reporting_month, 'expense', 'bank_contra', 'bank', $project_ids, $office_bank_ids),
      'deposit_in_transit' => $this->financial_report_model->list_oustanding_cheques_and_deposits($office_ids, $reporting_month, 'income', 'cash_contra', 'bank', $project_ids, $office_bank_ids), //$this->_deposit_in_transit($office_ids,$reporting_month),
      'cleared_deposit_in_transit' => $this->_list_cleared_effects($office_ids, $reporting_month, 'income', 'cash_contra', 'bank', $project_ids, $office_bank_ids),
      'expense_report' => $this->_expense_report($office_ids, $reporting_month, $project_ids, $office_bank_ids),
      'funds_transfers' => $this->voucher_model->month_funds_transfer_vouchers($office_ids, $reporting_month),
    ];
  }

  function ajax_test()
  {

    $report_id = '8zoLYo3YXb';
    $office_ids = [1];
    $reporting_month = '2020-04-01';
    $project_ids = [5];

    $result = $this->result_array($report_id, $office_ids, $reporting_month, $project_ids);
    //$result = $this->_fund_balance_report($office_ids,$reporting_month,$project_ids);

    echo json_encode($result);
  }

  function filter_financial_report()
  {

    // log_message('error', json_encode($this->input->post()));

    $project_ids = $this->input->post('project_ids') == null ? [] : $this->input->post('project_ids');
    $office_bank_ids = $this->input->post('office_bank_ids') == null ? [] : $this->input->post('office_bank_ids');
    $office_ids = $this->input->post('office_ids');
    $report_id = $this->input->post('report_id');
    $reporting_month = $this->input->post('reporting_month');

    $report_result = $this->result_array($report_id, $office_ids, $reporting_month, $project_ids, $office_bank_ids);
    $result['result'] = $report_result;
    $result['report_id'] = $report_id;

    //echo json_encode($result);

    $view_page =  $this->load->view('financial_report/ajax_view', $result, true);

    echo $view_page;
  }

  function view()
  {
    parent::view();
  }

  function _check_if_financial_report_is_submitted($office_ids, $reporting_month)
  {
    return $this->financial_report_model->check_if_financial_report_is_submitted($office_ids, $reporting_month);
  }

  function _bank_statements_uploads($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
  {

    $reconciliation_ids = [];

    $this->read_db->select(array('reconciliation_id'));
    $this->read_db->where_in('fk_office_id', $office_ids);
    $this->read_db->where(array('financial_report_month' => date('Y-m-01', strtotime($reporting_month))));
    $this->read_db->join('financial_report', 'financial_report.financial_report_id=reconciliation.fk_financial_report_id');

    if (!empty($office_bank_ids)) {
      $this->read_db->where_in('reconciliation.fk_office_bank_id', $office_bank_ids);
    }

    if (!empty($project_ids)) {
      $this->read_db->join('office_bank', 'office_bank.office_bank_id=reconciliation.office_bank_id');
      $this->read_db->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_office_bank_id=office_bank.office_bank_id');
      $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=office_bank_project_allocation.fk_project_allocation_id');
      $this->read_db->where_in('project_allocation.fk_project_id', $project_ids);
    }

    $reconciliation_ids_obj = $this->read_db->get('reconciliation');

    if ($reconciliation_ids_obj->num_rows() > 0) {
      $reconciliation_ids = $reconciliation_ids_obj->result_array();
    }

    $attachment_where_condition_array = [];

    $approve_item_name = 'reconciliation';

    $approve_item_id = $this->read_db->get_where(
      'approve_item',
      array('approve_item_name' => $approve_item_name)
    )->row()->approve_item_id;

    //print_r(array_column($reconciliation_ids,'reconciliation_id'));exit;

    $attachment_where_condition_array['fk_approve_item_id'] = $approve_item_id;
    $attachment_where_condition_array['attachment_primary_id'] = array_column($reconciliation_ids, 'reconciliation_id');

    // return $this->Aws_attachment_library->retrieve_file_uploads_info('reconciliation',array_column($reconciliation_ids,'reconciliation_id'));

    return $this->aws_attachment_library->retrieve_file_uploads_info($attachment_where_condition_array);
  }

  function _projects_balance_report($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
  {
    $headers = [];
    $body = [];


    $projects = $this->_office_projects($office_ids, $reporting_month, $project_ids, $office_bank_ids);

    // log_message('error', json_encode($projects));

    foreach ($projects as $project_id => $project) {
      $body[$project_id]['funder'] = $project['funder_name'];
      $body[$project_id]['project'] = $project['project_name'];
      //Income account
      $body[$project_id]['month_expense'] = $this->_projects_month_expense([$project['office_id']], $reporting_month, [$project_id], $office_bank_ids) == null ? 0 : $this->_projects_month_expense([$project['office_id']], $reporting_month, [$project_id], $office_bank_ids);
      $body[$project_id]['allocation_target'] = $this->_projects_allocation_target([$project['office_id']], [$project_id], $office_bank_ids) == null ? 0 : $this->_projects_allocation_target([$project['office_id']], [$project_id], $office_bank_ids);
    }

    if ($this->config->item('funding_balance_report_aggregate_method') == 'receipt') {
      $headers = [
        "funder" => get_phrase("funder"),
        "project" => get_phrase("project"),
        "allocation_target" => get_phrase("allocation_target"),
        "opening_balance" => get_phrase("opening_balance"),
        "month_income" => get_phrase("month_income"),
        "month_expense" => get_phrase("month_expense"),
        "closing_balance" => get_phrase("closing_balance")
      ];

      foreach ($projects as $project_id => $project) {
        $body[$project_id]['opening_balance'] = $this->_projects_opening_balances([$project['office_id']], $reporting_month, [$project_id], $office_bank_ids) == null ? 0 : $this->_projects_opening_balances([$project['office_id']], $reporting_month, [$project_id], $office_bank_ids);
        $body[$project_id]['month_income'] = $this->_projects_month_income([$project['office_id']], $reporting_month, [$project_id], $office_bank_ids) == null ? 0 : $this->_projects_month_income([$project['office_id']], $reporting_month, [$project_id], $office_bank_ids);
        $body[$project_id]['closing_balance'] = $this->_projects_receipt_closing_balance([$project['office_id']], $reporting_month, [$project_id], $office_bank_ids) == null ? 0 : $this->_projects_receipt_closing_balance([$project['office_id']], $reporting_month, [$project_id], $office_bank_ids);
      }
    } elseif ($this->config->item('funding_balance_report_aggregate_method') == 'allocation') {
      $headers = [
        "funder" => get_phrase("funder"),
        "project" => get_phrase("project"),
        "allocation_target" => get_phrase("allocation_target"),
        "month_expense" => get_phrase("month_expense"),
        "month_expense_to_date" => get_phrase("month_expense_to_date"),
        "closing_balance" => get_phrase("closing_balance")
      ];

      foreach ($projects as $project_id => $project) {
        $body[$project_id]['month_expense_to_date'] = $this->_projects_month_expense_to_date([$project['office_id']], $reporting_month, [$project_id], $office_bank_ids);
        $body[$project_id]['closing_balance'] = $this->_projects_allocation_closing_balance([$project['office_id']], $reporting_month, [$project_id], $office_bank_ids);
      }
    }

    // log_message('error', json_encode($body));
    $this->removeZeroProjectBalances($body);

    return ['headers' => $headers, 'body' => $body];
  }

  function removeZeroProjectBalances(&$balances)
  {
    foreach ($balances as $project_id => $balance) {
      if ($balance['opening_balance'] == 0 && $balance['month_income'] == 0 && $balance['month_expense'] == 0) {
        unset($balances[$project_id]);
      }
    }
  }

  function _projects_allocation_closing_balance($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
  {
    $closing_balance = $this->_projects_allocation_target($office_ids, $project_ids, $office_bank_ids) - $this->_projects_month_expense_to_date($office_ids, $reporting_month, $project_ids, $office_bank_ids);

    return $closing_balance;
  }

  function _projects_month_expense_to_date($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
  {

    $end_of_reporting_month = date('Y-m-t', strtotime($reporting_month));

    $this->read_db->select_sum('voucher_detail_total_cost');
    $this->read_db->where(array('voucher_type_effect_code' => 'expense'));
    $this->read_db->where(array('voucher_date<=' => $end_of_reporting_month));
    $this->read_db->where_in('voucher.fk_office_id', $office_ids);

    $this->read_db->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
    $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
    $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
    $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');
    $this->read_db->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_project_allocation_id=project_allocation.project_allocation_id');

    if (!empty($project_ids)) {
      $this->read_db->where_in('project_allocation.fk_project_id', $project_ids);
    }

    if (!empty($office_bank_ids)) {
      $this->read_db->where_in('office_bank_project_allocation.fk_office_bank_id', $office_bank_ids);
    }

    $voucher_detail_total_cost = $this->read_db->get('voucher_detail')->row()->voucher_detail_total_cost;

    return $voucher_detail_total_cost;
  }

  function _projects_allocation_target($office_ids, $project_ids = [], $office_bank_ids = [])
  {

    $this->read_db->select_sum('project_allocation_amount');
    $this->read_db->where_in('fk_office_id', $office_ids);

    if (!empty($project_ids)) {
      $this->read_db->join('project', 'project.project_id=project_allocation.fk_project_id');
      $this->read_db->where_in('project_id', $project_ids);
    }

    if (!empty($office_bank_ids)) {
      $this->read_db->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_project_allocation_id=project_allocation.project_allocation_id');
      $this->read_db->where_in('office_bank_project_allocation.fk_office_bank_id', $office_bank_ids);
    }


    $sum_project_allocation_amount = $this->read_db->get('project_allocation')->row()->project_allocation_amount;

    return $sum_project_allocation_amount;
  }

  function _projects_receipt_closing_balance($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
  {
    $opening_balance = $this->_projects_opening_balances($office_ids, $reporting_month, $project_ids, $office_bank_ids);
    $month_income = $this->_projects_month_income($office_ids, $reporting_month, $project_ids, $office_bank_ids);
    $month_expense = $this->_projects_month_expense($office_ids, $reporting_month, $project_ids, $office_bank_ids);

    $closing_balance = $opening_balance + $month_income - $month_expense;

    return $closing_balance;
  }

  function _projects_month_income($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
  {

    $start_date_of_reporting_month = date('Y-m-01', strtotime($reporting_month));
    $end_date_of_reporting_month = date('Y-m-t', strtotime($reporting_month));

    $this->read_db->select_sum('voucher_detail_total_cost');
    $this->read_db->where(array('voucher_type_effect_code' => 'income'));
    $this->read_db->where_in('voucher.fk_office_id', $office_ids);
    $this->read_db->where(array('voucher.voucher_date>=' => $start_date_of_reporting_month, 'voucher.voucher_date<=' => $end_date_of_reporting_month));

    $this->read_db->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
    $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
    $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');

    if (!empty($project_ids)) {
      $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');
      $this->read_db->where_in('project_allocation.fk_project_id', $project_ids);
    }

    if (!empty($office_bank_ids)) {
      $this->read_db->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_project_allocation_id=project_allocation.project_allocation_id');
      $this->read_db->where_in('office_bank_project_allocation.fk_office_bank_id', $office_bank_ids);
    }

    $voucher_detail_total_cost = $this->read_db->get('voucher_detail')->row()->voucher_detail_total_cost;

    return $voucher_detail_total_cost;
  }

  private function _project_allocation_system_opening_balance($office_ids, $reporting_month, $project_ids, $office_bank_ids)
  {

    $opening_allocation_balance = 0;

    $this->read_db->select_sum('opening_allocation_balance_amount');
    $this->read_db->where_in('system_opening_balance.fk_office_id', $office_ids);
    $this->read_db->where_in('project_id', $project_ids);
    $this->read_db->join('system_opening_balance', 'system_opening_balance.system_opening_balance_id=opening_allocation_balance.fk_system_opening_balance_id');
    $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=opening_allocation_balance.fk_project_allocation_id');
    $this->read_db->join('project', 'project.project_id=project_allocation.fk_project_id');
    $opening_allocation_balance_obj = $this->read_db->get('opening_allocation_balance');

    if ($opening_allocation_balance_obj->num_rows() > 0) {
      $opening_allocation_balance = $opening_allocation_balance_obj->row()->opening_allocation_balance_amount;
    }

    return $opening_allocation_balance;
  }

  function _projects_opening_balances($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
  {
    $system_opening_balance = $this->_project_allocation_system_opening_balance($office_ids, $reporting_month, $project_ids, $office_bank_ids); ////$this->_projects_allocation_target($office_ids,$project_ids,$office_bank_ids);
    $projects_previous_months_expense_to_date = $this->_projects_previous_months_expense_to_date($office_ids, $reporting_month, $project_ids, $office_bank_ids);
    $projects_previous_months_income_to_date = $this->_projects_previous_months_income_to_date($office_ids, $reporting_month, $project_ids, $office_bank_ids);;

    $opening_balance = ($system_opening_balance + $projects_previous_months_income_to_date) - $projects_previous_months_expense_to_date;

    return $opening_balance;
  }

  function _projects_previous_months_income_to_date($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
  {
    $start_of_reporting_month = date('Y-m-01', strtotime($reporting_month));

    $this->read_db->select_sum('voucher_detail_total_cost');
    $this->read_db->where(array('voucher_type_effect_code' => 'income'));
    $this->read_db->where(array('voucher_date<' => $start_of_reporting_month));
    $this->read_db->where_in('voucher.fk_office_id', $office_ids);

    $this->read_db->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
    $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
    $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');

    if (!empty($project_ids)) {
      $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');
      $this->read_db->where_in('project_allocation.fk_project_id', $project_ids);
    }

    if (!empty($office_bank_ids)) {
      $this->read_db->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_project_allocation_id=project_allocation.project_allocation_id');
      $this->read_db->where_in('office_bank_project_allocation.fk_office_bank_id', $office_bank_ids);
    }

    $voucher_detail_total_cost = $this->read_db->get('voucher_detail')->row()->voucher_detail_total_cost;

    return $voucher_detail_total_cost;
  }

  function _projects_previous_months_expense_to_date($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
  {

    $start_of_reporting_month = date('Y-m-01', strtotime($reporting_month));

    $this->read_db->select_sum('voucher_detail_total_cost');
    $this->read_db->where(array('voucher_type_effect_code' => 'expense'));
    $this->read_db->where(array('voucher_date<' => $start_of_reporting_month));
    $this->read_db->where_in('voucher.fk_office_id', $office_ids);

    $this->read_db->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
    $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
    $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');

    if (!empty($project_ids)) {
      $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');
      $this->read_db->where_in('project_allocation.fk_project_id', $project_ids);
    }

    if (!empty($office_bank_ids)) {
      $this->read_db->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_project_allocation_id=project_allocation.project_allocation_id');
      $this->read_db->where_in('office_bank_project_allocation.fk_office_bank_id', $office_bank_ids);
    }

    $voucher_detail_total_cost = $this->read_db->get('voucher_detail')->row()->voucher_detail_total_cost;

    return $voucher_detail_total_cost;
  }

  function _projects_month_expense($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
  {

    $start_date_of_reporting_month = date('Y-m-01', strtotime($reporting_month));
    $end_date_of_reporting_month = date('Y-m-t', strtotime($reporting_month));

    $this->read_db->select_sum('voucher_detail_total_cost');
    $this->read_db->where(array('voucher_type_effect_code' => 'expense'));
    $this->read_db->where_in('voucher.fk_office_id', $office_ids);
    $this->read_db->where(array('voucher.voucher_date>=' => $start_date_of_reporting_month, 'voucher.voucher_date<=' => $end_date_of_reporting_month));

    $this->read_db->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
    $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
    $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');


    if (!empty($project_ids)) {
      $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');
      $this->read_db->where_in('project_allocation.fk_project_id', $project_ids);
    }

    if (!empty($office_bank_ids)) {
      $this->read_db->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_project_allocation_id=project_allocation.project_allocation_id');
      $this->read_db->where_in('office_bank_project_allocation.fk_office_bank_id', $office_bank_ids);
    }

    $voucher_detail_total_cost = $this->read_db->get('voucher_detail')->row()->voucher_detail_total_cost;

    return $voucher_detail_total_cost;
  }

  //get income_code
  function get_project_income_account(int $project_id)
  {
    // SELECT income_account_code FROM income_account ia
    // JOIN project_income_account pi ON pi.fk_income_account_id=ia.income_account_id
    // JOIN project p ON p.project_id=pi.fk_project_id
    // where fk_project_id=7

    //$this->
  }

  function _office_projects($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
  {
    $start_date_of_reporting_month = date('Y-m-01', strtotime($reporting_month));
    $this->read_db->select(array('project_id', 'project_name', 'funder_name', 'project_allocation.fk_office_id', 'project_allocation_amount'));
    $this->read_db->where_in('project_allocation.fk_office_id', $office_ids);
    $query_condition = "(project_start_date <= '" . $start_date_of_reporting_month . "' AND project_end_date IS NOT NULL AND project_end_date NOT LIKE '0000-00-00')";
    $this->read_db->where($query_condition);
    // Only list non default projects. There can be only 1 default project per accouting system
    $this->read_db->where(array('project_is_default' => 0));
    $this->read_db->join('project', 'project.project_id=project_allocation.fk_project_id');

    if (!empty($project_ids)) {
      $this->read_db->where_in('project_id', $project_ids);
    }

    if (!empty($office_bank_ids)) {
      $this->read_db->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_project_allocation_id=project_allocation.project_allocation_id');
      $this->read_db->where_in('office_bank_project_allocation.fk_office_bank_id', $office_bank_ids);
    }

    $this->read_db->join('funder', 'funder.funder_id=project.fk_funder_id');
    $projects = $this->read_db->get('project_allocation')->result_array();

    $ordered_array = [];

    foreach ($projects as $project) {
      $ordered_array[$project['project_id']]['project_name'] = $project['project_name'];
      $ordered_array[$project['project_id']]['funder_name'] = $project['funder_name'];
      $ordered_array[$project['project_id']]['office_id'] = $project['fk_office_id'];
      $ordered_array[$project['project_id']]['project_allocation_amount'] = $project['project_allocation_amount'];
    }

    //print_r($ordered_array);exit;

    return $ordered_array;
  }

  function update_bank_statement_balance()
  {

    $post = $this->input->post();

    $financial_report_obj = $this->read_db->get_where(
      'financial_report',
      array(
        'fk_office_id' => $post['office_id'],
        'financial_report_month' => date('Y-m-01', strtotime($post['reporting_month']))
      )
    );

    $this->write_db->trans_start();

    $this->write_db->where(array('financial_report_id' => $financial_report_obj->row()->financial_report_id));
    //$update_financial_report_data['financial_report_statement_balance'] = $post['bank_statement_balance'];
    $update_financial_report_data['financial_report_statement_date'] = $post['statement_date'];
    $this->write_db->update('financial_report', $update_financial_report_data);

    $this->write_db->trans_complete();

    if ($this->write_db->trans_status() == false) {
      echo "Update failed";
    } else {
      echo "Updated successful";
    }
  }
  // Added by Onduso on 2/8/2022


  function get_opening_oustanding_cheque($cheque_id)
  {

    $bounced_chq_record = $this->financial_report_model->get_opening_oustanding_cheque($cheque_id);


    echo $bounced_chq_record;
  }

  function update_bank_support_funds_and_oustanding_cheque_opening_balances($office_bank_id, $cheque_id, $reporting_month, $bounced_flag)
  {

    echo $this->financial_report_model->update_bank_support_funds_and_oustanding_cheque_opening_balances($office_bank_id, $cheque_id, $reporting_month, $bounced_flag);
  }


  function clear_transactions()
  {
    $post = $this->input->post();

    $this->write_db->trans_start();

    if (isset($post['opening_deposit_transit_id']) && $post['opening_deposit_transit_id'] > 0) {

      $update_data['opening_deposit_transit_is_cleared'] = 1;
      $update_data['opening_deposit_transit_cleared_date'] = date('Y-m-t', strtotime($post['reporting_month'])); //date('Y-m-t');

      if ($post['voucher_state'] == 1) {
        $update_data['opening_deposit_transit_is_cleared'] = 0;
        $update_data['opening_deposit_transit_cleared_date'] = null;
      }

      $this->write_db->where(array('opening_deposit_transit_id' => $post['opening_deposit_transit_id']));
      $this->write_db->update('opening_deposit_transit', $update_data);
    } elseif (isset($post['opening_outstanding_cheque_id']) && $post['opening_outstanding_cheque_id'] > 0) {
      $update_data['opening_outstanding_cheque_is_cleared'] = 1;
      $update_data['opening_outstanding_cheque_cleared_date'] = date('Y-m-t', strtotime($post['reporting_month'])); //date('Y-m-t');

      if ($post['voucher_state'] == 1) {
        $update_data['opening_outstanding_cheque_is_cleared'] = 0;
        $update_data['opening_outstanding_cheque_cleared_date'] = NULL; //'0000-00-00';
        $update_data['opening_outstanding_cheque_bounced_flag'] = 0;
      }

      $this->write_db->where(array('opening_outstanding_cheque_id' => $post['opening_outstanding_cheque_id']));
      $this->write_db->update('opening_outstanding_cheque', $update_data);
    } else {
      $update_data['voucher_cleared'] = 1;
      $update_data['voucher_cleared_month'] = date('Y-m-t', strtotime($post['reporting_month'])); //date('Y-m-t');

      if ($post['voucher_state'] == 1) {
        $update_data['voucher_cleared'] = 0;
        $update_data['voucher_cleared_month'] = null;
      }

      $this->write_db->where(array('voucher_id' => $post['voucher_id']));

      $this->write_db->update('voucher', $update_data);
    }

    $this->write_db->trans_complete();

    if ($this->write_db->trans_status() == false) {
      echo false;
    } else {
      echo true;
    }
  }

  // function create__missing_reconciliation(){

  // }

  function upload_statements()
  {

    $post = $this->input->post();

    $office_banks = explode(",", $post['office_bank_ids']);


    // Check if a reconciliation record exists, if not create it
    $this->read_db->join('financial_report', 'financial_report.financial_report_id=reconciliation.fk_financial_report_id');
    $this->read_db->where(array('reconciliation.fk_office_bank_id' => $office_banks[0]));
    $reconciliation_obj = $this->read_db->get_where(
      'reconciliation',
      array(
        'financial_report.fk_office_id' => $post['office_id'],
        'financial_report_month' => $post['reporting_month']
      )
    );

    // echo json_encode($reconciliation_obj->num_rows()); 

    if ($reconciliation_obj->num_rows() == 0) {
      // Create a reconciliation record
      //$financial_report_id,$office_bank_id,$statement_balance = 0, $suspense_amount = 0

      $financial_report_id = $this->read_db->get_where(
        'financial_report',
        array('fk_office_id' => $post['office_id'], 'financial_report_month' => $post['reporting_month'])
      )->row()->financial_report_id;

      $this->insert_reconciliation($financial_report_id, $office_banks[0]);
    }

    $result = [];

    if (count($office_banks) == 1) {
      $this->read_db->join('financial_report', 'financial_report.financial_report_id=reconciliation.fk_financial_report_id');
      $this->read_db->where(array('reconciliation.fk_office_bank_id' => $office_banks[0]));
      $reconciliation_id = $this->read_db->get_where(
        'reconciliation',
        array(
          'fk_office_id' => $post['office_id'],
          'financial_report_month' => $post['reporting_month']
        )
      )->row()->reconciliation_id;


      $storeFolder = upload_url('reconciliation', $reconciliation_id, [$office_banks[0]]);

      if (
        is_array($this->attachment_model->upload_files($storeFolder)) &&
        count($this->attachment_model->upload_files($storeFolder)) > 0
      ) {
        $result = $this->attachment_model->upload_files($storeFolder);
      }
    }

    echo json_encode($result);
  }

  function delete_statement()
  {
    $path = $this->input->post('path');

    $msg = "File deletion failed";

    if (file_exists($path)) {
      if (unlink($path)) {
        $msg = "File deleted successful";
      }
    }

    echo $msg;
  }

  function reverse_mfr_submission($report_id)
  {

    $success = get_phrase("financial_report_not_declined");

    $data['financial_report_is_submitted'] = 0;
    $this->write_db->where(array('financial_report_id' => $report_id));
    $this->write_db->update('financial_report', $data);

    if ($this->write_db->affected_rows() > 0) {
      $success = get_phrase("financial_report_declined");
    }

    echo $success;
  }

  // function approve()
  // {

  //   //$message = 1;


  //   //if ($this->submit_financial_report($post) == 1) {
  //   parent::approve();
  //   //} else {
  //   $message = $this->submit_financial_report();
  //   //}

  //   echo json_encode($this->input->post());
  // }

  // function submit_financial_report()
  // {

  //   $this->load->model('cheque_book_model');

  //   $post = $this->input->post();

  //   $message = 1; //'MFR Submitted Successful';

  //   // Check of Proof Of Cash
  //   $is_proof_of_cash_correct = $this->is_proof_of_cash_correct($post['office_id'], $post['reporting_month']);

  //   // Check if the report has reconciled
  //   $report_reconciled = $this->_check_if_report_has_reconciled($post['office_id'], $post['reporting_month']);


  //   // Check if the all vouchers have been approved
  //   $vouchers_approved = $this->_check_if_month_vouchers_are_approved($post['office_id'], $post['reporting_month']);

  //   // // Check if their is a bank statement
  //   $bank_statements_uploaded = $this->_check_if_bank_statements_are_uploaded($post['office_id'], $post['reporting_month']);

  //   $budget_is_active = $this->check_if_budget_is_active($post['office_id'], $post['reporting_month']);

  //   if ((!$report_reconciled  || !$is_proof_of_cash_correct || !$vouchers_approved || !$bank_statements_uploaded || !$budget_is_active) && !$this->config->item('submit_mfr_without_controls')) {
  //     $message = "You have missing requirements and report is not submitted. Check the following items:\n";
  //     $items = "";

  //     if (!$is_proof_of_cash_correct) $items .= "-> Proof of Cash is correct\n";
  //     if (!$report_reconciled) $items .= "-> Report is reconciled\n";
  //     if (!$vouchers_approved) $items .= "-> All vouchers in the month are approved or journal is not empty\n";
  //     if (!$bank_statements_uploaded) $items .= "-> Bank statement uploaded\n";
  //     if (!$budget_is_active) $items .= "-> The current period budget for the office must be active\n";

  //     $message .= $items;
  //   } else {

  //     $office_id = $post['office_id'];
  //     $reporting_month = $post['reporting_month'];

  //     // Get next status Id
  //     $financial_report_information = $this->financial_report_information($post['financial_report_id']);
  //     $next_status_id = $this->general_model->next_status($financial_report_information['status_id']);
    
  //     // Log Fund Balances
  //     $this->fund_balance_summary_report($office_id, $reporting_month);
      
  //   }

  //   echo $message;
  // }


  // function fund_balance_summary_report($office_id, $reporting_month)
  // {
    
  //   $update_data = [];

  //   // Check if a financial report exists for the month given and is submitted
  //   $condition = array('fk_office_id' => $office_id, 'financial_report_month' => $reporting_month);
  //   $this->read_db->where($condition);
  //   $this->read_db->where(array('financial_report_is_submitted' => 1));
  //   $count_financial_report = $this->read_db->get('financial_report')->num_rows();

  //   if ($count_financial_report > 0) {
  //     $fund_balances = $this->_fund_balance_report([$office_id], $reporting_month);
  //     $income_account_ids = array_column($fund_balances, 'account_id');
  //     $fund_balance_amount = array_column($fund_balances, 'month_closing_balance');
  //     $fund_closing_balances = array_combine($income_account_ids, $fund_balance_amount);
  //     $this->removeZeroBalances($fund_closing_balances);

  //     $fund_balance_report = [];
  //     $cnt = 0;
  //     foreach ($fund_balances as $fund_balance) {
  //       $fund_balance_report[$cnt]['account_id'] = $fund_balance['account_id'];
  //       $fund_balance_report[$cnt]['month_opening_balance'] = $fund_balance['month_opening_balance'];
  //       $fund_balance_report[$cnt]['month_income'] = $fund_balance['month_income'];
  //       $fund_balance_report[$cnt]['month_expense'] = $fund_balance['month_expense'];
  //       $fund_balance_report[$cnt]['month_closing_balance'] = $fund_balance['month_closing_balance'];
  //       $cnt++;
  //     }

  //     // Log Project Balances
  //     $project_balances = $this->_projects_balance_report([$office_id], $reporting_month)['body'];
  //     $project_ids = array_keys($project_balances);
  //     $project_balance_amount = array_column($project_balances, 'closing_balance');
  //     $project_closing_balances = array_combine($project_ids, $project_balance_amount);
  //     $this->removeZeroBalances($project_closing_balances);

  //     $total_cash_balance = $this->_proof_of_cash([$office_id], $reporting_month);

  //     // Log Total Statement Balances
  //     $bank_reconciliation = $this->_bank_reconciliation([$office_id], $reporting_month, false, false);
  //     $statement_balance = ['bank_statement_date' => $bank_reconciliation['bank_statement_date'], 'bank_statement_balance' => $bank_reconciliation['bank_statement_balance']];

  //     $financial_report_is_reconciled = $bank_reconciliation['is_book_reconciled'] == 'true' || $bank_reconciliation['is_book_reconciled'] == true ? 1 : 0;


  //     // Log Outstanding cheques Balances
  //     $outstanding_cheques = $this->financial_report_model->list_oustanding_cheques_and_deposits([$office_id], $reporting_month, 'expense', 'bank_contra', 'bank');
  //     $outstanding_cheques_balance = [];
  //     $overdue_outstanding_cheques = [];

  //     // log_message('error', json_encode($outstanding_cheques));

  //     if (!empty($outstanding_cheques)) {
  //       $cnt = 0;
  //       foreach ($outstanding_cheques as $outstanding_cheque) {
  //         $outstanding_cheques_balance[$cnt]['voucher_id'] = isset($outstanding_cheque['voucher_id']) ? $outstanding_cheque['voucher_id'] : NULL;
  //         $outstanding_cheques_balance[$cnt]['voucher_date'] = $outstanding_cheque['voucher_date'];
  //         $outstanding_cheques_balance[$cnt]['voucher_number'] = isset($outstanding_cheque['voucher_number']) ? $outstanding_cheque['voucher_number'] : NULL;
  //         $outstanding_cheques_balance[$cnt]['cheque_number'] = $outstanding_cheque['voucher_cheque_number'];
  //         $outstanding_cheques_balance[$cnt]['description'] = $outstanding_cheque['voucher_description'];
  //         $outstanding_cheques_balance[$cnt]['office_bank_id'] = $outstanding_cheque['fk_office_bank_id'];
  //         // $outstanding_cheques_balance[$cnt]['office_bank_name'] = $outstanding_cheque['office_bank_name'];
  //         $outstanding_cheques_balance[$cnt]['amount'] = $outstanding_cheque['voucher_detail_total_cost'];

  //         $voucher_date = strtotime($outstanding_cheque['voucher_date']);
  //         $reportingTimestamp = strtotime($reporting_month);

  //         // Calculate the difference in seconds between the two dates
  //         $timeDifference = $reportingTimestamp - $voucher_date;

  //         // Number of seconds in 6 months (approximately)
  //         $sixMonthsInSeconds = 15778800;

  //         if ($timeDifference >= $sixMonthsInSeconds) {
  //           $overdue_outstanding_cheques[$cnt] = $outstanding_cheques_balance[$cnt];
  //         }

  //         $cnt++;
  //       }
  //     }

  //     // Log Transit Deposit Balances
  //     $transit_deposits = $this->financial_report_model->list_oustanding_cheques_and_deposits([$office_id], $reporting_month, 'income', 'cash_contra', 'bank');
  //     $transit_deposit_balance = [];
  //     $overdue_transit_deposit = [];

  //     if (!empty($transit_deposits)) {
  //       $cnt = 0;
  //       foreach ($transit_deposits as $transit_deposit) {
  //         $transit_deposit_balance[$cnt]['voucher_id'] = isset($transit_deposit['voucher_id']) ? $transit_deposit['voucher_id'] : NULL;
  //         $transit_deposit_balance[$cnt]['voucher_date'] = $transit_deposit['voucher_date'];
  //         $transit_deposit_balance[$cnt]['voucher_number'] = isset($transit_deposit['voucher_number']) ? $transit_deposit['voucher_number'] : NULL;
  //         $transit_deposit_balance[$cnt]['description'] = $transit_deposit['voucher_description'];
  //         $transit_deposit_balance[$cnt]['office_bank_id'] = $transit_deposit['fk_office_bank_id'];
  //         // $transit_deposit_balance[$cnt]['office_bank_name'] = $outstanding_cheque['office_bank_name'];
  //         $transit_deposit_balance[$cnt]['amount'] = $transit_deposit['voucher_detail_total_cost'];

  //         if(!empty($outstanding_cheque)){
  //           $voucher_date = strtotime($outstanding_cheque['voucher_date']);
  //           $reportingTimestamp = strtotime($reporting_month);
  
  //           // Calculate the difference in seconds between the two dates
  //           $timeDifference = $reportingTimestamp - $voucher_date;
  
  //           // Number of seconds in 6 months (approximately)
  //           $twoMonthsInSeconds = 5256192;
  
  //           if ($timeDifference >= $twoMonthsInSeconds) {
  //             $overdue_transit_deposit[$cnt] = $transit_deposit_balance[$cnt];
  //           }
  //         }

  //         $cnt++;
  //       }
  //     }

  //     // Log Expense Report Balances
  //     $expense_report = $this->_expense_report([$office_id], $reporting_month);
  //     $expense_report_balance = [];

  //     if (!empty($expense_report)) {
  //       $cnt = 0;
  //       foreach ($expense_report as $report) {
  //         if (!isset($report['income_account'])) continue;
  //         $expense_report_balance[$cnt]['income_account_id'] = $report['income_account']['income_account_id'];
  //         $inner = 0;
  //         foreach ($report['expense_accounts'] as $expense_account) {
  //           $expense_report_balance[$cnt]['expense_report'][$inner]['expense_account_id'] = $expense_account['expense_account']['expense_account_id'];
  //           $expense_report_balance[$cnt]['expense_report'][$inner]['month_expense'] = $expense_account['month_expense'];
  //           $expense_report_balance[$cnt]['expense_report'][$inner]['month_expense_to_date'] = $expense_account['month_expense_to_date'];
  //           $expense_report_balance[$cnt]['expense_report'][$inner]['budget_to_date'] = $expense_account['budget_to_date'];
  //           $expense_report_balance[$cnt]['expense_report'][$inner]['budget_variance'] = $expense_account['budget_to_date'] - $expense_account['month_expense_to_date'];
  //           $expense_report_balance[$cnt]['expense_report'][$inner]['budget_variance_percent'] = $expense_account['budget_to_date'] > 0 ? (($expense_account['budget_to_date'] - $expense_account['month_expense_to_date']) / $expense_account['budget_to_date']) * 100 : -100;
  //           $inner++;
  //         }
  //         $cnt++;
  //       }
  //     }
  //     $this->write_db->trans_start();
  //     // Update financial report table

  //     $this->load->model('budget_model');
  //     $current_budget = $this->budget_model->get_a_budget_by_office_current_transaction_date($office_id);

  //     $update_data['financial_report_is_submitted'] = 1;
  //     $update_data['closing_fund_balance_data'] = json_encode($fund_closing_balances);
  //     $update_data['closing_project_balance_data'] = json_encode($project_closing_balances);
  //     $update_data['closing_total_cash_balance_data'] = json_encode($total_cash_balance);
  //     $update_data['closing_total_statement_balance_data'] = json_encode($statement_balance);
  //     $update_data['closing_outstanding_cheques_data'] = json_encode($outstanding_cheques_balance);
  //     $update_data['closing_transit_deposit_data'] = json_encode($transit_deposit_balance);
  //     $update_data['closing_expense_report_data'] = json_encode($expense_report_balance);
  //     $update_data['closing_overdue_cheques_data'] = json_encode($overdue_outstanding_cheques);
  //     $update_data['closing_overdue_deposit_data'] = json_encode($overdue_transit_deposit);
  //     $update_data['financial_report_is_reconciled'] = $financial_report_is_reconciled;
  //     $update_data['month_fund_balance_report_data'] = json_encode($fund_balance_report);
  //     $update_data['financial_report_submitted_date'] = date('Y-m-d');
  //     // $update_data['fk_budget_id'] = $current_budget['budget_id'];
  //     $update_data['fk_status_id'] = $next_status_id;
  //     $update_data['fk_budget_id'] = isset($current_budget['budget_id']) ? $current_budget['budget_id'] : NULL;
  //     // log_message('error', json_encode($update_data));

  //     $this->write_db->where($condition);
  //     $this->write_db->update('financial_report', $update_data);


  //     // Deactivate non default cheque book
  //     $this->load->model('office_bank_model');
  //     $this->office_bank_model->deactivate_non_default_office_bank_by_office_id($office_id, $post['reporting_month']);

  //     if (method_exists($this->financial_report_model, 'post_approval_action_event')) {
  //       $this->financial_report_model->post_approval_action_event([
  //         'item' => 'financial_report',
  //         'post' => [
  //           'item_id' => $post['financial_report_id'],
  //           'next_status' => $next_status_id,
  //           'current_status' => $financial_report_information['status_id']
  //         ]
  //       ]);
  //     }

  //     $this->write_db->trans_complete();

  //     if ($this->write_db->trans_status() === FALSE)
  //     {
        
  //     }

  //     //parent::approve();
  //     // log_message('error', json_encode($update_data));      
  //   }

  //   return $update_data;
  // }

  // function submit_financial_report()
  // {

  //   $post = $this->input->post();

  //   $message = 1; //'MFR Submitted Successful';

  //   // Check of Proof Of Cash
  //   $is_proof_of_cash_correct = $this->is_proof_of_cash_correct($post['office_id'], $post['reporting_month']);

  //   // Check if the report has reconciled
  //   $report_reconciled = $this->_check_if_report_has_reconciled($post['office_id'], $post['reporting_month']);


  //   // Check if the all vouchers have been approved
  //   $vouchers_approved = $this->_check_if_month_vouchers_are_approved($post['office_id'], $post['reporting_month']);

  //   // // Check if their is a bank statement
  //   $bank_statements_uploaded = $this->_check_if_bank_statements_are_uploaded($post['office_id'], $post['reporting_month']);

  //   $budget_is_active = $this->check_if_budget_is_active($post['office_id'], $post['reporting_month']);

  //   if ((!$report_reconciled  || !$is_proof_of_cash_correct || !$vouchers_approved || !$bank_statements_uploaded || !$budget_is_active) && !$this->config->item('submit_mfr_without_controls')) {
  //     $message = "You have missing requirements and report is not submitted. Check the following items:\n";
  //     $items = "";

  //     if (!$is_proof_of_cash_correct) $items .= "-> Proof of Cash is correct\n";
  //     if (!$report_reconciled) $items .= "-> Report is reconciled\n";
  //     if (!$vouchers_approved) $items .= "-> All vouchers in the month are approved or journal is not empty\n";
  //     if (!$bank_statements_uploaded) $items .= "-> Bank statement uploaded\n";
  //     if (!$budget_is_active) $items .= "-> The current period budget for the office must be active\n";

  //     $message .= $items;
  //   } else {

  //     $office_id = $post['office_id'];
  //     $reporting_month = $post['reporting_month'];

  //     $this->fund_balance_summary_report($office_id, $reporting_month);
      
  //   }

  //   echo $message;
  // }


  // function fund_balance_summary_report($office_id, $reporting_month)
  // {
    
  //   $update_data = [];

  //   // Check if a financial report exists for the month given and is submitted
  //   $condition = array('fk_office_id' => $office_id, 'financial_report_month' => $reporting_month);
  //   $this->read_db->where($condition);
  //   $this->read_db->where(array('financial_report_is_submitted' => 1));
  //   $count_financial_report = $this->read_db->get('financial_report')->num_rows();

  //   if ($count_financial_report > 0) {
  //     $fund_balances = $this->_fund_balance_report([$office_id], $reporting_month);
  //     $income_account_ids = array_column($fund_balances, 'account_id');
  //     $fund_balance_amount = array_column($fund_balances, 'month_closing_balance');
  //     $fund_closing_balances = array_combine($income_account_ids, $fund_balance_amount);
  //     $this->removeZeroBalances($fund_closing_balances);

  //     $fund_balance_report = [];
  //     $cnt = 0;
  //     foreach ($fund_balances as $fund_balance) {
  //       $fund_balance_report[$cnt]['account_id'] = $fund_balance['account_id'];
  //       $fund_balance_report[$cnt]['month_opening_balance'] = $fund_balance['month_opening_balance'];
  //       $fund_balance_report[$cnt]['month_income'] = $fund_balance['month_income'];
  //       $fund_balance_report[$cnt]['month_expense'] = $fund_balance['month_expense'];
  //       $fund_balance_report[$cnt]['month_closing_balance'] = $fund_balance['month_closing_balance'];
  //       $cnt++;
  //     }

  //     // Log Project Balances
  //     $project_balances = $this->_projects_balance_report([$office_id], $reporting_month)['body'];
  //     $project_ids = array_keys($project_balances);
  //     $project_balance_amount = array_column($project_balances, 'closing_balance');
  //     $project_closing_balances = array_combine($project_ids, $project_balance_amount);
  //     $this->removeZeroBalances($project_closing_balances);

  //     $total_cash_balance = $this->_proof_of_cash([$office_id], $reporting_month);

  //     // Log Total Statement Balances
  //     $bank_reconciliation = $this->_bank_reconciliation([$office_id], $reporting_month, false, false);
  //     $statement_balance = ['bank_statement_date' => $bank_reconciliation['bank_statement_date'], 'bank_statement_balance' => $bank_reconciliation['bank_statement_balance']];

  //     $financial_report_is_reconciled = $bank_reconciliation['is_book_reconciled'] == 'true' || $bank_reconciliation['is_book_reconciled'] == true ? 1 : 0;


  //     // Log Outstanding cheques Balances
  //     $outstanding_cheques = $this->financial_report_model->list_oustanding_cheques_and_deposits([$office_id], $reporting_month, 'expense', 'bank_contra', 'bank');
  //     $outstanding_cheques_balance = [];
  //     $overdue_outstanding_cheques = [];

  //     // log_message('error', json_encode($outstanding_cheques));

  //     if (!empty($outstanding_cheques)) {
  //       $cnt = 0;
  //       foreach ($outstanding_cheques as $outstanding_cheque) {
  //         $outstanding_cheques_balance[$cnt]['voucher_id'] = isset($outstanding_cheque['voucher_id']) ? $outstanding_cheque['voucher_id'] : NULL;
  //         $outstanding_cheques_balance[$cnt]['voucher_date'] = $outstanding_cheque['voucher_date'];
  //         $outstanding_cheques_balance[$cnt]['voucher_number'] = isset($outstanding_cheque['voucher_number']) ? $outstanding_cheque['voucher_number'] : NULL;
  //         $outstanding_cheques_balance[$cnt]['cheque_number'] = $outstanding_cheque['voucher_cheque_number'];
  //         $outstanding_cheques_balance[$cnt]['description'] = $outstanding_cheque['voucher_description'];
  //         $outstanding_cheques_balance[$cnt]['office_bank_id'] = $outstanding_cheque['fk_office_bank_id'];
  //         // $outstanding_cheques_balance[$cnt]['office_bank_name'] = $outstanding_cheque['office_bank_name'];
  //         $outstanding_cheques_balance[$cnt]['amount'] = $outstanding_cheque['voucher_detail_total_cost'];

  //         $voucher_date = strtotime($outstanding_cheque['voucher_date']);
  //         $reportingTimestamp = strtotime($reporting_month);

  //         // Calculate the difference in seconds between the two dates
  //         $timeDifference = $reportingTimestamp - $voucher_date;

  //         // Number of seconds in 6 months (approximately)
  //         $sixMonthsInSeconds = 15778800;

  //         if ($timeDifference >= $sixMonthsInSeconds) {
  //           $overdue_outstanding_cheques[$cnt] = $outstanding_cheques_balance[$cnt];
  //         }

  //         $cnt++;
  //       }
  //     }

  //     // Log Transit Deposit Balances
  //     $transit_deposits = $this->financial_report_model->list_oustanding_cheques_and_deposits([$office_id], $reporting_month, 'income', 'cash_contra', 'bank');
  //     $transit_deposit_balance = [];
  //     $overdue_transit_deposit = [];

  //     if (!empty($transit_deposits)) {
  //       $cnt = 0;
  //       foreach ($transit_deposits as $transit_deposit) {
  //         $transit_deposit_balance[$cnt]['voucher_id'] = isset($transit_deposit['voucher_id']) ? $transit_deposit['voucher_id'] : NULL;
  //         $transit_deposit_balance[$cnt]['voucher_date'] = $transit_deposit['voucher_date'];
  //         $transit_deposit_balance[$cnt]['voucher_number'] = isset($transit_deposit['voucher_number']) ? $transit_deposit['voucher_number'] : NULL;
  //         $transit_deposit_balance[$cnt]['description'] = $transit_deposit['voucher_description'];
  //         $transit_deposit_balance[$cnt]['office_bank_id'] = $transit_deposit['fk_office_bank_id'];
  //         // $transit_deposit_balance[$cnt]['office_bank_name'] = $outstanding_cheque['office_bank_name'];
  //         $transit_deposit_balance[$cnt]['amount'] = $transit_deposit['voucher_detail_total_cost'];

  //         if(!empty($outstanding_cheque)){
  //           $voucher_date = strtotime($outstanding_cheque['voucher_date']);
  //           $reportingTimestamp = strtotime($reporting_month);
  
  //           // Calculate the difference in seconds between the two dates
  //           $timeDifference = $reportingTimestamp - $voucher_date;
  
  //           // Number of seconds in 6 months (approximately)
  //           $twoMonthsInSeconds = 5256192;
  
  //           if ($timeDifference >= $twoMonthsInSeconds) {
  //             $overdue_transit_deposit[$cnt] = $transit_deposit_balance[$cnt];
  //           }
  //         }

  //         $cnt++;
  //       }
  //     }

  //     // Log Expense Report Balances
  //     $expense_report = $this->_expense_report([$office_id], $reporting_month);
  //     $expense_report_balance = [];

  //     if (!empty($expense_report)) {
  //       $cnt = 0;
  //       foreach ($expense_report as $report) {
  //         if (!isset($report['income_account'])) continue;
  //         $expense_report_balance[$cnt]['income_account_id'] = $report['income_account']['income_account_id'];
  //         $inner = 0;
  //         foreach ($report['expense_accounts'] as $expense_account) {
  //           $expense_report_balance[$cnt]['expense_report'][$inner]['expense_account_id'] = $expense_account['expense_account']['expense_account_id'];
  //           $expense_report_balance[$cnt]['expense_report'][$inner]['month_expense'] = $expense_account['month_expense'];
  //           $expense_report_balance[$cnt]['expense_report'][$inner]['month_expense_to_date'] = $expense_account['month_expense_to_date'];
  //           $expense_report_balance[$cnt]['expense_report'][$inner]['budget_to_date'] = $expense_account['budget_to_date'];
  //           $expense_report_balance[$cnt]['expense_report'][$inner]['budget_variance'] = $expense_account['budget_to_date'] - $expense_account['month_expense_to_date'];
  //           $expense_report_balance[$cnt]['expense_report'][$inner]['budget_variance_percent'] = $expense_account['budget_to_date'] > 0 ? (($expense_account['budget_to_date'] - $expense_account['month_expense_to_date']) / $expense_account['budget_to_date']) * 100 : -100;
  //           $inner++;
  //         }
  //         $cnt++;
  //       }
  //     }

  //     // Update financial report table

  //     $this->load->model('budget_model');
  //     $current_budget = $this->budget_model->get_a_budget_by_office_current_transaction_date($office_id);

  //     $update_data['financial_report_is_submitted'] = 1;
  //     $update_data['closing_fund_balance_data'] = json_encode($fund_closing_balances);
  //     $update_data['closing_project_balance_data'] = json_encode($project_closing_balances);
  //     $update_data['closing_total_cash_balance_data'] = json_encode($total_cash_balance);
  //     $update_data['closing_total_statement_balance_data'] = json_encode($statement_balance);
  //     $update_data['closing_outstanding_cheques_data'] = json_encode($outstanding_cheques_balance);
  //     $update_data['closing_transit_deposit_data'] = json_encode($transit_deposit_balance);
  //     $update_data['closing_expense_report_data'] = json_encode($expense_report_balance);
  //     $update_data['closing_overdue_cheques_data'] = json_encode($overdue_outstanding_cheques);
  //     $update_data['closing_overdue_deposit_data'] = json_encode($overdue_transit_deposit);
  //     $update_data['financial_report_is_reconciled'] = $financial_report_is_reconciled;
  //     $update_data['month_fund_balance_report_data'] = json_encode($fund_balance_report);
  //     $update_data['financial_report_submitted_date'] = date('Y-m-d');
  //     $update_data['fk_budget_id'] = isset($current_budget['budget_id']) ? $current_budget['budget_id'] : NULL;
  //     // log_message('error', json_encode($update_data));

  //     $this->write_db->where($condition);
  //     $this->write_db->update('financial_report', $update_data);

  //     // log_message('error', json_encode($update_data));      
  //   }

  //   return $update_data;
  // }

  function submit_financial_report()
  {

    $this->load->model('cheque_book_model');

    $post = $this->input->post();
    $post['financial_report_id'] = hash_id($post['financial_report_id'], 'encode');

    $message = 1; //'MFR Submitted Successful';

    // Check of Proof Of Cash
    $is_proof_of_cash_correct = $this->is_proof_of_cash_correct($post['office_id'], $post['reporting_month']);
    
     // Check if the report has reconciled
    $report_reconciled = $this->_check_if_report_has_reconciled($post['office_id'], $post['reporting_month']);

    
    // Check if the all vouchers have been approved
    $vouchers_approved = $this->_check_if_month_vouchers_are_approved($post['office_id'], $post['reporting_month']);

    // // Check if their is a bank statement
    $bank_statements_uploaded = $this->_check_if_bank_statements_are_uploaded($post['office_id'], $post['reporting_month']);

    $budget_is_active = $this->check_if_budget_is_active($post['office_id'], $post['reporting_month']);

    if ((!$report_reconciled  || !$is_proof_of_cash_correct || !$vouchers_approved || !$bank_statements_uploaded || !$budget_is_active) && !$this->config->item('submit_mfr_without_controls')) {
      $message = "You have missing requirements and report is not submitted. Check the following items:\n";
      $items = "";

      if (!$is_proof_of_cash_correct) $items .= "-> Proof of Cash is correct\n";
      if (!$report_reconciled) $items .= "-> Report is reconciled\n";
      if (!$vouchers_approved) $items .= "-> All vouchers in the month are approved or journal is not empty\n";
      if (!$bank_statements_uploaded) $items .= "-> Bank statement uploaded\n";
      if(!$budget_is_active) $items .= "-> The current period budget for the office must be active\n";

      $message .= $items;
    } else {

      $office_id = $post['office_id'];
      $reporting_month = $post['reporting_month'];

      // Get next status Id
      $financial_report_information = $this->financial_report_information($post['financial_report_id']);
      $next_status_id = $this->general_model->next_status($financial_report_information['status_id']);
    
      // Log Fund Balances
      $fund_balances = $this->_fund_balance_report([$office_id], $reporting_month);
      $income_account_ids = array_column($fund_balances,'account_id');
      $fund_balance_amount = array_column($fund_balances,'month_closing_balance');
      $fund_closing_balances = array_combine($income_account_ids,$fund_balance_amount);
      $this->removeZeroBalances($fund_closing_balances);

      //month_fund_income_data
      $fund_balance_report = [];
      $cnt = 0;
      foreach($fund_balances as $fund_balance){
        $fund_balance_report[$cnt]['account_id'] = $fund_balance['account_id'];
        $fund_balance_report[$cnt]['month_opening_balance'] = $fund_balance['month_opening_balance'];
        $fund_balance_report[$cnt]['month_income'] = $fund_balance['month_income'];
        $fund_balance_report[$cnt]['month_expense'] = $fund_balance['month_expense'];
        $fund_balance_report[$cnt]['month_closing_balance'] = $fund_balance['month_closing_balance'];
        $cnt++;
      }

      // Log Project Balances
      $project_balances = $this->_projects_balance_report([$office_id], $reporting_month)['body'];
      $project_ids = array_keys($project_balances);
      $project_balance_amount = array_column($project_balances,'closing_balance');
      $project_closing_balances = array_combine($project_ids,$project_balance_amount);
      $this->removeZeroBalances($project_closing_balances);

      // Log Total Cash Balances
      $total_cash_balance = $this->_proof_of_cash([$office_id], $reporting_month);

      // Log Total Statement Balances
      $bank_reconciliation = $this->_bank_reconciliation([$office_id], $reporting_month, false, false);
      $statement_balance = ['bank_statement_date' => $bank_reconciliation['bank_statement_date'], 'bank_statement_balance' => $bank_reconciliation['bank_statement_balance']];

      $financial_report_is_reconciled = $bank_reconciliation['is_book_reconciled'] == 'true' || $bank_reconciliation['is_book_reconciled'] == true ? 1 : 0;
      

      // Log Outstanding cheques Balances
      $outstanding_cheques = $this->financial_report_model->list_oustanding_cheques_and_deposits([$office_id], $reporting_month, 'expense', 'bank_contra', 'bank');
      $outstanding_cheques_balance = [];
      $overdue_outstanding_cheques = [];

      // log_message('error', json_encode($outstanding_cheques));

      if(!empty($outstanding_cheques)){
        $cnt = 0;
        foreach($outstanding_cheques as $outstanding_cheque){
          $outstanding_cheques_balance[$cnt]['voucher_id'] = isset($outstanding_cheque['voucher_id']) ? $outstanding_cheque['voucher_id'] : NULL;
          $outstanding_cheques_balance[$cnt]['voucher_date'] = $outstanding_cheque['voucher_date'];
          $outstanding_cheques_balance[$cnt]['voucher_number'] = isset($outstanding_cheque['voucher_number']) ? $outstanding_cheque['voucher_number'] : NULL;
          $outstanding_cheques_balance[$cnt]['cheque_number'] = $outstanding_cheque['voucher_cheque_number'];
          $outstanding_cheques_balance[$cnt]['description'] = $outstanding_cheque['voucher_description'];
          $outstanding_cheques_balance[$cnt]['office_bank_id'] = $outstanding_cheque['fk_office_bank_id'];
          // $outstanding_cheques_balance[$cnt]['office_bank_name'] = $outstanding_cheque['office_bank_name'];
          $outstanding_cheques_balance[$cnt]['amount'] = $outstanding_cheque['voucher_detail_total_cost'];

          $voucher_date = strtotime($outstanding_cheque['voucher_date']);
          $reportingTimestamp = strtotime($reporting_month);

          // Calculate the difference in seconds between the two dates
          $timeDifference = $reportingTimestamp - $voucher_date;

          // Number of seconds in 6 months (approximately)
          $sixMonthsInSeconds = 15778800;

          if($timeDifference >= $sixMonthsInSeconds){
            $overdue_outstanding_cheques[$cnt] = $outstanding_cheques_balance[$cnt];
          }

          $cnt++;
        }
      }

      // Log Transit Deposit Balances
      $transit_deposits = $this->financial_report_model->list_oustanding_cheques_and_deposits([$office_id], $reporting_month, 'income', 'cash_contra', 'bank');
      $transit_deposit_balance = [];
      $overdue_transit_deposit = [];

      if(!empty($transit_deposits)){
        $cnt = 0;
        foreach($transit_deposits as $transit_deposit){
          $transit_deposit_balance[$cnt]['voucher_id'] = isset($transit_deposit['voucher_id']) ? $transit_deposit['voucher_id'] : NULL;
          $transit_deposit_balance[$cnt]['voucher_date'] = $transit_deposit['voucher_date'];
          $transit_deposit_balance[$cnt]['voucher_number'] = isset($transit_deposit['voucher_number']) ? $transit_deposit['voucher_number'] : NULL;
          $transit_deposit_balance[$cnt]['description'] = $transit_deposit['voucher_description'];
          $transit_deposit_balance[$cnt]['office_bank_id'] = $transit_deposit['fk_office_bank_id'];
          // $transit_deposit_balance[$cnt]['office_bank_name'] = $outstanding_cheque['office_bank_name'];
          $transit_deposit_balance[$cnt]['amount'] = $transit_deposit['voucher_detail_total_cost'];

          $voucher_date = strtotime($outstanding_cheque['voucher_date']);
          $reportingTimestamp = strtotime($reporting_month);

          // Calculate the difference in seconds between the two dates
          $timeDifference = $reportingTimestamp - $voucher_date;

          // Number of seconds in 6 months (approximately)
          $twoMonthsInSeconds = 5256192;

          if($timeDifference >= $twoMonthsInSeconds){
            $overdue_transit_deposit[$cnt] = $transit_deposit_balance[$cnt];
          }

          $cnt++;
        }
      }

      // Log Expense Report Balances
      $expense_report = $this->_expense_report([$office_id], $reporting_month);
      $expense_report_balance = [];

      if(!empty($expense_report)){
        $cnt = 0;
        foreach($expense_report as $report){
          if(!isset($report['income_account'])) continue;
          $expense_report_balance[$cnt]['income_account_id'] = $report['income_account']['income_account_id'];
          $inner = 0;
          foreach($report['expense_accounts'] as $expense_account){
            $expense_report_balance[$cnt]['expense_report'][$inner]['expense_account_id'] = $expense_account['expense_account']['expense_account_id'];
            $expense_report_balance[$cnt]['expense_report'][$inner]['month_expense'] = $expense_account['month_expense'];
            $expense_report_balance[$cnt]['expense_report'][$inner]['month_expense_to_date'] = $expense_account['month_expense_to_date'];
            $expense_report_balance[$cnt]['expense_report'][$inner]['budget_to_date'] = $expense_account['budget_to_date'];
            $expense_report_balance[$cnt]['expense_report'][$inner]['budget_variance'] = $expense_account['budget_to_date'] - $expense_account['month_expense_to_date'];
            $expense_report_balance[$cnt]['expense_report'][$inner]['budget_variance_percent'] = $expense_account['budget_to_date'] > 0 ? (($expense_account['budget_to_date'] - $expense_account['month_expense_to_date'])/$expense_account['budget_to_date']) * 100 : -100;
            $inner++;
          }
          $cnt++;
        }
      }

       // Post all vouchers
       $this->load->model('journal_model');
       $month_vouchers = $this->journal_model->journal_records($post['office_id'], $post['reporting_month']); 

      $this->write_db->trans_start();
      // Update financial report table
      $this->write_db->where(array('fk_office_id' => $post['office_id'], 'financial_report_month' => $post['reporting_month']));

      $this->load->model('budget_model');
      $current_budget = $this->budget_model->get_a_budget_by_office_current_transaction_date($post['office_id']);
      
      $update_data['financial_report_is_submitted'] = 1;
      $update_data['closing_fund_balance_data'] = json_encode($fund_closing_balances);
      $update_data['closing_project_balance_data'] = json_encode($project_closing_balances);
      $update_data['closing_total_cash_balance_data'] = json_encode($total_cash_balance);
      $update_data['closing_total_statement_balance_data'] = json_encode($statement_balance);
      $update_data['closing_outstanding_cheques_data'] = json_encode($outstanding_cheques_balance);
      $update_data['closing_transit_deposit_data'] = json_encode($transit_deposit_balance);
      $update_data['closing_expense_report_data'] = json_encode($expense_report_balance);
      $update_data['closing_overdue_cheques_data'] = json_encode($overdue_outstanding_cheques);
      $update_data['closing_overdue_deposit_data'] = json_encode($overdue_transit_deposit);
      $update_data['financial_report_is_reconciled'] = $financial_report_is_reconciled;
      $update_data['month_fund_balance_report_data'] = json_encode($fund_balance_report);
      $update_data['month_vouchers'] = json_encode($month_vouchers);
      $update_data['financial_report_submitted_date'] = date('Y-m-d');
      $update_data['fk_budget_id'] = $current_budget['budget_id'];
      $update_data['fk_status_id'] = $next_status_id;
      // log_message('error', json_encode($update_data));

      $this->write_db->update('financial_report', $update_data);


      // Deactivate non default cheque book
      $this->load->model('office_bank_model');
      $this->office_bank_model->deactivate_non_default_office_bank_by_office_id($office_id, $post['reporting_month']);

      if (method_exists($this->financial_report_model, 'post_approval_action_event')) {
        $this->financial_report_model->post_approval_action_event([
          'item' => 'financial_report',
          'post' => [
            'item_id' => $post['financial_report_id'],
            'next_status' => $next_status_id,
            'current_status' => $financial_report_information['status_id']
          ]
        ]);
      }

      $this->write_db->trans_complete();

      if ($this->write_db->trans_status() === FALSE)
      {
        
      }

      //parent::approve();
    }

    echo $message;
  }
  function mass_update_financial_review_data_by_account_system($account_system_id, $reporting_month)
  {
    // Get FCPs for an accounting system that have submitted MFR for the period given
    $this->read_db->select(array('office_id'));
    $this->read_db->where(array('office.fk_account_system_id' => $account_system_id, 
    'financial_report_month' => date('Y-m-01', strtotime($reporting_month))));
    $this->read_db->join('financial_report','financial_report.fk_office_id=office.office_id');
    $offices_obj = $this->read_db->get('office');

    if($offices_obj->num_rows() > 0){
      $office_ids = array_column($offices_obj->result_array(),'office_id');

      foreach($office_ids as $office_id){
        $this->fund_balance_summary_report($office_id, $reporting_month);
      }
    }
  }

  /**
   * removeZeroBalances
   * 
   * Filter balance amounts that are not zero. This method is called by reference
   *
   * @author nkarisa <nkarisa@ke.ci.org> 
   * @param array $balances - Raw list of balances
   * 
   * @return void
   */
  private function removeZeroBalances(&$balances)
  {
    foreach ($balances as $account_id => $amount) {
      if ($amount == 0) {
        unset($balances[$account_id]);
      }
    }
  }

  /**
   * is_proof_of_cash_correct: Check if the proof of cash is correct before submitting a financial report
   * 
   * @author Nicodemus Karisa Mwambire
   * @reviewer None
   * @reviewed_date None
   * @access private
   * 
   * @param $is_proof_of_cash_correct
   */

  private function is_proof_of_cash_correct($office_id, $reporting_month, $project_ids = [], $office_bank_ids = []): bool
  {

    $fund_balance_report = $this->_fund_balance_report([$office_id], $reporting_month, $project_ids, $office_bank_ids);

    $sum_month_opening_balance = array_sum(array_column($fund_balance_report, 'month_opening_balance'));
    $sum_month_income = array_sum(array_column($fund_balance_report, 'month_income'));
    $sum_month_expense = array_sum(array_column($fund_balance_report, 'month_expense'));

    $total_closing_fund_balance = $sum_month_opening_balance + $sum_month_income - $sum_month_expense;

    $total_cash = array_sum($this->_proof_of_cash([$office_id], $reporting_month, $project_ids, $office_bank_ids));

    $total_closing_fund_balance = $this->truncate($total_closing_fund_balance, 0);

    $total_cash = $this->truncate($total_cash, 0);

    // log_message('error', json_encode(['fund' => $total_closing_fund_balance, 'cash' => $total_cash]));
    // log_message('error', json_encode($total_cash - $total_closing_fund_balance));

    $is_proof_of_cash_correct = $total_cash == $total_closing_fund_balance ? true : false;


    return true; //$is_proof_of_cash_correct;
  }

  /**
   * @param float $number
   * @param int decimals
   * @return float
   */

  function truncate($number, $decimals = "0")
  {
    $power = pow(10, $decimals);
    if ($number > 0) {
      return floor($number * $power) / $power;
    } else {
      return ceil($number * $power) / $power;
    }
  }

  function check_if_budget_is_active($office_id, $reporting_month)
  {
    // log_message('error', json_encode([$office_id, $reporting_month]));

    $flag = false;

    $this->load->model('budget_tag_model');
    $this->load->model('custom_financial_year_model');

    $custom_financial_year = $this->custom_financial_year_model->get_default_custom_financial_year_id_by_office($office_id, true);
    $budget_tag_id = $this->budget_tag_model->get_budget_tag_id_based_on_reporting_month($office_id, $reporting_month, $custom_financial_year)['budget_tag_id'];//$this->get_budget_tag_id_by_date($office_id, $reporting_month);

    $budget_year = $this->get_financial_year($office_id, $reporting_month);

    $active_budget_status_ids = $this->get_active_budget_status_id($office_id);

    // log_message('error', json_encode(['budget_tag_id' => $budget_tag_id, 'budget_year' => $budget_year, 'active_budget_status_ids' => $active_budget_status_ids]));

    $this->read_db->where(
      array(
        'fk_office_id' => $office_id,
        'fk_budget_tag_id' => $budget_tag_id,
        'budget_year' => $budget_year
      )
    );

    $this->read_db->where_in('fk_status_id', $active_budget_status_ids);

    $budget_obj = $this->read_db->get('budget');


    if ($budget_obj->num_rows() > 0) {
      // log_message('error', json_encode($budget_obj->result_array()));
      $flag = true;
    }

    return $flag;
  }

  function get_active_budget_status_id($office_id)
  {

    // $active_budget_status = 0;

    // modify get_max_approval_status_id to consider a specific office in case a user in another country attempts to submit MFR for office in another country
    $active_budget_statuses = $this->general_model->get_max_approval_status_id('budget');

    // if(count($active_budget_statuses) == 1) { // Greater than 1 means that the logged user is not above country level
    //     $active_budget_status = $active_budget_statuses[0];
    // }

    // log_message('error', json_encode($active_budget_status));

    return $active_budget_statuses;
  }

  function get_financial_year($office_id, $reporting_month)
  {

    // $fy = get_fy($reporting_month);

    // log_message('error',$fy);
    $this->load->model('budget_model');
    $this->load->model('custom_financial_year_model');

    $default_custom_financial_year = $this->custom_financial_year_model->get_default_custom_financial_year_id_by_office($office_id);

    $fy = calculateFinancialYear($reporting_month, $default_custom_financial_year['custom_financial_year_start_month']);

    return $fy;
  }

  function get_budget_tag_id_by_date($office_id, $reporting_month)
  {

    $budget_tag_id = 0;

    $month_number = date('n', strtotime($reporting_month));

    $month_quarter = financial_year_quarter_months($month_number)['quarter_number'];

    $this->read_db->select(array('budget_tag_id', 'month_number'));
    $this->read_db->where(array('office_id' => $office_id));
    $this->read_db->join('account_system', 'account_system.account_system_id=budget_tag.fk_account_system_id');
    $this->read_db->join('office', 'office.fk_account_system_id=account_system.account_system_id');
    $this->read_db->join('month', 'month.month_id=budget_tag.fk_month_id');
    $budget_tags = $this->read_db->get('budget_tag')->result_array();

    foreach ($budget_tags as $budget_tag) {
      $quarter_number = financial_year_quarter_months($budget_tag['month_number'])['quarter_number'];

      if ($quarter_number == $month_quarter) {
        $budget_tag_id = $budget_tag['budget_tag_id'];
      }
    }

    // log_message('error', json_encode($budget_tag_id));

    return $budget_tag_id;
  }

  function _check_if_report_has_reconciled($office_id, $reporting_month)
  {
    //return false;

    $bank_reconciliation_statement = $this->_bank_reconciliation([$office_id], $reporting_month, false, true);

    $is_book_reconciled = $bank_reconciliation_statement['is_book_reconciled'];

    return $is_book_reconciled;
    //echo json_encode($bank_reconciliation_statement);
  }

  function _check_if_month_vouchers_are_approved($office_id, $reporting_month)
  {
    //return false;
    $this->load->model('voucher_model');
    return $this->voucher_model->check_if_month_vouchers_are_approved($office_id, $reporting_month);
  }

  function _check_if_bank_statements_are_uploaded($office_id, $reporting_month)
  {

    $this->read_db->select(array('office_bank_id'));
    $this->read_db->where(array('fk_office_id' => $office_id, 'office_bank_is_active' => 1));
    $office_bank = $this->read_db->get('office_bank');

    $statements_uploaded = true;

    $reconciliation_approve_item_id = $this->read_db->get_where(
      'approve_item',
      array('approve_item_name' => 'reconciliation')
    )->row()->approve_item_id;

    $this->load->model('office_bank_model');

    foreach ($office_bank->result_object() as $office_bank) {

      $is_office_bank_obselete = $this->office_bank_model->is_office_bank_obselete($office_bank->office_bank_id, $reporting_month);

      if($is_office_bank_obselete){
        continue;
      }

      $this->read_db->where(array(
        'reconciliation.fk_office_bank_id' => $office_bank->office_bank_id,
        'attachment.fk_approve_item_id' => $reconciliation_approve_item_id,
        'financial_report_month' => $reporting_month
      ));

      $this->read_db->join('reconciliation', 'reconciliation.reconciliation_id=attachment.attachment_primary_id');
      $this->read_db->join('financial_report', 'financial_report.financial_report_id=reconciliation.fk_financial_report_id');
      $attachment_obj = $this->read_db->get('attachment');

      if ($attachment_obj->num_rows() == 0) {
        $statements_uploaded = false;
        break;
      }
    }

    return $statements_uploaded;
  }

  function update_bank_reconciliation_balance()
  {
    $post = $_POST;

    $this->write_db->trans_start();


    if (
      count($post['office_ids']) > 1 ||
      (isset($post['project_ids']) && is_array($post['project_ids']) && count($post['project_ids']) > 1) ||
      (isset($post['office_bank_ids']) && is_array($post['office_bank_ids']) && count($post['office_bank_ids']) > 1)
    ) {
      // This piece f code will never run since the statement balance field is not present in the view when the above is met
      echo "Cannot update balances when multiple offices, banks or projects are selected";
    } else {

      $financial_report_id = $this->read_db->get_where(
        'financial_report',
        array('financial_report_month' => $post['reporting_month'], 'fk_office_id' => $post['office_ids'][0])
      )->row()->financial_report_id;

      $office_bank_id = 0;

      if (isset($post['office_bank_ids']) && is_array($post['office_bank_ids']) && !empty($post['office_bank_ids'])) {
        $office_bank_id = $post['office_bank_ids'][0];

        $condition_array = array('fk_financial_report_id' => $financial_report_id, 'fk_office_bank_id' => $office_bank_id);
      } elseif (isset($post['project_ids'])  && is_array($post['project_ids']) && !empty($post['project_ids'])) {

        $this->read_db->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_office_bank_id=office_bank.office_bank_id');
        $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=office_bank_project_allocation.fk_project_allocation_id');


        $office_bank_id = $this->read_db->get_where(
          'office_bank',
          array('fk_project_id' => $post['project_ids'][0])
        )->row()->office_bank_id;

        $condition_array = array('fk_financial_report_id' => $financial_report_id, 'fk_office_bank_id' => $office_bank_id);
      } else {
        // This piece will never run since reconciliation done when atleast 1 bank account is selected in the MFR filter
        $condition_array = array('fk_financial_report_id' => $financial_report_id);
      }
      // Check if reconciliation record exists and update else create

      $reconciliation_record = $this->read_db->get_where('reconciliation', $condition_array)->num_rows();

      if ($reconciliation_record == 0) {

        $data['reconciliation_track_number'] = $this->grants_model->generate_item_track_number_and_name('reconciliation')['reconciliation_track_number'];
        $data['reconciliation_name'] = $this->grants_model->generate_item_track_number_and_name('reconciliation')['reconciliation_name'];

        $data['fk_financial_report_id'] = $financial_report_id;
        $data['fk_office_bank_id'] = $office_bank_id;
        $data['reconciliation_statement_balance'] = $post['balance'];
        $data['reconciliation_suspense_amount'] = 0;

        $data['reconciliation_created_by'] = $this->session->user_id;
        $data['reconciliation_created_date'] = date('Y-m-d');
        $data['reconciliation_last_modified_by'] = $this->session->user_id;

        $data['fk_approval_id'] = $this->grants_model->insert_approval_record('reconciliation');
        $data['fk_status_id'] = $this->grants_model->initial_item_status('reconciliation');

        //echo $this->grants_model->initial_item_status('reconciliation'); exit(); 1534

        $this->write_db->insert('reconciliation', $data);
      } else {

        //$condition_array = array('fk_financial_report_id'=>$financial_report_id);
        //  print_r($condition_array) ;exit();

        $this->write_db->where($condition_array);

        $data['reconciliation_statement_balance'] = $post['balance'];
        $this->write_db->update('reconciliation', $data);
      }



      $this->write_db->trans_complete();

      if ($this->write_db->trans_status() == false) {
        echo "Error in updating bank reconciliation balance";
      } else {
        echo "Update completed";
      }
    }
  }

  function insert_reconciliation($financial_report_id, $office_bank_id, $statement_balance = 0, $suspense_amount = 0)
  {
    $data['reconciliation_track_number'] = $this->grants_model->generate_item_track_number_and_name('reconciliation')['reconciliation_track_number'];
    $data['reconciliation_name'] = $this->grants_model->generate_item_track_number_and_name('reconciliation')['reconciliation_name'];

    $data['fk_financial_report_id'] = $financial_report_id;
    $data['fk_office_bank_id'] = $office_bank_id;
    $data['reconciliation_statement_balance'] = $statement_balance;
    $data['reconciliation_suspense_amount'] = $suspense_amount;

    $data['reconciliation_created_by'] = $this->session->user_id;
    $data['reconciliation_created_date'] = date('Y-m-d');
    $data['reconciliation_last_modified_by'] = $this->session->user_id;

    $data['fk_approval_id'] = $this->grants_model->insert_approval_record('reconciliation');
    $data['fk_status_id'] = 0; //$this->grants_model->initial_item_status('reconciliation');

    $this->write_db->insert('reconciliation', $data);

    //return json_encode($data);
  }

  public function fund_balance_report()
  {

    $post = $this->input->post();

    $office_ids = [$post['office_id']];
    $reporting_month = $post['reporting_month'];
    $project_ids = [];
    $office_bank_ids = [];

    $office_banks = $this->get_office_banks($office_ids, $reporting_month);

    if (count($office_banks) > 1) {
      // log_message('error', json_encode($office_banks));
      $project_ids = isset($post['project_ids']) && $post['project_ids'] != "" ? explode(",", $post['project_ids']) : [];
      $office_bank_ids = isset($post['office_bank_ids']) && $post['office_bank_ids'] != "" ? explode(",", $post['office_bank_ids']) : [];
    }

    // log_message('error', json_encode($office_banks));

    $data['result']['fund_balance_report'] = $this->_fund_balance_report($office_ids, $reporting_month, $project_ids, $office_bank_ids);

    echo $this->load->view('financial_report/includes/include_fund_balance_report.php', $data, true);
  }

  public function proof_of_cash()
  {

    $post = $this->input->post();

    $office_ids = [$post['office_id']];
    $reporting_month = $post['reporting_month'];
    $project_ids = isset($post['project_ids']) && $post['project_ids'] != "" ? explode(",", $post['project_ids']) : [];
    $office_bank_ids = isset($post['office_bank_ids']) && $post['office_bank_ids'] != "" ? explode(",", $post['office_bank_ids']) : [];

    $data['proof_of_cash'] = $this->_proof_of_cash($office_ids, $reporting_month, $project_ids, $office_bank_ids);

    echo $this->load->view('financial_report/includes/include_proof_of_cash.php', $data, true);
  }

  static function get_menu_list()
  {
  }
}
