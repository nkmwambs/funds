<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/** 
 *	@author 	: Nicodemus Karisa and Modified by Livingstone Onduso
 *	@date		: 27th September, 2018
 * @method void get_menu_list() empty method.
 *
 *	@see https://techsysnow.com
 */


class Office_bank extends MY_Controller
{

  public function __construct()
  {
    parent::__construct();

    $this->load->library('office_bank_library');
  }

  public function index()
  {
    //Empty
  }

  public function check_office_has_default_office_bank()
  {
    $office_has_default_office_bank = false;
    $post = $this->input->post();

    $this->read_db->where(array('fk_office_id'=>$post['office_id'],'office_bank_is_active'=>1,
    'office_bank_is_default'=>1));
    $active_default_office_bank = $this->read_db->get('office_bank');

    if($active_default_office_bank->num_rows() > 0){
      $office_has_default_office_bank = true;
    }

    echo $office_has_default_office_bank;

  }


  function income_account_requiring_allocation($office_id){

    $office_bank_ids = [];
    $office_banks = [];

    $this->read_db->select(array('office_bank_id','office_bank_name'));
    $this->read_db->where(array('fk_office_id' => $office_id));
    $office_bank_ids_obj = $this->read_db->get('office_bank');

    if($office_bank_ids_obj->num_rows() > 0){
      $office_bank_ids_raw = $office_bank_ids_obj->result_array();

      $office_bank_ids = array_column($office_bank_ids_raw, 'office_bank_id');
      $office_banks = $office_bank_ids_raw;
    }

    $income_accounts = $this->income_account_missing_project_allocation($office_id, $office_bank_ids);

    $accounts = [];

    if(count($income_accounts) > 0){
      $this->read_db->select(array('income_account_id','income_account_name'));
      $this->read_db->where_in('income_account_id', $income_accounts);
      $accounts = $this->read_db->get('income_account')->result_array();
    }
    

    echo json_encode(['unallocated_income_account' => $accounts, 'existing_office_banks' => $office_banks]);
  }

  /**
   * income_account_missing_project_allocation
   * 
   * Get income accounts at opening that lack project assignment
   * 
   * @author Unknown
   * @reviewed_by Nicodemus Karisa
   * @reviewed_date 14th June 2023
   * 
   * @param int $office_id - Office Id
   * @param array $office_bank_ids - List office banks for the given office
   * 
   * @return array - List of income accounts missing project assignment
   */

  function income_account_missing_project_allocation(int $office_id, array $office_bank_ids):array {

    $income_accounts_with_allocation = [];

    $this->load->model('financial_report_model');
    $this->load->model('office_model');

    $office_start_dates = $this->office_model->get_office_start_date_by_id($office_id);
    $office_start_month = $office_start_dates['month_start_date'];

    // $all_financial_report_income_accounts = $this->financial_report_model->month_utilized_income_accounts([$office_id],'2022-12-01',[],[]);
    // $office_bank_financial_report_income_accounts = $this->financial_report_model->month_utilized_income_accounts([$office_id],'2022-12-01',[],$office_bank_ids);

    $all_financial_report_income_accounts = $this->financial_report_model->month_utilized_income_accounts([$office_id],$office_start_month,[],[]);
    $office_bank_financial_report_income_accounts = $this->financial_report_model->month_utilized_income_accounts([$office_id],$office_start_month,[],$office_bank_ids);

    $all_income_account_ids = array_column($all_financial_report_income_accounts, 'income_account_id');
    $office_bank_all_income_account_ids = array_column($office_bank_financial_report_income_accounts, 'income_account_id');

    $income_accounts_with_allocation = array_diff($all_income_account_ids,$office_bank_all_income_account_ids);

    // log_message('error', json_encode(['income_accounts_with_allocation' => $income_accounts_with_allocation, 'all_income_account_ids' => $all_income_account_ids, 'office_bank_all_income_account_ids' => $office_bank_all_income_account_ids]));

    return $income_accounts_with_allocation;

  }


