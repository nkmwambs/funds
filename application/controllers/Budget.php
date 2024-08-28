<?php

use PHPUnit\Util\Json;

 if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *  @author   : Nicodemus Karisa
 *  @date   : 27th September, 2018
 *  Finance management system for NGOs
 *  https://techsysnow.com
 *  NKarisa@ke.ci.org
 */


class Budget extends MY_Controller
{

  function __construct()
  {
    parent::__construct();
    $this->load->helper('budget_helper');
  }
  function index()
  {
  }

  function budget_summary_result($budget_year = "")
  {

    $data = [];

    $budget_office = $this->budget_office();

    $this->read_db->select(array(
      'income_account_name', 'income_account_code', 'income_account_id',
      'expense_account_id', 'expense_account_name', 'expense_account_code', 'month_id', 'month_name'
    ));
    $this->read_db->select_sum('budget_item_detail_amount');

    $this->read_db->join('budget_item', 'budget_item.fk_budget_id=budget.budget_id');
    $this->read_db->join('budget_item_detail', 'budget_item_detail.fk_budget_item_id=budget_item.budget_item_id');
    $this->read_db->join('expense_account', 'expense_account.expense_account_id=budget_item.fk_expense_account_id');
    $this->read_db->join('income_account', 'income_account.income_account_id=expense_account.fk_income_account_id');
    $this->read_db->join('month', 'month.month_id=budget_item_detail.fk_month_id');
    $this->read_db->where(array(
      'fk_office_id' => $budget_office->office_id,
      'budget_year' => $budget_office->budget_year, 'budget_id' => hash_id($this->id, 'decode')
    ));
    $this->read_db->group_by(array('fk_month_id', 'expense_account_id', 'income_account_id'));
    $this->read_db->order_by('month_order ASC');
    $result_raw  = $this->read_db->get('budget')->result_object();

    $result = [];

    foreach ($result_raw as $detail) {

      $result[$detail->income_account_id]['income_account'] = ['income_account_id' => $detail->income_account_id, 'income_account_name' => $detail->income_account_name, 'income_account_code' => $detail->income_account_code];
      $result[$detail->income_account_id]['spread_expense_account'][$detail->expense_account_id]['expense_account'] = ['account_name' => $detail->expense_account_name, 'account_code' => $detail->expense_account_code];
      $result[$detail->income_account_id]['spread_expense_account'][$detail->expense_account_id]['spread'][$detail->month_name] = $detail->budget_item_detail_amount;
    }

    $data['summary'] =  $result;
    $data['test'] = $result_raw;

    return $data;
  }

  function budget_office()
  {
    $this->read_db->select(array(
      'office_id', 'office_name', 'office_code', 'budget_year',
      'budget_tag_id', 'budget_tag_name', 'budget.fk_status_id as status_id'
    ));
    $this->read_db->join('office', 'office.office_id=budget.fk_office_id');
    $this->read_db->join('budget_tag', 'budget_tag.budget_tag_id=budget.fk_budget_tag_id');
    $budget_office = $this->read_db->get_where(
      'budget',
      array('budget_id' => hash_id($this->id, 'decode'))
    )->row();

    return $budget_office;
  }

  function budget_header_information($budget_year = '')
  {

    $budget_office = $this->budget_office();

    $budget_year = 0;
    $office_id = 0;
    $office_name = "";
    $budget_tag_name = "";
    $budget_status_id = 0;
    $budget_tag_id = 0;
    
    if (isset($budget_office->office_id)) {
      $office_id = $budget_office->office_id;
      $budget_year = $budget_office->budget_year;
      $office_name = $budget_office->office_name;
      $budget_tag_name = $budget_office->budget_tag_name;
      $budget_tag_id = $budget_office->budget_tag_id;
      $budget_status_id = $budget_office->status_id;
    }

    $this->read_db->select(array('funder_id', 'funder_name', 'project_allocation_id', 'project_allocation_name'));
    $this->read_db->join('project_allocation', 'project_allocation.fk_project_id=project.project_id');
    $this->read_db->join('funder', 'funder.funder_id=project.fk_funder_id');
    $this->read_db->where(array('fk_office_id' => $office_id));
    $projects = $this->read_db->get('project')->result_object();

    $data = [];

    $funder_projects = [];

    foreach ($projects as $project) {
      $data['funder_projects'][$project->funder_id]['funder'] = ['funder_id' => $project->funder_id, 'funder_name' => $project->funder_name];
      $data['funder_projects'][$project->funder_id]['projects'][] = ['project_allocation_id' => $project->project_allocation_id, 'project_allocation_name' => $project->project_allocation_name];
    }

    $data['current_year'] = $budget_year;
    $data['office'] = $office_name;
    $data['budget_tag'] = $budget_tag_name;
    $data['status_id'] = $budget_status_id;
    $data['office_id'] = $office_id;
    $data['budget_tag_id'] = $budget_tag_id;


    return $data;
  }

