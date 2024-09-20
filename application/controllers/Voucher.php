<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Voucher extends MY_Controller
{

  function __construct()
  {
    parent::__construct();

    $this->load->model('voucher_type_model');
    $this->load->model('cheque_book_model');
    $this->load->model('contra_account_model');
    $this->load->model('approval_model');
    $this->load->model('voucher_model');
    $this->load->library('voucher_library');
    $this->load->model('office_group_model');
    $this->load->model('office_bank_model');
    $this->load->model('request_model');
    $this->load->model('attachment_model');
    $this->load->model('voucher_type_model');
    $this->load->model('expense_account_model');
  }

  /**
   * get_cheques_for_office
   * 
   * This return list of cheques
   * 
   * @return Array - Array
   * @author Onduso
   */
  function get_cheques_for_office(Int $office, Int $bank_office_id, Int $cheque_number)
  {

    $cheque_number_exists = false;

    $cheque_numbers = $this->voucher_model->get_cheques_for_office($office, $bank_office_id, $cheque_number);

    if ($cheque_numbers > 0) {
      $cheque_number_exists = true;
    }
    echo $cheque_number_exists;
  }
  /**
   * get_expense_active_expense_account
   * 
   * This return list of account 
   * 
   * @return Array - Array
   * @author Onduso
   */
  function get_expense_active_expense_account($expense_income_id, $office_id)
  {


    $this->load->model('income_account_model');
    $this->load->model('expense_account_model');

    $income_account_id = $this->income_account_model->get_expense_income_account($expense_income_id)->income_account_id;

    //Get project ID , then income ID and Expense Ids


    //log_message('error',json_encode($income_account_id));

    $expense_accs = $this->expense_account_model->get_all_active_expense_accounts($income_account_id, $office_id);

    echo json_encode($expense_accs);
  }

  /**
   * get_voucher_type_effect
   * 
   * The method gives the voucher type effsct code for a give voucher type.
   * Each voucher type has an associated effect and an account. 
   * 
   * There are 4 voucher type effects with codes income, expense, bank_contra 
   * [bank_contra - is for monies taken from bank to petty cash box] and 
   * cash_contra [is for monies rebanked from petty cash box to bank]
   * 
   * There are 2 voucher type accounts with codes names bank [holds bank transactions] and cash [petty cash transactions]
   * 
   * A valid combination for a voucher type can therefore be Bank Account with Effect of Expense
   * 
   * @param int $voucher_type_id - Is an primary key of a certain voucher type
   * 
   * @return String - Voucher Type Effect of a given voucher type id
   * 
   * @author Nicodemus Karisa Mwambire
   * 
   */
  function get_voucher_type_effect(int $voucher_type_id): Void
  {
    //echo $this->voucher_library->get_voucher_type_effect($voucher_type_id)->voucher_type_effect_code;
    echo $this->voucher_model->get_voucher_type_effect($voucher_type_id)['voucher_type_effect_code'];
  }

  /**
   * repopulate_office_banks
   * 
   * Get an json encoded array of list of bank accounts for an office in the format of each record with office_bank_id and office_bank_name
   * 
   * There is no direct relationship of a bank record in the bank table with office. 
   * The relationship between bank and office is met through the office_bank table through the bank_branch
   * An office can have more than 1 record representing it in the office_bank table and of different bank branches
   * 
   * It reads from a post data
   * 
   * @return Void - JSON Encoded string of array query result
   * 
   */
  function repopulate_office_banks(): void
  {
    $office_id = $this->input->post('office_id');

    echo $this->voucher_library->get_json_populate_office_banks($office_id);
  }

  /**
   * validate_cheque_number
   * 
   * Get to check if the passed voucher cheque number for a given office bank record is a valid one.
   * 
   * It depends on the grants config item "allow_skipping_of_cheque_leaves". If set to false, an can only
   * enter cheque records sequentially without skipping as long as the cheque number is within the range of
   * the active cheque book leaves. The true allows skipping of cheque leaves with the range of the active cheque 
   * book leaves
   * 
   * @return Void - True [Is a useable/valid cheque number],
   * False [Invalid cheque number - already used/ or skipped depending of the allow_skipping_of_cheque_leaves config]
   * @todo This method is decaprecated and is to be removed in the future implementations
   */
  function validate_cheque_number(): void
  {
    $office_bank_id = $this->input->post('office_bank');
    $cheque_number = $this->input->post('cheque_number');

    echo $this->voucher_library->validate_cheque_number($office_bank_id, $cheque_number);
  }

  /**
   * reload_approved_request_details
   * 
   * This methods gives a view file in string format. The view file is not rendered in a browser.
   * This view list in a HTML table all request detail records that have attained the status 
   * with n-1 (highest - 1) status_approval_sequence
   * 
   * @return Void - A view page in string format
   */
  function reload_approved_request_details(): Void
  {
    echo $this->voucher_library->approved_unvouched_request_details();
  }

  function unset_voucher_office_session()
  {
    $this->session->unset_userdata('voucher_office');
  }


  function update_voucher_header_on_office_change()
  {
    $office_id = $this->input->post('office_id');

    // This session is very crucial in getting the list of approve request details
    if ($this->session->voucher_office) {
      $this->session->unset_userdata('voucher_office');
    }
    //Set a session for the voucher selected office
    $this->session->set_userdata('voucher_office', $office_id);

    //echo  $office_id;
    $voucher_number = $this->voucher_library->get_voucher_number($office_id);
    $voucher_date = $this->voucher_library->get_voucher_date($office_id);

    $data = ['voucher_number' => $voucher_number, 'voucher_date' => $voucher_date];
    echo json_encode($data);
  }


  function get_request_detail()
  {
    $post = $this->input->post();

    // To be done from request detail model
    $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=request_detail.fk_project_allocation_id');
    $this->read_db->join('expense_account', 'expense_account.expense_account_id=request_detail.fk_expense_account_id');
    $this->read_db->select(array(
      'request_detail_description', 'request_detail_quantity',
      'request_detail_unit_cost', 'request_detail_total_cost', 'expense_account_id', 'expense_account_name',
      'project_allocation_id', 'project_allocation_name', 'request_detail_id'
    ));

    $this->read_db->where(array('request_detail_id' => $post['request_detail_id']));

    $request_detail = $this->read_db->get('request_detail')->row();

    $array = [
      'request_detail_id' => $request_detail->request_detail_id,
      'voucher_detail_description' => $request_detail->request_detail_description,
      'voucher_detail_quantity' => $request_detail->request_detail_quantity,
      'voucher_detail_unit_cost' => $request_detail->request_detail_unit_cost,
      'voucher_detail_total_cost' => $request_detail->request_detail_total_cost,
      'expense_account_id' => $request_detail->expense_account_id,
      'project_allocation_id' => $request_detail->project_allocation_id,
      'expense_account_name' => $request_detail->expense_account_name,
      'project_allocation_name' => $request_detail->project_allocation_name
    ];

    echo json_encode($array);
  }

  // New voucher form methods
  public function delete_voucher_detail_record()
  {

    $voucher_detail_id = $this->input->post('voucher_detail_id');

    $this->write_db->where(['voucher_detail_id' => $voucher_detail_id]);
    $this->write_db->delete('voucher_detail');
  }

/**
   * get_transaction_voucher
   * @param int $id
   * @return Array
   * @access private
   * @author: Livingstone Onduso
   * @Date: 24/9/2022
   */
  private function get_transaction_voucher(string $id):array
  {

    // log_message('error', json_encode($id));
    $raw_result = $this->voucher_model->get_transaction_voucher(hash_id($id, 'decode'));
    
    $office_bank = $this->voucher_model->get_office_bank($raw_result[0]['fk_office_bank_id']);

    $account_system_id = $this->office_account_system($raw_result[0]['fk_office_id'])->account_system_id;
    $office_cash_id = $raw_result[0]['fk_office_cash_id'];

    // log_message('error', json_encode($raw_result[0]));

    $office_cash = $this->voucher_model->get_office_cash($account_system_id,$office_cash_id);
    $voucher_type = $this->voucher_model->get_voucher_type($raw_result[0]['fk_voucher_type_id']);
    $cash_recipient_account = $this->voucher_model->get_voucher_cash_recipients(hash_id($id, 'decode'));

    // log_message('error', json_encode($voucher_type));

    $header = [];
    $body = [];

    $office = $this->read_db->get_where('office', array('office_id' => $raw_result[0]['fk_office_id']))->row();

    $header['office_name'] = $office->office_code . ' - ' . $office->office_name;
    $header['office_code'] = $office->office_code;
    $header['office_id'] = $raw_result[0]['fk_office_id'];
    $header['funder_id'] = $raw_result[0]['fk_funder_id'];
    $header['voucher_id'] = $raw_result[0]['voucher_id'];
    $header['voucher_date'] = $raw_result[0]['voucher_date'];
    $header['voucher_number'] = $raw_result[0]['voucher_number'];
    $header['voucher_approvers'] = !is_null($raw_result[0]['voucher_approvers']) ? json_decode($raw_result[0]['voucher_approvers']) : [];
    
    $header['voucher_type_name'] = $voucher_type->voucher_type_name;
    
    $header['source_account'] = '';
    $header['destination_account'] = '';

    if($voucher_type->voucher_type_account_code == 'bank' && ($voucher_type->voucher_type_effect_code == 'income' || $voucher_type->voucher_type_effect_code == 'expense')){
      if(sizeof((array)$office_bank) > 0){
        $header['source_account'] = $office_bank->bank_name . '(' . $office_bank->office_bank_account_number . ')';
      }
    }elseif($voucher_type->voucher_type_account_code == 'bank' && $voucher_type->voucher_type_effect_code == 'bank_contra'){
      if(sizeof((array)$office_bank) > 0){
        $header['source_account'] = $office_bank->bank_name . '(' . $office_bank->office_bank_account_number . ')';
      }
      if(sizeof((array)$office_cash) > 0){
        $header['destination_account'] = $office_cash->office_cash_name;
      }
    }elseif($voucher_type->voucher_type_account_code == 'bank' && $voucher_type->voucher_type_effect_code == 'bank_to_bank_contra'){
      if(sizeof((array)$office_bank) > 0){
        $header['source_account'] = $office_bank->bank_name . '(' . $office_bank->office_bank_account_number . ')';
      }
      // log_message('error', json_encode($cash_recipient_account));
      if(!empty($cash_recipient_account) && $cash_recipient_account['office_bank_id'] > 0){
        $this->read_db->where(array('office_bank_id' => $cash_recipient_account['office_bank_id']));
        $header['destination_account'] = $this->read_db->get('office_bank')->row()->office_bank_name;
      }
    }elseif($voucher_type->voucher_type_account_code == 'cash' && ($voucher_type->voucher_type_effect_code == 'income' || $voucher_type->voucher_type_effect_code == 'expense')){
      if(sizeof((array)$office_cash) > 0){
        $header['destination_account'] = $office_cash->office_cash_name;
      }
    }elseif($voucher_type->voucher_type_account_code == 'cash' && $voucher_type->voucher_type_effect_code == 'cash_contra'){
      if(sizeof((array)$office_cash) > 0){
        $header['source_account'] = $office_cash->office_cash_name;
      }

      if(sizeof((array)$office_bank) > 0){
        $header['destination_account'] = $office_bank->bank_name . '(' . $office_bank->office_bank_account_number . ')';
      }

    }elseif($voucher_type->voucher_type_account_code == 'cash' && $voucher_type->voucher_type_effect_code == 'cash_to_cash_contra'){
      if(sizeof((array)$office_cash) > 0){
        $header['source_account'] = $office_cash->office_cash_name;
      }

      if(!empty($cash_recipient_account) && $cash_recipient_account['office_cash_id'] > 0){
        $this->read_db->where(array('office_cash_id' => $cash_recipient_account['office_cash_id']));
        $header['destination_account'] = $this->read_db->get('office_cash')->row()->office_cash_name;
      }
    }

    $header['voucher_cheque_number'] = $raw_result[0]['voucher_cheque_number'] == 0 || $raw_result[0]['voucher_cheque_number'] == null ? 0 : $raw_result[0]['voucher_cheque_number'];
    $header['voucher_vendor'] = $raw_result[0]['voucher_vendor'];

    $header['voucher_reversal_from'] = $raw_result[0]['voucher_reversal_from'];
    $header['voucher_reversal_to'] = $raw_result[0]['voucher_reversal_to'];
    $header['voucher_is_reversed'] = $raw_result[0]['voucher_is_reversed'];

    $header['voucher_vendor_address'] = $raw_result[0]['voucher_vendor_address'];
    $header['voucher_description'] = $raw_result[0]['voucher_description'];
    $header['voucher_created_date'] = $raw_result[0]['voucher_created_date'];
    $header['voucher_status_id'] = $raw_result[0]['status_id'];
    $header['effect_type_code'] = $raw_result[0]['voucher_type_effect_code'];
    $header['account_type_code'] = $raw_result[0]['voucher_type_account_code'];
    $header['fk_office_cash_id'] = $raw_result[0]['fk_office_cash_id'];
    $header['fk_office_bank_id'] = $raw_result[0]['fk_office_bank_id'];

    $count = 0;
    foreach ($raw_result as $row) {
      $body[$count]['quantity'] = $row['voucher_detail_quantity'];
      $body[$count]['description'] = $row['voucher_detail_description'];
      $body[$count]['unitcost'] = $row['voucher_detail_unit_cost'];
      $body[$count]['totalcost'] = $row['voucher_detail_total_cost'];

      if ($row['fk_expense_account_id'] > 0) {
        $body[$count]['account_code'] = $this->read_db->get_where(
          'expense_account',
          array('expense_account_id' => $row['fk_expense_account_id'])
        )->row()->expense_account_code;
      } elseif ($row['fk_income_account_id'] > 0) {
        $body[$count]['account_code'] = $this->read_db->get_where(
          'income_account',
          array('income_account_id' => $row['fk_income_account_id'])
        )->row()->income_account_code;
      } elseif ($row['fk_contra_account_id'] > 0) {
        $body[$count]['account_code'] = $this->read_db->get_where(
          'contra_account',
          array('contra_account_id' => $row['fk_contra_account_id'])
        )->row()->contra_account_code;
      }

      $allocation = $this->voucher_model->get_project_allocation($row['fk_project_allocation_id']);

      // $body[$count]['project_allocation_code'] = !empty($allocation) ? $allocation->project_allocation_name . ' (' . $allocation->project_name . ') ' : "";
      $body[$count]['project_allocation_code'] = !empty($allocation) ? $allocation->project_code : "";

      $count++;
    }

    // $item_status = $this->grants_model->initial_item_status('voucher');
    // $logged_role_id = $this->session->role_ids;
    // $table = 'voucher';
    // $primary_key = hash_id($this->id, 'decode');


    $voucher_raiser = $this->record_raiser_info($raw_result[0]['voucher_created_by']); //['full_name'];

    //$voucher_raiser_name = $this->record_raiser_info($raw_result[0]['voucher_last_modified_by'])['full_name'];

    return [
      "header" => $header,
      "body" => $body,
      "signitories" => $this->voucher_model->get_voucher_signitories($raw_result[0]['fk_office_id']),
      'raiser_approver_info' => $voucher_raiser, // ['fullname' => $voucher_raiser['full_name']],
      'account_system_id' => $office->fk_account_system_id,
      //'voucher_raised_date'=>$header['voucher_created_date'],
      //'action_labels' => ['show_label_as_button' => $this->general_model->show_label_as_button($item_status, $logged_role_id, $table, $primary_key)]
      //'chat_messages'=>$this->get_chat_messages($this->controller,$id),
    ];
  }

  function get_chat_messages($approve_item_name, $record_primary_key)
  {

    $approve_item_id = $this->read_db->get_where(
      'approve_item',
      array('approve_item_name' => $approve_item_name)
    )->row()->approve_item_id;


    $this->read_db->select(array(
      'fk_user_id as author',
      'message_detail_content as message',
      'message_detail_created_date as message_date'
    ));

    $this->read_db->join('message', 'message.message_id=message_detail.fk_message_id');

    $chat_messages = $this->read_db->get_where(
      'message_detail',
      array(
        'fk_approve_item_id' => $approve_item_id,
        'message_record_key' => 1
      )
    )->result_array();

    return $chat_messages;
  }

  function record_raiser_info($user_id)
  {

    $this->read_db->select(['user_firstname','user_lastname','role_name']);
    $this->read_db->join('role','role.role_id=user.fk_role_id');
    $user_obj = $this->read_db->get_where('user', array('user_id' => $user_id));

    $user_info = [];

    if ($user_obj->num_rows() > 0) {
      $user = $user_obj->row();
      // log_message('error', json_encode($user));
      $user_info['full_name'] = $user->user_firstname . ' ' . $user->user_lastname;
      $user_info['role_name'] = $user->role_name;
    }

    return $user_info;
  }
  /**
   * @todo Checkifincome or expense
   */

  function check_pending_expenses_exceeds_total_income(array $voucher_data)
  {

    $is_expense_more_than_income = false;
    $funder_id = $voucher_data['funder_id'];

    //Get the selected voucher total_cost_amount
    $selected_voucher_income_amount = $this->voucher_model->selected_voucher_income_total_cost(hash_id($this->id, 'decode'));

    $this->load->model('financial_report_model');

    // $total_current_expense_voucher=$this->voucher_model->unapproved_month_vouchers($voucher_data['office_id'], $voucher_data['voucher_date'],'expense', 'cash',$voucher_data['fk_office_cash_id'],$voucher_data['fk_office_bank_id']);
    //Bank Income vs Expenses
    if ($voucher_data['effect_type_code'] == 'income' && $voucher_data['account_type_code'] == 'bank') {

      //Income Totals
      $unapproved_income_voucher_total = $this->voucher_model->unapproved_month_vouchers($voucher_data['office_id'], $voucher_data['voucher_date'], 'income', 'bank', [$funder_id], $voucher_data['fk_office_cash_id'], $voucher_data['fk_office_bank_id']);

      $full_bank_income_voucher_total = $this->financial_report_model->compute_cash_at_bank([$voucher_data['office_id']], $voucher_data['voucher_date'],[$funder_id], [], [$voucher_data['fk_office_bank_id']], true);

      $total_income_bal = $unapproved_income_voucher_total + $full_bank_income_voucher_total;

      //Get all expenses
      $total_current_expense_voucher = $this->voucher_model->unapproved_month_vouchers($voucher_data['office_id'], $voucher_data['voucher_date'], 'expense', 'bank', [$funder_id], $voucher_data['fk_office_cash_id'], $voucher_data['fk_office_bank_id']);

      //Less amount of the selected voucher
      $total_income_bal -= $selected_voucher_income_amount;

      if (($total_current_expense_voucher > $total_income_bal) && $total_current_expense_voucher > 0) {

        $is_expense_more_than_income = true;
      }
    } else if ($voucher_data['effect_type_code'] == 'bank_contra' && $voucher_data['account_type_code'] == 'bank') {

      //Full cash vouchers
      $full_cash_income_voucher_total = $this->financial_report_model->compute_cash_at_hand([$voucher_data['office_id']], $voucher_data['voucher_date'],[$funder_id], [], [], $voucher_data['fk_office_cash_id'], true);

      //Petty Cash Deposit
      $total_petty_cash_deposit_voucher = $this->voucher_model->unapproved_month_vouchers($voucher_data['office_id'], $voucher_data['voucher_date'], 'bank_contra', 'bank', [$funder_id], $voucher_data['fk_office_cash_id'], $voucher_data['fk_office_bank_id']);

      if (($total_petty_cash_deposit_voucher < 0 && $full_cash_income_voucher_total >= 0) || ($total_petty_cash_deposit_voucher >= 0 && $total_petty_cash_deposit_voucher >= 0)) {
        $total_petty_cash = $full_cash_income_voucher_total + $total_petty_cash_deposit_voucher;
      } else if ($total_petty_cash_deposit_voucher >= 0 && $full_cash_income_voucher_total < 0 || ($total_petty_cash_deposit_voucher >= 0 && $total_petty_cash_deposit_voucher >= 0)) {
        $total_petty_cash = $total_petty_cash_deposit_voucher + $full_cash_income_voucher_total;
      }

      //Get all expenses
      $total_current_expense_voucher = $this->voucher_model->unapproved_month_vouchers($voucher_data['office_id'], $voucher_data['voucher_date'], 'expense', 'cash', [$funder_id], $voucher_data['fk_office_cash_id'], $voucher_data['fk_office_bank_id']);

      //Check if total expenses > total cash
      $total_petty_cash -= $selected_voucher_income_amount;

      if ((number_format($total_current_expense_voucher, 2) > number_format($total_petty_cash, 2)) && number_format($total_current_expense_voucher, 2) > 0) {

        $is_expense_more_than_income = true;
      }
    }

    return  $is_expense_more_than_income;
  }

  function is_voucher_cancellable($status_data, $voucher_data)
  {
    $is_voucher_cancellable = false;
    $initial_item_status = $this->grants_model->initial_item_status('Voucher');
    $direction = $status_data['item_status'][$voucher_data['voucher_status_id']]['status_approval_direction'];
    $roles = $status_data['item_status'][$voucher_data['voucher_status_id']]['status_role'];
    $voucher_is_reversed = $voucher_data['voucher_is_reversed'];

    if (
      ($voucher_data['voucher_status_id'] == $initial_item_status || $direction == -1)  &&
      $voucher_is_reversed == 0  &&
      array_intersect($roles, $this->session->role_ids)
    ) {
      $is_voucher_cancellable = true;
    }

    return $is_voucher_cancellable;
  }

  function printable_voucher(){
    $post = $this->input->post();

    $vouchers_ids = $post['voucher_ids'];

    // log_message('error', json_encode($vouchers_ids));

    $create_mass_vouchers = [];

    foreach($vouchers_ids as $voucher_id){
    
      $voucher = $this->get_transaction_voucher(hash_id($voucher_id,'encode'));

      // log_message('error', json_encode($voucher));

      $create_mass_vouchers[$voucher_id] = $voucher;

      $status_data = $this->general_model->action_button_data('voucher', $voucher['account_system_id']);
  
      $create_mass_vouchers[$voucher_id]['is_voucher_cancellable'] = $this->is_voucher_cancellable($status_data, $voucher['header']);
      $create_mass_vouchers[$voucher_id]['status_data'] = $status_data;
    }

    // log_message('error', json_encode($create_mass_vouchers));

    $data['vouchers'] = $create_mass_vouchers; 
    $data['journal_id'] = $post['journal_id'];

    // $printable_vouchers = $this->load->view('voucher/mass_vouchers', $data, true);
    $printable_vouchers = $this->load->view('voucher/mass_print_voucher_view', $data, true);


    echo $printable_vouchers;
  }


  function result($id = '')
  {
    if ($this->action == 'view') {

      $result = $this->get_transaction_voucher($this->id);

      // log_message('error', json_encode($result));

      $status_data = $this->general_model->action_button_data($this->controller, $result['account_system_id']);

      $result['is_voucher_cancellable'] = $this->is_voucher_cancellable($status_data, $result['header']);
      $result['check_expenses_aganist_income'] = $this->check_pending_expenses_exceeds_total_income($result['header']);
      $result['status_data'] = $status_data;
      
      $result['voucher_status_is_max'] = $this->general_model->is_status_id_max('voucher',hash_id($this->id,'decode'));

      return $result;
    } elseif ($this->action == 'multi_form_add') {
      $result = [];

      $this->load->model('funder_model');
      $user_funders = $this->funder_model->get_user_funders();
      
      $result['office_has_request'] = $this->request_model->get_office_request_count() == 0 ? false : true;
      $result['user_funder'] = $user_funders;

      return $result;
    } elseif ($this->action == 'edit') {

      $result = [];
      $result['voucher_header_info'] = $this->voucher_model->get_voucher_header_to_edit(hash_id($this->id, 'decode'));
      return $result;
    } elseif ($this->action == 'list') {
      $columns = $this->columns();
      // array_shift($columns);
      unset($columns[array_search('voucher_id',$columns)]);
      unset($columns[array_search('voucher.fk_status_id',$columns)]);
      $result['columns'] = $columns;
      $result['has_details_table'] = false;
      $result['has_details_listing'] = true;
      $result['is_multi_row'] = false;
      $result['show_add_button'] = true;

      return $result;
    } else {
      return parent::result($id);
    }
  }

  function user_transacting_offices(){
    $transacting_offices = $this->user_model->direct_user_offices($this->session->user_id, $this->session->context_definition['context_definition_name']);
    return setJsonResponse($transacting_offices);
  }

  /**
   * Enhancement
   *get_project_allocation_income_account(): Returns  income account numeric value
   * @author Livingstone Onduso: Dated 29-06-2023
   * @access public
   * @param Int Int $project_allocation_id
   * @return int
   **/
  function get_project_allocation_income_account(int $project_allocation_id): void
  {
    $this->load->model('income_account_model');

    $income_account_id = $this->income_account_model->get_project_allocation_income_account($project_allocation_id);

    echo $income_account_id;
  }


  /**
   *voucher_header_records(): Returns a rows of voucher details information from voucher_detail table
   * @author Livingstone Onduso: Dated 08-05-2023
   * @access public
   * @param Int $voucher_id - voucher id
   * @return void 
   */
  function voucher_header_records(int $voucher_id): void
  {

    $voucher_records_to_edit = $this->voucher_model->get_voucher_header_to_edit($voucher_id);

    echo json_encode($voucher_records_to_edit);
  }
  /**
   *get_voucher_detail_to_edit(): Returns a rows of voucher details information from voucher_detail table
   * @author Livingstone Onduso: Dated 08-05-2023
   * @access public
   * @param Int $voucher_id - voucher id
   * @return void
   */
  function get_voucher_detail_to_edit(int $voucher_id, string $voucher_type_effect_name)
  {

    $voucher_detail_records_to_edit = $this->voucher_model->get_voucher_detail_to_edit($voucher_id, $voucher_type_effect_name);

    echo json_encode($voucher_detail_records_to_edit);
  }




  function columns()
  {
    $columns = [
      'voucher_id',
      'voucher_track_number',
      'voucher_number',
      'voucher_description',
      'voucher_date',
      'voucher_cheque_number',
      'voucher_is_reversed',
      'voucher_created_date',
      'office_name',
      'voucher_type_name',
      'status_name',
      'voucher.fk_status_id'
      
    ];

    return $columns;
  }

  public function get_vouchers()
  {

    $max_voucher_status_id = $this->max_status_id;
    $columns = $this->columns();
    //array_push($columns, 'status_id');
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
      $this->read_db->order_by('voucher_id DESC');
    } else {
      $this->read_db->order_by($columns[$col], $dir);
    }

    // Searching

    $search = $this->input->post('search');
    $value = $search['value'];

   // array_shift($search_columns);

    if (!empty($value)) {
      $this->read_db->group_start();
      $column_key = 0;
      foreach ($search_columns as $column) {
        if ($column_key == 0) {
          $this->read_db->like($column, $value, 'both');
        } else {
          $this->read_db->or_like($column, $value, 'both');
        }
        $column_key++;
      }
      $this->read_db->group_end();
    }

    $this->read_db->select($columns);
    $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
    $this->read_db->join('status', 'status.status_id=voucher.fk_status_id');
    $this->read_db->join('office', 'office.office_id=voucher.fk_office_id');

    if (!$this->session->system_admin) {
      $this->read_db->where_in('voucher.fk_office_id', array_column($this->session->hierarchy_offices, 'office_id'));
    }

    if (empty($value)) {
      $this->read_db->where_not_in('voucher.fk_status_id', $max_voucher_status_id);
    }

    $result_obj = $this->read_db->get('voucher');

    $results = [];

    if ($result_obj->num_rows() > 0) {
      $results = $result_obj->result_array();
    }

    return $results;
  }

  function count_vouchers()
  {

    $max_voucher_status_id = $this->max_status_id;
    $columns = $this->columns();
    $search_columns = $columns;

    // Searching

    $search = $this->input->post('search');
    $value = $search['value'];

    array_shift($search_columns);

    if (!empty($value)) {
      $this->read_db->group_start();
      $column_key = 0;
      foreach ($search_columns as $column) {
        if ($column_key == 0) {
          $this->read_db->like($column, $value, 'both');
        } else {
          $this->read_db->or_like($column, $value, 'both');
        }
        $column_key++;
      }
      $this->read_db->group_end();
    }

    $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
    $this->read_db->join('status', 'status.status_id=voucher.fk_status_id');
    $this->read_db->join('office', 'office.office_id=voucher.fk_office_id');

    if (!$this->session->system_admin) {
      $this->read_db->where_in('voucher.fk_office_id', array_column($this->session->hierarchy_offices, 'office_id'));
    }

    if (empty($value)) {
      $this->read_db->where_not_in('voucher.fk_status_id', $max_voucher_status_id);
    }


    $this->read_db->from('voucher');
    $count_all_results = $this->read_db->count_all_results();

    return $count_all_results;
  }


  public function show_list()
  {

    //$max_voucher_status_id = $this->general_model->get_max_approval_status_id('voucher');

    $draw = intval($this->input->post('draw'));
    $vouchers = $this->get_vouchers();
    $count_vouchers = $this->count_vouchers();

    $month_cancelled_vouchers = isset($vouchers[0]) ? $this->voucher_model->month_cancelled_vouchers($vouchers[0]['voucher_date']) : date("Y-m-d");

    //log_message("error",json_encode($month_cancelled_vouchers));

    $result = [];

    $cnt = 0;
    $status_data = $this->general_model->action_button_data($this->controller);

    extract($status_data);

    $initial_record_status_id = $this->grants_model->initial_item_status('voucher');

  
    $status_info=$this->general_model->action_button_data($this->controller)['item_status'];

    $logged_user_permission=$this->user_model->check_role_has_permissions(ucfirst($this->controller), 'create');

    foreach ($vouchers as $voucher) {

      $voucher_id = array_shift($voucher);

      $status_id=$voucher['fk_status_id'];

      $voucher_status = array_pop($voucher);

      //Disable the edit if status is not Ready to Submit or Reinstate

     // $status_name=$status_info[$voucher_status]['status_button_label'];

      $status_approval_direction=$status_info[$voucher_status]['status_approval_direction'];


      $disable_edit='disabled';

      if( $voucher['voucher_is_reversed']!=1 && $logged_user_permission && ($initial_record_status_id== $status_id || $status_approval_direction==-1)){
        $disable_edit='';
      }



      //Construct the View and Edit <a> tag
      $voucher['voucher_track_number'] = '<a class="btn btn-default" href="' . base_url() . $this->controller . '/view/' . hash_id($voucher_id) . '"><i class="fa fa-eye" style="font-size:18px;color:black"></i> ' .' ['.$voucher['voucher_track_number'].']'. '</a>';

      
      $voucher['voucher_number'] = '<a   class="btn btn-success edit  ' . $disable_edit . '"  href="' . base_url() . $this->controller . '/edit/' . hash_id($voucher_id) . '"><i class="fa fa-pencil" style="font-size:18px;color:white"></i> ' .' ['.$voucher['voucher_number'].']'. '</a>';

      $voucher['voucher_is_reversed'] = $voucher['voucher_is_reversed'] == 1 ? get_phrase('yes') :  get_phrase('no');
      $row = array_values($voucher);

      $action = '';

      if (is_array($month_cancelled_vouchers) && !in_array($voucher_id, $month_cancelled_vouchers)) {
        $action = approval_action_button($this->controller, $item_status, $voucher_id, $voucher_status, $item_initial_item_status_id, $item_max_approval_status_ids);
      }

      array_unshift($row, $action);

      $result[$cnt] = $row;

      $cnt++;
    }

    $response = [
      'draw' => $draw,
      'recordsTotal' => $count_vouchers,
      'recordsFiltered' => $count_vouchers,
      'data' => $result
    ];

    echo json_encode($response);
  }

  function view()
  {
    parent::view();
  }

  function update_item_status($item)
  {

    $voucher_id = $this->input->post('item_id');

    // Update status of the original voucher after reversal
    $this->read_db->where(array('voucher_reversal_to' => $voucher_id));
    $original_voucher_id_obj = $this->read_db->get('voucher');

    //log_message("error",json_encode($original_voucher_id_obj->result_array()));

    if ($original_voucher_id_obj->num_rows() > 0) {

      $original_voucher_id = $original_voucher_id_obj->row()->voucher_id;

      $next_status_data['fk_status_id'] = $this->input->post('next_status');
      $this->write_db->where(array('voucher_id' => $original_voucher_id));
      $this->write_db->update('voucher', $next_status_data);
    }

    parent::update_item_status($item);
  }

  static function get_menu_list()
  {
  }


  // Custom voucher form functions

  function voucher_type_effect_and_code($voucher_type_id)
  {
    // log_message('error', json_encode($voucher_type_id));
    $this->read_db->select(array('voucher_type_account_code', 'voucher_type_effect_code'));
    $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
    $this->read_db->join('voucher_type_account', 'voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
    $voucher_type_effect_and_code = $this->read_db->get_where('voucher_type', array('voucher_type_id' => $voucher_type_id))->row();

    return $voucher_type_effect_and_code;
  }

  function office_account_system($office_id)
  {

    $this->read_db->join('account_system', 'account_system.account_system_id=office.fk_account_system_id');
    $this->read_db->where(array('office_id' => $office_id));
    $office_accounting_system = $this->read_db->get('office')->row();

    return $office_accounting_system;
  }
  /**
   * get_active_voucher_types(): get json string of voucher types
   * @author  Livingstone Onduso
   * @dated: 4/06/2023
   * @access public
   * @return void
   * @param int $$office_id, $transaction_date
   */
  function get_active_voucher_types(int $office_id, string $transaction_date): void
  {
    $account_system_id = $this->office_account_system($office_id)->account_system_id;

    $voucher_types = $this->voucher_type_model->get_active_voucher_types($account_system_id, $office_id, $transaction_date);

    echo json_encode($voucher_types);
  }
  /**
   * get_active_office_cash(): get json string of voucher types
   * @author  Livingstone Onduso
   * @dated: 4/06/2023
   * @access public
   * @return void
   */
  function get_active_office_cash(): void
  {

    $account_system_id = $this->session->user_account_system_id;

    $this->load->model('office_cash_model');

    $office_cash_accounts = $this->office_cash_model->get_active_office_cash($account_system_id);

    echo json_encode($office_cash_accounts);
  }

   /**
   * get_active_project_expenses_accounts
   * @date: 13 Nov 2023
   * 
   * @return void
   * @author Onduso
   */
  function get_active_project_expenses_accounts(int $project_id,int $voucher_type_id):void
  {

    $account_ids_and_names=$this->voucher_model->get_active_project_expenses_accounts($project_id,$voucher_type_id);
    echo json_encode($account_ids_and_names);
  
  }

  /**
   * get_active_office_bank(): get json string of voucher types
   * @author  Livingstone Onduso
   * @dated: 5/06/2023
   * @param int $office_id
   * @access public
   * @return void
   */
  function get_active_office_bank(int $office_id): void
  {

    $office_bank = $this->office_bank_model->get_active_office_bank($office_id);

    echo json_encode($office_bank);
  }

  /**
   * get_active_recipient_bank(): get json string of voucher types
   * @author  Livingstone Onduso
   * @dated: 5/03/2024
   * @param int $voucher_id
   * @access public
   * @return void
   */
  function get_active_recipient_bank(int $voucher_id): void
  {

    $recipient_office_bank = $this->office_bank_model->get_active_recipient_bank($voucher_id);

    echo json_encode($recipient_office_bank);
  }

  function check_active_cheque_book_for_office_bank_exist($office_id, $office_bank_id, $transaction_date)
  {
    $check_exists = false;
    $max_cheque_book_status_id = $this->general_model->get_max_approval_status_id('cheque_book');

    $this->read_db->where(array('fk_status_id <> ' =>  $max_cheque_book_status_id[0], 'fk_office_bank_id' => $office_bank_id));
    $count_of_unapproved_cheque_book = $this->read_db->get('cheque_book')->num_rows(); // $this->cheque_book_model->check_active_cheque_book_for_office_bank_exist($office_bank_id);

    $this->read_db->select_max('cheque_book_id');
    $this->read_db->where(array('fk_office_bank_id' => $office_bank_id));
    $max_cheque_book_id = $this->read_db->get('cheque_book')->row()->cheque_book_id;
    //log_message('error', json_encode($max_cheque_book_status_id));
    // log_message('error', json_encode($max_cheque_book_obj->result_array()));

    // Check if the cheque book is completed, yes turn it inactive
    $remaining_cheque_leaves = $this->cheque_book_model->get_remaining_unused_cheque_leaves($office_bank_id);
    // log_message('error', json_encode($remaining_cheque_leaves));
    if (count($remaining_cheque_leaves) == 0) {
      // Turn the cheque book to inactive
      $this->cheque_book_model->deactivate_cheque_book($office_bank_id);
    }


    // Unused reused cheque leaves
    $reused_cheques = $this->cheque_book_model->get_unused_reused_cheques($office_bank_id);

    $is_max_cheque_book_fully_approved = false;
    $current_cheque_book_id = 0;

    $are_all_cheque_books_fully_approved = true; //$max_cheque_book_obj->num_rows();

    // log_message('error', json_encode(count($reused_cheques)));
    // log_message('error', json_encode($count_of_unapproved_cheque_book));

    // The count($reused_cheques) is to ensure that no new cheque is created if there are still unused reused cheque leaves in place even if all cheque books are not active
    if ($count_of_unapproved_cheque_book > 0) {
      $are_all_cheque_books_fully_approved =  $count_of_unapproved_cheque_book > 0 ? false : true; // $count_of_unapproved_cheque_book > 0 ? (in_array($max_cheque_book_obj->row()->fk_status_id, $max_cheque_book_status_id) ? true : false) : false; 
    }

    $active_cheque_book_obj = $this->cheque_book_model->check_active_cheque_book_for_office_bank_exist($office_bank_id);

    // log_message('error', json_encode($reused_cheques));

    if ($active_cheque_book_obj->num_rows() > 0 || count($reused_cheques) > 0) {
      $check_exists = true;
      $current_cheque_book_id = hash_id($max_cheque_book_id, 'encode');
    }

    $this->load->model('office_bank_model');

    $disable_controls = false;

    $office_banks_for_office = $this->office_bank_model->get_office_banks_for_office($office_id);

    if (!empty($office_banks_for_office['chequebook_exemption_expiry_date'])) {
      //  && $office_banks_for_office['chequebook_exemption_expiry_date'] < $transaction_date

      foreach ($office_banks_for_office['chequebook_exemption_expiry_date'] as $chequebook_exemption_expiry_date) {
        // This seems but a code repeat but was neccessary to prevent the method get_remaining_unused_cheque_leaves being called in a loop continously
        if ($chequebook_exemption_expiry_date > $transaction_date) {

          $disable_controls = true;

          break;
        } else {
          $this->load->model('cheque_book_model');
          $leaves = $this->cheque_book_model->get_remaining_unused_cheque_leaves($office_bank_id);
          // log_message('error', json_encode($leaves));
          if (!empty($leaves)) {
            $disable_controls = true;

            break;
          }
        }
      }
    }
    
    if ($disable_controls) {
      $check_exists = true;
      $are_all_cheque_books_fully_approved = true;
      $current_cheque_book_id = 0;
    }

    // log_message('error', json_encode($check_exists));

    $response =  [
      'is_active_cheque_book_existing' => $check_exists,
      'are_all_cheque_books_fully_approved' => $are_all_cheque_books_fully_approved, ///'is_active_cheque_book_fully_approved' => $is_max_cheque_book_fully_approved,
      'current_cheque_book_id' => $current_cheque_book_id
    ];

    echo json_encode($response);
  }



  function voucher_type_requires_cheque_referencing($voucher_type_id){
    echo $this->voucher_type_model->voucher_type_requires_cheque_referencing($voucher_type_id);
  }
  

  function check_voucher_type_affects_bank($office_id, $funder_id, $voucher_type_id = 0)
  {
    // log_message('error', json_encode(compact('office_id','funder_id', 'voucher_type_id')));
    $response['is_transfer_contra'] = false;
    $response['office_banks'] = [];
    $response['office_cash'] = [];
    $response['is_bank_payment'] = false;
    $response['is_cash_payment'] = false;

    $response['voucher_type_requires_cheque_referencing'] = $this->voucher_type_model->voucher_type_requires_cheque_referencing($voucher_type_id);

    $voucher_type_effect_and_code = $this->voucher_type_effect_and_code($voucher_type_id);

    $voucher_type_effect = $voucher_type_effect_and_code->voucher_type_effect_code;
    $voucher_type_account = $voucher_type_effect_and_code->voucher_type_account_code;

    $office_accounting_system = $this->office_account_system($office_id);

    if (count($this->office_bank_model->get_active_office_banks($office_id, $funder_id)) > 1 && $voucher_type_account == 'cash') {
      $response['office_banks'] = $this->get_office_banks($office_id, $funder_id);
    }

    if ($voucher_type_account == 'cash' || $voucher_type_effect == 'bank_contra' || $voucher_type_effect == 'cash_to_cash_contra') {
      $response['office_cash'] = $this->read_db->select(array('office_cash_id as item_id', 'office_cash_name as item_name'))->get_where(
        'office_cash',
        array('fk_account_system_id' => $office_accounting_system->account_system_id, 'office_cash_is_active' => 1)
      )->result_array();
    }

    if ($voucher_type_account == 'bank' || $voucher_type_effect == 'cash_contra' || $voucher_type_effect == 'bank_to_bank_contra') {
      $response['office_banks'] = $this->get_office_banks($office_id, $funder_id);
    }

    if ($voucher_type_effect == 'bank_to_bank_contra' || $voucher_type_effect == 'cash_to_cash_contra') {
      $response['is_transfer_contra'] = true;
    }

    if ($voucher_type_effect == 'bank_to_bank_contra' || $voucher_type_effect == 'bank_contra' || ($voucher_type_account == 'bank' && $voucher_type_effect == 'expense')) {
      $response['is_bank_payment'] = true;
    }

    if ($voucher_type_effect == 'cash_to_cash_contra' || $voucher_type_effect == 'cash_contra' || ($voucher_type_account == 'cash' && $voucher_type_effect == 'expense')) {
      $response['is_cash_payment'] = true;
    }

    echo json_encode($response);
  }

  function get_voucher_accounts_and_allocation()
  {

    $post = $this->input->post();

    $office_id = $post['office_id'];
    $funder_id = $post['funder_id'];
    $voucher_type_id = $post['voucher_type_id'];
    $transaction_date = $post['transaction_date'];
    $office_bank_id = $post['office_bank_id'];

    $response = [];
    $response['approved_requests'] = 0;
    $response['project_allocation'] = [];
    $response['is_contra'] = false;
    $response['project_allocation'] = [];
    //$response['accounts'] = [];

    if(!validate_date($transaction_date)){
      $transaction_date = date('Y-m-d');
    }

    $office_accounting_system = $this->office_account_system($office_id);

    $project_allocation = [];

    if (
      !$office_accounting_system->account_system_is_allocation_linked_to_account ||
      $this->config->item("toggle_accounts_by_allocation")
    ) {
      //Working as expected
      $query_condition = "fk_office_id = " . $office_id . " AND (project_end_date >= '" . $transaction_date . "' OR  project_allocation_extended_end_date >= '" . $transaction_date . "' OR project_end_date LIKE '0000-00-00' || project_end_date IS NULL) AND project_start_date <= '" . $transaction_date . "'";
      $this->read_db->select(array('project_allocation_id', 'project_name as project_allocation_name'));
      $this->read_db->join('project', 'project.project_id=project_allocation.fk_project_id');
      $this->read_db->where(['project.fk_funder_id' => $funder_id]);
      if ($this->input->post('office_bank_id')) {
        $this->read_db->where(array('fk_office_bank_id' => $this->input->post('office_bank_id')));
        $this->read_db->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_project_allocation_id=project_allocation.project_allocation_id');
      }

      $this->read_db->where(array('project_allocation_is_active' => 1));
      $this->read_db->where($query_condition);
      $project_allocation_obj = $this->read_db->get('project_allocation');
      
      if($project_allocation_obj->num_rows() > 0){
        $project_allocation = $project_allocation_obj->result_object();
      }
    }

    $voucher_type_effect_and_code = $this->voucher_type_effect_and_code($voucher_type_id);

    $voucher_type_effect = $voucher_type_effect_and_code->voucher_type_effect_code;
    //$voucher_type_account = $voucher_type_effect_and_code->voucher_type_account_code;

    $response['project_allocation'] = $project_allocation;

    if ($voucher_type_effect == 'bank_contra' || $voucher_type_effect == 'cash_contra') {
      $response['is_contra'] = true;
    }

    if ($voucher_type_effect == 'expense') {
      $response['approved_requests'] = count($this->voucher_model->get_approved_unvouched_request_details($office_id));
    }

    //log_message('error',json_encode($response));
    echo json_encode($response);
  }

  function make_atleast_one_office_bank_default_if_one_missing($office_id){
    
    // Every query must be done on the write_db node
    
    $this->write_db->where(array('fk_office_id' => $office_id,'office_bank_is_active' => 1,'office_bank_is_default' => 1));
    $office_bank_id_obj = $this->write_db->get('office_bank');
    
    if($office_bank_id_obj->num_rows() == 0){
      $this->write_db->where(array('office_bank_is_active' => 1, 'fk_office_id' => $office_id));
      $active_office_bank_count_obj = $this->write_db->get('office_bank');

      if($active_office_bank_count_obj->num_rows() > 0){
        // Make one active office bank default
        $first_active_office_bank_id = $active_office_bank_count_obj->row()->office_bank_id;
        $this->write_db->where(array('office_bank_id' => $first_active_office_bank_id,'office_bank_is_active' => 1, 'fk_office_id' => $office_id));
        $this->write_db->update('office_bank',['office_bank_is_default' => 1]);

        // Make inactive office banks not default
        $this->write_db->where(array('office_bank_is_active' => 0, 'fk_office_id' => $office_id));
        $this->write_db->update('office_bank',['office_bank_is_default' => 0]);
      }
    }
  }

  function get_accounts_for_project_allocation()
  {
    //voucher_type_id
    $post = $this->input->post();

    $voucher_type_effect_and_code = $this->voucher_type_effect_and_code($post['voucher_type_id']);

    $voucher_type_effect = $voucher_type_effect_and_code->voucher_type_effect_code;
    $voucher_type_account = $voucher_type_effect_and_code->voucher_type_account_code;

    $accounts_obj = [];

    $project_allocation_id = $post['allocation_id'];
    $office_bank_id = $post['office_bank_id'];
    //$accounts = $this->contra_account_model->add_contra_account($office_bank_id);
    $office_accounting_system = $this->office_account_system($this->input->post('office_id'));

    // $this->read_db->where(array('fk_account_system_id' => $office_accounting_system->account_system_id));


    if ($voucher_type_effect == 'expense') {

      // Check if the office is a lead in an office group
      $is_office_group_lead = $this->office_group_model->check_if_office_is_office_group_lead($this->input->post('office_id'));

      if (!$is_office_group_lead) {
        $string_condition = 'AND expense_account_office_association_is_active = 1';
        $this->grants_model->not_exists_sub_query('expense_account', 'expense_account_office_association', $string_condition);
      }


      $this->read_db->join('income_account', 'income_account.income_account_id=expense_account.fk_income_account_id');
      $this->read_db->join('project_income_account', 'project_income_account.fk_income_account_id=income_account.income_account_id');
      $this->read_db->join('project', 'project.project_id=project_income_account.fk_project_id');
      $this->read_db->join('project_allocation', 'project_allocation.fk_project_id=project.project_id');
      $this->read_db->where(array('project_allocation_id' => $project_allocation_id, 'expense_account_is_active' => 1));
      $this->read_db->where(array('fk_account_system_id' => $office_accounting_system->account_system_id));
      // $this->read_db->join('office','project_allocation.fk_office_id=office.office_id');
      // $this->read_db->join("expense_account_office_association","expense_account_office_association.fk_office_id=office.office_id");

      $this->read_db->select(array('expense_account_id as account_id', 'expense_account_name as account_name'));
      $accounts_obj = $this->read_db->get('expense_account');
    } elseif ($voucher_type_effect == 'income' || $voucher_type_effect == 'bank_to_bank_contra') {
      $this->read_db->where(array('project_allocation_id' => $project_allocation_id, 'income_account_is_active' => 1));
      $this->read_db->where(array('fk_account_system_id' => $office_accounting_system->account_system_id));
      $this->read_db->join('project_income_account', 'project_income_account.fk_income_account_id=income_account.income_account_id');
      $this->read_db->join('project', 'project.project_id=project_income_account.fk_project_id');
      $this->read_db->join('project_allocation', 'project_allocation.fk_project_id=project.project_id');
      $this->read_db->select(array('income_account_id as account_id', 'income_account_name as account_name'));
      $accounts_obj = $this->read_db->get('income_account');
    } elseif ($voucher_type_effect == 'cash_contra') {

      $accounts = $this->contra_account_model->add_contra_account($office_bank_id);

      $this->read_db->select(array('contra_account_id as account_id', 'contra_account_name as account_name', 'contra_account_code as account_code'));
      $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=contra_account.fk_voucher_type_effect_id');
      $this->read_db->join('office_bank', 'office_bank.office_bank_id=contra_account.fk_office_bank_id');
      $this->read_db->where(array('fk_account_system_id' => $office_accounting_system->account_system_id));
      $accounts_obj = $this->read_db->get_where(
        'contra_account',
        array(
          'voucher_type_effect_code' => 'cash_contra',
          //'fk_account_system_id' => $office_accounting_system->account_system_id,
          'office_bank_is_active' => 1,
          'office_bank_id' => $office_bank_id
        )
      );
    } elseif ($voucher_type_effect == 'bank_contra') {

      $this->contra_account_model->add_contra_account($office_bank_id);

      $this->read_db->select(array('contra_account_id as account_id', 'contra_account_name as account_name', 'contra_account_code as account_code'));
      $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=contra_account.fk_voucher_type_effect_id');
      $this->read_db->join('office_bank', 'office_bank.office_bank_id=contra_account.fk_office_bank_id');
      $this->read_db->where(array('fk_account_system_id' => $office_accounting_system->account_system_id));
      $this->read_db->where(array(
        'voucher_type_effect_code' => 'bank_contra',
        //'fk_account_system_id' => $office_accounting_system->account_system_id,
        'office_bank_is_active' => 1,
        'office_bank_id' => $office_bank_id
      ));
      $accounts_obj = $this->read_db->get('contra_account');
    } elseif ($voucher_type_effect == 'cash_to_cash_contra') {

      $this->make_atleast_one_office_bank_default_if_one_missing($this->input->post('office_id'));

      $this->contra_account_model->add_contra_account($office_bank_id);
      
      $this->read_db->where(array('fk_office_id' => $this->input->post('office_id'),'office_bank_is_active' => 1,'office_bank_is_default' => 1));
      $office_bank_id = $this->read_db->get('office_bank')->row()->office_bank_id;

      $this->read_db->select(array('contra_account_id as account_id', 'contra_account_name as account_name', 'contra_account_code as account_code'));
      $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=contra_account.fk_voucher_type_effect_id');
      $this->read_db->join('office_bank', 'office_bank.office_bank_id=contra_account.fk_office_bank_id');
      $this->read_db->where(array('fk_account_system_id' => $office_accounting_system->account_system_id));
      $accounts_obj = $this->read_db->get_where(
        'contra_account',
        array(
          'voucher_type_effect_code' => 'cash_to_cash_contra',
          'fk_account_system_id' => $office_accounting_system->account_system_id,
          'office_bank_is_active' => 1,
          'office_bank_id' => $office_bank_id
        )
      );
    }

    $expense_or_income_accounts_array = [];

    if ($accounts_obj->num_rows() > 0) {

      $accounts = $accounts_obj->result_object();

      //Remove duplicates account_ids
      $account_ids = array_column($accounts, 'account_id');
      $unique_account_ids = array_unique($account_ids);

      //Remove duplicate account names and combine the arrays to form one
      $account_names = array_column($accounts, 'account_name');
      $unique_names = array_unique($account_names);

      if (sizeof($unique_account_ids) == sizeof($unique_names)) {
        $expense_or_income_accounts_array = array_combine($unique_account_ids, $unique_names);
      }
    }

    echo json_encode($expense_or_income_accounts_array);
  }

  function get_office_banks(int $office_id, int $funder_id)
  {

    //echo $office_id;
    $this->read_db->select(array('DISTINCT(office_bank_id) as item_id', 'bank_name', 'office_bank_name as item_name', 'office_bank_account_number '));

    $this->read_db->join('bank', 'bank.bank_id=office_bank.fk_bank_id');
    $this->read_db->join('office_bank_project_allocation', 'office_bank.office_bank_id=office_bank_project_allocation.fk_office_bank_id');

    //$this->grants_model->create_table_join_statement_with_depth('office_bank',['bank_branch','bank']);

    $office_banks = $this->read_db->get_where(
      'office_bank',
      array('fk_office_id' => $office_id, 'office_bank_is_active' => 1, 'office_bank.fk_funder_id' => $funder_id)
    )->result_object();

    return $office_banks;
  }

  function check_eft_validity()
  {
    $post = $this->input->post();
    $is_valid = true;

    $cheque_number = $post['cheque_number'];
    $office_bank_id = $post['bank_id'];

    $this->read_db->select(array('voucher_cheque_number'));
    $this->read_db->join('voucher_type','voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
    $this->read_db->where(array('fk_office_bank_id' => $office_bank_id,'voucher_cheque_number' => $cheque_number, 'voucher_type_is_cheque_referenced'=>0));
    $used_eft_ref = $this->read_db->get('voucher');

    if ($used_eft_ref->num_rows() > 0) {
      $is_valid = false;
    }

    echo $is_valid;
  }

  function compute_next_voucher_number()
  {
    $office_id = $this->input->post('office_id');
    echo $this->voucher_model->get_voucher_number($office_id);
  }

  function get_office_voucher_date()
  {

    $office_id = $this->input->post('office_id');

    $next_vouching_date = $this->voucher_model->get_voucher_date($office_id);
    $last_vouching_month_date = date('Y-m-t', strtotime($next_vouching_date));

    $voucher_date_field_dates = ['next_vouching_date' => $next_vouching_date, 'last_vouching_month_date' => $last_vouching_month_date];

    echo json_encode($voucher_date_field_dates);
  }

  function get_approve_request_details($office_id)
  {
    //echo "Approved request details";
    echo $this->voucher_library->approved_unvouched_request_details($office_id);
  }
  // private function get_financial_report_for_current_vouching_month($voucher_date, $office_id)
  // {

  //   $current_month_financial_report = $this->voucher_model->get_financial_report_for_current_vouching_month($voucher_date, $office_id);

  //   //check if financial report exists
  //   $financial_report_exists = false;

  //   if (sizeof($current_month_financial_report) == 1) {
  //     $financial_report_exists = true;
  //   }

  //   return $financial_report_exists;
  // }

  private function get_journal_for_current_vouching_month($voucher_date, $office_id)
  {

    $this_month_journal = $this->voucher_model->get_journal_for_current_vouching_month($voucher_date, $office_id);

    //check if journal exists
    $journal_exists = false;

    if (sizeof($this_month_journal) == 1) {
      $journal_exists = true;
    }

    return $journal_exists;
  }

  function delete_duplicate_cj($journal_date, $office_id)
  {

    $journal_month = date('Y-m-1', strtotime($journal_date));

    // Check if a journal for the same month and FCP exists
    $this->write_db->where(array('fk_office_id' => $office_id, 'journal_month' => $journal_month));
    $count_journals = $this->write_db->get('journal')->result_array();

    if (!empty($count_journals)) {

      if (sizeof($count_journals) > 1) {

        $duplicate_cash_journal_id = $count_journals[1]['journal_id'];

        $this->write_db->where(['journal_id' => $duplicate_cash_journal_id, 'fk_office_id' => $office_id]);

        $this->write_db->delete('journal');

        echo 'Duplicate CJ deleted';
      } else {
        echo "No Duplicate CJ exists";
      }
    }
  }


  // function get_approve_request_details($office_id)
  // {
  //   //echo "Approved request details";
  //   echo $this->voucher_library->approved_unvouched_request_details($office_id);
  // }
  function delete_duplicate_mfr($report_date, $office)
  {

    $report_month = date('Y-m-1', strtotime($report_date));

    // Check if a journal for the same month and FCP exists
    $this->write_db->where(array('fk_office_id' => $office, 'financial_report_month' => $report_month));
    $count_financial_report = $this->write_db->get('financial_report')->result_array();

    if (!empty($count_financial_report)) {

      if (sizeof($count_financial_report) > 1) {

        $financial_report_id = '';

        foreach ($count_financial_report as $key => $record) {
          if ($record['financial_report_is_submitted'] == 0) {
            $financial_report_id = $count_financial_report[$key]['financial_report_id'];

            break;
          }
        }
        //Delete duplicate mfr
        $this->write_db->where(['financial_report_id' => $financial_report_id, 'fk_office_id' => $office]);
        $this->write_db->delete('financial_report');

        echo 'Duplicate MFR deleted';
      } else {
        echo "No duplicate MFRs exists";
      }
    }
  }

  private function get_financial_report_for_current_vouching_month($voucher_date, $office_id)
  {

    $current_month_financial_report = $this->voucher_model->get_financial_report_for_current_vouching_month($voucher_date, $office_id);

    //check if financial report exists
    $financial_report_exists = false;

    if (sizeof($current_month_financial_report) == 1) {

      $financial_report_exists = true;
    }

    return $financial_report_exists;
  }


  function create_cash_recipient_account_record($voucher_id, $post)
  {

    $cash_recipient_account_data['cash_recipient_account_name'] = $this->grants_model->generate_item_track_number_and_name('cash_recipient_account')['cash_recipient_account_name'];
    $cash_recipient_account_data['cash_recipient_account_track_number'] = $this->grants_model->generate_item_track_number_and_name('cash_recipient_account')['cash_recipient_account_track_number'];
    $cash_recipient_account_data['fk_voucher_id'] = $voucher_id;

    if (isset($post['fk_office_bank_id']) && $post['fk_office_bank_id'] > 0) {
      $cash_recipient_account_data['fk_office_bank_id'] = $post['cash_recipient_account'];
    } elseif ($post['fk_office_cash_id'] > 0) {
      $cash_recipient_account_data['fk_office_cash_id'] = $post['cash_recipient_account'];
    }

    $cash_recipient_account_data['cash_recipient_account_created_date'] = date('Y-m-d');
    $cash_recipient_account_data['cash_recipient_account_created_by'] = $this->session->user_id;
    $cash_recipient_account_data['cash_recipient_account_last_modified_by'] = $this->session->user_id;

    $cash_recipient_account_data['fk_approval_id'] = $this->grants_model->insert_approval_record('cash_recipient_account');
    $cash_recipient_account_data['fk_status_id'] = $this->grants_model->initial_item_status('cash_recipient_account');

    $this->write_db->insert('cash_recipient_account', $cash_recipient_account_data);
  }

  // function get_duplicate_cheques_for_an_office($office_id, $cheque_number, $office_bank_id, $hold_cheque_number_for_edit = 0)
  // {

  //   echo $this->voucher_model->get_duplicate_cheques_for_an_office($office_id, $cheque_number, $office_bank_id, $hold_cheque_number_for_edit);
  // }

  function get_duplicate_cheques_for_an_office($office_id=0, $cheque_number='', $office_bank_id=0,$hold_cheque_number_for_edit = 0, $has_eft='')
  {

    echo $this->voucher_model->get_duplicate_cheques_for_an_office($office_id, $cheque_number, $office_bank_id, $hold_cheque_number_for_edit,$has_eft);
  }

  /**
   * get_count_of_request
   * @param 
   * @return Integer
   * @author: Onduso
   * @Date: 4/12/2020
   */
  function get_count_of_unvouched_request($office_id)
  {

    echo $this->voucher_model->get_count_of_unvouched_request($office_id);
  }
  
  //Test

  function get_expence_account_income(int $account_id, int $voucher_type){
    
    
    //Get the voucher type effect
    $this->read_db->select(['voucher_type_effect_code']);
    $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
    $this->read_db->where(['voucher_type_id'=>$voucher_type]);
    $voucher_type_effect_code=$this->read_db->get('voucher_type')->row()->voucher_type_effect_code;


    //Get income for an expense

    if($voucher_type_effect_code=='expense'){

      $this->read_db->select(['fk_income_account_id']);

      $this->read_db->where(['expense_account_id' => $account_id]);

      $income_account= $this->read_db->get('expense_account')->row()->fk_income_account_id;

    }
    else{
      $income_account=$account_id;
    }

    echo   $income_account;
    

  }


  /**
   *edit_voucher(): It modifies a voucher and saves it
   *
   * @author Livingstone Onduso: Dated 08-04-2023
   * @access public
   * @param Int $voucher_id - Office primary key
   * @return void - True if reconciliation has been created else false
   */

  public function edit_voucher(int $voucher_id): void
  {

   

    $this->write_db->trans_begin();

    $voucher_type_effect_code = $this->voucher_type_effect_and_code($this->input->post('fk_voucher_type_id'))->voucher_type_effect_code;
    
    $post = $this->input->post();

    $office_cash_id = 0;

    $cheque_number = 0;

    $fk_voucher_type_id=$post['fk_voucher_type_id'];
    $original_voucher_type_effect_before_edit=$post['hold_voucher_type_effect_for_edit'];

    //Voucher header details
    if (isset($post['fk_office_cash_id'])) {
      $office_cash_id = $post['fk_office_cash_id'] == null ? 0 : $post['fk_office_cash_id'];
    }
    if (isset($post['voucher_cheque_number'])) {
      $cheque_number = $post['voucher_cheque_number'] == null ? 0 : $post['voucher_cheque_number'];
    }
    $voucher_header_data = [
      'fk_office_id' => $post['fk_office_id'],
      'voucher_date' => $post['voucher_date'],
      'fk_voucher_type_id' => $fk_voucher_type_id,
      'fk_office_bank_id' => $this->get_office_bank_id_to_post($post['fk_office_id']),
      'fk_office_cash_id' => $office_cash_id,
      'voucher_cheque_number' => $cheque_number,
      'voucher_vendor' => $post['voucher_vendor'],
      'voucher_vendor_address' => $post['voucher_vendor_address'],
      'voucher_description' => $post['voucher_description'],
      'voucher_last_modified_by' => $this->session->user_id
    ];

    $is_voucher_type_affects_bank=$this->voucher_type_model->is_voucher_type_affects_bank($fk_voucher_type_id);

    if($is_voucher_type_affects_bank){

      $voucher_header_data['voucher_cleared']=0;
      $voucher_header_data['voucher_cleared_month']=NULL;
    }


    $quantity = count($post['voucher_detail_quantity']);


    if ($quantity > 0) {
      //Update Voucher Table
      $this->write_db->where(['voucher_id' => $voucher_id]);

      $this->write_db->update('voucher', $voucher_header_data);

      $detail = [];

      //Loop to update Voucher Details
      for ($i = 0; $i <  $quantity; $i++) {
        
        $voucher_detail_quantity = str_replace(",", "", $this->input->post('voucher_detail_quantity')[$i]);

        $voucher_detail_unit_cost = str_replace(",", "", $this->input->post('voucher_detail_unit_cost')[$i]);

        $voucher_detail_total_cost = str_replace(",", "", $this->input->post('voucher_detail_total_cost')[$i]);

        $detail['voucher_detail_quantity'] = $voucher_detail_quantity;

        $detail['voucher_detail_description'] = $this->input->post('voucher_detail_description')[$i];

        $detail['voucher_detail_unit_cost'] = $voucher_detail_unit_cost;

        $detail['voucher_detail_total_cost'] = $voucher_detail_total_cost;
        //$detail['hold_voucher_detail_id']=$this->input->post('hold_voucher_detail_id')[$i];

        //$voucher_detail_id='';
        //Update Records in Voucher Detail Table
        // if($voucher_type==$fk_voucher_type_id){
        //   $voucher_detail_id = $this->input->post('hold_voucher_detail_id')[$i];
        // }


        //Update Records in Voucher Detail Table
        //if original_voucher_type_effect_before_edit is EMPTY it means voucher type effect didn't change since the voucher type dropdown was not toggled
        $voucher_detail_id='';
        if($original_voucher_type_effect_before_edit==$voucher_type_effect_code || $original_voucher_type_effect_before_edit==''){
          $voucher_detail_id = $this->input->post('hold_voucher_detail_id')[$i];
        }
  
       


        if ($voucher_type_effect_code == 'expense') {

          $detail['fk_expense_account_id'] = $this->input->post('voucher_detail_account')[$i];

          $detail['fk_contra_account_id'] = 0;

          $detail['fk_income_account_id'] = $this->input->post('store_income_account_id')[$i];

        } elseif ($voucher_type_effect_code == 'income' || $voucher_type_effect_code == 'bank_to_bank_contra') {

          $detail['fk_expense_account_id'] = 0;

          $detail['fk_contra_account_id'] = 0;

          $detail['fk_income_account_id'] = $this->input->post('store_income_account_id')[$i];
        } elseif ($voucher_type_effect_code == 'bank_contra' || $voucher_type_effect_code == 'cash_contra') {

          $detail['fk_expense_account_id'] = 0;

          $detail['fk_contra_account_id'] = $this->input->post('voucher_detail_account')[$i];
        }

        $detail['fk_project_allocation_id'] = isset($this->input->post('fk_project_allocation_id')[$i]) ? $this->input->post('fk_project_allocation_id')[$i] : 0;

        $detail['fk_request_detail_id'] =  isset($this->input->post('fk_request_detail_id')[$i]) ? $this->input->post('fk_request_detail_id')[$i] : 0;

       

        if ($voucher_detail_id != '') {

          //update the voucher_detail table
          $this->write_db->where(['voucher_detail_id' => $voucher_detail_id]);

          $this->write_db->update('voucher_detail', $detail);


        } else {
          //Insert newlt added detail row

          $detail['fk_voucher_id'] = $voucher_id;

          $detail['voucher_detail_track_number'] = $this->grants_model->generate_item_track_number_and_name('voucher_detail')['voucher_detail_track_number'];

          $detail['voucher_detail_name'] = $this->grants_model->generate_item_track_number_and_name('voucher_detail')['voucher_detail_name'];

          $detail['fk_approval_id'] = $this->grants_model->insert_approval_record('voucher_detail');

          $detail['fk_status_id'] = $this->grants_model->initial_item_status('voucher_detail');

          // $row[] = $detail;

          $this->write_db->insert('voucher_detail', $detail);

        }
      }

      // This is to be used in the future as a replacement for inserting in the details
      // $this->write_db->where(array('voucher_id' => $voucher_id));
      // $this->write_db->update('voucher',["voucher_details" => json_encode($detail)]);

      //echo json_encode($post);
      //If No errors
      if ($this->write_db->trans_status() === FALSE || empty($this->input->post('voucher_detail_quantity'))) {
        $this->write_db->trans_rollback();
        echo 0; //"Voucher Update failed"
      } else {
        $this->write_db->trans_commit();
        echo 1; //"Voucher Updated successfully";
      }
    }
  }

  /**
   * check_cheque_validity(): gets a json string with chq numbers
   * @author Karisa & Onduso 
   * @access public
   * @return void
   */
  public function check_cheque_validity(): void
  {

    $post = $this->input->post();

    $office_bank_id = $post['bank_id'];

    $edit_chq_number = $post['cheque_number'];

    $leaves = $this->cheque_book_model->get_remaining_unused_cheque_leaves($office_bank_id, true);
    // log_message('error', json_encode($leaves));
    //Added by Onduso on 7/6/2023 to be used by Edit voucher function on used checks
    $chq_to_edit_arr = [];

    if ($edit_chq_number > 0 && $edit_chq_number != '') {

      $chq_to_edit_arr['cheque_id'] = (int)$edit_chq_number;

      $chq_to_edit_arr['cheque_number'] = (int)$edit_chq_number;

      array_unshift($leaves, $chq_to_edit_arr);
    }

    // log_message('error', json_encode($leaves));
    //End of changes 

    $reused_cheques = $this->cheque_book_model->get_unused_reused_cheques($office_bank_id);
    // log_message('error', json_encode($reused_cheques));
    foreach ($leaves as $id => $leaf) {
      if (in_array($leaf['cheque_id'], $reused_cheques)) {
        $leaves[$id]['cheque_number'] = $leaf['cheque_number'] . " [Re-used]";
      }
    }

    echo json_encode($leaves);
  }

  /**
   * active_cheques_for_use_in_edit_form(): gets a json string with chq numbers
   * @author Onduso 
   * @access public
   * @return void
   */
  public function active_cheques_for_use_in_edit_form(): void
  {

    $post = $this->input->post();

    $office_bank_id = $post['bank_id'];

    $leaves = $this->cheque_book_model->get_remaining_unused_cheque_leaves($office_bank_id);

    echo json_encode($leaves);
  }

  function get_project_details_account()
  {

    $post = $this->input->post();

    $voucher_type_effect_and_code = $this->voucher_type_effect_and_code($post['voucher_type_id']);

    $voucher_type_effect = $voucher_type_effect_and_code->voucher_type_effect_code;
    $voucher_type_account = $voucher_type_effect_and_code->voucher_type_account_code;

    $project_allocation = [];

    $income_account_id = $post['account_id'];

    $office_accounting_system = $this->office_account_system($this->input->post('office_id'));

    if ($voucher_type_effect == 'expense') {

      $this->read_db->select('income_account_id');
      $this->read_db->join('income_account', 'income_account.income_account_id=expense_account.fk_income_account_id');
      $income_account_id = $this->read_db->get_where(
        'expense_account',
        array('expense_account_id' => $post['account_id'])
      )->row()->income_account_id;
    }

    if ($voucher_type_effect == 'expense' || $voucher_type_effect == 'income') {
      $query_condition = "fk_office_id = " . $post['office_id'] . " AND (project_end_date >= '" . $post['transaction_date'] . "' OR  project_allocation_extended_end_date >= '" . $post['transaction_date'] . "')";
      $this->read_db->select(array('project_allocation_id', 'project_allocation_name'));
      $this->read_db->join('project', 'project.project_id=project_allocation.fk_project_id');

      if ($this->input->post('office_bank_id')) {
        $this->read_db->where(array('fk_office_bank_id' => $this->input->post('office_bank_id')));
        $this->read_db->join('office_bank_project_allocation', 'office_bank_project_allocation.fk_project_allocation_id=project_allocation.project_allocation_id');
      }

      if ($office_accounting_system->account_system_is_allocation_linked_to_account) {
        $this->read_db->where(array('fk_income_account_id' => $income_account_id));
      }

      $this->read_db->where($query_condition);
      $project_allocation = $this->read_db->get('project_allocation')->result_object();
    }


    echo json_encode($project_allocation);

    
  }

  // private function verify_cheque_book_id_by_voucher_type_id($voucher_type_id, $voucher_cheque_number, $office_bank_id){

  //   $cheque_book_id = NULL;

  //   if($this->voucher_type_model->is_voucher_type_cheque_referenced($voucher_type_id)){
  //     $model_cheque_book_id = $this->cheque_book_model->get_cheque_book_id_for_cheque_number($voucher_cheque_number, $office_bank_id);
  //     if($model_cheque_book_id){
  //       $cheque_book_id = $model_cheque_book_id;
  //     }
  //   }
    
  //   return $cheque_book_id;
  // }

  function insert_new_voucher()
  {

    //echo json_encode($this->input->post());exit;

    $header = [];
    $detail = [];
    $row = [];
    $office_id = $this->input->post('fk_office_id');
    $funder_id = $this->input->post('fk_funder_id');
    $voucher_number = $this->input->post('voucher_number');
    // $first_day_of_month = date("Y-m-01", strtotime($this->input->post('voucher_date')));
    $voucher_date = $this->input->post('voucher_date');

    $this->write_db->where(array('fk_office_id' => $office_id, 'voucher_number' => $voucher_number));
    $voucher_obj = $this->write_db->get('voucher');

    if ($voucher_obj->num_rows() > 0) {
      $voucher_number =  $this->voucher_model->get_voucher_number($office_id);
    }

    $this->write_db->trans_begin();

    // $office_id = $this->input->post('fk_office_id');

    // Check if this is the first voucher in the month, if so create a new journal record for the month
    // This must be run before a voucher is created
    if (!$this->voucher_model->office_has_vouchers_for_the_transacting_month($office_id, $voucher_date)) {

      // Create a journal record
      $this->load->model('journal_model');
      $this->journal_model->create_new_journal(date("Y-m-01", strtotime($voucher_date)), $office_id);

      // Insert the month MFR Record
      $this->load->model('financial_report_model');
      $this->financial_report_model->create_financial_report(date("Y-m-01", strtotime($voucher_date)), $office_id);
    }

    //Retry ro Create new_journal_of_month and MFR if it was not created on the first instance when creating first voucher of the month 
    $journal_of_month_exists_flag = $this->get_journal_for_current_vouching_month(date("Y-m-01", strtotime($voucher_date)), $office_id);

    $finacial_report_of_month_exists_flag = $this->get_financial_report_for_current_vouching_month(date("Y-m-01", strtotime($voucher_date)), $office_id);

    if (!$journal_of_month_exists_flag) {
      // Create a journal record
      $this->load->model('journal_model');
      $this->journal_model->create_new_journal(date("Y-m-01", strtotime($voucher_date)), $office_id);
    }

    if (!$finacial_report_of_month_exists_flag) {
      // Create financial report record
      $this->load->model('financial_report_model');
      $this->financial_report_model->create_financial_report(date("Y-m-01", strtotime($voucher_date)), $office_id);
    }

    // Check voucher type

    $voucher_type_effect_and_account = $this->voucher_type_effect_and_code($this->input->post('fk_voucher_type_id'));
    $voucher_type_effect_code = $voucher_type_effect_and_account->voucher_type_effect_code;
    $voucher_type_account_code = $voucher_type_effect_and_account->voucher_type_account_code;


    $header['voucher_track_number'] = $this->grants_model->generate_item_track_number_and_name('voucher')['voucher_track_number'];
    $header['voucher_name'] = $this->grants_model->generate_item_track_number_and_name('voucher')['voucher_name'];

    $header['fk_office_id'] = $office_id;
    $header['fk_funder_id'] = $funder_id;
    $header['voucher_date'] = $voucher_date;
    $header['voucher_number'] = $voucher_number; //$this->input->post('voucher_number');
    $header['fk_voucher_type_id'] = $this->input->post('fk_voucher_type_id');
    // log_message('error', json_encode($this->get_office_bank_id_to_post($office_id)));
    $header['fk_office_bank_id'] = $this->get_office_bank_id_to_post($office_id,  $funder_id);
    $header['fk_office_cash_id'] = $this->input->post('fk_office_cash_id') == null ?: $this->input->post('fk_office_cash_id');
    // log_message('error', json_encode($this->input->post('fk_office_cash_id')));
    $header['voucher_cheque_number'] = $this->input->post('voucher_cheque_number') == null ? 0 : $this->input->post('voucher_cheque_number');
    $header['fk_cheque_book_id'] = $this->voucher_type_model->is_voucher_type_cheque_referenced($header['fk_voucher_type_id']) ? $this->cheque_book_model->get_cheque_book_id_for_cheque_number($header['voucher_cheque_number'], $header['fk_office_bank_id']) : NULL;
    // $header['fk_cheque_book_id'] = $this->verify_cheque_book_id_by_voucher_type_id($header['fk_voucher_type_id'], $header['voucher_cheque_number'], $header['fk_office_bank_id']);
    $header['voucher_vendor'] = $this->input->post('voucher_vendor');
    $header['voucher_vendor_address'] = $this->input->post('voucher_vendor_address');
    $header['voucher_description'] = $this->input->post('voucher_description');

    $header['voucher_created_by'] = $this->session->user_id;
    $header['voucher_created_date'] = date('Y-m-d');
    $header['voucher_last_modified_by'] = $this->session->user_id;

    $header['fk_approval_id'] = $this->grants_model->insert_approval_record('voucher');
    $header['fk_status_id'] = $this->grants_model->initial_item_status('voucher');


    $this->write_db->insert('voucher', $header);

    $header_id = $this->write_db->insert_id();

    if ($this->input->post('cash_recipient_account') !== null) {
      $this->create_cash_recipient_account_record($header_id, $this->input->post());
    }

    // Check if the cheque book is used up. If yes, make the current cheque book inactive.
    // if($header['fk_office_bank_id'] > 0 && $header_id > 0){
    //   $this->check_and_deactivate_fully_used_cheque_book($header['fk_office_bank_id']);
    // }

    if (!empty($this->input->post('voucher_detail_quantity'))) {
      for ($i = 0; $i < sizeof($this->input->post('voucher_detail_quantity')); $i++) {


        $voucher_detail_quantity = str_replace(",", "", $this->input->post('voucher_detail_quantity')[$i]);

        $voucher_detail_unit_cost = str_replace(",", "", $this->input->post('voucher_detail_unit_cost')[$i]);

        $voucher_detail_total_cost = str_replace(",", "", $this->input->post('voucher_detail_total_cost')[$i]);


        $detail['fk_voucher_id'] = $header_id;
        $detail['voucher_detail_track_number'] = $this->grants_model->generate_item_track_number_and_name('voucher_detail')['voucher_detail_track_number'];
        $detail['voucher_detail_name'] = $this->grants_model->generate_item_track_number_and_name('voucher_detail')['voucher_detail_name'];

        $detail['voucher_detail_quantity'] = $voucher_detail_quantity;
        $detail['voucher_detail_description'] = $this->input->post('voucher_detail_description')[$i];
        $detail['voucher_detail_unit_cost'] = $voucher_detail_unit_cost;
        $detail['voucher_detail_total_cost'] = $voucher_detail_total_cost;

        if ($voucher_type_effect_code == 'expense') {
          $expense_account_id = $this->input->post('voucher_detail_account')[$i];
          $detail['fk_expense_account_id'] = $expense_account_id;
          $detail['fk_income_account_id'] = $this->expense_account_model->get_expense_income_account_id($expense_account_id);
          $detail['fk_contra_account_id'] = NULL;
        } elseif ($voucher_type_effect_code == 'income' || $voucher_type_effect_code == 'bank_to_bank_contra') {
          $detail['fk_expense_account_id'] = NULL;
          $detail['fk_income_account_id'] = $this->input->post('voucher_detail_account')[$i];
          $detail['fk_contra_account_id'] = NULL;
        } elseif ($voucher_type_effect_code == 'bank_contra' || $voucher_type_effect_code == 'cash_contra') {
          $detail['fk_expense_account_id'] = NULL;
          $detail['fk_income_account_id'] = NULL;
          $detail['fk_contra_account_id'] = $this->input->post('voucher_detail_account')[$i];
        }elseif($voucher_type_account_code == 'cash' || $voucher_type_effect_code == 'cash_contra'){
          $detail['fk_expense_account_id'] = NULL;
          $detail['fk_income_account_id'] = NULL;
          $detail['fk_contra_account_id'] = $this->input->post('voucher_detail_account')[$i];
        }
       

        $detail['fk_project_allocation_id'] = isset($this->input->post('fk_project_allocation_id')[$i]) ? $this->input->post('fk_project_allocation_id')[$i] : null;
        $detail['fk_request_detail_id'] =  isset($this->input->post('fk_request_detail_id')[$i]) ? $this->input->post('fk_request_detail_id')[$i] : NULL;
        $detail['fk_approval_id'] = $this->grants_model->insert_approval_record('voucher_detail');
        $detail['fk_status_id'] = $this->grants_model->initial_item_status('voucher_detail');

        // // if request_id > 0 give the item the final status
        if (isset($this->input->post('fk_request_detail_id')[$i]) && $this->input->post('fk_request_detail_id')[$i] > 0) {
          $this->update_request_detail_status_on_vouching($this->input->post('fk_request_detail_id')[$i], $header_id);
          // Check if all request detail items in the request has the last status and update the request to last status too
          $this->update_request_on_paying_all_details($this->input->post('fk_request_detail_id')[$i]);
        }

        $row[] = $detail;
      }

      //echo json_encode($row);
      $this->write_db->insert_batch('voucher_detail', $row);

      // This is a future implementation of inserting a voucher detail
      // log_message('error', json_encode($row));

      // $this->write_db->where(['voucher_id' => $header_id]);
      // $this->write_db->update('voucher',['voucher_details' => json_encode($row)]);
    }

    //$this->write_db->trans_complete();

    if ($this->write_db->trans_status() === FALSE || empty($this->input->post('voucher_detail_quantity'))) {
      $this->write_db->trans_rollback();
      echo 0; //"Voucher posting failed"
    } else {
      $this->write_db->trans_commit();
      echo 1; //"Voucher posted successfully";
    }
  }

  function get_office_bank_id_to_post($office_id, $funder_id)
  {

    $office_bank_id =  $this->input->post('fk_office_bank_id') == null ? 0 : $this->input->post('fk_office_bank_id');

    if ($office_bank_id == 0) {
      // Get id of active office bank
      $office_bank_id = $this->office_bank_model->get_active_office_banks($office_id, $funder_id)[0]['office_bank_id'];
    }

    return $office_bank_id;
  }

  function get_remaining_unused_cheque_leaves($office_bank_id)
  {

    $unused_cheque_leaves = $this->cheque_book_model->get_remaining_unused_cheque_leaves($office_bank_id);

    //$first_leaf = current($unused_cheque_leaves);

    return json_encode($unused_cheque_leaves);
  }

  function update_request_detail_status_on_vouching($request_detail_id, $voucher_id)
  {
    // Update the request detail record
    $this->write_db->where(array('request_detail_id' => $request_detail_id));
    $this->write_db->update('request_detail', array('fk_voucher_id' => $voucher_id));
  }

  function update_request_on_paying_all_details($request_detail_id)
  {
    $request_id = $this->read_db->get_where('request_detail', array('request_detail_id' => $request_detail_id))->row()->fk_request_id;
    $unpaid_request_details = $this->read_db->get_where('request_detail', array('fk_request_id' => $request_id, 'fk_voucher_id' => 0))->num_rows();

    if ($unpaid_request_details == 0) {
      $this->write_db->where(array('request_id' => $request_id));
      $this->write_db->update('request', array('request_is_fully_vouched' => 1));
    }
  }

  /**
   *unapproved_month_vouchers(): Returns the total of unapproved vouchers for current month for an office
   *
   * @author Livingstone Onduso: Dated 08-04-2023
   * @access public
   * @param Int $office_id - Office primary key
   * @param String $reporting_month - Date of the month
   * @param String $effect_code - Effect code e.g. income or expense
   * @param String $account_code - Account code e.g cash or bank
   * @param Int $cash_type_id - Cash type e.g. petty cash
   * @param Int $office_bank_id - Cash type e.g. bank 1 
   * @return float - True if reconciliation has been created else false
   */

  public function get_unapproved_month_vouchers()
  {
    // int $office_id, string $reporting_month, string $effect_code, string $account_code, int $cash_type_id = 0, int $office_bank_id = 0
    $post = $this->input->post();

    $office_id = $post['office_id'];
    $reporting_month = $post['transction_date'];
    $effect_code = $post['effect_code'];
    $account_code = $post['account_code'];
    $cash_type_id = $post['office_cash_id'];
    $office_bank_id = $post['office_bank_id'];
    $funder_id = $post['funder_id'];

    $unproved_expense_vouchers = $this->voucher_model->unapproved_month_vouchers($office_id, $reporting_month, $effect_code, $account_code, [$funder_id], $cash_type_id, $office_bank_id);

    echo json_encode($unproved_expense_vouchers);
  }

  /**
   *compute_bank_balance(): Returns json string of unapproaved float bank amount to used in a ajax call
   * @author Livingstone Onduso: Dated 08-04-2023
   * @access public
   * @return void - json string
   */
  function compute_bank_balance($edit_voucher=0): void
  {

    $this->load->model('financial_report_model');

    $post = $this->input->post();

    $office_id = $post['office_id'];
    $funder_id = $post['funder_id'];
    $office_bank_id = $post['office_bank_id'];
    $reporting_month = date('Y-m-01', strtotime($post['transaction_date']));

    //Total bank approved/fully paid vouchers
    $fully_approved_vouchers_bank_balance = $this->financial_report_model->compute_cash_at_bank([$office_id], $reporting_month, [$funder_id], [], [$office_bank_id], true);

    //Income to bank
    $unapproved_cash_recieved_to_bank_vouchers = $this->voucher_model->unapproved_month_vouchers($office_id, $reporting_month,  'income', 'bank', [$funder_id], 0, $office_bank_id);

    $unapproved_petty_cash_rebank_vouchers = $this->voucher_model->unapproved_month_vouchers($office_id, $reporting_month,  'cash_contra', 'cash', [$funder_id], 0, $office_bank_id);

    //Bank expenses unapproved vouchers 
    $unapproved_bank_expense_vouchers = $this->voucher_model->unapproved_month_vouchers($office_id, $reporting_month,  'expense', 'bank', [$funder_id], 0, $office_bank_id);

    $unapproved_petty_cash_deposit_vouchers = $this->voucher_model->unapproved_month_vouchers($office_id, $reporting_month,  'bank_contra', 'bank', [$funder_id], 0, $office_bank_id);

    $total_bank_expenses = $unapproved_bank_expense_vouchers + $unapproved_petty_cash_deposit_vouchers;

    $total_bank_balance = ($unapproved_cash_recieved_to_bank_vouchers + $unapproved_petty_cash_rebank_vouchers + $fully_approved_vouchers_bank_balance) - $total_bank_expenses;
    
    //Code when Editing a voucher to correct bug found from SN ticket
    if($edit_voucher==1){
      $voucher_being_edited_id=$post['voucher_being_edited_id'];
      $voucher_id=hash_id($voucher_being_edited_id,'decode');

      //Voucher total cost
      $total_cost_on_voucher_being_edited=$this->voucher_model->total_cost_for_voucher_to_edit($voucher_id);

      $total_bank_balance=$total_bank_balance+$total_cost_on_voucher_being_edited;
      
    }

    echo json_encode(['approved_and_unapproved_vouchers_bank_bal' => round($total_bank_balance, 2)]);
  }


  /**
   *compute_cash_balance(): Returns json string of unapproaved float cash amount to used in a ajax call
   * @author Livingstone Onduso: Dated 08-04-2023
   * @access public
   * @return void - json string
   */

  public function compute_cash_balance($edit_voucher=0): void
  {

    $this->load->model('financial_report_model');

    $post = $this->input->post();

    $office_id = $post['office_id'];
    $funder_id = $post['funder_id'];
    $office_cash_id = $post['office_cash_id'];

    $reporting_month = date('Y-m-01', strtotime($post['transaction_date']));

    //Get unapproved and approved vourchers
    $fully_approved_vouchers_cash_balance = $this->financial_report_model->compute_cash_at_hand([$office_id], $reporting_month, [$funder_id], [], [], $office_cash_id, true);

    $unsubmitted_and_submitted_vouchers_cash_income = $this->voucher_model->unapproved_month_vouchers($office_id, $reporting_month,  'bank_contra', 'bank', [$funder_id], $office_cash_id);

    //Total Income
    $total_cash_income = $unsubmitted_and_submitted_vouchers_cash_income + $fully_approved_vouchers_cash_balance;

    //Total Expense
    $total_cash_expense = $this->voucher_model->unapproved_month_vouchers($office_id, $reporting_month,  'expense', 'cash', [$funder_id], $office_cash_id);

    $unsubmitted_vouchers_cash_rebank_voucher = $this->voucher_model->unapproved_month_vouchers($office_id, $reporting_month,  'cash_contra', 'cash', [$funder_id], $office_cash_id);

    //Total Cash balance
    $total_cash_balance = $total_cash_income - ($total_cash_expense + $unsubmitted_vouchers_cash_rebank_voucher);

    //Code when Editing a voucher to correct bug found from SN ticket
    if($edit_voucher==1){
      $voucher_being_edited_id=$post['voucher_being_edited_id'];
      $voucher_id=hash_id($voucher_being_edited_id,'decode');

      //Voucher total cost
      $total_cost_on_voucher_being_edited=$this->voucher_model->total_cost_for_voucher_to_edit($voucher_id);

      $total_cash_balance =$total_cash_balance +$total_cost_on_voucher_being_edited;
      
    }

    echo json_encode(['approved_and_unapproved_vouchers_cash_bal' => round($total_cash_balance, 2)]);
  }

  /**
   *cash_limit_exceed_check(): Returns 1 for true and 0 for false
   * @author Livingstone Onduso: Dated 08-04-2023
   * @access public
   * @return void - echo 1 0r 0 to be used with ajax
   */

  public function cash_limit_exceed_check(): void
  {

    $this->load->model('financial_report_model');

    $post = $this->input->post();

    $voucher_type_id = $post['voucher_type_id'];

    $amount = floatval(preg_replace('/[^\d.]/', '', $post['amount']));

    //$office_bank_id = $post['office_bank_id'];

    $office_cash_id = $post['office_cash_id'];

    $voucher_cheque_number = $post['cheque_number'];

    $cash_limit_exceeded = 0;

    if ($office_cash_id != "" && $voucher_cheque_number == "") {

      $unapproved_and_approved_cash_vouchers = $post['unapproved_and_approved_vouchers'];
    } else if ($voucher_cheque_number != "" && $office_cash_id != "") {

      $unapproved_and_approved_cash_vouchers = $post['bank_balance'];
    } else {

      $unapproved_and_approved_cash_vouchers = $post['bank_balance'];
    }

    //Get the account and effect codes
    $voucher_type_obj = $this->voucher_model->get_account_and_effect_codes($voucher_type_id);

    //Check if the transaction an expense or cash/bank contra and the balance is negative
    if ((strpos($unapproved_and_approved_cash_vouchers, "-") === 0) && ($voucher_type_obj->voucher_type_effect_code == "cash_contra" || $voucher_type_obj->voucher_type_effect_code == "expense" || $voucher_type_obj->voucher_type_effect_code == "bank_contra")) {
      $cash_limit_exceeded = 1;
    } else {

      //Compute the expense and cash balance balance
      if ((strpos($unapproved_and_approved_cash_vouchers, "-") === 0)) {

        $unapproved_and_approved_cash_vouchers = -round(floatval(preg_replace('/[^\d.]/', '', $unapproved_and_approved_cash_vouchers)), 2);

        $unapproved_and_approved_cash_vouchers += $amount;
      } else {
        $unapproved_and_approved_cash_vouchers = round(floatval(preg_replace('/[^\d.]/', '', $unapproved_and_approved_cash_vouchers)), 2);

        $unapproved_and_approved_cash_vouchers -= $amount;
      }

      //Check if amount is > than Balance

      if (round($unapproved_and_approved_cash_vouchers, 2) < 0 && ($voucher_type_obj->voucher_type_effect_code == 'expense' || $voucher_type_obj->voucher_type_effect_code == 'bank_contra' || $voucher_type_obj->voucher_type_effect_code == 'cash_contra')) {

        $cash_limit_exceeded = 1;
      }
    }

    echo  $cash_limit_exceeded;
  }
}
