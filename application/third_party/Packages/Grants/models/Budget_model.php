<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Budget_model extends MY_Model
{
  public $table = 'budget'; // you MUST mention the table name
  public $dependant_table = '';
  public $is_multi_row = false;

  function __construct()
  {
    parent::__construct();

    $this->load->model('budget_tag_model');
  }

  function delete($id = null)
  {
  }

  function index()
  {
  }

  public function lookup_tables()
  {
    return array('office', 'budget_tag');
  }

  public function detail_tables()
  {
    return array('budget_item');
  }

  public function master_table_visible_columns()
  {
  }

  public function master_table_hidden_columns()
  {
  }

  public function list_table_visible_columns()
  {
    return ['budget_track_number', 'office_name', 'budget_tag_name', 'budget_year'];
  }

  public function list_table_hidden_columns()
  {
  }

  public function detail_list_table_visible_columns()
  {
  }

  public function detail_list_table_hidden_columns()
  {
  }

  //public function single_form_add_visible_columns(){}

  //public function single_form_add_hidden_columns(){}

  public function master_multi_form_add_visible_columns()
  {
    // return array('budget_name', 'budget_year', 'office_name');
    return array('funder_name','office_name','budget_year','budget_tag_name');
  }

  public function detail_multi_form_add_visible_columns()
  {
  }

  public function master_multi_form_add_hidden_columns()
  {
  }

  public function detail_multi_form_add_hidden_columns()
  {
  }

  public function single_form_add_visible_columns()
  {
    return array('office_name','budget_year','budget_tag_name');
  }

  public function single_form_add_hidden_columns()
  {
  }

  function detail_list()
  {
  }

  function master_view()
  {
  }

  public function list()
  {
  }

  public function view()
  {
  }

  function action_before_insert($post_array){
    $office_id  = $post_array['header']['fk_office_id'];
    $funder_id  = $post_array['header']['fk_funder_id'];

    $current_unsubmitted_budget = $this->get_current_unsigned_off_budget($office_id, $funder_id);

    if(!empty($current_unsubmitted_budget)){
      return ['message' => get_phrase('has_unsigned_off_budget','Failure to create budget due to unsigned off previous budgets')];
    }

    return $post_array;
  }

  public function lookup_values()
  {

    $lookup_values = parent::lookup_values();

    if (!$this->session->system_admin) {
      $user_offices = $this->user_model->direct_user_offices($this->session->user_id, $this->session->context_definition['context_definition_name']);

      $this->read_db->where_in('office_id', array_column($user_offices, 'office_id'));
      $this->read_db->where(['office_is_readonly' => 0]);
      $lookup_values['office'] = $this->read_db->get('office')->result_array();

      if ($this->session->env == 'production' || (!$this->config->item('show_all_budget_tags') && !$this->session->env == 'production')) {

        $current_month = date('n');

        $next_current_quarter_months = financial_year_quarter_months(month_after_adding_size_of_budget_review_period($current_month));

        $this->read_db->select(array('budget_tag_id', 'budget_tag_name'));
        $this->read_db->group_start();

        $months_in_quarter_index_offset = $this->config->item('size_in_months_of_a_budget_review_period') - $this->config->item('number_of_month_to_start_budget_review_before_close_of_review_period');

        if ($months_in_quarter_index_offset < 0) {
          $months_in_quarter_index_offset = $this->config->item('size_in_months_of_a_budget_review_period') - 1;
        }

        if (month_after_adding_size_of_budget_review_period($current_month) >= $next_current_quarter_months['months_in_quarter'][$months_in_quarter_index_offset]) {
          $this->read_db->where_in('fk_month_id', $next_current_quarter_months['months_in_quarter']);
        }

        $this->read_db->or_where(array('budget_tag_level' => $next_current_quarter_months['quarter_number'] - 1 == 0 ? $this->config->item('maximum_review_count') : $next_current_quarter_months['quarter_number'] - 1));

        $this->read_db->group_end();
      }

      $this->read_db->where(array('fk_account_system_id' => $this->session->user_account_system_id, 'budget_tag_is_active' => 1));
      $lookup_values['budget_tag'] = $this->read_db->get('budget_tag')->result_array();

      $user_offices = array_filter($this->session->hierarchy_offices, function($office){
        if($office['context_definition_id'] == $this->session->context_definition['context_definition_level']){
          return $office;
        }
      });

      $this->read_db->join('project','project.fk_funder_id=funder.funder_id');
      $this->read_db->join('project_allocation','project_allocation.fk_project_id=project.project_id','left');
      // Also filter by allocation extended date and project end date
      $this->read_db->where(['funder_is_active' => 1, 'project_allocation_is_active' => 1]);
      $this->read_db->where_in('project_allocation.fk_office_id', array_column($user_offices, 'office_id'));
      $lookup_values['funder'] = $this->read_db->get('funder')->result_array();
    }


    return $lookup_values;
  }

  public function budget_to_date_amount_by_income_account($budget_id, $income_account_id)
  {

    $budget_item_detail_amount = 0;

    $this->read_db->select_sum('budget_item_detail_amount');
    $this->read_db->where(array('fk_budget_id' => $budget_id, 'fk_income_account_id' => $income_account_id));
    $this->read_db->join('budget_item', 'budget_item.budget_item_id=budget_item_detail.fk_budget_item_id');
    $this->read_db->join('expense_account', 'expense_account.expense_account_id=budget_item.fk_expense_account_id');
    $budget_item_detail_amount_obj = $this->read_db->get('budget_item_detail');

    if ($budget_item_detail_amount_obj->num_rows() > 0) {
      $budget_item_detail_amount = $budget_item_detail_amount_obj->row()->budget_item_detail_amount;
    }

    return $budget_item_detail_amount;
  }

  function edit_visible_columns()
  {
    return ['budget_tag_name', 'budget_year', 'office_name'];
  }

  function list_table_where()
  {
    
    $user_offices = array_filter($this->session->hierarchy_offices, function($office){
      if($office['context_definition_id'] == $this->session->context_definition['context_definition_level']){
        return $office;
      }
    });

    // log_message('error', json_encode($user_offices));
    // $this->read_db->join('funder','funder.funder_id=budget.fk_funder_id');
    // $this->read_db->join('project','project.fk_funder_id=funder.funder_id');
    // $this->read_db->join('project_allocation','project_allocation.fk_project_id=project.project_id','left');
    // // Also filter by allocation extended date and project end date
    // $this->read_db->where(['funder_is_active' => 1, 'project_allocation_is_active' => 1]);
    // $this->read_db->where_in('project_allocation.fk_office_id', array_column($user_offices, 'office_id'));

    // Only list requests from the users' hierachy offices
    $this->read_db->where_in($this->controller . '.fk_office_id', array_column($user_offices, 'office_id'));
  }

  function transaction_validate_duplicates_columns()
  {
    return ['fk_office_id', 'fk_budget_tag_id', 'budget_year','fk_custom_financial_year_id'];
  }

  // public function get_immediate_previous_budget($office_id, $current_budget_fy, $header_id, $start_month = '')
  // {
  //   // Get the budget tag level of the just previous budget

  //   $budget_tag_level_of_previous_budget = 0;
  //   $budget_id_of_previous_budget = 0;
  //   $budget_tag_id_of_previous_budget = 0;
  //   $budget_fy_start_month_of_previous_budget = 7;

  //   $this->read_db->select(array('budget_tag_level', 'budget_id','budget_tag_id', 'fk_custom_financial_year_id'));
  //   $this->read_db->where(array(
  //     'budget.fk_office_id' => $office_id,
  //     'budget_year' => $current_budget_fy, 'budget_id<' => $header_id
  //   ));

  //   if($start_month != '' && $start_month != 7){
  //     $this->read_db->select(array('custom_financial_year_start_month'));
  //     $this->read_db->join('custom_financial_year','custom_financial_year.custom_financial_year_id=budget.fk_custom_financial_year_id');
  //     $this->read_db->where(array('custom_financial_year_start_month' => $start_month));
  //   }

  //   $this->read_db->limit(1);
  //   $this->read_db->order_by('budget_id DESC');
  //   $this->read_db->order_by('budget_tag_level DESC');
  //   $this->read_db->join('budget_tag', 'budget_tag.budget_tag_id=budget.fk_budget_tag_id');
  //   $previous_budgets_obj = $this->read_db->get('budget');

  //   if ($previous_budgets_obj->num_rows() > 0) {
  //     // log_message('error', json_encode($previous_budgets_obj->result_array()));

  //     $previous_budget =  $previous_budgets_obj->row_array();

  //     $budget_tag_level_of_previous_budget = $previous_budget['budget_tag_level'];
  //     $budget_id_of_previous_budget = $previous_budget['budget_id'];
  //     $budget_tag_id_of_previous_budget = $previous_budget['budget_tag_id'];
 
  //      if($previous_budget['fk_custom_financial_year_id'] != NULL){
  //       $this->read_db->select(array('custom_financial_year_start_month'));
  //       $this->read_db->where(array('custom_financial_year_id' => $previous_budget['fk_custom_financial_year_id']));
  //       $budget_fy_start_month_of_previous_budget = $this->read_db->get('custom_financial_year')->row()->custom_financial_year_start_month;
  //      }

  //   }

  //   return [
  //             'budget_tag_level' => $budget_tag_level_of_previous_budget, 
  //             'budget_id' => $budget_id_of_previous_budget,
  //             'budget_tag_id'=>$budget_tag_id_of_previous_budget,
  //             'budget_fy_start_month' => $budget_fy_start_month_of_previous_budget
  //           ];
  // }

  // function create_budget_projection($post_array,$approval_id,$header_id){
  //   // Check if a budget projection is present and if not create one

  //   $this->read_db->where(array('fk_budget_id'=>$header_id));
  //   $budget_projection_obj = $this->read_db->get('budget_projection');

  //   if($budget_projection_obj->num_rows() == 0){

  //     $budget_projection_data['budget_projection_name'] = $this->grants_model->generate_item_track_number_and_name('budget_projection')['budget_projection_name'];
  //     $budget_projection_data['budget_projection_track_number'] = $this->grants_model->generate_item_track_number_and_name('budget_projection')['budget_projection_track_number'];
  //     $budget_projection_data['fk_budget_id'] = $header_id;
  //     $budget_projection_data['budget_projection_created_by'] = $this->session->user_id;
  //     $budget_projection_data['budget_projection_created_date'] = date('Y-m-d');
  //     $budget_projection_data['budget_projection_last_modified_by'] = $this->session->user_id;
  //     $budget_projection_data['fk_approval_id'] = '';
  //     $budget_projection_data['fk_status_id'] = '';

  //     $budget_projection_to_insert = $this->grants_model->merge_with_history_fields('budget_projection',$budget_projection_data,false);
  //     $this->write_db->insert('budget_projection',$budget_projection_to_insert);

  //   }
  // }

  function replicate_budget_limit($new_budget_array,$previous_budget_array, $funder_id){
    // Get Limits for the previous budget
    
    $budget_limits_data = [];

    $this->read_db->where(
      array(
          'fk_budget_id' => $previous_budget_array['budget_id'],
          'budget.fk_funder_id' => $funder_id
        )
      );
    $this->read_db->join('budget','budget.budget_id=budget_limit.fk_budget_id');
    $budget_limit_obj = $this->read_db->get('budget_limit');

    if($budget_limit_obj->num_rows() > 0){
      $budget_limits = $budget_limit_obj->result_array();
      
   
      foreach($budget_limits as $key => $budget_limit){
        unset($budget_limits[$key]['budget_limit_id']);
        $budget_limits_data[$key]['budget_limit_name'] = $this->grants_model->generate_item_track_number_and_name('budget_limit')['budget_limit_name'];;
        $budget_limits_data[$key]['budget_limit_track_number'] = $this->grants_model->generate_item_track_number_and_name('budget_limit')['budget_limit_track_number'];;
        $budget_limits_data[$key]['fk_budget_id'] = $new_budget_array['fk_budget_id'];
        $budget_limits_data[$key]['budget_limit_amount'] = $budget_limit['budget_limit_amount'];
        $budget_limits_data[$key]['fk_income_account_id'] = $budget_limit['fk_income_account_id'];
        $budget_limits_data[$key]['budget_limit_created_date'] = date('Y-m-d');
        $budget_limits_data[$key]['budget_limit_created_by'] = $this->session->user_id;
        $budget_limits_data[$key]['budget_limit_last_modified_date'] = date('Y-m-d h:i:s');
        $budget_limits_data[$key]['budget_limit_last_modified_by'] = $this->session->user_id;
        $budget_limits_data[$key]['fk_status_id'] = $this->grants_model->initial_item_status('budget_limit');
        // $budget_limits_data[$key]['fk_approval_id'] = $this->grants_model->insert_approval_record('budget_limit');

      
      }
      
      // Must be a write for it to work as expected.Please do not update to read_db
      $new_limit_count = $this->write_db->get_where('budget_limit', 
      array('fk_budget_id' => $new_budget_array['fk_budget_id']))->num_rows();
      
      if($new_limit_count == 0){
        $this->write_db->insert_batch('budget_limit',$budget_limits_data);
      }
    }

  }



  /**
   * @todo:
   * Awaiting Documentation
   */

  public function get_custom_financial_year_start_month($office_id){

    $custom_financial_year_start_month = 7;

    $this->read_db->select(array('custom_financial_year_start_month'));
    $this->read_db->where(array('custom_financial_year_is_default'=> 1,'fk_office_id' => $office_id));
    $custom_financial_year_obj = $this->read_db->get('custom_financial_year');

    if($custom_financial_year_obj->num_rows() > 0){
      $custom_financial_year_start_month = $custom_financial_year_obj->row()->custom_financial_year_start_month;
    }

    return $custom_financial_year_start_month;
  }

  public function get_immediate_previous_budget($office_id, $current_budget_fy, $header_id, $start_month = '', $is_financial_year_switching = false, $funder_id = 0)
  {
    // Get the budget tag level of the just previous budget

    $budget_tag_level_of_previous_budget = 0;
    $budget_id_of_previous_budget = 0;
    $budget_tag_id_of_previous_budget = 0;
    $budget_fy_start_month_of_previous_budget = 7;

    $this->read_db->select(array('budget_tag_level', 'budget_id','budget_tag_id', 'fk_custom_financial_year_id'));
    $this->read_db->where(array('budget.fk_office_id' => $office_id,'budget_id<' => $header_id, 'budget.fk_funder_id' => $funder_id));
    
    $this->read_db->limit(1);
    $this->read_db->order_by('budget_id DESC');

    if(!$is_financial_year_switching){
      if($start_month != '' && $start_month != 7){
        $this->read_db->select(array('custom_financial_year_start_month'));
        $this->read_db->join('custom_financial_year','custom_financial_year.custom_financial_year_id=budget.fk_custom_financial_year_id');
        $this->read_db->where(array('custom_financial_year_start_month' => $start_month));
      }
      $this->read_db->order_by('budget_tag_level DESC');
      $this->read_db->where(array('budget_year' => $current_budget_fy));
    }

    $this->read_db->join('budget_tag', 'budget_tag.budget_tag_id=budget.fk_budget_tag_id');
    $previous_budgets_obj = $this->read_db->get('budget');

    if ($previous_budgets_obj->num_rows() > 0) {
      // log_message('error', json_encode($previous_budgets_obj->result_array()));

      $previous_budget =  $previous_budgets_obj->row_array();

      $budget_tag_level_of_previous_budget = $previous_budget['budget_tag_level'];
      $budget_id_of_previous_budget = $previous_budget['budget_id'];
      $budget_tag_id_of_previous_budget = $previous_budget['budget_tag_id'];
 
       if($previous_budget['fk_custom_financial_year_id'] != NULL){
        $this->read_db->select(array('custom_financial_year_start_month'));
        $this->read_db->where(array('custom_financial_year_id' => $previous_budget['fk_custom_financial_year_id']));
        $budget_fy_start_month_of_previous_budget = $this->read_db->get('custom_financial_year')->row()->custom_financial_year_start_month;
       }

    }

    return [
              'budget_tag_level' => $budget_tag_level_of_previous_budget, 
              'budget_id' => $budget_id_of_previous_budget,
              'budget_tag_id'=>$budget_tag_id_of_previous_budget,
              'budget_fy_start_month' => $budget_fy_start_month_of_previous_budget
            ];
  }

  // private function get_immediate_previous_budget_for_first_flexible_budget($office_id, $current_budget_fy, $header_id, $start_month){
    
  //    // Get the budget tag level of the just previous budget

  //    $budget_tag_level_of_previous_budget = 0;
  //    $budget_id_of_previous_budget = 0;
  //    $budget_tag_id_of_previous_budget = 0;
  //    $budget_fy_start_month_of_previous_budget = 7;
 
  //    $this->read_db->select(array('budget_tag_level', 'budget_id','budget_tag_id', 'fk_custom_financial_year_id'));
  //    $this->read_db->where(array(
  //      'budget.fk_office_id' => $office_id,
  //      'budget_id<' => $header_id
  //    ));
 
  //    $this->read_db->order_by('budget_id DESC');
  //    $this->read_db->limit(1);
  //    $this->read_db->join('budget_tag', 'budget_tag.budget_tag_id=budget.fk_budget_tag_id');
  //    $previous_budgets_obj = $this->read_db->get('budget');
 
  //    if ($previous_budgets_obj->num_rows() > 0) {
  //     $previous_budget =  $previous_budgets_obj->row_array();

  //     $budget_tag_level_of_previous_budget = $previous_budget['budget_tag_level'];
  //     $budget_id_of_previous_budget = $previous_budget['budget_id'];
  //     $budget_tag_id_of_previous_budget = $previous_budget['budget_tag_id'];
 
  //      if($previous_budget['fk_custom_financial_year_id'] != NULL){
  //       $this->read_db->select(array('custom_financial_year_start_month'));
  //       $this->read_db->where(array('custom_financial_year_id' => $previous_budget['fk_custom_financial_year_id']));
  //       $budget_fy_start_month_of_previous_budget = $this->read_db->get('custom_financial_year')->row()->custom_financial_year_start_month;
  //      }
       
  //    }
 
  //    return [
  //              'budget_tag_level' => $budget_tag_level_of_previous_budget, 
  //              'budget_id' => $budget_id_of_previous_budget,
  //              'budget_tag_id'=>$budget_tag_id_of_previous_budget,
  //              'budget_fy_start_month' => $budget_fy_start_month_of_previous_budget
  //            ];
  // }

  private function is_financial_year_switching($default_custom_financial_year){
    $is_financial_year_switching = false;

    if(!empty($default_custom_financial_year)){
      $custom_fy_id = $default_custom_financial_year['id'];

      // This MUST be write_db query DO NOT CHANGE IT
      $this->write_db->where(array('fk_custom_financial_year_id' => $custom_fy_id));
      $count_of_budget_with_custom_fy = $this->write_db->get('budget')->num_rows();

      $is_financial_year_switching = $count_of_budget_with_custom_fy == 1 ? true : false;
    }

    return $is_financial_year_switching;
  }

  function replicate_budget($post_array, $approval_id, $header_id, $default_custom_financial_year)
  {
    // Checking the bugdet tag level of the posted budget and retrive the budget record that has n-1 budget tag level
    // $budget_tag_id_of_new_budget = $post_array['fk_budget_tag_id'];
    $office_id = $post_array['fk_office_id'];
    $funder_id = $post_array['fk_funder_id'];
    $current_budget_fy = $post_array['budget_year'];
    $previous_budget_id = 0;
    $previous_budget = [];
    $budget_overlap_month = [];
    $budget_start_date = '';

    $custom_financial_year_start_month = $this->get_custom_financial_year_start_month($post_array['fk_office_id']);

    // Check if the budget is using a flexible FY and its the first budget
    $is_financial_year_switching  =  $this->is_financial_year_switching($default_custom_financial_year);
    
    if($is_financial_year_switching){
      $budget_start_date = $default_custom_financial_year['start_date'];
      $previous_budget = $this->get_immediate_previous_budget($office_id, $current_budget_fy, $header_id, $custom_financial_year_start_month, $is_financial_year_switching, $funder_id);
      // log_message('error', "Switch FY");
      // log_message('error', json_encode($previous_budget));
    }else{
      $previous_budget = $this->get_immediate_previous_budget($office_id, $current_budget_fy, $header_id, $custom_financial_year_start_month, false, $funder_id);
      $this->replicate_budget_limit($post_array, $previous_budget, $funder_id);
      // log_message('error', "Standard FY");
      // log_message('error', json_encode($previous_budget));
    }

    $previous_budget_id = $previous_budget['budget_id'];
    $budget_overlap_month = $this->budget_overlap_month($header_id, $previous_budget, $budget_start_date);

    if(!empty($budget_overlap_month['overlap_months'])){
      // log_message('error', json_encode($budget_overlap_month['overlap_months']));
      $this->insert_previous_budget_items($header_id, $previous_budget_id, $budget_overlap_month['overlap_months'], $is_financial_year_switching);
    }
    
  }

  private function get_budget_start_year($startMonth, $budget_year){
    // $months = array();
    $budget_start_year = '20'.$budget_year;

    // Loop through the months, wrapping around from December to January if necessary
    for ($i = 0; $i < 12; $i++) {
        $month = ($startMonth + $i) % 12;
        // Adjust for 0-based array index
        if ($month === 0) {
            $month = 12;
        }
        $months[] = $month;

        if($i > 0 && $month == 1){
            $budget_year -= 1;
            $budget_start_year = '20'.$budget_year;
        }
    }

    return $budget_start_year;
  }

  private function get_budget_start_month_and_year($budget_id){

    // Queries in this method MUST use write_db handle. DO NOT CHANGE

    $budget_start_year = '';
    $start_month = 7;
    $this->write_db->where(array('budget_id' => $budget_id));
    $budget = $this->write_db->get('budget')->row();
    
    // log_message('error', json_encode($budget));

    if($budget->fk_custom_financial_year_id > 0){
      $this->write_db->where(array('custom_financial_year_id' => $budget->fk_custom_financial_year_id));
      $start_month = $this->write_db->get('custom_financial_year')->row()->custom_financial_year_start_month;
    }

    $budget_start_year = $this->get_budget_start_year($start_month, $budget->budget_year);

    $result = ['budget_start_year' => $budget_start_year, 'start_month' => $start_month];
    return $result;
  }

private function list_budget_month_order($budget_id, $custom_year_start_date = ""){
    $budget_start_month_and_year = $this->get_budget_start_month_and_year($budget_id);
    $monthNumber = $budget_start_month_and_year['start_month']; //4; // April
    $year = $budget_start_month_and_year['budget_start_year']; //2023;

    // Create an array to store the first-day dates
    $firstDayDates = array();

    // Create a starting date for the first day of the specified month and year
    $date = new DateTime("$year-$monthNumber-01");

    // Define the end date by adding one year
    $endDate = clone $date;
    $endDate->modify('+1 year');

    // Loop through the months and add the first day of each month to the array
    while ($date < $endDate) {
        $firstDayDates[] = $date->format('Y-m-d');
        $date->modify('+1 month'); // Move to the next month
    }

      if($custom_year_start_date != ""){
        foreach ($firstDayDates as $key => $date) {  
          // Check if the month is less than or equal to 6
          if ($date < $custom_year_start_date) {
              // Remove the date from the array
              unset($firstDayDates[$key]);
          }
      }
    }

    return $firstDayDates;
  }

  function budget_overlap_month($budget_id, $previous_budget, $custom_year_start_date = ""){

    $previous_budget_id = 0;
    $overlap_month_numbers = [];

    if($previous_budget['budget_id'] > 0){
      $previous_budget_id = $previous_budget['budget_id'];
      // $new_budget_month_order = ['2023-04-01','2023-05-01','2023-06-01','2023-07-01','2023-08-01','2023-09-01','2023-10-01','2023-11-01','2023-12-01','2024-01-01','2024-02-01','2024-03-01'];// $this->list_budget_month_order($budget_id);
      $new_budget_month_order = $this->list_budget_month_order($budget_id, $custom_year_start_date);
      //$previous_budget_month_order = ['2023-07-01','2023-08-01','2023-09-01','2023-10-01','2023-11-01','2023-12-01','2024-01-01','2024-02-01','2024-03-01','2024-04-01','2024-05-01','2024-06-01'];// $this->list_budget_month_order($previous_budget['budget_id']);
      $previous_budget_month_order = $this->list_budget_month_order($previous_budget['budget_id']);

      // Find overlapping months
      $overlap = array_intersect($new_budget_month_order, $previous_budget_month_order);

      // Initialize an array to store month numbers
      $overlap_month_numbers = array();

      // Extract month numbers from overlapping dates
      foreach ($overlap as $date) {
          $month_number = date('n', strtotime($date));
          $overlap_month_numbers[] = $month_number;
      }
    }

    $result = ['previous_budget_id' => $previous_budget_id,'overlap_months' => $overlap_month_numbers];
    // log_message('error', json_encode($result));
    // {"previous_budget_id":"6592","overlap_months":[7,8,9,10,11,12,1,2,3,4,5,6]}
    return $result;
  }

  // function budget_overlap_month($budget_id){
  //   // Intentionally using write db handle to prevent delays in reads
  //   $overlap_months = [];
  //   $overlap_budgets = [];

  //   $this->write_db->select(array('budget_id','fk_office_id','budget_year'));
  //   $this->write_db->where(array('budget_id' => $budget_id));
  //   $current_budget = $this->write_db->get('budget')->row();

  //   // return $current_budget;

  //   $this->write_db->select(array('budget_id','budget.fk_office_id as office_id','fk_budget_tag_id','fk_custom_financial_year_id','budget_year','custom_financial_year_start_month','custom_financial_year_reset_date'));
  //   $this->write_db->where(array('budget.fk_office_id' => $current_budget->fk_office_id, 'budget_year' => $current_budget->budget_year));
  //   $this->write_db->order_by('budget_id DESC');
  //   $this->write_db->limit(2);
  //   $this->write_db->join('custom_financial_year','custom_financial_year.custom_financial_year_id=budget.fk_custom_financial_year_id', 'LEFT');
  //   $overlap_budgets = $this->write_db->get('budget')->result_array();

  //   $custom_financial_years = array_column($overlap_budgets,'fk_custom_financial_year_id');
  //   // log_message('error', json_encode($overlap_budgets));
  //   // log_message('error', json_encode($custom_financial_years));

  //   $office_transitioning_to_custom_fy = in_array(null,$custom_financial_years) && count($custom_financial_years) > 1;

  //   if($office_transitioning_to_custom_fy){

  //     $custom_fy_month_spread = $this->fy_month_spread($overlap_budgets[0]['custom_financial_year_reset_date']);
  //     $legacy_fy_month_spread = $this->fy_month_spread('20'.$overlap_budgets[0]['budget_year'].'-07-01');
      
  //     $offset = 0;

  //     for($i = 0; $i < sizeof($legacy_fy_month_spread); $i++){
  //       if($legacy_fy_month_spread[$i] == $custom_fy_month_spread[0]){
  //         $offset = 12 - $i;
  //       }
  //     }

  //     $overlap_months = array_slice($custom_fy_month_spread, 0, $offset);// ['legacy_fy_month_spread' => $legacy_fy_month_spread, 'custom_fy_month_spread' => $custom_fy_month_spread];
  //   }

  //   return ['previous_budget_id' => isset($overlap_budgets[1]) ? $overlap_budgets[1]['budget_id'] : 0,'overlap_months' => $overlap_months];
  // }

  function fy_month_spread($budget_start_date){

    $startDate = new DateTime($budget_start_date);
    $endDate = clone $startDate;
    $endDate->modify('+1 year');

    $result = array();

    while ($startDate < $endDate) {
        $result[] = intval($startDate->format('n'));
        $startDate->modify('+1 month');
    }

    return $result;
  }

  /**
   * insert_previous_budget_items
   * 
   * Replicate budget from previous tag or on fy transitions
   * 
   * @author Nicodemus Karisa Mwambire
   * @authored_date 22nd June 2023
   * 
   * @param int $current_budget_id - Newly created budget id 
   * @param int $previous_budget_id - Immediate previous budget id
   * @param array $overlaps_month - Overlapping months incase of FY settings transitions
   * 
   * @return void
   */

  function insert_previous_budget_items(int $current_budget_id, int $previous_budget_id, array $overlaps_month = [], $is_financial_year_switching = false ):void{
    // log_message('error', json_encode(['current_budget_id' => $current_budget_id, 'previous_budget_id' => $previous_budget_id, 'overlaps_month' => $overlaps_month ]));
    // Get the budget items and budget item details for the previous budget
    $this->read_db->select(
      array(
        'budget_item_detail_id',
        'budget_item_id',
        'budget_item_quantity',
        'budget_item_unit_cost',
        'budget_item_often',
        'budget_item_total_cost',
        'fk_expense_account_id',
        'budget_item_description',
        'fk_project_allocation_id',
        'fk_month_id',
        'budget_item_detail_amount',
        'budget_item.fk_status_id as fk_status_id',
        'budget_item_marked_for_review'
      )
    );

    $this->read_db->where(array('fk_budget_id' => $previous_budget_id));

    if(count($overlaps_month) > 0){
      $this->read_db->where_in('fk_month_id', $overlaps_month);
    }

    $this->read_db->join('budget_item', 'budget_item.budget_item_id=budget_item_detail.fk_budget_item_id');
    $budget_item_detail_obj = $this->read_db->get('budget_item_detail');

    if ($budget_item_detail_obj->num_rows() > 0) {

      $budget_item_details = $budget_item_detail_obj->result_array();

      $budget_item_details_grouped = [];

      foreach ($budget_item_details as $budget_item_detail) {
        $budget_item_details_grouped[$budget_item_detail['budget_item_id']]['budget_item'] = [
          'budget_item_quantity' => $budget_item_detail['budget_item_quantity'],
          'budget_item_unit_cost' => $budget_item_detail['budget_item_unit_cost'],
          'budget_item_often' => $budget_item_detail['budget_item_often'],
          'budget_item_total_cost' => $budget_item_detail['budget_item_total_cost'],
          'fk_expense_account_id' => $budget_item_detail['fk_expense_account_id'],
          'budget_item_description' => $budget_item_detail['budget_item_description'],
          'fk_project_allocation_id' => $budget_item_detail['fk_project_allocation_id'],
          'fk_status_id' => $budget_item_detail['fk_status_id'],
          'budget_item_marked_for_review' => $budget_item_detail['budget_item_marked_for_review'],
          'budget_item_id' => $budget_item_detail['budget_item_id']
        ];

        $budget_item_details_grouped[$budget_item_detail['budget_item_id']]['budget_item_detail'][$budget_item_detail['fk_month_id']] = $budget_item_detail['budget_item_detail_amount'];
      }

      foreach ($budget_item_details_grouped as $loop_budget_item_and_details) {
        // Insert budget item
        //$budget_item_insert_array = $budget_item_details_grouped[$budget_item_detail['budget_item_id']]['budget_item'];
        $budget_item_array['budget_item_name'] = $this->grants_model->generate_item_track_number_and_name('budget_item')['budget_item_name'];
        $budget_item_array['budget_item_track_number'] = $this->grants_model->generate_item_track_number_and_name('budget_item')['budget_item_track_number'];
        $budget_item_array['fk_budget_id'] = $current_budget_id;
        $budget_item_array['budget_item_quantity'] = $loop_budget_item_and_details['budget_item']['budget_item_quantity'];
        $budget_item_array['budget_item_unit_cost'] = $loop_budget_item_and_details['budget_item']['budget_item_unit_cost'];
        $budget_item_array['budget_item_often'] = $loop_budget_item_and_details['budget_item']['budget_item_often'];
        $budget_item_array['budget_item_total_cost'] = $loop_budget_item_and_details['budget_item']['budget_item_total_cost'];
        $budget_item_array['fk_expense_account_id'] = $loop_budget_item_and_details['budget_item']['fk_expense_account_id'];
        $budget_item_array['budget_item_description'] = $loop_budget_item_and_details['budget_item']['budget_item_description'];
        $budget_item_array['fk_project_allocation_id'] = $loop_budget_item_and_details['budget_item']['fk_project_allocation_id'];

        $budget_item_array['budget_item_created_by'] = $this->session->user_id ? $this->session->user_id : 1;
        $budget_item_array['budget_item_created_date'] = date('Y-m-d');
        $budget_item_array['budget_item_marked_for_review'] = $loop_budget_item_and_details['budget_item']['budget_item_marked_for_review'];

        $budget_item_array['fk_approval_id'] = 0;
        $budget_item_array['fk_status_id'] = $loop_budget_item_and_details['budget_item']['fk_status_id'];

        $budget_item_array['budget_item_source_id'] = $loop_budget_item_and_details['budget_item']['budget_item_id'];

        $budget_item_computed_total_cost = $budget_item_array['budget_item_quantity'] * $budget_item_array['budget_item_unit_cost'] * $budget_item_array['budget_item_often'];

        if(!$is_financial_year_switching && $loop_budget_item_and_details['budget_item']['budget_item_marked_for_review'] == 1){
          $budget_item_array['fk_approval_id'] = $this->grants_model->insert_approval_record('budget_item');
          $budget_item_array['fk_status_id'] = $this->grants_model->initial_item_status('budget_item');
        }

        $this->write_db->insert('budget_item', $budget_item_array);

        $budget_item_id = $this->write_db->insert_id();

        $budget_item_details_to_loop =  $loop_budget_item_and_details['budget_item_detail'];

        $item_total_amount = 0;
        foreach ($budget_item_details_to_loop as $month_id => $amount) {

          $budget_item_detail_array['budget_item_detail_name'] = $this->grants_model->generate_item_track_number_and_name('budget_item_detail')['budget_item_detail_name'];
          $budget_item_detail_array['budget_item_detail_track_number'] = $this->grants_model->generate_item_track_number_and_name('budget_item_detail')['budget_item_detail_track_number'];
          $budget_item_detail_array['fk_month_id'] = $month_id;
          $budget_item_detail_array['budget_item_detail_amount'] = $amount;
          $budget_item_detail_array['fk_budget_item_id'] = $budget_item_id;

          $budget_item_detail_array_to_insert = $this->grants_model->merge_with_history_fields('budget_item_detail', $budget_item_detail_array, false);
          $this->write_db->insert('budget_item_detail', $budget_item_detail_array_to_insert);

          $item_total_amount += $amount;
        }

        // Update the qty, unit cost and frequency if the spreading differs from the origin budget especially in cases of first custom fy budget
        if($item_total_amount != $budget_item_computed_total_cost){
          $update_item['budget_item_quantity'] = 1;
          $update_item['budget_item_unit_cost'] = $item_total_amount;
          $update_item['budget_item_often'] = 1;
          $update_item['budget_item_total_cost'] = $item_total_amount;

          $this->write_db->where(array('budget_item_id' => $budget_item_id));
          $this->write_db->update('budget_item', $update_item);
          
          $note = get_phrase('recomputed_buget_item_frequency', 'The budget item quantity, unit cost and frequency has been recomputed due to partial spreading from the previous budget review');

          $this->message_model->post_new_message('budget_item',$budget_item_id,$note);
        }
      }
    }
  }

  /**
   * @todo:
   * Await documentation
   */

  function action_after_insert($post_array, $approval_id, $header_id)
  {

    $this->write_db->trans_start();

    $default_custom_financial_year = $this->get_custom_financial_year($post_array['fk_office_id'], true);

    if(!empty($default_custom_financial_year)){
      $this->update_budget_custom_financial_year($header_id, $default_custom_financial_year['start_month'], $default_custom_financial_year['id']);
    }

    $this->replicate_budget($post_array, $approval_id, $header_id, $default_custom_financial_year);

    $this->write_db->trans_complete();

    return $this->write_db->trans_status();
  }

  function update_budget_custom_financial_year($budget_id, $start_month, $default_custom_financial_year){
    $this->write_db->where(array('budget_id' => $budget_id));
    $this->write_db->update('budget', ['fk_custom_financial_year_id' => $default_custom_financial_year]);
  }

  /**
   * @todo:
   * Await documentation
   */

  function deactivate_active_custom_financial_year($office_id, $custom_financial_year){
    $this->write_db->where(array('fk_office_id' => $office_id, 'custom_financial_year_id' => $custom_financial_year));
    $this->write_db->update('custom_financial_year', ['custom_financial_year_is_active' => 0]);
  }

  public function has_initial_status_budget_items($budget_id)
  {
    $has_initial_status_budget_items = false;

    $this->read_db->where(array('fk_budget_id' => $budget_id, 'status_approval_sequence' => 1));
    $this->read_db->join('status', 'status.status_id=budget_item.fk_status_id');
    $budget_item_count = $this->read_db->get('budget_item')->num_rows();

    if ($budget_item_count > 0) {
      $has_initial_status_budget_items = true;
    }

    return $has_initial_status_budget_items;
  }

  function get_budget_id_based_on_month($office_id, $reporting_month)
  {

    $this->load->model('custom_financial_year_model');
    $custom_financial_year = $this->custom_financial_year_model->get_default_custom_financial_year_id_by_office($office_id, true);
    $budget_tag_id = $this->budget_tag_model->get_budget_tag_id_based_on_reporting_month($office_id, $reporting_month, $custom_financial_year)['budget_tag_id'];
    // log_message('error', json_encode($budget_tag_id));
    $budget_id = 0;

    $budget_year = get_fy($reporting_month);

    $this->read_db->where(array(
      'fk_budget_tag_id' => $budget_tag_id,
      'fk_office_id' => $office_id, 'budget_year' => $budget_year
    ));

    $budget_id_obj = $this->read_db->get('budget');

    if ($budget_id_obj->num_rows() > 0) {
      $budget_id =  $budget_id_obj->row()->budget_id;
    }

    return $budget_id;
  }

  function get_budget_by_id($budget_id)
  {
    // $this->read_db->select(
    //   ['fk_office_id', 'fk_budget_tag_id', 'budget_year']
    // );
    $this->read_db->where(array('budget_id' => $budget_id));
    $this->read_db->join('budget_tag','budget_tag.budget_tag_id=budget.fk_budget_tag_id');
    $budget_obj = $this->read_db->get('budget')->row();

    return  $budget_obj;
  }

  function office_budget_records($office_id, $budget_year = '', $start_moth = '', $funder_id = 0){
    // log_message('error', json_encode(compact('office_id', 'budget_year', 'start_moth','funder_id')));
    $office_budget_records = [];

    $this->read_db->select(
        [
        'budget.fk_office_id office_id', 
        'fk_account_system_id account_system_id', 
        'budget_year', 'budget_tag_id', 
        'budget_tag_level'
      ]
    );
    $this->read_db->join('budget_tag','budget_tag.budget_tag_id=budget.fk_budget_tag_id');
    $this->read_db->where(array('budget.fk_office_id' => $office_id, 'fk_funder_id' => $funder_id));

    if($budget_year != ''){
      $this->read_db->where(array('budget_year' => $budget_year));
    }

    if($start_moth != ''){
      $this->read_db->join('custom_financial_year','custom_financial_year.custom_financial_year_id=budget.fk_custom_financial_year_id');
      $this->read_db->where(array('custom_financial_year_start_month' => $start_moth));
    }

    $this->read_db->order_by('budget_year ASC', 'budget_tag_level ASC');
    $all_budgets_obj = $this->read_db->get("budget");

    if($all_budgets_obj->num_rows() > 0 ){
      $office_budget_records = $all_budgets_obj->result_array();
    }

    return $office_budget_records;
  }

  // To be moved to office model

  function get_office($office_id){

    $this->read_db->select(array('office_id','office_start_date','fk_account_system_id account_system_id'));
    $this->read_db->where(array('office_id' =>$office_id ));
    $office_start_date = $this->read_db->get('office')->row();

    return $office_start_date;
  }

  function office_budget_review_count($office_id){

    // $this->read_db->join('account_system','account_system.account_system_id=budget_review_count.fk_account_system_id');
    // $this->read_db->join('office','office.fk_account_system_id=account_system.account_system_id');
    // $this->read_db->where(array('office_id' => $office_id));
    // $budget_review_count = $this->read_db->get('budget_review_count')->row()->budget_review_count_number;

    // return $budget_review_count;

    $this->load->model('budget_review_count_model');

    $review_count = $this->budget_review_count_model->budget_review_count_by_office($office_id);

    return $review_count;
  }

  /**
   * @todo:
   * Await documentation
   */

  function valid_budget_years($office_id){

    $this->load->model('voucher_model');
    $this->load->model('custom_financial_year_model');

    $valid_budget_years = [];

    // Get office start date
    $custom_financial_year = $this->custom_financial_year_model->get_default_custom_financial_year_id_by_office($office_id);

    // log_message('error', json_encode($custom_financial_year));

    $budget_start_date = $this->voucher_model->get_voucher_date($office_id);

    if($custom_financial_year['custom_financial_year_is_active']){
      $budget_start_date = $custom_financial_year['custom_financial_year_reset_date'];
    }

    $office_start_fy = calculateFinancialYear($budget_start_date, $custom_financial_year['custom_financial_year_start_month']);

    // log_message('error', json_encode(['budget_start_date' => $budget_start_date, 'custom_financial_year' => $custom_financial_year, 'office_start_fy' => $office_start_fy]));

    $budget_review_count = $this->office_budget_review_count($office_id);

    // All budget present for the office
    $office_budget_records = []; // 

    // $default_custom_financial_year = $this->get_custom_financial_year($office_id, true);

    if($custom_financial_year['custom_financial_year_id'] != NULL){
      $office_budget_records = $this->office_budget_records($office_id, $office_start_fy, $custom_financial_year['custom_financial_year_start_month']);
    }else{
      $office_budget_records = $this->office_budget_records($office_id);
    }

    if(empty($office_budget_records)){
      $valid_budget_years = [$office_start_fy];
    }else{
        $last_budget_record = end($office_budget_records);
        $last_budget_year = $last_budget_record['budget_year'];
        $budget_tag_level = $last_budget_record['budget_tag_level'];

        if($budget_tag_level == $budget_review_count){
          $valid_budget_years = [$last_budget_year + 1];
        }else{
          $valid_budget_years = [$last_budget_year];
        }
    }

    return $valid_budget_years;
  }

  /**
   * @todo:
   * Await documentation
   */

  function get_custom_financial_year($office_id, $show_default_only = false){

    $budget_year = [];

    $this->read_db->select(array('custom_financial_year_id','custom_financial_year_start_month as start_month', 'custom_financial_year_reset_date as start_date'));
    
    $additional_condition = ['custom_financial_year_is_active' => 1, 'fk_office_id' => $office_id];

    if($show_default_only){
      $additional_condition = ['custom_financial_year_is_default' => 1, 'fk_office_id' => $office_id];
    }

    $this->read_db->where($additional_condition);

    $custom_financial_year_obj = $this->read_db->get('custom_financial_year');

    if($custom_financial_year_obj->num_rows() > 0){
      $custom_financial_year = $custom_financial_year_obj->row();
      $start_date = $custom_financial_year->start_date;
      $start_month = $custom_financial_year->start_month;
      $custom_financial_year_id = $custom_financial_year->custom_financial_year_id;
      
      $fy = get_fy($custom_financial_year->start_date, $office_id);

      $budget_year = ['budget_year' => $fy, 'start_date' => $start_date, 'start_month' => $start_month, 'id' => $custom_financial_year_id];
    }

    // log_message('error', json_encode($budget_year));

    return $budget_year;
  }

  /**
   * @todo:
   * Await documentation
   */

  function valid_budget_tags($office_id, $budget_year, $funder_id = 0){
    // log_message('error', json_encode(compact('budget_year','office_id','funder_id')));
    $valid_budget_tags = [];

    $office = $this->get_office($office_id);

    
    $default_custom_financial_year = $this->get_custom_financial_year($office_id, true);
    
    $year_budget_records = [];
    
    $get_custom_financial_year = $this->get_custom_financial_year($office_id);
    
    if(!empty($get_custom_financial_year)){
      $month_number = date('n', strtotime($default_custom_financial_year['start_date']));
      $quarter_number = financial_year_quarter_months($month_number, $office_id)['quarter_number'];
      $condition = array('fk_account_system_id' => $office->account_system_id, 'budget_tag_level' => $quarter_number);
      $year_budget_records = $this->office_budget_records($office_id, $budget_year, $default_custom_financial_year['start_month'], $funder_id);
      
      // log_message('error', json_encode(compact('month_number','quarter_number', 'condition', 'year_budget_records')));

    }else{
      $condition = array('fk_account_system_id' => $office->account_system_id);
        
      if(!empty($default_custom_financial_year)){
        $year_budget_records = $this->office_budget_records($office_id, $budget_year, $default_custom_financial_year['start_month'], $funder_id);
      }else{
        $year_budget_records = $this->office_budget_records($office_id, $budget_year,'', $funder_id);
        // log_message('error', json_encode($year_budget_records));
      }

    }

    // return $year_budget_records;
    // log_message('error', json_encode(['default_custom_financial_year' => $default_custom_financial_year]));

    $utilized_budget_tag_levels = array_column($year_budget_records, 'budget_tag_level');
    $range_of_budget_tag_levels = range(1, $this->office_budget_review_count($office_id));
  
    // log_message('error', json_encode(['default_custom_financial_year' => $default_custom_financial_year, 'utilized_budget_tag_levels' => $utilized_budget_tag_levels]));
    // Sort and iterate the standard list of budget tag levels and remove those levels that are before the first utilized quarter
      
    // return $range_of_budget_tag_levels;

    if(!empty($utilized_budget_tag_levels)){
        
      sort($utilized_budget_tag_levels);
  
      $lowest_utilized_level = $utilized_budget_tag_levels[0];
  
      foreach($range_of_budget_tag_levels as $range_of_budget_tag_level){
        if($range_of_budget_tag_level < $lowest_utilized_level){
          unset($range_of_budget_tag_levels[array_search($range_of_budget_tag_level, $range_of_budget_tag_levels)]);
      }
          
    }
  }
  
    $year_remaining_budget_tags = array_diff($range_of_budget_tag_levels, $utilized_budget_tag_levels);

    // log_message('error', json_encode(['default_custom_financial_year' => $default_custom_financial_year, 'utilized_budget_tag_levels' => $utilized_budget_tag_levels, 'year_remaining_budget_tags' => $year_remaining_budget_tags]));  
  
    sort($year_remaining_budget_tags);
  
      // Get all office budget records
      $all_office_budget_records = $year_budget_records;//$this->office_budget_records($office_id);
  
      // For new offices i.e. which do not have any budget record, show all budget tags
      if(!empty($all_office_budget_records) && !empty($year_remaining_budget_tags)){
        $this->read_db->where(array('budget_tag_level' => $year_remaining_budget_tags[0])); // Take the immediate next tag
      }
   // }

    $this->read_db->where($condition);
    $this->read_db->select(array('budget_tag_id','budget_tag_name'));
    $budget_tags = $this->read_db->get('budget_tag')->result_array();

    $budget_tag_ids = array_column($budget_tags, 'budget_tag_id');
    $budget_tag_names = array_column($budget_tags, 'budget_tag_name');

    $valid_budget_tags = array_combine($budget_tag_ids, $budget_tag_names);

    return $valid_budget_tags;
  }

  /**
   * get_current_unsigned_off_budget
   * 
   * Get the recent unsubmitted budget for an office
   * 
   * @author Nicodemus Karisa Mwambire
   * @authored_date 21st June 2023
   * 
   * @param int $office_id
   * 
   * @return array Last unsubmitted budget
   */

   function get_current_unsigned_off_budget(int $office_id, $funder_id): array {

    $budgets = [];

    $max_approval_ids = $this->general_model->get_max_approval_status_id('budget', [$office_id]);
    
    $this->read_db->where(array('budget.fk_office_id' => $office_id, 'fk_funder_id' => $funder_id));
    $this->read_db->where_not_in('budget.fk_status_id', $max_approval_ids);
    $this->read_db->select(array('budget_id','budget_tag_id','budget_year','budget_tag_name','status_name'));
    $this->read_db->join('budget_tag','budget_tag.budget_tag_id=budget.fk_budget_tag_id');
    $this->read_db->join('status','status.status_id=budget.fk_status_id');
    $this->read_db->order_by('budget_id DESC');
    $budget_obj = $this->read_db->get('budget');

    if($budget_obj->num_rows() > 0){
      $budgets = $budget_obj->row_array();
    }

    return $budgets;
  }

  function oldest_declined_financial_report($office_id){

    $decline_status_ids = $this->general_model->get_decline_status_ids('financial_report');

    $this->read_db->select(array('financial_report.fk_office_id as office_id','financial_report_id','budget_id', 'fk_custom_financial_year_id as custom_financial_year_id'));
    $this->read_db->where_in('financial_report.fk_status_id', $decline_status_ids);
    $this->read_db->where(array('financial_report.fk_office_id' => $office_id));
    $this->read_db->order_by('financial_report_month ASC');
    $this->read_db->join('budget','budget.budget_id=financial_report.fk_budget_id');
    $financial_report_obj = $this->read_db->get('financial_report');
      
    $oldest_declined_report = [];
      
    if($financial_report_obj->num_rows() > 0){
        $oldest_declined_report = $financial_report_obj->row_array();
    }
      
    return  $oldest_declined_report;
  }

  function evaluate_custom_financial_year($office_id, $next_vouching_date, &$custom_financial_year){

    $oldest_declined_financial_report = $this->oldest_declined_financial_report($office_id);

    if(!empty($oldest_declined_financial_report)){
      if($oldest_declined_financial_report['custom_financial_year_id'] == null){
        $custom_financial_year = ['custom_financial_year_start_month' => 7, 'custom_financial_year_id' => NULL, 'custom_financial_year_is_active' => 0, 'custom_financial_year_reset_date' => NULL];
      }else{
        $custom_financial_year = $this->custom_financial_year_model->get_custom_financial_year_by_id($oldest_declined_financial_report['custom_financial_year_id']);
      }
    }

    // Check if the vouching period is still behind the default custom fy reset date
    $transaction_period_behind_default_custom_fy_reset_date = $this->custom_financial_year_model->transaction_period_behind_default_custom_fy_reset_date($next_vouching_date,$custom_financial_year);

    if($transaction_period_behind_default_custom_fy_reset_date){
      $custom_financial_year = ['custom_financial_year_start_month' => 7, 'custom_financial_year_id' => NULL, 'custom_financial_year_is_active' => 0, 'custom_financial_year_reset_date' => NULL];
      
      if($custom_financial_year['custom_financial_year_id'] != null){
        $custom_financial_year = $this->custom_financial_year_model->get_previous_custom_financial_year_by_current_id($office_id, $custom_financial_year['custom_financial_year_id']);
      }
    }

  }


  // function get_a_budget_by_office_current_transaction_date($office_id){

  //   $this->load->model('voucher_model');
  //   $this->load->model('financial_report_model');
  //   $this->load->model('custom_financial_year_model');

  //   $next_vouching_date = $this->voucher_model->get_voucher_date($office_id);

  //   $custom_financial_year = $this->custom_financial_year_model->get_default_custom_financial_year_id_by_office($office_id, true);

  //   $this->evaluate_custom_financial_year($office_id, $next_vouching_date, $custom_financial_year);
   
  //   $start_month = $custom_financial_year['custom_financial_year_id'] != NULL && !$custom_financial_year['custom_financial_year_is_active'] ? $custom_financial_year['custom_financial_year_start_month'] : 7;
  //   $custom_financial_year_id = $custom_financial_year['custom_financial_year_id'] != NULL ? $custom_financial_year['custom_financial_year_id'] : 0;

  //   $fy = calculateFinancialYear($next_vouching_date, $start_month);

  //   $mfr_budget_tag_id = $this->budget_tag_model->get_budget_tag_id_based_on_reporting_month($office_id, $next_vouching_date)['budget_tag_id'];
  //   $max_budget_approval_ids = $this->general_model->get_max_approval_status_id('budget');

  //   // $custom_financial_year_id_of_last_active_mfr = $this->custom_financial_year_id_of_last_active_mfr();

  //   // log_message('error', json_encode(['next_vouching_date' => $next_vouching_date, 'custom_financial_year' => $custom_financial_year, 
  //   // 'start_month' => $start_month, 'custom_financial_year_id' => $custom_financial_year_id, 'fy' => $fy, 'mfr_budget_tag_id' => $mfr_budget_tag_id,
  //   // 'max_budget_approval_ids' => $max_budget_approval_ids, 'next_vouching_date' => $next_vouching_date, 'office_id' => $office_id]));
    
  //   if($custom_financial_year['custom_financial_year_id'] != NULL && !$custom_financial_year['custom_financial_year_is_active']){
  //     $this->read_db->where(array('fk_custom_financial_year_id' => $custom_financial_year_id));
  //   }
  //   $this->read_db->where(array('fk_office_id' => $office_id,'budget_year' => $fy,'fk_budget_tag_id' => $mfr_budget_tag_id));
  //   $this->read_db->where_in('budget.fk_status_id', $max_budget_approval_ids);

  //   $budget_obj = $this->read_db->get('budget');
  //   $budget = [];
  //   if($budget_obj->num_rows() > 0){
  //     $budget = $budget_obj->row_array();
  //   }
    
  //   // log_message('error', json_encode($budget));

  //   return $budget;
  // }

  function get_a_budget_by_office_current_transaction_date($office_id, $funder_id){

    $this->load->model('voucher_model');
    $this->load->model('financial_report_model');
    $this->load->model('custom_financial_year_model');

    $next_vouching_date = $this->voucher_model->get_voucher_date($office_id);

    $custom_financial_year = $this->custom_financial_year_model->get_default_custom_financial_year_id_by_office($office_id, true);

    $this->evaluate_custom_financial_year($office_id, $next_vouching_date, $custom_financial_year);
   
    $start_month = $custom_financial_year['custom_financial_year_id'] != NULL && !$custom_financial_year['custom_financial_year_is_active'] ? $custom_financial_year['custom_financial_year_start_month'] : 7;
    $custom_financial_year_id = $custom_financial_year['custom_financial_year_id'] != NULL ? $custom_financial_year['custom_financial_year_id'] : 0;

    $fy = calculateFinancialYear($next_vouching_date, $start_month);

    // log_message('error', json_encode(['fy' => $fy, 'start_month' => $start_month, 'next_vouching_date' => $next_vouching_date]));

    $mfr_budget_tag_id = isset($this->budget_tag_model->get_budget_tag_id_based_on_reporting_month($office_id, $next_vouching_date, $custom_financial_year)['budget_tag_id'])?:0;
    $max_budget_approval_ids = $this->general_model->get_max_approval_status_id('budget');

    // $custom_financial_year_id_of_last_active_mfr = $this->custom_financial_year_id_of_last_active_mfr();

    // log_message('error', json_encode(['next_vouching_date' => $next_vouching_date, 'custom_financial_year' => $custom_financial_year, 
    // 'start_month' => $start_month, 'custom_financial_year_id' => $custom_financial_year_id, 'fy' => $fy, 'mfr_budget_tag_id' => $mfr_budget_tag_id,
    // 'max_budget_approval_ids' => $max_budget_approval_ids, 'next_vouching_date' => $next_vouching_date, 'office_id' => $office_id]));
    
    if($custom_financial_year['custom_financial_year_id'] != NULL && !$custom_financial_year['custom_financial_year_is_active']){
      $this->read_db->where(array('fk_custom_financial_year_id' => $custom_financial_year_id));
    }
    $this->read_db->where(array('fk_office_id' => $office_id, 'budget.fk_funder_id' => $funder_id,'budget_year' => $fy,'fk_budget_tag_id' => $mfr_budget_tag_id));
    $this->read_db->where_in('budget.fk_status_id', $max_budget_approval_ids);

    $budget_obj = $this->read_db->get('budget');
    $budget = [];
    if($budget_obj->num_rows() > 0){
      $budget = $budget_obj->row_array();
    }
    
    // log_message('error', json_encode($budget));

    return $budget;
  }


  function post_approval_action_event($event_payload){
    $item = $event_payload['item'];
    $item_id = $event_payload['post']['item_id'];
    $status_id = $event_payload['post']['next_status'];

    $max_approval_status_ids = $this->general_model->get_max_approval_status_id($item);
    // $mfr_max_approval_status_ids = $this->general_model->get_max_approval_status_id('financial_report');

    $this->read_db->select(array('fk_office_id','fk_custom_financial_year_id'));
    $this->read_db->where(array('budget_id' => $item_id));
    $budget_obj = $this->read_db->get('budget')->row();

    //log_message('error', json_encode(['status_id' => $status_id, 'max_approval_status_ids' => $max_approval_status_ids]));

    if(in_array($status_id, $max_approval_status_ids)){
      $this->deactivate_active_custom_financial_year($budget_obj->fk_office_id, $budget_obj->fk_custom_financial_year_id);
      
    }
  }
  
  function get_budget_fy_dates($budget_id){

    // Get the default fy start month number
    $this->load->model('month_model');
    $default_fy_start_month = $this->month_model->default_fy_start_month()->month_number;

    // log_message('error', json_encode($budget_id));

    // $sql="SELECT budget_year, custom_financial_year_start_month FROM budget b
    //       LEFT JOIN custom_financial_year cfy ON b.fk_custom_financial_year_id=cfy.custom_financial_year_id
    //       WHERE b.budget_id=".$budget_id;

    // $budget_obj = $this->read_db->query($sql)->row();
    
    // Get budget record and start month number if flexible FY
    $this->read_db->select(array('budget_year', 'custom_financial_year_start_month'));
    $this->read_db->where(array('budget_id' => $budget_id));
    $this->read_db->join('custom_financial_year','custom_financial_year.custom_financial_year_id=budget.fk_custom_financial_year_id','left');
    $budget_obj = $this->read_db->get('budget')->row();

    // Get the Budget FY
    $budget_fy = $budget_obj->budget_year;

    // Get the Budget start Month
    $budget_start_month = $budget_obj->custom_financial_year_start_month == null ? $default_fy_start_month : $budget_obj->custom_financial_year_start_month;

    // Get century prefix 
    $fourDigitYear = date('Y');
    $centuryPrefix = substr($fourDigitYear, 0, 2);

    // Compute FY start date
    $fy_start_year = $centuryPrefix.$budget_fy;

    if($budget_start_month != 1){
      $budget_fy--;
      $fy_start_year = $centuryPrefix.$budget_fy;
    }

    $fy_start_date = date('Y-m-d', mktime(0, 0, 0, $budget_start_month, 1, $fy_start_year));

    // Compute FY end date
    // Convert the given date to a timestamp
    $fy_start_date_timestamp = strtotime($fy_start_date);
    // Get the last day of the 12th month from the given date
    $fy_last_date = date('Y-m-t', strtotime('+11 months', $fy_start_date_timestamp));

    return ['fy_start_date' => $fy_start_date, 'fy_end_date' => $fy_last_date];

  }

}