  //function budget_schedule_result($office_id,$year,$income_account,$funder_id){
  function budget_schedule_result($income_account_id)
  {
    $result = [];

    $budget_office = $this->budget_office();

    $this->read_db->select(array(
      'budget_item_id', 'budget_item_total_cost', 'budget_item_track_number',
      'budget_item_description', 'budget_item_quantity', 'budget_item_unit_cost', 'budget_item_often',
      'status_id', 'status_name', 'fk_project_allocation_id',
      'budget_item_detail_id', 'budget_item_detail_amount',
      'month_id', 'month_name', 'month_number', 'fk_office_id', 'budget_year', 'income_account_name',
      'income_account_id', 'income_account_code', 'expense_account_id', 'expense_account_name',
      'expense_account_code','budget_item_marked_for_review', 'message_id', 'budget_item_source_id','budget_item_revisions',
      'budget_item_objective'
    ));

    $this->read_db->where(array('income_account_id' => $income_account_id));

    $this->read_db->join('budget_item', 'budget_item.budget_item_id=budget_item_detail.fk_budget_item_id');
    $this->read_db->join('message','message.message_record_key=budget_item.budget_item_id','LEFT');
    $this->read_db->join('budget', 'budget.budget_id=budget_item.fk_budget_id');
    $this->read_db->join('expense_account', 'expense_account.expense_account_id=budget_item.fk_expense_account_id');
    $this->read_db->join('income_account', 'income_account.income_account_id=expense_account.fk_income_account_id');
    $this->read_db->join('month', 'month.month_id=budget_item_detail.fk_month_id');
    $this->read_db->join('status', 'status.status_id=budget_item.fk_status_id');
    $this->read_db->order_by('month_order ASC, expense_account_code ASC');
    $this->read_db->where(array('fk_office_id' => $budget_office->office_id, 'budget_year' => $budget_office->budget_year, 'fk_budget_id' => hash_id($this->id, 'decode')));
    $budget_item_details = $this->read_db->get('budget_item_detail')->result_object();

    $result_grid = [];
    $month_spread = [];

    foreach ($budget_item_details as $row) {
      $month_spread[$row->budget_item_id][$row->month_number] =
        [
          'month_id' => $row->month_id,
          'month_number' => $row->month_number,
          'month_name' => $row->month_name,
          'amount' => $row->budget_item_detail_amount
        ];
    }

    foreach ($budget_item_details as $row) {

      $result_grid[$row->income_account_id]['income_account'] = ['income_account_id' => $row->income_account_id, 'income_account_name' => $row->income_account_name, 'income_account_code' => $row->income_account_code];
      $result_grid[$row->income_account_id]['budget_items'][$row->expense_account_id]['expense_account'] = ['expense_account_id' => $row->expense_account_id, 'expense_account_name' => $row->expense_account_name, 'expense_account_code' => $row->expense_account_code];
      $result_grid[$row->income_account_id]['budget_items'][$row->expense_account_id]['expense_items'][$row->budget_item_id] =
        [
          'budget_item_id' => $row->budget_item_id,
          'track_number' => $row->budget_item_track_number,
          'description' => $row->budget_item_description,
          'quantity' => $row->budget_item_quantity,
          'unit_cost' => $row->budget_item_unit_cost,
          'often' => $row->budget_item_often,
          'total_cost' => $row->budget_item_total_cost,
          'status' => ['status_id' => $row->status_id, 'status_name' => $row->status_name],
          'budget_item_marked_for_review' => $row->budget_item_marked_for_review,
          'message_id' => $row->message_id,
          'month_spread' => $month_spread[$row->budget_item_id],
          'budget_item_source_id' => $row->budget_item_source_id,
          'budget_item_revisions' => !is_null($row->budget_item_revisions) ? json_decode($row->budget_item_revisions): [],
          'objectives' => !is_null($row->budget_item_objective) ? json_decode($row->budget_item_objective) : []
        ];
    }

    //$result_grid['spreading_of_month'] = $month_spread;
    return $result_grid;
  }