  function result($id = 0){

    $result = [];

    if($this->action == 'list'){
      $columns = $this->columns();
      array_shift($columns);
      $result['columns'] = $columns;
      $result['has_details_table'] = false; 
      $result['has_details_listing'] = false;
      $result['is_multi_row'] = false;
      $result['show_add_button'] = true;
    }else{
      $result = parent::result($id);
    }

    return $result;
  }

  function columns(){
    $columns = [
      'office_bank_id',
      'office_bank_track_number',
      'office_bank_name',
      'bank_name',
      'office_name',
      'office_bank_account_number',
      'office_bank_chequebook_size',
      'office_bank_book_exemption_expiry_date',
      'office_bank_is_active',
      'office_bank_is_default',
      'office_bank_created_date'
    ];

    return $columns;
  }

  function get_office_banks(){
    $user_offices = $this->user_model->direct_user_offices($this->session->user_id, $this->session->context_definition['context_definition_name']);

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
      $this->read_db->order_by('office_bank_id DESC');
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
    
    if(!$this->session->system_admin){
      $this->read_db->where(array('bank.fk_account_system_id'=>$this->session->user_account_system_id));
      $this->read_db->where_in('office_bank.fk_office_id', array_column($user_offices, 'office_id'));
    }

    if($this->session->master_table){
      $this->read_db->where(array('office_bank.fk_funder_id'=>$this->input->post('id')));
    }

    $this->read_db->select($columns);
    $this->read_db->join('bank','bank.bank_id=office_bank.fk_bank_id');
    $this->read_db->join('office','office.office_id=office_bank.fk_office_id');
    $this->read_db->join('account_system','account_system.account_system_id=bank.fk_account_system_id');



    $result_obj = $this->read_db->get('office_bank');
    
    $results = [];

    if($result_obj->num_rows() > 0){
      $results = $result_obj->result_array();
    }

    return $results;
  }

  function count_office_banks(){

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
      $this->read_db->where(array('bank.fk_account_system_id'=>$this->session->user_account_system_id));
    }

    $this->read_db->join('bank','bank.bank_id=office_bank.fk_bank_id');
    $this->read_db->join('office','office.office_id=office_bank.fk_office_id');
    $this->read_db->join('account_system','account_system.account_system_id=bank.fk_account_system_id');
    

    $this->read_db->from('office_bank');
    $count_all_results = $this->read_db->count_all_results();

    return $count_all_results;
  }

  function show_list(){
   
    $draw =intval($this->input->post('draw'));
    $office_banks = $this->get_office_banks();
    $count_office_banks = $this->count_office_banks();

    $result = [];

    $cnt = 0;
    foreach($office_banks as $office_bank){
      $office_bank_id = array_shift($office_bank);
      $office_bank_track_number = $office_bank['office_bank_track_number'];
      $office_bank['office_bank_track_number'] = '<a href="'.base_url().$this->controller.'/view/'.hash_id($office_bank_id).'">'.$office_bank_track_number.'</a>';
      $office_bank['office_bank_is_active'] = $office_bank['office_bank_is_active'] == 1 ? get_phrase('yes') : get_phrase('no');
      $office_bank['office_bank_is_default'] = $office_bank['office_bank_is_default'] == 1 ? get_phrase('yes') : get_phrase('no');
      $row = array_values($office_bank);

      $result[$cnt] = $row;

      $cnt++;
    }

    $response = [
      'draw'=>$draw,
      'recordsTotal'=>$count_office_banks,
      'recordsFiltered'=>$count_office_banks,
      'data'=>$result
    ];
    
    echo json_encode($response);
  }

  function count_active_office_banks($office_id){
    $count  = $this->office_bank_model->get_active_office_bank($office_id);
    echo count($count);
  }

  public static function get_menu_list()
  {
    //Empty
  }

}