<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class System_opening_balance extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('system_opening_balance_library');
  }

  function index(){}

  // function result($id = 0){
  //   $this->load->model('opening_allocation_balance_model');
  //   $this->load->model('opening_bank_balance_model');
  //   $this->load->model('opening_cash_balance_model');
  //   $this->load->model('opening_fund_balance_model');
  //   $this->load->model('opening_outstanding_cheque_model');
  //   $this->load->model('opening_deposit_transit_model');
  //   $this->load->model('financial_report_model');

  //   if($this->action == 'view'){
     
  //     $office_bank_ids = $this->system_opening_balance_model->get_system_opening_balance_office_bank_ids( hash_id($this->id,'decode'));

  //     $office_has_financial_report_submitted = $this->financial_report_model->count_of_submitted_financial_reports($office_bank_ids) > 0 ? true : false;

  //     $result['office_has_financial_report_submitted'] = $office_has_financial_report_submitted;

  //     $result['header'] = $this->master_table();

  //     $result['detail']['opening_allocation_balance']['columns'] = $this->opening_allocation_balance_model->columns();
  //     $result['detail']['opening_allocation_balance']['has_details_table'] = true; 
  //     $result['detail']['opening_allocation_balance']['has_details_listing'] = false;
  //     $result['detail']['opening_allocation_balance']['is_multi_row'] = false;
  //     $result['detail']['opening_allocation_balance']['show_add_button'] = true;

  //     $result['detail']['opening_bank_balance']['columns'] = $this->opening_bank_balance_model->columns();
  //     $result['detail']['opening_bank_balance']['has_details_table'] = true; 
  //     $result['detail']['opening_bank_balance']['has_details_listing'] = false;
  //     $result['detail']['opening_bank_balance']['is_multi_row'] = false;
  //     $result['detail']['opening_bank_balance']['show_add_button'] = true;

  //     $result['detail']['opening_cash_balance']['columns'] = $this->opening_cash_balance_model->columns();
  //     $result['detail']['opening_cash_balance']['has_details_table'] = true; 
  //     $result['detail']['opening_cash_balance']['has_details_listing'] = false;
  //     $result['detail']['opening_cash_balance']['is_multi_row'] = false;
  //     $result['detail']['opening_cash_balance']['show_add_button'] = true;

  //     $result['detail']['opening_fund_balance']['columns'] = $this->opening_fund_balance_model->columns();
  //     $result['detail']['opening_fund_balance']['has_details_table'] = true; 
  //     $result['detail']['opening_fund_balance']['has_details_listing'] = false;
  //     $result['detail']['opening_fund_balance']['is_multi_row'] = false;
  //     $result['detail']['opening_fund_balance']['show_add_button'] = true;

  //     $result['detail']['opening_outstanding_cheque']['columns'] = $this->opening_outstanding_cheque_model->columns();
  //     $result['detail']['opening_outstanding_cheque']['has_details_table'] = true; 
  //     $result['detail']['opening_outstanding_cheque']['has_details_listing'] = false;
  //     $result['detail']['opening_outstanding_cheque']['is_multi_row'] = false;
  //     $result['detail']['opening_outstanding_cheque']['show_add_button'] = true;

  //     $result['detail']['opening_deposit_transit']['columns'] = $this->opening_deposit_transit_model->columns();
  //     $result['detail']['opening_deposit_transit']['has_details_table'] = true; 
  //     $result['detail']['opening_deposit_transit']['has_details_listing'] = false;
  //     $result['detail']['opening_deposit_transit']['is_multi_row'] = false;
  //     $result['detail']['opening_deposit_transit']['show_add_button'] = true;
     
  //     return $result;//parent::result($id = '');;
  //   }elseif($this->action == 'list'){
  //     $columns = $this->columns();
  //     array_shift($columns);
  //     $result['columns'] = $columns;
  //     $result['has_details_table'] = false; 
  //     $result['has_details_listing'] = false;
  //     $result['is_multi_row'] = false;
  //     $result['show_add_button'] = false;

  //     return $result;
  //   }else{
  //     return parent::result($id);
  //   }
  // }

  function get_cash_boxes_for_office_by_system_opening_balance_id($system_opening_balance_id){

    $this->read_db->select(array('office_cash_id','office_cash_name'));
    $this->read_db->where(array('system_opening_balance_id' => $system_opening_balance_id));
    $this->read_db->join('account_system','account_system.account_system_id=office_cash.fk_account_system_id');
    $this->read_db->join('office','office.fk_account_system_id=account_system.account_system_id');
    $this->read_db->join('system_opening_balance','system_opening_balance.fk_office_id=office.office_id');
    $office_cash = $this->read_db->get('office_cash')->result_array();

    $ids = array_column($office_cash,'office_cash_id');
    $names = array_column($office_cash,'office_cash_name');

    return array_combine($ids, $names);
  }

  public function get_project_income_accounts($project_id){
    $income_accounts = [];
    $this->read_db->select(array('income_account_id','income_account_name'));
    $this->read_db->where(array('fk_project_id' => $project_id));
    $this->read_db->join('income_account','income_account.income_account_id=project_income_account.fk_income_account_id');
    $income_account_obj = $this->read_db->get('project_income_account');

    if($income_account_obj->num_rows() > 0){
      $income_accounts_raw = $income_account_obj->result_array();

      $income_account_ids = array_column($income_accounts_raw, 'income_account_id');
      $income_account_names = array_column($income_accounts_raw, 'income_account_name');

      $income_accounts = array_combine($income_account_ids, $income_account_names);
    }
    
    echo json_encode($income_accounts);
  }

  function get_office_projects_by_system_opening_balance_id($system_opening_balance_id){
    $this->read_db->select(array('project_id','project_name'));
    $this->read_db->where(array('system_opening_balance_id' => $system_opening_balance_id));
    $this->read_db->join('project_allocation','project_allocation.fk_project_id=project.project_id');
    $this->read_db->join('office','office.office_id=project_allocation.fk_office_id');
    $this->read_db->join('system_opening_balance','system_opening_balance.fk_office_id=office.office_id');
    $projects = $this->read_db->get('project')->result_array();

    // $projects = array_map(function ($element) {
    //   return trim($element['project_name']);
    // }, $projects);
    $trimed_projects = [];
    foreach ($projects as $project) {
      $trimed_projects[] = trim($project['project_name']);
    }

    $ids = array_column($projects, 'project_id');
    $names = $trimed_projects;// array_column($projects, 'project_name');

    return array_combine($ids, $names);
  }

  function result($id = 0){
    $this->load->model('opening_allocation_balance_model');
    $this->load->model('opening_bank_balance_model');
    $this->load->model('opening_cash_balance_model');
    $this->load->model('opening_fund_balance_model');
    $this->load->model('opening_outstanding_cheque_model');
    $this->load->model('opening_deposit_transit_model');
    $this->load->model('financial_report_model');

    if($this->action == 'view'){
     
      $office_bank_ids = $this->system_opening_balance_model->get_system_opening_balance_office_bank_ids( hash_id($this->id,'decode'));

      $office_has_financial_report_submitted = $this->financial_report_model->count_of_submitted_financial_reports($office_bank_ids) > 0 ? true : false;

      $result['office_has_financial_report_submitted'] = $office_has_financial_report_submitted;

      $result['header'] = $this->master_table();

      $result['detail']['opening_allocation_balance']['columns'] = $this->opening_allocation_balance_model->columns();
      $result['detail']['opening_allocation_balance']['has_details_table'] = true; 
      $result['detail']['opening_allocation_balance']['has_details_listing'] = false;
      $result['detail']['opening_allocation_balance']['is_multi_row'] = false;
      $result['detail']['opening_allocation_balance']['show_add_button'] = true;

      $result['detail']['opening_bank_balance']['columns'] = $this->opening_bank_balance_model->columns();
      $result['detail']['opening_bank_balance']['has_details_table'] = true; 
      $result['detail']['opening_bank_balance']['has_details_listing'] = false;
      $result['detail']['opening_bank_balance']['is_multi_row'] = false;
      $result['detail']['opening_bank_balance']['show_add_button'] = true;

      $result['detail']['opening_cash_balance']['columns'] = $this->opening_cash_balance_model->columns();
      $result['detail']['opening_cash_balance']['has_details_table'] = true; 
      $result['detail']['opening_cash_balance']['has_details_listing'] = false;
      $result['detail']['opening_cash_balance']['is_multi_row'] = false;
      $result['detail']['opening_cash_balance']['show_add_button'] = true;

      $result['detail']['opening_fund_balance']['columns'] = $this->opening_fund_balance_model->columns();
      $result['detail']['opening_fund_balance']['has_details_table'] = true; 
      $result['detail']['opening_fund_balance']['has_details_listing'] = false;
      $result['detail']['opening_fund_balance']['is_multi_row'] = false;
      $result['detail']['opening_fund_balance']['show_add_button'] = true;

      $result['detail']['opening_outstanding_cheque']['columns'] = $this->opening_outstanding_cheque_model->columns();
      $result['detail']['opening_outstanding_cheque']['has_details_table'] = true; 
      $result['detail']['opening_outstanding_cheque']['has_details_listing'] = false;
      $result['detail']['opening_outstanding_cheque']['is_multi_row'] = false;
      $result['detail']['opening_outstanding_cheque']['show_add_button'] = true;

      $result['detail']['opening_deposit_transit']['columns'] = $this->opening_deposit_transit_model->columns();
      $result['detail']['opening_deposit_transit']['has_details_table'] = true; 
      $result['detail']['opening_deposit_transit']['has_details_listing'] = false;
      $result['detail']['opening_deposit_transit']['is_multi_row'] = false;
      $result['detail']['opening_deposit_transit']['show_add_button'] = true;
     
      return $result;
    }elseif($this->action == 'edit'){
      $result['header'] = $this->master_table();

      $system_opening_balance_id = hash_id($this->id,'decode');
      $result['cash_boxes'] = $this->get_cash_boxes_for_office_by_system_opening_balance_id($system_opening_balance_id);
      $result['office_projects'] = $this->get_office_projects_by_system_opening_balance_id($system_opening_balance_id);
      $result['income_accounts'] = [];

      $office_id = $this->read_db->get_where('system_opening_balance',
      ['system_opening_balance_id' => hash_id($this->id, 'decode')])->row()->fk_office_id;
      $result['office_banks'] = $this->financial_report_model->get_office_banks([$office_id]);

      return $result;
    }elseif($this->action == 'list'){
      $columns = $this->columns();
      array_shift($columns);
      $result['columns'] = $columns;
      $result['has_details_table'] = false; 
      $result['has_details_listing'] = false;
      $result['is_multi_row'] = false;
      $result['show_add_button'] = false;

      return $result;
    }else{
      return parent::result($id = '');
    }
  }

  function opening_bank_balance($office_id, $office_bank_id){
    
    $this->load->model('financial_report_model');
    $system_opening_bank_balance = $this->financial_report_model->system_opening_bank_balance([$office_id],[],[$office_bank_id]);

    return $system_opening_bank_balance;
  }

  function opening_cash_balance($system_opening_balance_id, $office_bank_id){
    
    $this->read_db->select(array('fk_office_cash_id as office_cash_id'));
    $this->read_db->select_sum('opening_cash_balance_amount');
    $this->read_db->where(array('fk_system_opening_balance_id' => $system_opening_balance_id, 'fk_office_bank_id' => $office_bank_id));
    $this->read_db->group_by(array('fk_office_cash_id'));
    $obj = $this->read_db->get('opening_cash_balance');

    $balances = [];

    if($obj->num_rows() > 0){
      $rec = $obj->result_array();
      
      for($i = 0;$i < count($rec);$i++){
        $balances[$rec[$i]['office_cash_id']]['office_cash_id'] = $rec[$i]['office_cash_id'];
        $balances[$rec[$i]['office_cash_id']]['amount'] = $rec[$i]['opening_cash_balance_amount'];
      }
    }

    return $balances;
  }

  private function upsert_bank_opening_balance($system_opening_balance_id, $office_bank_id, $book_bank_balance){
        // Get opening bank book balances
        $opening_bank_balance = [];
        $this->read_db->where(array('fk_office_bank_id' => $office_bank_id));
        $opening_bank_balance_obj = $this->read_db->get('opening_bank_balance');
    
        if($opening_bank_balance_obj->num_rows() > 0){
          $opening_bank_balance = $opening_bank_balance_obj->row_array();
        }
    
        // Insert Book Bank Balance
        if(empty($opening_bank_balance)){
          // Insert
          $track_number_and_name = $this->grants_model->generate_item_track_number_and_name('opening_bank_balance');
    
          $opening_bank_balance_insert_data = [
            'fk_system_opening_balance_id' => $system_opening_balance_id,
            'opening_bank_balance_track_number' => $track_number_and_name['opening_bank_balance_track_number'],
            'opening_bank_balance_name' => $track_number_and_name['opening_bank_balance_name'],
            'opening_bank_balance_amount' => $book_bank_balance,
            'fk_office_bank_id' => $office_bank_id,
            'opening_bank_balance_created_date' => date('Y-m-d'),
            'opening_bank_balance_created_by' => $this->session->user_id,
            'opening_bank_balance_last_modified_by' => $this->session->user_id,
            'fk_status_id' => $this->grants_model->initial_item_status('opening_bank_balance'),
            'fk_approval_id' => $this->grants_model->insert_approval_record('opening_bank_balance')
          ];
    
          $this->write_db->insert('opening_bank_balance', $opening_bank_balance_insert_data);
        }else{
          // Update
          $opening_bank_balance_update_data = [
            'opening_bank_balance_amount' => $book_bank_balance,
            'opening_bank_balance_last_modified_date' => date('Y-m-d h:i:s'),
            'opening_bank_balance_last_modified_by' => $this->session->user_id,
            'fk_status_id' => $this->grants_model->initial_item_status('opening_bank_balance'),
            'fk_approval_id' => $this->grants_model->insert_approval_record('opening_bank_balance')
          ];
    
          $this->write_db->where(array('fk_office_bank_id' => $office_bank_id, 'opening_bank_balance_id' => $opening_bank_balance['opening_bank_balance_id']));
          $this->write_db->update('opening_bank_balance', $opening_bank_balance_update_data);
        }
  }

  private function upsert_opening_cash_balances($system_opening_balance_id, $office_bank_id, $cash_balances){
    // log_message('error', json_encode(['system_opening_balance_id' => $system_opening_balance_id, 'cash_balances' => $cash_balances]));

    $account_system_office_cash_ids = array_keys($this->get_cash_boxes_for_office_by_system_opening_balance_id($system_opening_balance_id));

    if(!empty($cash_balances)){
      $opening_cash_balances = [];
      $this->read_db->where(array('fk_system_opening_balance_id' => $system_opening_balance_id));
      $opening_cash_balance_obj = $this->read_db->get('opening_cash_balance');

      if($opening_cash_balance_obj->num_rows() > 0){
        $opening_cash_balances = $opening_cash_balance_obj->result_array();
      }

      if(empty($opening_cash_balances)){
        // Insert
        $insert_opening_cash_balance_data = [];
        $i = 0;
        foreach($cash_balances as $office_cash_id => $cash_balance_amount){

          if($cash_balance_amount == 0) continue;

          $track_number_and_name = $this->grants_model->generate_item_track_number_and_name('opening_cash_balance');

          $insert_opening_cash_balance_data[$i]['opening_cash_balance_track_number'] = $track_number_and_name['opening_cash_balance_track_number'];
          $insert_opening_cash_balance_data[$i]['opening_cash_balance_name'] = $track_number_and_name['opening_cash_balance_name'];
          $insert_opening_cash_balance_data[$i]['fk_system_opening_balance_id'] = $system_opening_balance_id;
          $insert_opening_cash_balance_data[$i]['fk_office_bank_id'] = $office_bank_id;
          $insert_opening_cash_balance_data[$i]['fk_office_cash_id'] = $office_cash_id;
          $insert_opening_cash_balance_data[$i]['opening_cash_balance_amount'] = $cash_balance_amount;

          $insert_opening_cash_balance_data[$i]['opening_cash_balance_created_date'] = date('Y-m-d');
          $insert_opening_cash_balance_data[$i]['opening_cash_balance_created_by'] = $this->session->user_id;
          $insert_opening_cash_balance_data[$i]['opening_cash_balance_last_modified_by'] = $this->session->user_id;
          $insert_opening_cash_balance_data[$i]['fk_approval_id'] = $this->grants_model->insert_approval_record('opening_cash_balance');
          $insert_opening_cash_balance_data[$i]['fk_status_id'] = $this->grants_model->initial_item_status('opening_cash_balance');

          $i++;
        }

        $this->write_db->insert_batch('opening_cash_balance', $insert_opening_cash_balance_data);

      }else{

        $recorded_office_cash_ids = array_column($opening_cash_balances,'fk_office_cash_id');
        $recorded_office_cash_amounts = array_column($opening_cash_balances,'opening_cash_balance_amount');

        $recorded_office_cash = array_combine($recorded_office_cash_ids, $recorded_office_cash_amounts);

        foreach($account_system_office_cash_ids as $office_cash_id){
            if(array_key_exists($office_cash_id, $recorded_office_cash)){
              if($recorded_office_cash[$office_cash_id] != $cash_balances[$office_cash_id]){ // Amount updated
                // Update change
                $update_opening_cash_balance_data['opening_cash_balance_amount'] = $cash_balances[$office_cash_id];
                $update_opening_cash_balance_data['opening_cash_balance_last_modified_by'] = $this->session->user_id;
                $update_opening_cash_balance_data['opening_cash_balance_last_modified_date'] = date('Y-m-d h:i:s');

                $this->write_db->where(['fk_office_cash_id' => $office_cash_id, 'fk_system_opening_balance_id' => $system_opening_balance_id]);
                $this->write_db->update('opening_cash_balance', $update_opening_cash_balance_data);
              }elseif($cash_balances[$office_cash_id] == 0){ // Amount set to zero
                // Delete record
                $this->write_db->where(['fk_office_cash_id' => $office_cash_id, 'fk_system_opening_balance_id' => $system_opening_balance_id]);
                $this->write_db->delete('opening_cash_balance');
              }
            }else{
              $track_number_and_name = $this->grants_model->generate_item_track_number_and_name('opening_cash_balance');

              $single_insert_opening_cash_balance_data['opening_cash_balance_track_number'] = $track_number_and_name['opening_cash_balance_track_number'];
              $single_insert_opening_cash_balance_data['opening_cash_balance_name'] = $track_number_and_name['opening_cash_balance_name'];
              $single_insert_opening_cash_balance_data['fk_system_opening_balance_id'] = $system_opening_balance_id;
              $single_insert_opening_cash_balance_data['fk_office_bank_id'] = $office_bank_id;
              $single_insert_opening_cash_balance_data['fk_office_cash_id'] = $office_cash_id;
              $single_insert_opening_cash_balance_data['opening_cash_balance_amount'] = $cash_balances[$office_cash_id];

              $single_insert_opening_cash_balance_data['opening_cash_balance_created_date'] = date('Y-m-d');
              $single_insert_opening_cash_balance_data['opening_cash_balance_created_by'] = $this->session->user_id;
              $single_insert_opening_cash_balance_data['opening_cash_balance_last_modified_by'] = $this->session->user_id;
              $single_insert_opening_cash_balance_data['fk_approval_id'] = $this->grants_model->insert_approval_record('opening_cash_balance');
              $single_insert_opening_cash_balance_data['fk_status_id'] = $this->grants_model->initial_item_status('opening_cash_balance');

              $this->write_db->insert('opening_cash_balance', $single_insert_opening_cash_balance_data);
            

            }
        }
      }
    }
  }

  private function upsert_fund_balances(
    $system_opening_balance_id,
    $office_bank_id, 
    $project_ids,
    $income_account_ids, 
    $opening_amounts, 
    $income_amounts, 
    $expense_amounts
    )
  {
    $opening_fund_balances = [];
    $this->read_db->where(array('fk_system_opening_balance_id' => $system_opening_balance_id));
    $opening_fund_balance_obj = $this->read_db->get('opening_fund_balance');

    if($opening_fund_balance_obj->num_rows() > 0){
      $opening_fund_balances = $opening_fund_balance_obj->result_array();
    }


    $track_number_and_name = $this->grants_model->generate_item_track_number_and_name('opening_fund_balance');

    for($i = 0; $i < sizeof($project_ids); $i++){
      $insert_fund_balance_batch[$i]['fk_system_opening_balance_id'] = $system_opening_balance_id;
      $insert_fund_balance_batch[$i]['opening_fund_balance_track_number'] = $track_number_and_name['opening_fund_balance_track_number'];
      $insert_fund_balance_batch[$i]['opening_fund_balance_name'] = $track_number_and_name['opening_fund_balance_name'];
      $insert_fund_balance_batch[$i]['fk_income_account_id'] = $income_account_ids[$i];
      $insert_fund_balance_batch[$i]['fk_office_bank_id'] = $office_bank_id;
      $insert_fund_balance_batch[$i]['fk_project_id'] = $project_ids[$i];
      $insert_fund_balance_batch[$i]['opening_fund_balance_opening'] = $opening_amounts[$i];
      $insert_fund_balance_batch[$i]['opening_fund_balance_income'] = $income_amounts[$i];
      $insert_fund_balance_batch[$i]['opening_fund_balance_expense'] = $expense_amounts[$i];
      $insert_fund_balance_batch[$i]['opening_fund_balance_amount'] = $opening_amounts[$i] + $income_amounts[$i] - $expense_amounts[$i];

      $insert_fund_balance_batch[$i]['opening_fund_balance_created_date'] = date('Y-m-d');
      $insert_fund_balance_batch[$i]['opening_fund_balance_created_by'] = $this->session->user_id;
      $insert_fund_balance_batch[$i]['fk_approval_id'] = $this->grants_model->insert_approval_record('opening_fund_balance');
      $insert_fund_balance_batch[$i]['fk_status_id'] = $this->grants_model->initial_item_status('opening_fund_balance');
    }

    if(!empty($opening_fund_balances)){
      $this->write_db->where(array('fk_system_opening_balance_id' => $system_opening_balance_id, 'fk_office_bank_id' => $office_bank_id));
      $this->write_db->delete('opening_fund_balance');
    }
      $this->write_db->insert_batch('opening_fund_balance', $insert_fund_balance_batch);
  }

  private function upsert_opening_outstanding_cheque(
    $system_opening_balance_id, 
    $office_bank_id,
    $cheque_transaction_date,
    $cheque_number,
    $cheque_description,
    $cheque_amount
    )
  {
    $opening_outstanding_cheque = [];
    $this->read_db->where(array('fk_system_opening_balance_id' => $system_opening_balance_id));
    $opening_outstanding_cheque_obj = $this->read_db->get('opening_outstanding_cheque');

    if($opening_outstanding_cheque_obj->num_rows() > 0){
      $opening_outstanding_cheque = $opening_outstanding_cheque_obj->result_array();
    }

    $track_number_and_name = $this->grants_model->generate_item_track_number_and_name('opening_outstanding_cheque');

    for($i = 0; $i < sizeof($cheque_number);$i++){
      $insert_outstanding_cheque_batch[$i]['fk_system_opening_balance_id'] = $system_opening_balance_id;
      $insert_outstanding_cheque_batch[$i]['opening_outstanding_cheque_name'] = $track_number_and_name['opening_outstanding_cheque_name'];
      $insert_outstanding_cheque_batch[$i]['opening_outstanding_cheque_track_number'] = $track_number_and_name['opening_outstanding_cheque_track_number'];
      $insert_outstanding_cheque_batch[$i]['opening_outstanding_cheque_description'] = $cheque_description[$i];
      $insert_outstanding_cheque_batch[$i]['opening_outstanding_cheque_date'] = $cheque_transaction_date[$i];
      $insert_outstanding_cheque_batch[$i]['fk_office_bank_id'] = $office_bank_id;
      $insert_outstanding_cheque_batch[$i]['opening_outstanding_cheque_number'] = $cheque_number[$i];
      $insert_outstanding_cheque_batch[$i]['opening_outstanding_cheque_amount'] = $cheque_amount[$i];

      $insert_outstanding_cheque_batch[$i]['opening_outstanding_cheque_created_date'] = date('Y-m-d');
      $insert_outstanding_cheque_batch[$i]['opening_outstanding_cheque_created_by'] = $this->session->user_id;
      $insert_outstanding_cheque_batch[$i]['opening_outstanding_cheque_last_modified_by'] = $this->session->user_id;
      $insert_outstanding_cheque_batch[$i]['fk_approval_id'] = $this->grants_model->insert_approval_record('opening_outstanding_cheque');
      $insert_outstanding_cheque_batch[$i]['fk_status_id'] = $this->grants_model->initial_item_status('opening_outstanding_cheque');
    }

    if(!empty($opening_outstanding_cheque)){
      $this->write_db->where(array('fk_system_opening_balance_id' => $system_opening_balance_id, 'fk_office_bank_id' => $office_bank_id));
      $this->write_db->delete('opening_outstanding_cheque');
    }
      $this->write_db->insert_batch('opening_outstanding_cheque', $insert_outstanding_cheque_batch);
  }

  private function upsert_opening_deposit_transit(
    $system_opening_balance_id, 
    $office_bank_id,
    $deposit_transaction_date,
    $transaction_description,
    $transaction_amount
  ){
    $opening_deposit_transit = [];
    $this->read_db->where(array('fk_system_opening_balance_id' => $system_opening_balance_id));
    $opening_deposit_transit_obj = $this->read_db->get('opening_deposit_transit');

    if($opening_deposit_transit_obj->num_rows() > 0){
      $opening_deposit_transit = $opening_deposit_transit_obj->result_array();
    }

    $track_number_and_name = $this->grants_model->generate_item_track_number_and_name('opening_deposit_transit');

    for($i = 0; $i < sizeof($transaction_amount);$i++){
      $insert_deposit_transit_batch[$i]['fk_system_opening_balance_id'] = $system_opening_balance_id;
      $insert_deposit_transit_batch[$i]['opening_deposit_transit_name'] = $track_number_and_name['opening_deposit_transit_name'];
      $insert_deposit_transit_batch[$i]['opening_deposit_transit_track_number'] = $track_number_and_name['opening_deposit_transit_track_number'];
      $insert_deposit_transit_batch[$i]['opening_deposit_transit_description'] = $transaction_description[$i];
      $insert_deposit_transit_batch[$i]['opening_deposit_transit_date'] = $deposit_transaction_date[$i];
      $insert_deposit_transit_batch[$i]['fk_office_bank_id'] = $office_bank_id;
      $insert_deposit_transit_batch[$i]['opening_deposit_transit_amount'] = $transaction_amount[$i];

      $insert_deposit_transit_batch[$i]['opening_deposit_transit_created_date'] = date('Y-m-d');
      $insert_deposit_transit_batch[$i]['opening_deposit_transit_created_by'] = $this->session->user_id;
      $insert_deposit_transit_batch[$i]['opening_deposit_transit_last_modified_by'] = $this->session->user_id;
      $insert_deposit_transit_batch[$i]['fk_approval_id'] = $this->grants_model->insert_approval_record('opening_deposit_transit');
      $insert_deposit_transit_batch[$i]['fk_status_id'] = $this->grants_model->initial_item_status('opening_deposit_transit');
    }

    if(!empty($opening_deposit_transit)){
      $this->write_db->where(array('fk_system_opening_balance_id' => $system_opening_balance_id, 'fk_office_bank_id' => $office_bank_id));
      $this->write_db->delete('opening_deposit_transit');
    }
      $this->write_db->insert_batch('opening_deposit_transit', $insert_deposit_transit_batch);
  }

  private function upsert_reconciliation_statement(
    $system_opening_balance_id,
    $office_bank_id,
    $statement_date,
    $book_bank_balance,
    $statement_balance,
    $bank_reconciled_difference
  ){
    
    // Get opening bank book balances
    $opening_bank_balance = [];
    $this->read_db->where(array('fk_office_bank_id' => $office_bank_id));
    $opening_bank_balance_obj = $this->read_db->get('opening_bank_balance');

    if($opening_bank_balance_obj->num_rows() > 0){
      $opening_bank_balance = $opening_bank_balance_obj->row_array();
    }

    // Insert Book Bank Balance
    if(empty($opening_bank_balance)){
      // Insert
      $track_number_and_name = $this->grants_model->generate_item_track_number_and_name('opening_bank_balance');

      $opening_bank_balance_insert_data = [
        'fk_system_opening_balance_id' => $system_opening_balance_id,
        'opening_bank_balance_track_number' => $track_number_and_name['opening_bank_balance_track_number'],
        'opening_bank_balance_name' => $track_number_and_name['opening_bank_balance_name'],
        'opening_bank_balance_amount' => $book_bank_balance,
        'opening_bank_balance_statement_amount' => $statement_balance,
        'opening_bank_balance_statement_date' => $statement_date,
        'opening_bank_balance_is_reconciled' => $bank_reconciled_difference != 0 ? 0 : 1,
        'fk_office_bank_id' => $office_bank_id,
        'opening_bank_balance_created_date' => date('Y-m-d'),
        'opening_bank_balance_created_by' => $this->session->user_id,
        'opening_bank_balance_last_modified_by' => $this->session->user_id,
        'fk_status_id' => $this->grants_model->initial_item_status('opening_bank_balance'),
        'fk_approval_id' => $this->grants_model->insert_approval_record('opening_bank_balance')
      ];

      $this->write_db->insert('opening_bank_balance', $opening_bank_balance_insert_data);
    }else{
        // Update
        $opening_bank_balance_update_data = [
        'opening_bank_balance_statement_amount' => $statement_balance,
        'opening_bank_balance_statement_date' => $statement_date,
        'opening_bank_balance_is_reconciled' => $bank_reconciled_difference != 0 ? 0 : 1,
        'opening_bank_balance_last_modified_date' => date('Y-m-d h:i:s'),
        'opening_bank_balance_last_modified_by' => $this->session->user_id,
        'fk_status_id' => $this->grants_model->initial_item_status('opening_bank_balance'),
        'fk_approval_id' => $this->grants_model->insert_approval_record('opening_bank_balance')
      ];

      $this->write_db->where(array('fk_office_bank_id' => $office_bank_id, 'opening_bank_balance_id' => $opening_bank_balance['opening_bank_balance_id']));
      $this->write_db->update('opening_bank_balance', $opening_bank_balance_update_data);
    }
  }

  function save_opening_balances($system_opening_balance_id){
    $post = $this->input->post();

    // log_message('error', json_encode($post));

    // Insert and Update Bank Opening Balance
    $this->upsert_bank_opening_balance($system_opening_balance_id, $post['office_bank_id'], $post['book_bank_balance']);

    // Insert and Update Cash Opening balances
    $this->upsert_opening_cash_balances($system_opening_balance_id, $post['office_bank_id'], $post['cash_balance']);

    // Insert or Update fund balances
    if(isset($post['income_account_ids'])){
      $this->upsert_fund_balances(
        $system_opening_balance_id, 
        $post['office_bank_id'], 
        $post['project_ids'], 
        $post['income_account_ids'], 
        $post['opening_amounts'], 
        $post['income_amounts'], 
        $post['expense_amounts']
      );
    }

    // Insert and Update Opening Outstanding Cheques
    if(isset($post['cheque_transaction_date'])){
      $this->upsert_opening_outstanding_cheque(
        $system_opening_balance_id, 
        $post['office_bank_id'],
        $post['cheque_transaction_date'],
        $post['cheque_number'],
        $post['cheque_description'],
        $post['cheque_amount']
      );
    }

    // Insert and Update Opening Deposit in Transit
    if(isset($post['deposit_transaction_date'])){
      $this->upsert_opening_deposit_transit(
        $system_opening_balance_id, 
        $post['office_bank_id'],
        $post['deposit_transaction_date'],
        $post['transaction_description'],
        $post['transaction_amount']
      );
    }

    // Insert and Update reconciliation statement
    $this->upsert_reconciliation_statement(
      $system_opening_balance_id,
      $post['office_bank_id'],
      $post['statement_date'],
      $post['book_bank_balance'],
      $post['statement_balance'],
      $post['bank_reconciled_difference']
    );

    // Upload a bank statement
    // log_message('error', json_encode($_FILES));
    $storeFolder = upload_url('system_opening_balance', $system_opening_balance_id);
    
    // log_message('error', json_encode($_FILES['file']['name'][0]));
    if(isset($_FILES) && $_FILES['file']['name'][0] != ""){
      if (
          is_array($this->attachment_model->upload_files($storeFolder)) &&
          count($this->attachment_model->upload_files($storeFolder)) > 0
        ) {
          $this->attachment_model->upload_files($storeFolder);
      }
    }

    // Get uploaded files
    $bank_statements_uploads = [];

    $this->read_db->where(['system_opening_balance_id' => $system_opening_balance_id]);
    $office_id = $this->read_db->get('system_opening_balance')->row()->fk_office_id;
  
    $bank_statements_uploads = $this->opening_bank_statements($office_id);
    $bank_statements_uploads = $this->load->view('system_opening_balance/list_statements',['bank_statements_uploads' => $bank_statements_uploads],TRUE);

    // The value 1 has to be controlled instead of being hard coded
    echo json_encode(['success' => 1, 'bank_statements_uploads' => $bank_statements_uploads]);
  }

  private function get_system_opening_balance_office_id($system_opening_balance_id){
    $office_id  = $this->read_db->get_where('system_opening_balance',
    ['system_opening_balance_id' => $system_opening_balance_id])->row()->fk_office_id;

    return $office_id;
  }

  function opening_bank_statements($office_id){

    $system_opening_balance_ids = [];

    $this->read_db->select(array('system_opening_balance_id'));
    $this->read_db->where(array('fk_office_id' => $office_id));
    $system_opening_balance_obj = $this->read_db->get('system_opening_balance');

    if($system_opening_balance_obj->num_rows() > 0) {
      $system_opening_balance_ids = $system_opening_balance_obj->result_array();
    }

    $attachment_where_condition_array = [];

    $approve_item_name = 'system_opening_balance';

    $approve_item_id = $this->read_db->get_where(
      'approve_item',
      array('approve_item_name' => $approve_item_name)
    )->row()->approve_item_id;

    //print_r(array_column($reconciliation_ids,'reconciliation_id'));exit;

    $attachment_where_condition_array['fk_approve_item_id'] = $approve_item_id;
    $attachment_where_condition_array['attachment_primary_id'] = array_column($system_opening_balance_ids, 'system_opening_balance_id');

    // return $this->Aws_attachment_library->retrieve_file_uploads_info('reconciliation',array_column($reconciliation_ids,'reconciliation_id'));

    return $this->aws_attachment_library->retrieve_file_uploads_info($attachment_where_condition_array);
  }

  function delete_single_statement($attachment_id){
    $this->write_db->where(['attachment_id' => $attachment_id]);
    $this->write_db->delete('attachment');
  }

  function delete_statement(){
    $post = $this->input->post();
    $system_opening_balance_id = $post['system_opening_balance_id'];
    $attachment_id = $post['attachment_id'];
    $this->delete_single_statement($attachment_id);
    $office_id  = $this->get_system_opening_balance_office_id($system_opening_balance_id);
    $bank_statements_uploads = $this->opening_bank_statements($office_id);

    $result['bank_statements_uploads'] = $this->load->view('system_opening_balance/list_statements',['bank_statements_uploads' => $bank_statements_uploads],TRUE);
    
    echo json_encode($result);
  }

  function load_office_bank_balances(){
    $post = $this->input->post();
    $system_opening_balance_id = $post['system_opening_balance_id'];
    
    $office_id  = $this->get_system_opening_balance_office_id($system_opening_balance_id);

    // Proof of Cash
    $office_bank_id = $post['office_bank_id'];
    $result['opening_bank_balance'] = $this->opening_bank_balance($office_id, $office_bank_id);
    $system_opening_cash_balance = $this->opening_cash_balance($system_opening_balance_id, $office_bank_id);
    $result['opening_cash_balance'] = $system_opening_cash_balance;
    $total_petty_cash_balance = array_sum(array_column($result['opening_cash_balance'], 'amount'));
    $result['opening_total_cash'] = $result['opening_bank_balance'] + $total_petty_cash_balance;
    $bank_statements_uploads = $this->opening_bank_statements($office_id);
    // log_message('error', json_encode($bank_statements_uploads));
    // log_message('error', json_encode($bank_statements_uploads));

    $result['bank_statements_uploads'] = $this->load->view('system_opening_balance/list_statements',['bank_statements_uploads' => $bank_statements_uploads],TRUE);
    
    // log_message('error', json_encode($list));
    $this->read_db->select(array('fk_project_id as  project_id','fk_income_account_id as income_account_id','income_account_name','opening_fund_balance_opening as opening','opening_fund_balance_income income','opening_fund_balance_expense expense','opening_fund_balance_amount closing'));
    $this->read_db->where(array('opening_fund_balance.fk_system_opening_balance_id' => $system_opening_balance_id, 'fk_project_id' > 0));
    $this->read_db->join('income_account','income_account.income_account_id=opening_fund_balance.fk_income_account_id');
    $opening_fund_balance = $this->read_db->get('opening_fund_balance')->result_array();

    // log_message('error', json_encode($opening_fund_balance));
    $fund_balance = [];
    $i = 0;
    foreach($opening_fund_balance as $balance){
      $fund_balance[$i]['project_id'] = $balance['project_id'];
      $fund_balance[$i]['income_account_id'] = $balance['income_account_id'];
      $fund_balance[$i]['income_account_name'] = $balance['income_account_name'];
      $fund_balance[$i]['opening'] = ($balance['opening'] == NULL || $balance['opening'] == 0) && ($balance['income'] == NULL || $balance['income'] == 0) && ($balance['expense'] == NULL || $balance['expense'] == 0) ?  $balance['closing']  : $balance['opening'];
      $fund_balance[$i]['income'] = $balance['income'] != NULL ? $balance['income'] : 0;
      $fund_balance[$i]['expense'] = $balance['expense'] != NULL ? $balance['expense'] : 0;
      $fund_balance[$i]['closing'] = $balance['closing'] != NULL ? $balance['closing'] : 0;
      $i++;
    }

    // log_message('error', json_encode($fund_balance));

    // Fund Balance
    $result['fund_balance'] = $fund_balance;

    // Outstanding Cheques
    $result['outstanding_cheques'] = [];
    $this->read_db->select(array('opening_outstanding_cheque_date as transaction_date','opening_outstanding_cheque_number as cheque_number'));
    $this->read_db->select(array('opening_outstanding_cheque_description as description','opening_outstanding_cheque_amount as amount'));
    $this->read_db->where(array('opening_outstanding_cheque.fk_system_opening_balance_id' => $system_opening_balance_id));
    $opening_outstanding_cheque_obj = $this->read_db->get('opening_outstanding_cheque');

    if($opening_outstanding_cheque_obj->num_rows() > 0){
      $result['outstanding_cheques'] = $opening_outstanding_cheque_obj->result_array();
    }

    // Deposit Transit
    $result['deposit_transit'] = [];

    $this->read_db->select(array('opening_deposit_transit_date as transaction_date', 'opening_deposit_transit_description as description'));
    $this->read_db->select(array('opening_deposit_transit_amount as amount'));
    $this->read_db->where(array('opening_deposit_transit.fk_system_opening_balance_id' => $system_opening_balance_id));
    $opening_deposit_transit_obj = $this->read_db->get('opening_deposit_transit');

    if($opening_deposit_transit_obj-> num_rows() > 0){
      $result['deposit_transit'] = $opening_deposit_transit_obj->result_array();
    }

    // Reconciliation data - Missing thus shall be computed for past deployments
    $result['reconciliation_statement'] = [];

    $this->read_db->select(array('opening_bank_balance_statement_amount as statement_balance','opening_bank_balance_statement_date as statement_date'));
    $this->read_db->where(array('opening_bank_balance.fk_system_opening_balance_id' => $system_opening_balance_id));
    $opening_bank_balance_obj = $this->read_db->get('opening_bank_balance');

    if($opening_bank_balance_obj->num_rows() > 0){
      $opening_bank_balance = $opening_bank_balance_obj->row();
      $result['reconciliation_statement'] = [
          'statement_date' => $opening_bank_balance->statement_date,
          'statement_balance' => $opening_bank_balance->statement_balance
        ];
    }

    echo json_encode($result);
  }

  function master_table(){

    $this->read_db->select(array('system_opening_balance_track_number','system_opening_balance_name',
    'system_opening_balance_created_date','CONCAT(user_firstname," ", user_lastname) as system_opening_balance_created_by',
    'system_opening_balance_last_modified_date','office_name'));
    $this->read_db->join('office','office.office_id=system_opening_balance.fk_office_id');
    $this->read_db->join('user','user.user_id=system_opening_balance.system_opening_balance_created_by');
    $this->read_db->where(array('system_opening_balance_id'=>hash_id($this->id,'decode')));
    $result = $this->read_db->get('system_opening_balance')->row_array();

    return $result;
  }

  function columns(){
    $columns = [
      'system_opening_balance_id',
      'system_opening_balance_track_number',
      'system_opening_balance_name',
      'office_name',
      'system_opening_balance_created_date'
    ];

    return $columns;

  }


  function get_system_opening_balances(){

    $columns = $this->columns();
    $search_columns = $columns;

    // Limiting records
    $start = intval($this->input->post('start'));
    $length = intval($this->input->post('length'));

    $this->read_db->limit($length, $start);

    // Ordering records

    $order = $this->input->post('order');
    $col = '';
    $dir = 'desc';
    
    if(!empty($order)){
      $col = $order[0]['column'];
      $dir = $order[0]['dir'];
    }
          
    if( $col == ''){
      $this->read_db->order_by('system_opening_balance_id DESC');
    }else{
      $this->read_db->order_by($columns[$col],$dir); 
    }

    // Searching

    $search = $this->input->post('search');
    $value = $search['value'];

    array_shift($search_columns);

    if(!empty($value)){
      $this->read_db->group_start();
      $column_key = 0;
        foreach($search_columns as $column){
          if($column_key == 0) {
            $this->read_db->like($column,$value,'both'); 
          }else{
            $this->read_db->or_like($column,$value,'both');
        }
          $column_key++;				
      }
      $this->read_db->group_end();       
    }

    $this->read_db->select($columns);
    $this->read_db->join('status','status.status_id=system_opening_balance.fk_status_id');
    $this->read_db->join('office','office.office_id=system_opening_balance.fk_office_id');

    if(!$this->session->system_admin){
      $this->read_db->where_in('system_opening_balance.fk_office_id',array_column($this->session->hierarchy_offices,'office_id'));
    }

    $this->read_db->where(array('office_is_readonly'=>0,'office_is_active'=>1));

    $result_obj = $this->read_db->get('system_opening_balance');
    
    $results = [];

    if($result_obj->num_rows() > 0){
      $results = $result_obj->result_array();
    }

    return $results;
  }

  function count_system_opening_balances(){

    $columns = $this->columns();
    $search_columns = $columns;

    // Searching

    $search = $this->input->post('search');
    $value = $search['value'];

    array_shift($search_columns);

    if(!empty($value)){
      $this->read_db->group_start();
      $column_key = 0;
        foreach($search_columns as $column){
          if($column_key == 0) {
            $this->read_db->like($column,$value,'both'); 
          }else{
            $this->read_db->or_like($column,$value,'both');
        }
          $column_key++;				
      }
      $this->read_db->group_end();
    }
    
    if(!$this->session->system_admin){
      $this->read_db->where_in('system_opening_balance.fk_office_id',array_column($this->session->hierarchy_offices,'office_id'));
    }

    $this->read_db->where(array('office_is_readonly'=>0,'office_is_active'=>1));

    $this->read_db->join('status','status.status_id=system_opening_balance.fk_status_id');
    $this->read_db->join('office','office.office_id=system_opening_balance.fk_office_id');
    $this->read_db->from('system_opening_balance');
    $count_all_results = $this->read_db->count_all_results();

    return $count_all_results;
  }

  function show_list(){

    $draw =intval($this->input->post('draw'));
    $system_opening_balances = $this->get_system_opening_balances();
    $count_system_opening_balances = $this->count_system_opening_balances();

    $result = [];

    $cnt = 0;
    foreach($system_opening_balances as $system_opening_balance){
      $system_opening_balance_id = array_shift($system_opening_balance);
      $system_opening_balance_track_number = $system_opening_balance['system_opening_balance_track_number'];
      $system_opening_balance['system_opening_balance_track_number'] = '<a href="'.base_url().$this->controller.'/view/'.hash_id($system_opening_balance_id).'">'.$system_opening_balance_track_number.'</a>';
      $row = array_values($system_opening_balance);

      $result[$cnt] = $row;

      $cnt++;
    }

    $response = [
      'draw'=>$draw,
      'recordsTotal'=>$count_system_opening_balances,
      'recordsFiltered'=>$count_system_opening_balances,
      'data'=>$result
    ];
    
    echo json_encode($response);
  }


  static function get_menu_list(){}

}