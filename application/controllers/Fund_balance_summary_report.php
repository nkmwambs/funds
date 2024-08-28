<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *  @author   : Nicodemus Karisa
 *  @date   : 27th September, 2018
 *  Finance management system for NGOs
 *  https://techsysnow.com
 *  NKarisa@ke.ci.org
 */


class Fund_balance_summary_report extends MY_Controller
{
  private $civ_accounts = [];
  private $income_accounts = [];
  private $cols = [];
  private $period = null; 

  private $selected_account_system_id = 0;

  function __construct(){
    parent::__construct();
    $this->load->library('fund_balance_summary_report_library');

    $this->set_selected_account_system_id();
  }

  function index(){}

  function result($id = 0){

    $result = [];

    if($this->action == 'list'){
      $result['columns'] = $this->get_fund_columns();
      $result['accounting_system'] = $this->get_accounting_systems();
      $result['month'] = date('Y-m-01', strtotime('last day of previous month'));
      $result['has_details_table'] = false; 
      $result['has_details_listing'] = false;
      $result['is_multi_row'] = false;
      $result['show_add_button'] = false;
    }else{
      $result = parent::result($id);
    }

    return $result;
  }

  function get_accounting_systems(){
    $accounting_systems = [];

    $this->read_db->select(array('account_system_id','account_system_name','office_name'));
    $this->read_db->where(array('office.fk_context_definition_id' => 4));
    $this->read_db->join('office','office.fk_account_system_id=account_system.account_system_id');
    $accounting_systems_obj = $this->read_db->get('account_system');

    if($accounting_systems_obj->num_rows() > 0){
      $accounting_systems_raw = $accounting_systems_obj->result_array();

      foreach($accounting_systems_raw as $office){
        $accounting_systems[$office['account_system_id']] = $office['office_name'];
      }
    }

    return $accounting_systems;
  }


  function get_civ_income_accounts(){
    $civ_accounts = $this->account_system_income_accounts($this->selected_account_system_id, false);
    return $civ_accounts;
  }

  function get_fund_income_accounts(){
    return $this->account_system_income_accounts($this->selected_account_system_id, true);
  }
  
  function account_system_income_accounts($selected_account_system_id, $show_income_account_balance){
    $income_accounts = [];

    $columns = array('income_account_id as account_id','income_account_code as account_code');
    
    if(!$show_income_account_balance){
      $columns = array('project_id as account_id','project_code as account_code');
    }

    $this->read_db->select($columns);
    if(!$this->session->system_admin){
      $this->read_db->where(array('fk_account_system_id' => $this->selected_account_system_id));
    }

    if(!$show_income_account_balance){
      $this->read_db->join('project_income_account','project_income_account.fk_income_account_id=income_account.income_account_id');
      $this->read_db->join('project','project.project_id=project_income_account.fk_project_id');
      $this->read_db->where('project_end_date IS NOT NULL AND project_end_date <> "0000-00-00"');
    }

    $account_id = $this->input->post('accounts');

    // log_message('error', json_encode($account_id));
 
    if($account_id > 0){
      if($show_income_account_balance){
        $this->read_db->where(array('income_account_id' => $account_id));
      }else{
        $this->read_db->where(array('project_id' => $account_id));
      }
    }

    $obj = $this->read_db->get('income_account');

    if($obj->num_rows() > 0){
      $income_accounts_raw = $obj->result_array();

      $ids = array_column($income_accounts_raw,'account_id');
      $codes = array_column($income_accounts_raw,'account_code');

      $income_accounts = array_combine($ids, $codes);
    }

    return $income_accounts;
  }

  function user_offices_with_submitted_report_and_logged_balances($show_income_account_balance){

    $account_id = $this->input->post('accounts');

    $summary_type = $show_income_account_balance ? ['closing_fund_balance_data <>' => NULL] : ['closing_project_balance_data <>' => NULL];

    $this->read_db->select(array('fk_office_id as office_id','office_code'));
    $this->read_db->where_in('office_id', array_column($this->session->hierarchy_offices,'office_id'));
    $this->read_db->where(array('fk_account_system_id' => $this->selected_account_system_id, 'fk_context_definition_id' => 1));
    $this->read_db->where(array('financial_report_is_submitted' => 1,'financial_report_month' => date('Y-m-01', strtotime($this->period))));
    $this->read_db->where($summary_type);
    $this->read_db->join('office','office.office_id=financial_report.fk_office_id');

    if($account_id > 0){
      if($show_income_account_balance){
        $this->read_db->like('closing_fund_balance_data', '"'.$account_id.'"', 'both'); 
      }else{
        $this->read_db->like('closing_project_balance_data', '"'.$account_id.'"', 'both'); 
      }
    }

    $offices_with_report = $this->read_db->get('financial_report');

    $offices = [];

    if($offices_with_report->num_rows() > 0){
      $records = $offices_with_report->result_array();
      $office_ids = array_column($records,'office_id');
      $office_codes = array_column($records,'office_code');

      $offices = array_combine($office_ids, $office_codes);
    }

    return $offices;
  }