  function is_budget_declined_state($budget_id){
    // Get the status of the opened budget
    $this->read_db->where(array('budget_id'=>$budget_id));
    $this->read_db->join('budget','budget.fk_status_id=status.status_id');
    $status_approval_direction = $this->read_db->get('status')->row()->status_approval_direction;

    return $status_approval_direction == -1 ? true : false;
  }

  function budget_limits($budget_header){
   
    $budget_limit_array = [];

    $this->read_db->select(array('fk_income_account_id','budget_limit_amount'));
    $this->read_db->join('budget','budget.budget_id=budget_limit.fk_budget_id');
    $this->read_db->where(array('budget.fk_office_id'=>$budget_header['office_id'],
    'budget.fk_budget_tag_id'=>$budget_header['budget_tag_id'],
    'budget_year'=>$budget_header['current_year']));
    $budget_limit_obj = $this->read_db->get('budget_limit');

    if($budget_limit_obj->num_rows() > 0){
      $budget_limits = $budget_limit_obj->result_array();
      $accounts = array_column($budget_limits, 'fk_income_account_id');
      $amounts = array_column($budget_limits,'budget_limit_amount');
      $budget_limit_array = array_combine($accounts,$amounts);
    }

    return $budget_limit_array;

  }

  private function is_budget_final_approval_status($budget_id){

    $max_budget_approval_status_ids = $this->general_model->get_max_approval_status_id('budget');

    $this->read_db->where(array('budget_id' => $budget_id));
    $this->read_db->where_in('fk_status_id', $max_budget_approval_status_ids);
    $budget_count = $this->read_db->get('budget')->num_rows();
    
    return $budget_count > 0 ? true : false;
  }

  /**
   * @todo:
   * Await documentation
   */

  function result($id = '')
  {

    $segment_budget_view_type = $this->uri->segment(4, 'summary');

    $budget_header = $this->budget_header_information();

    $result = [];

    if ($this->action == 'view') {

      $this->load->model('custom_financial_year_model');
      $this->load->model('financial_report_model');
      $budget_id = hash_id($this->id,'decode');

      $this->read_db->select(array('office_id','office_name','office_code','budget_year','office.fk_account_system_id account_system_id','budget.fk_status_id as budget_status_id', 'fk_custom_financial_year_id as custom_financial_year_id'));
      $this->read_db->join('budget','budget.fk_office_id=office.office_id');
      $this->read_db->where(array('budget_id'=> $budget_id));
  
      $office = $this->read_db->get('office')->row();

      if ($segment_budget_view_type == 'summary') {

        $this->load->library('budget_limit_library');
        $this->load->library('strategic_objectives_library');
        

        $budget_summary = $this->budget_summary_result();
        $result = array_merge($budget_header, $budget_summary);
        $result['budget_limits'] = $this->budget_limits($budget_header);
        $result['months'] = month_order($office->office_id, $budget_id);
        $result['budget_limit_list_view'] = $this->budget_limit_library->load_budget_list_view($budget_id);
        $result['strategic_objectives_costing_view'] = $this->strategic_objectives_library->load_strategic_objectives_costing_view($budget_id);
        
      } else {
        $income_account_id = hash_id($this->uri->segment(5), 'decode');
        // $max_voucher_approval_ids = $this->general_model->get_max_approval_status_id('budget_item');
        
        $month_array = month_order($office->office_id, $budget_id);

        $month_numbers = array_column($month_array, 'month_number');
        $month_names = array_column($month_array, 'month_name');

        $budget_schedule['budget_status_id'] = $budget_header['status_id'];
        $budget_schedule['month_names_with_number_keys'] = array_combine($month_numbers, $month_names);
        $budget_schedule['budget_schedule'] = $this->budget_schedule_result($income_account_id);
        $is_current_review['is_current_review'] = $this->check_if_current_review();
        $is_last_budget_review['is_last_budget_review'] = $this->check_if_is_last_budget_review(hash_id($this->id,'decode'),$office->office_id);
        // $budget_item_id_fully_approved['budget_item_id_fully_approved'] = max_voucher_approval_ids;

        $status_data = $this->general_model->action_button_data('Budget_item');
        
        // log_message('error', json_encode($status_data));
        $result = array_merge($budget_header, $budget_schedule, $is_current_review,$status_data,$is_last_budget_review);
      }

      $custom_financial_year = $this->custom_financial_year_model->get_default_custom_financial_year_id_by_office($office->office_id, true);

      $result['active_custom_fy'] = $custom_financial_year['custom_financial_year_is_active'];
      $result['all_mfrs_submitted'] = $this->financial_report_model->all_office_financial_report_submitted($office->office_id);
      $result['is_budget_final_approval_status'] = $this->is_budget_final_approval_status($budget_id);
      $budget_has_custom_fy = $office->custom_financial_year_id;

      $result['action_button_disabled'] = false;

      if($result['active_custom_fy'] && !$result['all_mfrs_submitted'] && !$result['is_budget_final_approval_status'] && $budget_has_custom_fy){
        $result['action_button_disabled'] = true;
      }

      $result['budget_message'] = $result['action_button_disabled'] ? get_phrase('all_mfrs_submitted_message', 'Make sure all financial reports are submitted in order to change the budget approval status or sign off the budget. This is due to newly activated custom financial year.') : "";

      $result['is_declined_state'] = $this->is_budget_declined_state(hash_id($this->id,'decode'));
      $result['status_data'] = $this->general_model->action_button_data('Budget', $office->account_system_id);
      $result['budget_status_id'] = $office->budget_status_id;
    } elseif($this->action == 'list') {
      $columns = alias_columns($this->columns());

      array_shift($columns);
      $result['columns'] = $columns;
      $result['has_details_table'] = false; 
      $result['has_details_listing'] = true;
      $result['is_multi_row'] =  $this->budget_model->is_multi_row;
      $result['show_add_button'] = true;
    }else{
      $result = parent::result($id);
    }

    return $result;
  }

