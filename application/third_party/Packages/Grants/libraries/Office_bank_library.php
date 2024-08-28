<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

 class Office_bank_library extends Grants
 {

   private $CI;

   function __construct(){
     parent::__construct();
     $this->CI =& get_instance();
   }

  function index(){

  }

  function change_field_type(){
    $change_field_type = array();

    $change_field_type['office_bank_chequebook_size']['field_type']='select';
  
    $change_field_type['office_bank_chequebook_size']['options'] = [50 => 50,60=>60, 100 => 100, 150 => 150, 200 => 200];


    return $change_field_type;
  }

  function page_position(){

    $widget = [];
    
    if($this->CI->action == 'view' || $this->CI->action == 'edit'){
      $message = $this->create_account_balance_message_for_office_bank_account(); 
      $widget['position_1']['view'][] =  '<div style = "color:red;font-weight:bold;text-align:center;">'.$message.'</div><hr/>';
      $widget['position_1']['edit'][] =  '<div style = "color:red;font-weight:bold;text-align:center;">'.$message.'</div><hr/>';
    }

    if($this->CI->action == 'single_form_add'){
      // Only show if this is not the first office bank account
      $message = $this->checks_when_creating_office_bank();
      $widget['position_1']['single_form_add'][] = $message;
    }

    return $widget;
  }

  private function checks_when_creating_office_bank(){
    $message = '<div id = "office_bank_conditions" class = "hidden">
                  <b>'.get_phrase('actions_before_creating_a_new_office_bank','Before creating another office bank account for an office for an office to transition to, please make sure that you have done the following').':</b><br/><br/>
                  
                  <div style = "text-align: left;position:relative; left: 250px;">
                    <input type = "checkbox" class = "office_bank_checklist_items"/> '.get_phrase('committee_minutes_required','The FCP has discussed and minuted the matter with the FCP committee and have submitted the minutes to you').' <br/>
                    <input type = "checkbox" class = "office_bank_checklist_items"/> '.get_phrase('submitted_bank_details_to_finance','The new bank account details have been sent to the National Office accountants and ready to receive funds in the next disbursement').' <br/>
                    <input type = "checkbox" class = "office_bank_checklist_items"/> '.get_phrase('sent_copy_of_cheque_book_leaf','The FCP has sent you a screenshot of the first leaf of the cheque book given by the new bank').' <br/>
                    <input type = "checkbox" class = "office_bank_checklist_items"/> '.get_phrase('noted_amount_to_transfer','You are aware of the total amount the FCP is going to transfer from the old office bank to the new office bank. You will be required to follow up on this in your next FCP visit').' <br/>
                    <input type = "checkbox" class = "office_bank_checklist_items"/> '.get_phrase('noted_outstanding_cheques','You have checked if the FCP has outstanding cheques in the old office bank and adviced them not to request for the physical closure of the old account until all cheques are paid by the bank').' <br/>
                    <input type = "checkbox" class = "office_bank_checklist_items"/> '.get_phrase('uploaded_minutes_and_cheque_leaf','Make sure that you upload the minutes, last bank statement of the previous bank and first leaf the new bank account cheque book in the comment section of this record after creation for future reference').' <br/><br/>
                  
                  
                  '.get_phrase('funds_transfer_condition','Note: FCP can only transfer funds to the new office bank as per the book balance and not the whole amount in the old bank account unless they lack outstanding cheques').' <br/><br/>

                  '.get_phrase('After creating the new office bank record, you will be requred to do the following follow up. Make sure you create a Connect Facilitation Plan Task for each of the items below').':<br/><br/>

                
                    1. '.get_phrase('follow_up_on_transfered_funds','The FCP transferred the exact funds as agreed when creating the new office bank record').' <br/>
                    2. '.get_phrase('follow_up_on_transfer_voucher','A Bank to Bank Transfer voucher has been created in the system for the funds tranfers done').' <br/>
                    3. '.get_phrase('follow_up_on_cheques_clearance','All the outstanding cheques that were present in the old bank have been fully paid and the FCP has given instrusctions to close the account').' <br/>
                  </div>
              </div>
              ';
    
    return $message;
  }

  function create_account_balance_message_for_office_bank_account(){
    $this->CI->load->model('office_bank_model');
    $this->CI->load->model('voucher_model');
    $this->CI->load->model('country_currency_model');

    // Office bank Id
    $office_bank_id = hash_id($this->CI->id,'decode');

    // Office Id
    $this->CI->read_db->where(array('office_bank_id' => $office_bank_id));
    $office_id = $this->CI->read_db->get('office_bank')->row()->fk_office_id;

    // Account balance amount
    $reporting_month = date('Y-m-01',strtotime($this->CI->voucher_model->get_voucher_date($office_id)));
    $account_balance = $this->CI->office_bank_model->office_bank_account_balance($office_bank_id, $reporting_month);

    // Office Currency Code
    $currency_code = $this->CI->country_currency_model->get_country_currency_code_by_office_id($office_id);

    $message = '';

    if($account_balance > 0){
      $message = get_phrase(
        'account_balance_deactivation_notification',
        'Office bank account cannot be closed/deactivated if balance is not zero. The current book balance is {{currency_code}}. {{account_balance}}.', 
        ['currency_code' => $currency_code, 'account_balance' => number_format(round($account_balance,2),2)]);
    }

    return $message;
  }

}