  function set_selected_account_system_id(){
    $this->selected_account_system_id = $this->session->user_account_system_id;

    if($this->input->post('account_system_id') > 0 && $this->session->system_admin){
      $this->selected_account_system_id = $this->input->post('account_system_id');
    }
  }

  function count_civ_balances(){
    $office_ids = $this->user_offices_with_submitted_report_and_logged_balances(false);
    return  count($office_ids); 
  }

  function count_fund_balances(){
    $office_ids = $this->user_offices_with_submitted_report_and_logged_balances(true);
    return  count($office_ids); 
  }

  function get_fund_balances(){
    $show_income_account_balance = true;
    $this->period = $this->input->post('date_range');

    //$logged_offices = $this->user_offices_with_submitted_report_and_logged_balances($show_income_account_balance);
    // $office_ids = [];//array_keys($logged_offices);

    $result = [];//['cols' => [], 'data' => []];
    $header_cols = [];

    // if(empty($office_ids)){
    //   return ['data' => $result, 'columns' => $header_cols];
    // }

    $this->set_selected_account_system_id();

     // Limiting records
     $start = intval($this->input->post('start'));
     $length = intval($this->input->post('length'));
 
     $order = $this->input->post('order');
     $col = '';
     $dir = 'desc';
     
     if(!empty($order)){
       $col = $order[0]['column'];
       $dir = $order[0]['dir'];
     }
           
     if( $col == '' || $col == 0){
       $col = 'office_id';
     }
 
     $account_system_income_accounts = $this->get_fund_income_accounts();

     if($col > 0){
       $col = array_values($account_system_income_accounts)[$col - 1];
     }
 
     $search = $this->input->post('search');
     $search_value = isset($search['value']) ? $search['value'] : '';

     $search_column = $this->input->post('accounts');

    $fund_balance_summary = $this->fund_balance_summary_report_model->logged_fund_balance_report($this->period, $show_income_account_balance, $start, $length, $col, $dir, $search_value, $search_column);
    
    // if(!empty($fund_balance_summary) && isset($fund_balance_summary['office_ids'])){
    //   $this->office_ids = $fund_balance_summary['office_ids'];
    // }

    $cnt = 0;

    foreach($fund_balance_summary['records'] as $office_code => $financial_report){
      foreach($financial_report as $financial_report_id => $balances){
        if(!is_array($balances)) continue;
        $result[$cnt]['office_code'] = '<a target="_blank" href="'.base_url().'financial_report/view/'.hash_id($financial_report_id,"encode").'">'.$office_code.'</a>';
        $header_cols['office_code'] = get_phrase('office_code');
        $innerCnt = 1;
        $sum = 0;
        foreach($account_system_income_accounts as $income_account_id => $income_account_code){
          $closing_balance = 0;
          if(array_key_exists($income_account_id, $balances)){
            $closing_balance = $balances[$income_account_id];
          }

          // if($search_column > 0 && $closing_balance == 0) {
          //   unset($result[$cnt]);
          //   continue;
          // }

          $sum += $closing_balance;
          $header_cols[$income_account_code] = $income_account_code;
          $result[$cnt][$income_account_code] = number_format($closing_balance,2);
          $innerCnt++;
        }
        $result[$cnt]['totals'] = number_format($sum,2);
        $header_cols['totals'] = 'Totals';
      }
      $cnt++;
    }

    return ['data' => $result, 'columns' => $header_cols];
  }

