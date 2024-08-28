<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Replicate_opening_balances extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('replicate_opening_balances_library');

  }

  function index(){}

  function result($id = ""){

    $result = [];
    
    if($this->action == 'single_form_add'){
      $result['origin_office'] = $this->get_office($this->id);
      $result['target_offices'] = $this->get_offices_with_default_bank();
    }else{
      $result = parent::result($id);
    }

    return $result;
  }


  function get_office($system_opening_balance_id){

    $this->read_db->select(array('office_id','office_name','system_opening_balance_id','system_opening_balance_name'));
    $this->read_db->where(array("system_opening_balance_id" => hash_id($system_opening_balance_id,'decode')));
    $this->read_db->join('office','office.office_id=system_opening_balance.fk_office_id');
    $system_opening_balance_office = $this->read_db->get('system_opening_balance')->row_array();

    return $system_opening_balance_office;
  }

  function get_offices_with_default_bank(){

    $this->read_db->select(array('office_id','office_name'));
    $this->read_db->join('office_bank','office_bank.fk_office_id=office.office_id');
    $this->read_db->join('system_opening_balance','system_opening_balance.fk_office_id=office.office_id');
    $this->read_db->where(array('office_bank_is_active' => 1, 'office_bank_is_default' => 1));
    // $this->read_db->where('NOT EXISTS (SELECT * FROM opening_fund_balance WHERE opening_fund_balance.fk_system_opening_balance_id=system_opening_balance.system_opening_balance_id AND office.fk_account_system_id = '.$this->session->user_account_system_id.')','',FALSE);
    $this->read_db->where('NOT EXISTS (SELECT * FROM financial_report WHERE financial_report.fk_office_id=office.office_id AND financial_report_is_submitted = 1  AND office.fk_account_system_id = '.$this->session->user_account_system_id.')','',FALSE);
    if(!$this->session->system_admin){
      $this->read_db->where(array('office.fk_account_system_id' => $this->session->user_account_system_id));
    }

    $offices = $this->read_db->get('office')->result_array();

    return $offices;
    
  }

  function create_replication(){
    $post = $this->input->post();

    $target_offices = $post['target_offices'];

    // Get Default Bank Accounts for Each Target Office
    $targets_office_banks = $this->get_targets_office_banks($target_offices);

    $origin_opening_balance_id = $post['opening_balance_id'];

    $success = false;

    $this->write_db->trans_start();

    // Delete all balances
    $this->delete_all_balances($targets_office_banks);

    // Replicate Opening Funds Balance
    $replicate_opening_fund_balance = $this->replicate_opening_fund_balance($origin_opening_balance_id, $targets_office_banks);

    $replicate_opening_bank_balance = $this->replicate_opening_bank_balance($origin_opening_balance_id, $targets_office_banks);

    $replicate_opening_cash_balance = $this->replicate_opening_cash_balance($origin_opening_balance_id, $targets_office_banks);

    $replicate_opening_outstanding_cheques = $this->replicate_opening_outstanding_cheques($origin_opening_balance_id, $targets_office_banks);

    $replicate_opening_deposit_transit = $this->replicate_opening_deposit_transit($origin_opening_balance_id, $targets_office_banks);

    $replicate_opening_allocation = $this->replicate_opening_allocation($origin_opening_balance_id, $targets_office_banks);

    $this->write_db->trans_complete();

    if ($this->write_db->trans_status() == true) {
      $success = true;
    }

    echo $success;
  }

  function delete_all_balances($targets_office_banks){
    
    $opening_balance_tables = [
      'opening_fund_balance',
      'opening_bank_balance',
      'opening_cash_balance',
      'opening_outstanding_cheque',
      'opening_deposit_transit',
      'opening_allocation_balance'
    ];

    foreach ($opening_balance_tables as $opening_balance_table) {
      $this->write_db->where_in('fk_system_opening_balance_id', array_column($targets_office_banks,'system_opening_balance_id'));
      $this->write_db->delete($opening_balance_table);
    }
  }

  function replicate_opening_fund_balance($origin_opening_balance_id, $targets_office_banks){
    
    $opening_fund_balance = [];

    $this->read_db->select(array('fk_income_account_id','opening_fund_balance_amount'));
    $this->read_db->where(array('fk_system_opening_balance_id' => $origin_opening_balance_id));
    $opening_fund_balance_obj = $this->read_db->get('opening_fund_balance');

    if($opening_fund_balance_obj->num_rows() > 0){
      $opening_fund_balance = $opening_fund_balance_obj->result_array();
    }

    $target_opening_fund_balance = [];
    
    if(!empty($opening_fund_balance)){

      $office_cnt = 0;

      foreach($targets_office_banks as $targets_office_bank){

        $account_cnt = 0;

        foreach($opening_fund_balance as $account_balance){

          $target_opening_fund_balance[$office_cnt][$account_cnt]['fk_system_opening_balance_id'] = $targets_office_bank['system_opening_balance_id'];
          $target_opening_fund_balance[$office_cnt][$account_cnt]['opening_fund_balance_track_number'] = $this->grants_model->generate_item_track_number_and_name('opening_fund_balance')['opening_fund_balance_track_number'];
          $target_opening_fund_balance[$office_cnt][$account_cnt]['opening_fund_balance_name'] = $targets_office_bank['office_code']. ' - ' . get_phrase('opening_fund_balance');
          $target_opening_fund_balance[$office_cnt][$account_cnt]['fk_income_account_id'] = $account_balance['fk_income_account_id'];
          $target_opening_fund_balance[$office_cnt][$account_cnt]['opening_fund_balance_amount'] = $account_balance['opening_fund_balance_amount'];
          $target_opening_fund_balance[$office_cnt][$account_cnt]['fk_office_bank_id'] = $targets_office_bank['office_bank_id'];

          $target_opening_fund_balance[$office_cnt][$account_cnt]['opening_fund_balance_created_date'] = date('Y-m-d');
          $target_opening_fund_balance[$office_cnt][$account_cnt]['opening_fund_balance_created_by'] = $this->session->user_id;
          $target_opening_fund_balance[$office_cnt][$account_cnt]['opening_fund_balance_last_modified_by'] = $this->session->user_id;
          $target_opening_fund_balance[$office_cnt][$account_cnt]['opening_fund_balance_last_modified_date'] = date('Y-m-d h:i:s');
          $target_opening_fund_balance[$office_cnt][$account_cnt]['fk_status_id'] = $this->grants_model->initial_item_status('opening_fund_balance');

          $account_cnt++;
        }

        $this->write_db->insert_batch('opening_fund_balance', $target_opening_fund_balance[$office_cnt]);
  
        $office_cnt++;
      }
    }
    
    return $target_opening_fund_balance;
  }

  function replicate_opening_bank_balance($origin_opening_balance_id, $targets_office_banks){

    $opening_bank_balance = [];

    $this->read_db->select(array('opening_bank_balance_amount'));
    $this->read_db->where(array('fk_system_opening_balance_id' => $origin_opening_balance_id));
    $opening_bank_balance_obj = $this->read_db->get('opening_bank_balance');

    if($opening_bank_balance_obj->num_rows() > 0){
      $opening_bank_balance = $opening_bank_balance_obj->result_array();
    }

    $target_opening_bank_balance = [];
    
    if(!empty($opening_bank_balance)){

      $office_cnt = 0;

      foreach($targets_office_banks as $targets_office_bank){

        $account_cnt = 0;

        foreach($opening_bank_balance as $account_balance){

          $target_opening_bank_balance[$office_cnt][$account_cnt]['fk_system_opening_balance_id'] = $targets_office_bank['system_opening_balance_id'];
          $target_opening_bank_balance[$office_cnt][$account_cnt]['opening_bank_balance_track_number'] = $this->grants_model->generate_item_track_number_and_name('opening_bank_balance')['opening_bank_balance_track_number'];
          $target_opening_bank_balance[$office_cnt][$account_cnt]['opening_bank_balance_name'] = $targets_office_bank['office_code']. ' - ' . get_phrase('opening_bank_balance');
          $target_opening_bank_balance[$office_cnt][$account_cnt]['opening_bank_balance_amount'] = $account_balance['opening_bank_balance_amount'];
          $target_opening_bank_balance[$office_cnt][$account_cnt]['fk_office_bank_id'] = $targets_office_bank['office_bank_id'];

          $target_opening_bank_balance[$office_cnt][$account_cnt]['opening_bank_balance_created_date'] = date('Y-m-d');
          $target_opening_bank_balance[$office_cnt][$account_cnt]['opening_bank_balance_created_by'] = $this->session->user_id;
          $target_opening_bank_balance[$office_cnt][$account_cnt]['opening_bank_balance_last_modified_by'] = $this->session->user_id;
          $target_opening_bank_balance[$office_cnt][$account_cnt]['opening_bank_balance_last_modified_date'] = date('Y-m-d h:i:s');
          $target_opening_bank_balance[$office_cnt][$account_cnt]['fk_status_id'] = $this->grants_model->initial_item_status('opening_bank_balance');

          $account_cnt++;
        }

        $this->write_db->insert_batch('opening_bank_balance', $target_opening_bank_balance[$office_cnt]);
  
        $office_cnt++;
      }
    }
    
    return $target_opening_bank_balance;
  }

  function replicate_opening_cash_balance($origin_opening_balance_id, $targets_office_banks){
    $opening_cash_balance = [];

    $this->read_db->select(array('fk_office_cash_id','opening_cash_balance_amount'));
    $this->read_db->where(array('fk_system_opening_balance_id' => $origin_opening_balance_id));
    $opening_cash_balance_obj = $this->read_db->get('opening_cash_balance');

    if($opening_cash_balance_obj->num_rows() > 0){
      $opening_cash_balance = $opening_cash_balance_obj->result_array();
    }

    $target_opening_cash_balance = [];
    
    if(!empty($opening_cash_balance)){

      $office_cnt = 0;

      foreach($targets_office_banks as $targets_office_bank){

        $account_cnt = 0;

        foreach($opening_cash_balance as $account_balance){

          $target_opening_cash_balance[$office_cnt][$account_cnt]['fk_system_opening_balance_id'] = $targets_office_bank['system_opening_balance_id'];
          $target_opening_cash_balance[$office_cnt][$account_cnt]['opening_cash_balance_track_number'] = $this->grants_model->generate_item_track_number_and_name('opening_cash_balance')['opening_cash_balance_track_number'];
          $target_opening_cash_balance[$office_cnt][$account_cnt]['opening_cash_balance_name'] = $targets_office_bank['office_code']. ' - ' . get_phrase('opening_bank_balance');
          $target_opening_cash_balance[$office_cnt][$account_cnt]['opening_cash_balance_amount'] = $account_balance['opening_cash_balance_amount'];
          $target_opening_cash_balance[$office_cnt][$account_cnt]['fk_office_bank_id'] = $targets_office_bank['office_bank_id'];
          $target_opening_cash_balance[$office_cnt][$account_cnt]['fk_office_cash_id'] = $account_balance['fk_office_cash_id'];

          $target_opening_cash_balance[$office_cnt][$account_cnt]['opening_cash_balance_created_date'] = date('Y-m-d');
          $target_opening_cash_balance[$office_cnt][$account_cnt]['opening_cash_balance_created_by'] = $this->session->user_id;
          $target_opening_cash_balance[$office_cnt][$account_cnt]['opening_cash_balance_last_modified_by'] = $this->session->user_id;
          $target_opening_cash_balance[$office_cnt][$account_cnt]['opening_cash_balance_last_modified_date'] = date('Y-m-d h:i:s');
          $target_opening_cash_balance[$office_cnt][$account_cnt]['fk_status_id'] = $this->grants_model->initial_item_status('opening_cash_balance');

          $account_cnt++;
        }

        $this->write_db->insert_batch('opening_cash_balance', $target_opening_cash_balance[$office_cnt]);
  
        $office_cnt++;
      }
    }
    
    return $target_opening_cash_balance;
  }

  function replicate_opening_outstanding_cheques($origin_opening_balance_id, $targets_office_banks){
    $opening_outstanding_cheque = [];

    $this->read_db->select(array('opening_outstanding_cheque_name','opening_outstanding_cheque_description','opening_outstanding_cheque_date','opening_outstanding_cheque_number','opening_outstanding_cheque_amount'));
    $this->read_db->where(array('fk_system_opening_balance_id' => $origin_opening_balance_id));
    $opening_outstanding_cheque_obj = $this->read_db->get('opening_outstanding_cheque');

    if($opening_outstanding_cheque_obj->num_rows() > 0){
      $opening_outstanding_cheque = $opening_outstanding_cheque_obj->result_array();
    }

    $target_opening_outstanding_cheque = [];
    
    if(!empty($opening_outstanding_cheque)){

      $office_cnt = 0;

      foreach($targets_office_banks as $targets_office_bank){

        $account_cnt = 0;

        foreach($opening_outstanding_cheque as $account_balance){

          $target_opening_outstanding_cheque[$office_cnt][$account_cnt]['fk_system_opening_balance_id'] = $targets_office_bank['system_opening_balance_id'];
          $target_opening_outstanding_cheque[$office_cnt][$account_cnt]['opening_outstanding_cheque_track_number'] = $this->grants_model->generate_item_track_number_and_name('opening_outstanding_cheque')['opening_outstanding_cheque_track_number'];
          $target_opening_outstanding_cheque[$office_cnt][$account_cnt]['opening_outstanding_cheque_name'] = $account_balance['opening_outstanding_cheque_name'];
          $target_opening_outstanding_cheque[$office_cnt][$account_cnt]['opening_outstanding_cheque_description'] = $account_balance['opening_outstanding_cheque_description'];
          $target_opening_outstanding_cheque[$office_cnt][$account_cnt]['opening_outstanding_cheque_date'] = $account_balance['opening_outstanding_cheque_date'];

          $target_opening_outstanding_cheque[$office_cnt][$account_cnt]['opening_outstanding_cheque_number'] = $account_balance['opening_outstanding_cheque_number'];
          $target_opening_outstanding_cheque[$office_cnt][$account_cnt]['opening_outstanding_cheque_amount'] = $account_balance['opening_outstanding_cheque_amount'];
          $target_opening_outstanding_cheque[$office_cnt][$account_cnt]['opening_outstanding_cheque_is_cleared'] = 0;
          $target_opening_outstanding_cheque[$office_cnt][$account_cnt]['opening_outstanding_cheque_bounced_flag'] = 0;
          $target_opening_outstanding_cheque[$office_cnt][$account_cnt]['fk_office_bank_id'] = $targets_office_bank['office_bank_id'];

          $target_opening_outstanding_cheque[$office_cnt][$account_cnt]['opening_outstanding_cheque_created_date'] = date('Y-m-d');
          $target_opening_outstanding_cheque[$office_cnt][$account_cnt]['opening_outstanding_cheque_created_by'] = $this->session->user_id;
          $target_opening_outstanding_cheque[$office_cnt][$account_cnt]['opening_outstanding_cheque_last_modified_by'] = $this->session->user_id;
          $target_opening_outstanding_cheque[$office_cnt][$account_cnt]['opening_outstanding_cheque_last_modified_date'] = date('Y-m-d h:i:s');
          $target_opening_outstanding_cheque[$office_cnt][$account_cnt]['fk_status_id'] = $this->grants_model->initial_item_status('opening_outstanding_cheque');

          $account_cnt++;
        }

        $this->write_db->insert_batch('opening_outstanding_cheque', $target_opening_outstanding_cheque[$office_cnt]);
  
        $office_cnt++;
      }
    }
    
    return $target_opening_outstanding_cheque;
  }

  function replicate_opening_deposit_transit($origin_opening_balance_id, $targets_office_banks){
    $opening_deposit_transit = [];

    $this->read_db->select(array('opening_deposit_transit_name','opening_deposit_transit_date','opening_deposit_transit_description','opening_deposit_transit_amount'));
    $this->read_db->where(array('fk_system_opening_balance_id' => $origin_opening_balance_id));
    $opening_deposit_transit_obj = $this->read_db->get('opening_deposit_transit');

    if($opening_deposit_transit_obj->num_rows() > 0){
      $opening_deposit_transit = $opening_deposit_transit_obj->result_array();
    }

    $target_opening_deposit_transit = [];
    
    if(!empty($opening_deposit_transit)){

      $office_cnt = 0;

      foreach($targets_office_banks as $targets_office_bank){

        $account_cnt = 0;

        foreach($opening_deposit_transit as $account_balance){

          $target_opening_deposit_transit[$office_cnt][$account_cnt]['fk_system_opening_balance_id'] = $targets_office_bank['system_opening_balance_id'];
          $target_opening_deposit_transit[$office_cnt][$account_cnt]['opening_deposit_transit_track_number'] = $this->grants_model->generate_item_track_number_and_name('opening_deposit_transit')['opening_deposit_transit_track_number'];
          $target_opening_deposit_transit[$office_cnt][$account_cnt]['opening_deposit_transit_name'] = $account_balance['opening_deposit_transit_name'];
          $target_opening_deposit_transit[$office_cnt][$account_cnt]['opening_deposit_transit_description'] = $account_balance['opening_deposit_transit_description'];
          $target_opening_deposit_transit[$office_cnt][$account_cnt]['opening_deposit_transit_date'] = $account_balance['opening_deposit_transit_date'];

          $target_opening_deposit_transit[$office_cnt][$account_cnt]['opening_deposit_transit_amount'] = $account_balance['opening_deposit_transit_amount'];
          $target_opening_deposit_transit[$office_cnt][$account_cnt]['opening_deposit_transit_is_cleared'] = 0;

          $target_opening_deposit_transit[$office_cnt][$account_cnt]['fk_office_bank_id'] = $targets_office_bank['office_bank_id'];
          
          $target_opening_deposit_transit[$office_cnt][$account_cnt]['opening_deposit_transit_created_date'] = date('Y-m-d');
          $target_opening_deposit_transit[$office_cnt][$account_cnt]['opening_deposit_transit_created_by'] = $this->session->user_id;
          $target_opening_deposit_transit[$office_cnt][$account_cnt]['opening_deposit_transit_last_modified_by'] = $this->session->user_id;
          $target_opening_deposit_transit[$office_cnt][$account_cnt]['opening_deposit_transit_last_modified_date'] = date('Y-m-d h:i:s');
          $target_opening_deposit_transit[$office_cnt][$account_cnt]['fk_status_id'] = $this->grants_model->initial_item_status('opening_deposit_transit');

          $account_cnt++;
        }

        $this->write_db->insert_batch('opening_deposit_transit', $target_opening_deposit_transit[$office_cnt]);
  
        $office_cnt++;
      }
    }
    
    return $target_opening_deposit_transit;
  }

  function replicate_opening_allocation($origin_opening_balance_id, $targets_office_banks){
    return 'replicate_opening_allocation'; // To be implemented later
  }

  function get_targets_office_banks($target_office_ids){

    $office_with_banks = [];

    $this->read_db->select(array('office_id','office_name','office_code','office_bank_id','system_opening_balance_id'));
    $this->read_db->where_in('office_id', $target_office_ids);
    $this->read_db->where(array('office_bank_is_default' => 1, "office_bank_is_active" => 1, 'office_is_active' => 1));
    $this->read_db->join('office_bank','office_bank.fk_office_id=office.office_id');
    $this->read_db->join('system_opening_balance','system_opening_balance.fk_office_id=office.office_id');
    $office_obj = $this->read_db->get('office');

    if($office_obj->num_rows() > 0){
      $office_with_banks = $office_obj->result_array();
    }

    return $office_with_banks;

  }

  static function get_menu_list(){}

}