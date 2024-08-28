<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Voucher_type_model extends MY_Model 
{
  public $table = 'voucher_type'; // you MUST mention the table name
  public $name_field = "voucher_type_name";
  //public $primary_key = "voucher_detail_id";

  function __construct(){
    parent::__construct();
    $this->load->database();

  }

  function delete($id = null){

  }

  function index(){}

  public function lookup_tables(){
    return array('voucher_type_account','voucher_type_effect','account_system');
  }

  public function detail_tables(){}

  public function list(){}

  public function view(){}

  function get_active_voucher_types($account_system_id, $office_id, $transaction_date){

    $this->load->model('office_bank_model');

    $office_banks_for_office = $this->office_bank_model->get_office_banks_for_office($office_id);
    // log_message('error', json_encode($office_banks_for_office));

    // Do not show bank_to_bank_contra voucher effect types if the office has only 1 bank
    if(count($office_banks_for_office['is_active']) < 2){
      $this->read_db->where_not_in('voucher_type_effect_code',['bank_to_bank_contra']);
    }

    if(!empty($office_banks_for_office['chequebook_exemption_expiry_date'])){
      
      foreach($office_banks_for_office['chequebook_exemption_expiry_date'] as $office_bank_id => $chequebook_exemption_expiry_date){
        if($chequebook_exemption_expiry_date > $transaction_date){
          // log_message('error', json_encode(compact('chequebook_exemption_expiry_date','transaction_date')));
          $this->load->model('cheque_book_model');

          $leaves = $this->cheque_book_model->get_remaining_unused_cheque_leaves($office_bank_id);
          // log_message('error', json_encode($leaves));
          if(empty($leaves)){
            $this->read_db->where(['voucher_type_is_cheque_referenced' => 0]);
          }
          break;
        }
      }

    }

    $this->read_db->select(array('voucher_type_id','voucher_type_name','voucher_type_account_code','voucher_type_effect_code'));
    $this->read_db->join('account_system','account_system.account_system_id=voucher_type.fk_account_system_id');
    $this->read_db->join('voucher_type_effect','voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
    $this->read_db->join('voucher_type_account','voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
    $voucher_types =  $this->read_db->get_where('voucher_type',array('voucher_type_is_active'=>1,'voucher_type_is_hidden' => 0,'fk_account_system_id'=>$account_system_id))->result_object();
  
    return $voucher_types;
  }

  function voucher_type_requires_cheque_referencing($voucher_type_id){
    $this->read_db->select(array('voucher_type_is_cheque_referenced'));
    
    $voucher_type_is_cheque_referenced =  $this->read_db->get_where('voucher_type',
    array('voucher_type_id'=>$voucher_type_id))->row()->voucher_type_is_cheque_referenced;

    return $voucher_type_is_cheque_referenced;
  }

  function list_table_where(){
    if(!$this->session->system_admin){
      $this->read_db->where(array('account_system_code'=>$this->session->user_account_system,'voucher_type_is_hidden' => 0));
    }
  }

  function single_form_add_visible_columns()
  {
    $columns = [
      'voucher_type_name',
      'voucher_type_abbrev',
      'voucher_type_is_active',
      'voucher_type_account_name',
      'voucher_type_effect_name',
      'voucher_type_is_cheque_referenced',
      'account_system_name'
    ];

    if($this->session->system_admin){
      array_push($columns,'voucher_type_is_hidden','account_system_name');
    }else{
      array_push($columns,'account_system_name');
    }

    return $columns;
  }

  function edit_visible_columns(){
    $columns = [
      'voucher_type_name',
      'voucher_type_abbrev',
      'voucher_type_is_active',
      'voucher_type_account_name',
      'voucher_type_effect_name',
      'voucher_type_is_cheque_referenced',
    ];

    if($this->session->system_admin){
      array_push($columns,'voucher_type_is_hidden','account_system_name');
    }else{
      array_push($columns,'account_system_name');
    }

    return $columns;
  }

  public function get_hidden_voucher_type($voucher_type_abbrev, $account_system_id){
    $abbrev = ['IFTR','EFTR','VChq'];

    if(!in_array($voucher_type_abbrev, $abbrev)){
      return (object)[];
    }

    $this->read_db->join('voucher_type_account','voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
    $this->read_db->join('voucher_type_effect','voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
    $this->read_db->where(array('voucher_type_is_active' => 1, 'voucher_type_is_hidden <> ' => 0));
    $this->read_db->where(array('fk_account_system_id' => $account_system_id, 'voucher_type_abbrev' => $voucher_type_abbrev));
    $voucher_type_obj = $this->read_db->get('voucher_type');

    $voucher_type = [];
    if($voucher_type_obj->num_rows() > 0){
      $voucher_type = $voucher_type_obj->row();
    }

    return $voucher_type;
  }

  // function get_hidden_voucher_type($voucher_type_account_code,$voucher_type_effect_code, $voucher_type_is_hidden=1, $voucher_type_is_cheque_referenced=0){
    
  //   $this->read_db->join('voucher_type_account','voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
  //   $this->read_db->join('voucher_type_effect','voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
  //   $this->read_db->where(array('voucher_type_is_active' => 1, 'voucher_type_is_hidden' => $voucher_type_is_hidden));
  //   $this->read_db->where(array('voucher_type_account_code' => $voucher_type_account_code, 'voucher_type_effect_code' => $voucher_type_effect_code));
  //   $this->read_db->where(array('fk_account_system_id' => $this->session->user_account_system_id,'voucher_type_is_cheque_referenced' => $voucher_type_is_cheque_referenced));
  //   $voucher_type = $this->read_db->get('voucher_type')->row();

  //   // log_message('error', json_encode($voucher_type));

  //   return $voucher_type;
  // }

  function check_if_hidden_bank_income_expense_voucher_type_present(){

    $hidden_bank_income_expense_voucher_type_present = false;

    $this->read_db->join('voucher_type_account','voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
    $this->read_db->join('voucher_type_effect','voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
    $this->read_db->where(array('voucher_type_is_active' => 1, 'voucher_type_is_hidden' => 1));
    $this->read_db->where(array('voucher_type_account_code' => 'bank'));
    $this->read_db->where_in('voucher_type_effect_code', ['income','expense']);
    $this->read_db->where(array('fk_account_system_id' => $this->session->user_account_system_id));
    $voucher_type = $this->read_db->get('voucher_type');

    if($voucher_type->num_rows() < 2){
      // Create the missing one
      $hidden_bank_income_expense_voucher_type_present = $this->create_hidden_voucher_types();
    }else{
      $hidden_bank_income_expense_voucher_type_present = true;
    }

    return $hidden_bank_income_expense_voucher_type_present;
  }

  /**
   * Check if the voucher type id provided is set to be requiring a cheque number reference
   * @author Nicodemus Kairsa Mwambire nkarisa@ke.ci.org
   * @date 18th March 2024
   * @param integer voucher_type_id  - Country voucher type id
   * @return bool
   * @source master-record-cheque-id
   * @version v24.3.0.1
   */
  public function is_voucher_type_cheque_referenced($voucher_type_id): bool{
    $is_voucher_type_cheque_referenced = false;
    $this->read_db->where(['voucher_type_id' => $voucher_type_id, 'voucher_type_is_cheque_referenced' => 1]);
    $voucher_type_obj = $this->read_db->get('voucher_type');
 
    if($voucher_type_obj->num_rows() > 0){
      $is_voucher_type_cheque_referenced = true;
    }
 
    return $is_voucher_type_cheque_referenced;
  }

  //New method
  function is_voucher_type_affects_bank(int $voucher_type_id): bool {

    $is_voucher_type_affects_bank = false;

    $this->read_db->where(['voucher_type_id' => $voucher_type_id ]);

    $this->read_db->group_start();

   $this->read_db->where(['voucher_type_account_code'=>'bank' ]);

    $this->read_db->or_where(['voucher_type_effect_code'=>'cash_contra' ]);

    $this->read_db->group_end();

    $this->read_db->join('voucher_type_account','voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');

    $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');

    $voucher_obj = $this->read_db->get('voucher_type');
 
    if($voucher_obj->num_rows() > 0){
      $is_voucher_type_affects_bank = true;
    }
 
    return $is_voucher_type_affects_bank;
  }

  function office_hidden_bank_voucher_types($office_id){

    $account_system_id = $this->read_db->get_where('office',array('office_id' => $office_id))->row()->fk_account_system_id;
    
    $voucher_type_ids = [];

    $this->read_db->select(array('voucher_type_id','voucher_type_effect_code'));
    $this->read_db->join('voucher_type_account','voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
    $this->read_db->join('voucher_type_effect','voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
    $this->read_db->where(array('voucher_type_is_active' => 1, 'voucher_type_is_hidden' => 1));
    $this->read_db->where(array('voucher_type_account_code' => 'bank'));
    $this->read_db->where(array('fk_account_system_id' => $account_system_id));
    $voucher_types = $this->read_db->get('voucher_type');


    

    if($voucher_types->num_rows() > 0){
      $voucher_type_ids = array_column($voucher_types->result_array(),'voucher_type_id');
    }

    return $voucher_type_ids;
  }

  function check_missing_hidden_voucher_type_by_voucher_type_effect_code($voucher_type_effect_code){

    $this->read_db->join('voucher_type_account','voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
    $this->read_db->join('voucher_type_effect','voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
    $this->read_db->where(array('voucher_type_is_active' => 1, 'voucher_type_is_hidden' => 1));
    $this->read_db->where(array('voucher_type_account_code' => 'bank'));
    $this->read_db->where(array('voucher_type_effect_code' => $voucher_type_effect_code));
    $this->read_db->where(array('fk_account_system_id' => $this->session->user_account_system_id));
    $voucher_type = $this->read_db->get('voucher_type');

    $check = false;

    if($voucher_type->num_rows() > 0){
      $check = true;
    }

    return $check;
  }

  function create_missing_void_hidden_voucher_types($account_system_id){

    $this->read_db->select(array('voucher_type_effect_id','voucher_type_effect_code'));
    $this->read_db->where_in('voucher_type_effect_code', ['income','expense']);
    $voucher_type_effects = $this->read_db->get('voucher_type_effect')->result_array();

    $voucher_type_effect_ids = array_column($voucher_type_effects,'voucher_type_effect_id');
    $voucher_type_effect_codes = array_column($voucher_type_effects,'voucher_type_effect_code');

    $ordered_voucher_type_effect =  array_combine($voucher_type_effect_codes, $voucher_type_effect_ids);

    $this->read_db->where(array('voucher_type_account_code' => 'bank'));
    $bank_voucher_type_account_id = $this->read_db->get('voucher_type_account')->row()->voucher_type_account_id;

    $data['voucher_type_track_number'] = $this->grants_model->generate_item_track_number_and_name('voucher_type')['voucher_type_track_number'];
    $data['voucher_type_name'] = 'Voided Cheque';
    $data['voucher_type_is_active'] = 1;
    $data['voucher_type_is_hidden'] = 1;
    $data['voucher_type_abbrev'] = 'VChq';
    $data['fk_voucher_type_account_id'] = $bank_voucher_type_account_id;
    $data['fk_voucher_type_effect_id'] = $ordered_voucher_type_effect['expense'];
    $data['voucher_type_is_cheque_referenced'] = 1;
    $data['fk_account_system_id'] = $account_system_id;
    $data['voucher_type_created_by'] = $this->session->user_id;
    $data['voucher_type_created_date'] = date('Y-m-d');
    $data['voucher_type_last_modified_by'] = $this->session->user_id;
    $data['voucher_type_last_modified_date'] = date('Y-m-d h:i:s');
    $data['fk_status_id'] = $this->general_model->get_max_approval_status_id('voucher_type', [], $account_system_id)[0];

    if($this->check_missing_hidden_voiding_voucher_type($account_system_id)){
      // log_message('error', json_encode($data));
      $this->write_db->insert('voucher_type',$data);
    }
  }

  private function check_missing_hidden_voiding_voucher_type($account_system_id){
    $this->read_db->where(['fk_account_system_id' => $account_system_id, 'voucher_type_abbrev' => 'VChq']);
    $voucher_type_obj = $this->read_db->get('voucher_type');

    if($voucher_type_obj->num_rows() == 0){
      return true;
    }

    return false;
  }

  function create_hidden_voucher_types(){
    //return true;

    $this->read_db->select(array('voucher_type_effect_id','voucher_type_effect_code'));
    $this->read_db->where_in('voucher_type_effect_code', ['income','expense']);
    $voucher_type_effects = $this->read_db->get('voucher_type_effect')->result_array();

    $voucher_type_effect_ids = array_column($voucher_type_effects,'voucher_type_effect_id');
    $voucher_type_effect_codes = array_column($voucher_type_effects,'voucher_type_effect_code');

    $ordered_voucher_type_effect =  array_combine($voucher_type_effect_codes, $voucher_type_effect_ids);

    $this->read_db->where(array('voucher_type_account_code' => 'bank'));
    $bank_voucher_type_account_id = $this->read_db->get('voucher_type_account')->row()->voucher_type_account_id;

    $data['income']['voucher_type_track_number'] = $this->grants_model->generate_item_track_number_and_name('voucher_type')['voucher_type_track_number'];
    $data['income']['voucher_type_name'] = 'Income Funds Transfer Requests';
    $data['income']['voucher_type_is_active'] = 1;
    $data['income']['voucher_type_is_hidden'] = 1;
    $data['income']['voucher_type_abbrev'] = 'IFTR';
    $data['income']['fk_voucher_type_account_id'] = $bank_voucher_type_account_id;
    $data['income']['fk_voucher_type_effect_id'] = $ordered_voucher_type_effect['income'];
    $data['income']['voucher_type_is_cheque_referenced'] = 0;
    $data['income']['fk_account_system_id'] = $this->session->user_account_system_id;
    $data['income']['voucher_type_created_by'] = $this->session->user_id;
    $data['income']['voucher_type_created_date'] = date('Y-m-d');
    $data['income']['voucher_type_last_modified_by'] = $this->session->user_id;
    $data['income']['voucher_type_last_modified_date'] = date('Y-m-d h:i:s');
    $data['income']['fk_status_id'] = $this->general_model->get_max_approval_status_id('voucher_type')[0];

    $data['expense']['voucher_type_track_number'] = $this->grants_model->generate_item_track_number_and_name('voucher_type')['voucher_type_track_number'];
    $data['expense']['voucher_type_name'] = 'Expense Funds Transfer Requests';
    $data['expense']['voucher_type_is_active'] = 1;
    $data['expense']['voucher_type_is_hidden'] = 1;
    $data['expense']['voucher_type_abbrev'] = 'EFTR';
    $data['expense']['fk_voucher_type_account_id'] = $bank_voucher_type_account_id;
    $data['expense']['fk_voucher_type_effect_id'] = $ordered_voucher_type_effect['expense'];
    $data['expense']['voucher_type_is_cheque_referenced'] = 0;
    $data['expense']['fk_account_system_id'] = $this->session->user_account_system_id;
    $data['expense']['voucher_type_created_by'] = $this->session->user_id;
    $data['expense']['voucher_type_created_date'] = date('Y-m-d');
    $data['expense']['voucher_type_last_modified_by'] = $this->session->user_id;
    $data['expense']['voucher_type_last_modified_date'] = date('Y-m-d h:i:s');
    $data['expense']['fk_status_id'] = $this->general_model->get_max_approval_status_id('voucher_type')[0];

    $flag = false;

    $this->write_db->trans_start();

    if(!$this->check_missing_hidden_voucher_type_by_voucher_type_effect_code('income')){
      $this->write_db->insert('voucher_type',$data['income']);
    }

    if(!$this->check_missing_hidden_voucher_type_by_voucher_type_effect_code('expense')){
      $this->write_db->insert('voucher_type',$data['expense']);
    }

    $this->write_db->trans_complete();

    if($this->write_db->trans_status() == true){
      $flag = true;
    }

    return $flag;

  }
  
}