  function get_civ_balances(){

    $show_income_account_balance = false;
    $this->period = $this->input->post('date_range');

    //$logged_offices = $this->user_offices_with_submitted_report_and_logged_balances($show_income_account_balance);
    // $office_ids = []; //array_keys($logged_offices);

  
    $result = [];//['cols' => [], 'data' => []];
    $header_cols = [];

    //if(empty($office_ids)){
      //return ['data' => $result, 'columns' => $header_cols];
    //}

    $this->set_selected_account_system_id();

     // Limiting records
     $start = intval($this->input->post('start'));
     $length = intval($this->input->post('length'));
 
     $order = $this->input->post('order');
     $col = '';
     $dir = 'desc';
     
     if(!empty($order)){
       $col = $order[0]['column'];
       $dir = $order[0]['dir'];
     }
           
     if( $col == '' || $col == 0){
       $col = 'office_id';
     }
     
    //  log_message('error', json_encode([__METHOD__ => $this->input->post('accounts')]));
     $account_system_income_accounts = $this->get_civ_income_accounts();
     if($col > 0){
       $col = array_values($account_system_income_accounts)[$col - 1];
     }
 
     $search = $this->input->post('search');
     $search_value = isset($search['value']) ? $search['value'] : '';

     $search_column = $this->input->post('accounts');

    $fund_balance_summary = $this->fund_balance_summary_report_model->logged_fund_balance_report($this->period, $show_income_account_balance, $start, $length, $col, $dir, $search_value, $search_column);
    
    // if(!empty($fund_balance_summary) && isset($fund_balance_summary['office_ids'])){
    //   $this->office_ids = $fund_balance_summary['office_ids'];
    // }

    // log_message('error', json_encode($fund_balance_summary));

    $cnt = 0;

    foreach($fund_balance_summary['records'] as $office_code => $financial_report){
      foreach($financial_report as $financial_report_id => $balances){
        if(!is_array($balances)) continue;
        $result[$cnt]['office_code'] = '<a target="_blank" href="'.base_url().'financial_report/view/'.hash_id($financial_report_id,"encode").'">'.$office_code.'</a>';
        $header_cols['office_code'] = get_phrase('office_code');
        $innerCnt = 1;
        $sum = 0;
        foreach($account_system_income_accounts as $income_account_id => $income_account_code){
          $closing_balance = 0;
          if(array_key_exists($income_account_id, $balances)){
            $closing_balance = $balances[$income_account_id];
          }
          
          // if($search_column > 0 && $closing_balance == 0) {
          //   unset($result[$cnt]);
          //   continue;
          // }

          $sum += $closing_balance;
          $header_cols[$income_account_code] = $income_account_code;
          $result[$cnt][$income_account_code] = number_format($closing_balance,2);
          $innerCnt++;
        }
        $result[$cnt]['totals'] = number_format($sum,2);
        $header_cols['totals'] = 'Totals';
      }
      $cnt++;
    }

    // log_message('error', json_encode($result));

    return ['data' => $result, 'columns' => $header_cols];
  }

  function format_datatable_headers($header_cols){
    $rst = [];

    $rst[0]['data'] = 'office_code';
    $rst[0]['title'] = get_phrase('office_code');

    $i = 1;
    foreach($header_cols as $key => $col){
      $rst[$i]['data'] = $col;
      $rst[$i]['title'] = $col;
      $rst[$i]['id'] = $key;
      $i++;
    }

    $rst[$i]['data'] = 'totals';
    $rst[$i]['title'] = get_phrase('totals');

    return $rst;
  }


  function fund_show_list(){
    $this->set_selected_account_system_id();
    $balances = $this->get_fund_balances();
    $draw =intval($this->input->post('draw'));
    $count_balances = $this->count_fund_balances();

    // log_message('error', json_encode($this->input->post('account_system_id')));

    $data = $balances['data'];

    $response = [
        "draw" => $draw,
        "recordsTotal" => $count_balances,
        "recordsFiltered" => $count_balances,
        "data" => $data
    ];

    echo json_encode($response);
  }

  function civ_show_list(){
    $this->set_selected_account_system_id();
    $balances = $this->get_civ_balances();
    $draw =intval($this->input->post('draw'));
    $count_balances = $this->count_civ_balances();

    $data = $balances['data'];

    $response = [
        "draw" => $draw,
        "recordsTotal" => $count_balances,
        "recordsFiltered" => $count_balances,
        "data" => $data
    ];

    echo json_encode($response);
  }

  function civ_columns(){

    $this->set_selected_account_system_id();
    $civ_income_accounts = $this->get_civ_income_accounts();

    // log_message('error', json_encode([__METHOD__ => $this->input->post('accounts')]));

    $accounts = $this->format_datatable_headers($civ_income_accounts);

    echo json_encode($accounts);
  }

  function get_fund_columns(){
    $this->set_selected_account_system_id();
    $fund_income_accounts = $this->get_fund_income_accounts();

    $accounts = $this->format_datatable_headers($fund_income_accounts);

    return $accounts;
  }

  function fund_columns(){
    
    $accounts = $this->get_fund_columns();
    echo json_encode($accounts);
  }

  static function get_menu_list(){}

}