  private function check_if_is_last_budget_review($budget_id, $office_id){

    $check = false;

    $this->read_db->where(array('budget_id' => $budget_id));
    $budget_tag_id = $this->read_db->get('budget')->row()->fk_budget_tag_id;

    // Get all country budget tags in order
    $this->read_db->select(array('fk_account_system_id'));
    $this->read_db->where(array('office_id' => $office_id));
    $account_system_id = $this->read_db->get('office')->row()->fk_account_system_id;
    
    $this->read_db->select(array('budget_tag_id','budget_tag_level'));
    $this->read_db->where(array('fk_account_system_id' => $account_system_id));
    $this->read_db->order_by('budget_tag_level ASC');
    $budget_tag = $this->read_db->get('budget_tag')->result_array();

    $last_budget_tag_id = end($budget_tag)['budget_tag_id'];

    if($budget_tag_id == $last_budget_tag_id){
      $check = true;
    }

    return $check;
  }

  function columns(){
    $columns = [
      'budget_id',
      'budget_track_number',
      'office_name',
      'budget_tag_name',
      'budget_year',
      'status_name',
      'month_name as budget_fy_start_month',
      'custom_financial_year_reset_date as fy_reset_start_date'
    ];

    return $columns;

  }
  
/**
 * @todo:
 * Await documentation
 */
  function get_budgets(){

    $columns = $this->columns();
    $search_columns = array_column(alias_columns($columns),'query_columns');

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
      $this->read_db->order_by('budget_id DESC');
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
    
    // log_message('error', json_encode($columns));

    $this->read_db->select($columns);
    $this->read_db->join('budget_tag','budget_tag.budget_tag_id=budget.fk_budget_tag_id');
    $this->read_db->join('status','status.status_id=budget.fk_status_id');
    $this->read_db->join('office','office.office_id=budget.fk_office_id');
    $this->read_db->join('custom_financial_year','custom_financial_year.custom_financial_year_id=budget.fk_custom_financial_year_id', 'LEFT');
    $this->read_db->join('month','month.month_number=custom_financial_year.custom_financial_year_start_month', 'LEFT');
    $this->read_db->where_in('budget.fk_office_id',array_column($this->session->hierarchy_offices,'office_id'));
    $result_obj = $this->read_db->get('budget');
    
    $results = [];

    if($result_obj->num_rows() > 0){
      $results = $result_obj->result_array();
    }

    return $results;
  }

  function count_budgets(){

    $columns = $columns = $this->columns();
    $search_columns = array_column(alias_columns($columns),'query_columns');

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
      $this->read_db->where_in('budget.fk_office_id',array_column($this->session->hierarchy_offices,'office_id'));
    }

    // $this->read_db->join('status','status.status_id=budget.fk_status_id');
    // $this->read_db->join('office','office.office_id=budget.fk_office_id');
    // $this->read_db->join('budget_tag','budget_tag.budget_tag_id=budget.fk_budget_tag_id');

    // $this->read_db->where_in('budget.fk_office_id',array_column($this->session->hierarchy_offices,'office_id'));
    // $this->read_db->from('budget');

    $this->read_db->join('budget_tag','budget_tag.budget_tag_id=budget.fk_budget_tag_id');
    $this->read_db->join('status','status.status_id=budget.fk_status_id');
    $this->read_db->join('office','office.office_id=budget.fk_office_id');
    $this->read_db->join('custom_financial_year','custom_financial_year.custom_financial_year_id=budget.fk_custom_financial_year_id', 'LEFT');
    $this->read_db->join('month','month.month_number=custom_financial_year.custom_financial_year_start_month', 'LEFT');
    $this->read_db->where_in('budget.fk_office_id',array_column($this->session->hierarchy_offices,'office_id'));

    $this->read_db->from('budget');
    
    $count_all_results = $this->read_db->count_all_results();

    return $count_all_results;
  }

  function show_list(){
   
    $draw =intval($this->input->post('draw'));
    $budgets = $this->get_budgets();
    $count_budgets = $this->count_budgets();

    $result = [];

    $cnt = 0;
    foreach($budgets as $budget){
      $budget_id = array_shift($budget);
      $budget_track_number = $budget['budget_track_number'];
      $budget['budget_track_number'] = '<a href="'.base_url().$this->controller.'/view/'.hash_id($budget_id).'">'.$budget_track_number.'</a>';
      $budget['budget_year'] = "FY". $budget['budget_year'];
      $row = array_values($budget);

      $result[$cnt] = $row;

      $cnt++;
    }

    $response = [
      'draw'=>$draw,
      'recordsTotal'=>$count_budgets,
      'recordsFiltered'=>$count_budgets,
      'data'=>$result
    ];
    
    echo json_encode($response);
  }

  function check_if_current_review()
  {
    $budget_id = hash_id($this->id, 'decode');

    // Get office object for the budget
    $this->read_db->select(array('budget_year', 'fk_office_id', 'budget_tag_level','fk_custom_financial_year_id'));
    $this->read_db->join('budget_tag', 'budget_tag.budget_tag_id=budget.fk_budget_tag_id');
    $budget_obj = $this->read_db->get_where('budget', array('budget_id' => $budget_id))->row();

    $fy = $budget_obj->budget_year;
    $office_id = $budget_obj->fk_office_id;
    $current_budget_tag_level = $budget_obj->budget_tag_level;
    $custom_financial_year_id = $budget_obj->fk_custom_financial_year_id;

    // Get all used budget tag levels for office and fy
    $this->read_db->select(array('budget_tag_level'));
    $this->read_db->join('budget_tag', 'budget_tag.budget_tag_id=budget.fk_budget_tag_id');
    $this->read_db->order_by('budget_tag_level ASC');
    $budget_tag_levels = $this->read_db->get_where('budget', array('fk_office_id' => $office_id, 'fk_custom_financial_year_id' => $custom_financial_year_id, 'budget_year' => $fy))->result_array();

    $budget_tag_levels_array = array_column($budget_tag_levels, 'budget_tag_level');

    $max_used_level = array_pop($budget_tag_levels_array);

    if ($current_budget_tag_level == $max_used_level) {
      return true;
    } else {
      return false;
    }
  }


  function page_name(): String
  {

    $segment_budget_view_type = parent::page_name();

    if ($this->action == 'view') {

      $segment_budget_view_type = $this->uri->segment(4, 'summary');

      if ($this->uri->segment(4) && $this->uri->segment(4) == 'schedule') {
        $segment_budget_view_type = 'budget_schedule_view';
      } else {
        $segment_budget_view_type = 'budget_summary_view';
      }
    }

    return $segment_budget_view_type;
  }

  public function update_budget_status()
  {
    $post = $this->input->post();

    // Update all budget items with a status not maximum  to maximum

    // Update the Budget status to maximum

    // Get status name of the maximum 

    //$result['button_label'] = 'Fully Approved';

    //echo json_encode($result);
  }

  function list_valid_budget_years_for_office(){
    $post = $this->input->post();

    $office_id = $post['office_id'];

    $valid_budget_years = $this->budget_model->valid_budget_years($office_id);

    echo json_encode($valid_budget_years);
  }

  function get_office_budget_tags(){
    $post = $this->input->post();

    $office_id = $post['office_id'];
    $budget_year = $post['budget_year'];

    $valid_budget_tags = $this->budget_model->valid_budget_tags($office_id, $budget_year);

    echo json_encode($valid_budget_tags);
  }

  function list_budgetable_income_account($office_id){
    
    $this->load->model('income_account_model');

    $income_accounts = $this->income_account_model->income_account_by_office_id($office_id);

    echo json_encode($income_accounts);
  }

  function post_budget(){
    $post = $this->input->post();

    $this->write_db->trans_begin();

    $action_before_insert = $this->budget_model->action_before_insert($post);
    
    
    extract($post);

    if(!array_key_exists('header',$action_before_insert)){
      echo json_encode($action_before_insert);
      return false;
    }
    // Insert the budget

    $tracking = $this->grants_model->generate_item_track_number_and_name('budget');
    $budget_insert_data['budget_track_number'] = $tracking['budget_track_number'];
    $budget_insert_data['budget_name'] = $tracking['budget_name'];
    $budget_insert_data['fk_office_id'] = $header['fk_office_id'];
    $budget_insert_data['budget_year'] = $header['budget_year'];
    $budget_insert_data['fk_budget_tag_id'] = $header['fk_budget_tag_id'];
    $budget_insert_data['fk_status_id'] = $this->grants_model->initial_item_status('budget');
    $budget_insert_data['budget_created_by'] = $this->session->user_id;
    $budget_insert_data['budget_created_date'] = date('Y-m-d');
    $budget_insert_data['budget_last_modified_by'] = $this->session->user_id;
    $budget_insert_data['budget_last_modified_date  '] = date('Y-m-d h:i:s');
    $this->write_db->insert('budget', $budget_insert_data);

    $budget_id = $this->write_db->insert_id();
    $hashed_budget_id = hash_id($budget_id, 'encode');

    // Insert budget limits
    $budget_limit_insert_data = [];

    $income_account_ids = $details['fk_income_account_id'];

    for($i = 0; $i < sizeof($income_account_ids); $i++){
      $budget_limit_tracking = $this->grants_model->generate_item_track_number_and_name('budget_limit');
      $budget_limit_insert_data[$i]['budget_limit_track_number'] = $budget_limit_tracking['budget_limit_track_number'];
      $budget_limit_insert_data[$i]['budget_limit_name'] = $budget_limit_tracking['budget_limit_name'];
      $budget_limit_insert_data[$i]['fk_income_account_id'] = $details['fk_income_account_id'][$i];
      $budget_limit_insert_data[$i]['budget_limit_amount'] = $details['budget_limit_amount'][$i];
      $budget_limit_insert_data[$i]['fk_budget_id'] = $budget_id;

      $budget_limit_insert_data[$i]['fk_status_id'] = $this->grants_model->initial_item_status('budget_limit');
      $budget_limit_insert_data[$i]['budget_limit_created_by'] = $this->session->user_id;
      $budget_limit_insert_data[$i]['budget_limit_created_date'] = date('Y-m-d');
      $budget_limit_insert_data[$i]['budget_limit_last_modified_by'] = $this->session->user_id;
      $budget_limit_insert_data[$i]['budget_limit_last_modified_date'] = date('Y-m-d h:i:s');
    }

    if(count($budget_limit_insert_data) > 0){
      $this->write_db->insert_batch('budget_limit', $budget_limit_insert_data);
    }
    $post['header']['fk_budget_id'] = $budget_id;
    $action_after_insert = $this->budget_model->action_after_insert($post['header'],0,$budget_id);

    if ($this->write_db->trans_status() == false || !$action_after_insert) {
      $this->write_db->trans_rollback();
      echo json_encode(["message" => "Budget record failed to create"]);
    } else {
      $this->write_db->trans_commit();
      echo json_encode(["budget_id" => $hashed_budget_id]);
    }

  }

  function check_office_period_budget_exists($office_id){
    
    $budget = $this->budget_model->get_a_budget_by_office_current_transaction_date($office_id);

    // log_message('error', json_encode($budget));
    
    $check = false;

    if(count($budget) > 0){
      $check = true;
    }

    echo $check;
  }

  function view()
  {
    parent::view();
  }

  static function get_menu_list()
  {
  }
}

