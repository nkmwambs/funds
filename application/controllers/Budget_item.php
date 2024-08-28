<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Budget_item extends MY_Controller
{

  function __construct(){
    parent::__construct();

    $this->load->model('budget_limit_model');
    $this->load->model('month_model');
    $this->load->model('budget_item_model');

  }

  function index(){}

  function page_name():String{
    //return the page name if the user has permissions otherwise error page of user not allowed access display
    parent::page_name();

    if((hash_id($this->id,'decode') == null && $this->action == 'multi_form_add') || !$this->has_permission){
      return 'error';
    }else{
      return $this->action;
    }
  }


  function get_office_year_pca_objectives($office_id, $budget_id){

    $objectives = [];
    $this->load->model('budget_model');
    $fy_date_range = $this->budget_model->get_budget_fy_dates($budget_id); 

    $this->read_db->distinct()->select(array('pca_strategy_objective_id','pca_strategy_objective_name'));
    $this->read_db->where(array('fk_office_id' => $office_id, 'pca_strategy_end_date' => $fy_date_range['fy_end_date']));
    $pca_strategy_obj = $this->read_db->get('pca_strategy');

    if($pca_strategy_obj->num_rows() > 0){
      $objective_id = array_column($pca_strategy_obj->result_array(),'pca_strategy_objective_id');
      $objective_name = array_column($pca_strategy_obj->result_array(),'pca_strategy_objective_name');

      $objectives = array_combine($objective_id, $objective_name);
    }

    return $objectives;
  }

  function ajax_get_objective_interventions(){
    $post = $this->input->post();
    $objective_id = $post['objective'];

    $pca_strategy_interventions = $this->get_objective_interventions($objective_id);

    echo json_encode($pca_strategy_interventions);
  }

  function get_objective_interventions($objective_id){

    $pca_strategy_interventions = [];

    $this->read_db->select(array('pca_strategy_intervention_id','pca_strategy_intervention_name'));
    $this->read_db->where(array('pca_strategy_objective_id' => $objective_id));
    $pca_strategy_interventions_obj = $this->read_db->get('pca_strategy');

    if($pca_strategy_interventions_obj->num_rows() > 0){
      $pca_strategy_interventions_array = $pca_strategy_interventions_obj->result_array();

      $pca_strategy_intervention_ids = array_column($pca_strategy_interventions_array,'pca_strategy_intervention_id');
      $pca_strategy_intervention_names = array_column($pca_strategy_interventions_array,'pca_strategy_intervention_name');

      $pca_strategy_interventions = array_combine($pca_strategy_intervention_ids, $pca_strategy_intervention_names);
    }

    return $pca_strategy_interventions;
  }

  function result($id = ''){
    if($this->action == 'multi_form_add' || $this->action == 'edit'){

    // if($this->action == 'multi_form_add'){

    // }
    
    $result = [];
    $expense_accounts = [];
    $project_allocations = [];
    $income_account = [];
    $budget_limit_amount = 0;
    
    $this->read_db->select(array('office_id','office_name','office_code','budget_year','office.fk_account_system_id account_system_id','budget_tag_level'));
    $this->read_db->join('budget','budget.fk_office_id=office.office_id');
    $this->read_db->join('budget_tag','budget_tag.budget_tag_id=budget.fk_budget_tag_id');

    if($this->action == 'multi_form_add'){
      $budget_id = hash_id($this->id,'decode');
      $this->read_db->where(array('budget_id'=>$budget_id));
    }
    else{
      $this->read_db->join('budget_item','budget_item.fk_budget_id=budget.budget_id');
      $this->read_db->where(array('budget_item_id'=>hash_id($this->id,'decode')));
    }

    $office = $this->read_db->get('office')->row();

    $budget_id=$this->budget_item_model->get_budget_id_by_budget_item_id(hash_id($this->id,'decode'));

    $pca_objectives = $this->get_office_year_pca_objectives($office->office_id, $budget_id);

    // log_message('error', json_encode($pca_objectives));
    // $months = month_order($office->office_id);
    
    $months = [];// month_order($office->office_id, $budget_id);

    // log_message('error', json_encode($months));

    // Get project allocations
    $budgeting_date = date('Y-m-d');
    $query_condition = "fk_office_id = ".$office->office_id." AND ((project_end_date >= '".$budgeting_date."' OR project_end_date LIKE '0000-00-00' OR project_end_date IS NULL) OR  project_allocation_extended_end_date >= '".$budgeting_date."')";
    $this->read_db->where($query_condition);
    $this->read_db->select(array('project_allocation_id','project_allocation_name','project_name'));
    $this->read_db->join('project','project.project_id=project_allocation.fk_project_id');
    $this->read_db->join('project_income_account','project_income_account.fk_project_id=project.project_id');
    $this->read_db->join('income_account','income_account.income_account_id=project_income_account.fk_income_account_id');
    $this->read_db->where(array('income_account_is_budgeted'=>1));
    $project_allocations_with_duplicates_obj = $this->read_db->get('project_allocation');

    $project_allocations = [];

    if($project_allocations_with_duplicates_obj->num_rows() > 0){
      
      $project_allocations_with_duplicates = $project_allocations_with_duplicates_obj->result_object();
      
      foreach($project_allocations_with_duplicates as  $project_allocation){
        $project_allocations[$project_allocation->project_allocation_id] = $project_allocation;
      }
    }

    $months_to_freeze = $this->month_model->past_months_in_fy($office->office_id, $office->budget_tag_level);

    if($this->action == 'edit'){

      // Get Income Account
      $this->read_db->where(array('budget_item_id'=>hash_id($this->id,'decode')));
      $this->read_db->join('expense_account','expense_account.fk_income_account_id=income_account.income_account_id');
      $this->read_db->join('budget_item','budget_item.fk_expense_account_id=expense_account.expense_account_id');
      $income_account = $this->read_db->get('income_account')->row();

      // Get Expense Accounts
      $this->read_db->select(array('expense_account_id','expense_account_name','expense_account_code'));
      $this->read_db->join('income_account','income_account.income_account_id=expense_account.fk_income_account_id');
      $this->read_db->join('account_system','account_system.account_system_id=income_account.fk_account_system_id');
      $this->read_db->where(array('fk_income_account_id'=>$income_account->income_account_id));
      $expense_accounts = $this->read_db->get_where('expense_account',
      array('fk_account_system_id'=>$office->account_system_id,'expense_account_is_active'=>1))->result_object();

      
      // Get Budget Items
      $this->read_db->join('expense_account','expense_account.expense_account_id=budget_item.fk_expense_account_id');
      $this->read_db->where(array('budget_item_id'=>hash_id($this->id,'decode')));
      $budget_item = $this->read_db->get('budget_item')->row();

      
      $budget_limit_amount = $this->budget_limit_model->budget_limit_remaining_amount($budget_item->fk_budget_id,$budget_item->fk_income_account_id);

      $this->read_db->select(array(
        'budget_item_detail_id',
        'month_id','month_number',
        'budget_item_detail_amount',
        'budget_item_id',
        'fk_budget_id as budget_id',
        'budget_item_total_cost',
        'expense_account_id',
        'budget_item_description',
        'budget_item_quantity',
        'budget_item_unit_cost',
        'budget_item_often',
        'budget_item_marked_for_review',
        'budget_item_source_id',
        'budget_item_revisions',
        'fk_project_allocation_id',
        'expense_account_id',
        'expense_account_name',
        'expense_account_code',
        'budget_item.fk_status_id as status_id',
        'budget_item_objective as objective'
      ));

      $this->read_db->join('budget_item','budget_item.budget_item_id=budget_item_detail.fk_budget_item_id');
      $this->read_db->join('expense_account','expense_account.expense_account_id=budget_item.fk_expense_account_id');
      $this->read_db->join('month','month.month_id=budget_item_detail.fk_month_id');
      $this->read_db->where(array('budget_item_id'=>hash_id($this->id,'decode')));
      $budget_item_details = $this->read_db->get('budget_item_detail')->result_array();
      
      $result['budget_item_details'] = [];

      foreach($budget_item_details as $budget_item_detail){
        $budget_item_detail['objective'] = json_decode($budget_item_detail['objective']);
        $result['budget_item_details'][$budget_item_detail['month_number']] = $budget_item_detail;
      }

      $result['current_expense_account_id'] = $income_account->fk_expense_account_id;
      

      $result['interventions'] = $budget_item_details[0]['objective']!=null ? $this->get_objective_interventions(json_decode($budget_item_details[0]['objective'])->pca_strategy_objective_id):[];

      $months = month_order($office->office_id, $budget_item->fk_budget_id);
      $months_to_freeze = $this->month_model->past_months_in_fy($office->office_id, $office->budget_tag_level, true);
    }else{
      $months = month_order($office->office_id, $budget_id);
    }

    $result['project_allocations'] = $project_allocations; 
    $result['expense_accounts'] = $expense_accounts;
    $result['months'] = $months;
    $result['office'] = $office;
    $result['budget_limit_amount'] = $budget_limit_amount;
    $result['pca_objectives'] = $pca_objectives;
    $result['months_to_freeze'] = $months_to_freeze;

    //Added by Onduso to resolve the bug of freezing all months when the FY is beginning and during editing
    // $result['count_of_fys_occurence_and_pick_last_value'] = $this->month_model->count_fys_occurances($office->office_id);

    return $result;
    }else{
      return parent::result($id);
    }
  }

  // public function compute_budget_limit($user_with_ajax=0, $id=0){

  //   // Get Budget Items
  //   $this->read_db->join('expense_account','expense_account.expense_account_id=budget_item.fk_expense_account_id');
  //   $this->read_db->where(array('budget_item_id'=>hash_id($this->id,'decode')));
  //   $budget_item = $this->read_db->get('budget_item')->row();

  //   $this->read_db->join('expense_account','expense_account.expense_account_id=budget_item.fk_expense_account_id');

  //   if($id!=0){
  //     $this->read_db->where(array('budget_item_id'=>$id));
  //   }else{
  //     $this->read_db->where(array('budget_item_id'=>hash_id($this->id,'decode')));
  //   }
  //   $budget_item = $this->read_db->get('budget_item')->row();
  //   $budget_limit_amount = $this->budget_limit_model->budget_limit_remaining_amount($budget_item->fk_budget_id,$budget_item->fk_income_account_id);

    
  //   if($user_with_ajax==1){
      
  //     echo $budget_limit_amount;
  //   }else{
  //     return $budget_limit_amount;
  //   }
   
  // }

 
  function get_budget_limit_remaining_amount($budget_id,$expense_account_id){
    
    $this->read_db->where(array('expense_account_id'=>$expense_account_id));
    $income_account_id = $this->read_db->get('expense_account')->row()->fk_income_account_id;
    // log_message('error', json_encode(['budget_id' => $budget_id, 'expense_account_id' => $expense_account_id]));
    echo $this->budget_limit_model->budget_limit_remaining_amount($budget_id,$income_account_id);
  }

  /**
   * get_budget_item_by_id
   * 
   * Get a budget item by its item Id
   */
  function get_budget_item_by_id($budget_item_id){
    $budget_item = [];

    $this->read_db->select(array('budget_item.fk_status_id as budget_item_status_id','fk_expense_account_id','budget_item_quantity','budget_item_unit_cost','budget_item_often','budget_item_total_cost','budget_item_description'));
    $this->read_db->where(array('budget_item_id' => $budget_item_id));
    $budget_item_obj = $this->read_db->get('budget_item');

    if($budget_item_obj->num_rows() > 0){
      $budget_item = $budget_item_obj->row_array();
    }

    return $budget_item;
  }

  function last_qtr_months_to_be_reviewed($budget_item_id, $source_budget_item_id, $budget_item_marked_for_review, $month_spread){

    $last_qtr_months_to_be_reviewed = [];
    $current_budget = [];
    $past_budget = [];

    $this->read_db->select(array('fk_office_id as office_id','budget_tag_level','fk_custom_financial_year_id'));
    $this->read_db->where(array('budget_item_id' => $source_budget_item_id));
    $this->read_db->join('budget_tag','budget_tag.budget_tag_id=budget.fk_budget_tag_id');
    $this->read_db->join('budget_item','budget_item.fk_budget_id=budget.budget_id');
    $past_budget_obj = $this->read_db->get('budget');

    $this->read_db->select(array('fk_office_id as office_id','budget_tag_level', 'fk_custom_financial_year_id'));
    $this->read_db->where(array('budget_item_id' => $budget_item_id));
    $this->read_db->join('budget_tag','budget_tag.budget_tag_id=budget.fk_budget_tag_id');
    $this->read_db->join('budget_item','budget_item.fk_budget_id=budget.budget_id');
    $current_budget_obj = $this->read_db->get('budget');

    if($current_budget_obj->num_rows() > 0 && $past_budget_obj->num_rows() > 0){
      $current_budget = $current_budget_obj->row_array();
      $past_budget = $past_budget_obj->row_array();

      $this->load->model('month_model');
      $past_months_in_fy = $this->month_model->past_months_in_fy($current_budget['office_id'], $current_budget['budget_tag_level']);

      // Check if the custom fy is the same for both the previous and current budget ?????? If not $last_qtr_months_to_be_reviewed is empty
      $pastAndCurrentBudgetHasSameFYSetting = $past_budget['fk_custom_financial_year_id'] == $current_budget['fk_custom_financial_year_id'] ? true : false;

      // Check if these months have registered any change ???? If not $last_qtr_months_to_be_reviewed is empty
      $last_qtr_months_to_be_reviewed = $budget_item_marked_for_review ? array_slice($past_months_in_fy, -3) : [];

      if($budget_item_marked_for_review && $pastAndCurrentBudgetHasSameFYSetting){
        $last_qtr_months_to_be_reviewed = array_slice($past_months_in_fy, -3);

        // log_message('error', json_encode([$budget_item_id, $source_budget_item_id, $last_qtr_months_to_be_reviewed]));

        $past_months_has_changes = $this->past_months_has_changes($source_budget_item_id, $last_qtr_months_to_be_reviewed, $month_spread);

        if(!$past_months_has_changes){
          $last_qtr_months_to_be_reviewed = [];
        }
      }
    }

    return $last_qtr_months_to_be_reviewed;
  }

  function past_months_has_changes($source_budget_item_id, $last_qtr_months_to_be_reviewed, $month_spread){

    $past_months_has_changes = false;
    $sum_past = 0;
    $sum_current = 0;

    $this->read_db->select(array('budget_item_detail_amount'));
    $this->read_db->where_in('fk_month_id', $last_qtr_months_to_be_reviewed);
    $this->read_db->where(array('fk_budget_item_id' => $source_budget_item_id));
    $current_sum_obj = $this->read_db->get('budget_item_detail');

    if($current_sum_obj->num_rows() > 0){
      $details = $current_sum_obj->result_array();      
      foreach($details as $detail){
        $sum_past += $detail['budget_item_detail_amount'];
      }

    }

    foreach($month_spread as $month_id => $amount){
      if(in_array($month_id, $last_qtr_months_to_be_reviewed)){
        $sum_current += $amount;
      }
    }

    if($sum_past != $sum_current){
      $past_months_has_changes = true;
    }

    return $past_months_has_changes;
  }

  function upsert_budget_item_detail($budget_item_id, $month_id, $month_amount){
     // Check if the budget item detail exists
     $cond = array('fk_budget_item_id'=>$budget_item_id,'fk_month_id'=>$month_id);

     $this->read_db->where($cond);
     $budget_item_detail_obj = $this->read_db->get('budget_item_detail');

     if($budget_item_detail_obj->num_rows() > 0){
       $body['budget_item_detail_amount'] = $month_amount;
       $this->write_db->where($cond);
       $this->write_db->update('budget_item_detail',$body);
     }elseif($month_amount > 0){
       $track = $this->grants_model->generate_item_track_number_and_name('budget_item_detail');

       $data_insert['budget_item_detail_track_number'] = $track['budget_item_detail_track_number'];
       $data_insert['budget_item_detail_name'] = $track['budget_item_detail_name'];
       $data_insert['fk_budget_item_id'] = $budget_item_id;
       $data_insert['fk_month_id'] = $month_id;
       $data_insert['fk_status_id'] = $this->grants_model->initial_item_status('budget_item_detail');
       $data_insert['budget_item_detail_amount'] = $month_amount;
       $data_insert['budget_item_detail_created_date'] = date('Y-m-d');
       $data_insert['budget_item_detail_created_by'] = $this->session->user_id;
       $data_insert['budget_item_detail_last_modified_by'] = $this->session->user_id;

       $this->write_db->insert('budget_item_detail', $data_insert);
     }
  }

  function is_new_budget_item_status ($budget_item_status_id, $budget_item_marked_for_review){
    
    $is_new_budget_item_status = false;

    $initial_item_status = $this->grants_model->initial_item_status('budget_item');

    if(!$budget_item_marked_for_review && $initial_item_status == $budget_item_status_id){
      $is_new_budget_item_status = true;
    }

    return $is_new_budget_item_status;
  }
  function update_budget_item($budget_item_id){

    $post = $this->input->post();

    $source_budget_item_id = $post['source_budget_item_id'];    
    $budget_item_marked_for_review = $post['budget_item_marked_for_review'];


    // last_qtr_months_to_be_reviewed gives the months of the last qtr if changes were made in this period during the budget review
    $last_qtr_months_to_be_reviewed = $this->last_qtr_months_to_be_reviewed($budget_item_id, $source_budget_item_id, $budget_item_marked_for_review, $post['fk_month_id']);

    $this->write_db->trans_start();

    $header = [];

    if(isset($this->session->system_settings['use_pca_objectives']) && $this->session->system_settings['use_pca_objectives']){
      $strategy = [];

      $this->read_db->select(array('pca_strategy_objective_id','pca_strategy_objective_name','pca_strategy_intervention_id','pca_strategy_intervention_name'));
      $this->read_db->where(array('pca_strategy_intervention_id' => $post['pca_intervention']));
      $strategy_obj = $this->read_db->get('pca_strategy');

      if($strategy_obj->num_rows() > 0){
        $strategy = $strategy_obj->row_array();
      }

      if(!empty($strategy)){
        $objective_array = [
          'pca_strategy_objective_id' => $post['pca_objective'], 
          'pca_strategy_objective_name' => $strategy['pca_strategy_objective_name'], 
          'pca_strategy_intervention_id' => $post['pca_intervention'], 
          'pca_strategy_intervention_name' => $strategy['pca_strategy_intervention_name']];
    
        $header['budget_item_objective'] = json_encode($objective_array);
      }

    }

    // Update budget item record
    /**
     * {"budget_item_description":"Secondary School fees for 2020",
     * "fk_expense_account_id":"1",
     * "fk_month_id":{"7":["2500000.00"],"8":["0.00"],"9":["0.00"],
     * "10":["0.00"],"11":["0.00"],"12":["0.00"],"1":["2000000.00"],"2":["0.00"],
     * "3":["0.00"],"4":["0.00"],"5":["0.00"],"6":["0.00"]},"budget_item_total_cost":"4500000",
     * "fk_budget_id":"1"}
     */

    $current_budget_item = $this->get_budget_item_by_id($budget_item_id);
    
    $header['fk_expense_account_id'] = $post['fk_expense_account_id'];
    $header['budget_item_quantity'] = $post['budget_item_quantity'];
    $header['budget_item_unit_cost'] = $post['budget_item_unit_cost'];
    $header['budget_item_often'] = $post['budget_item_often'];

    $header['budget_item_total_cost'] = $post['budget_item_total_cost'];
    $header['budget_item_description'] = $post['budget_item_description'];

    $this->grants_model->create_change_history($header, $current_budget_item);

    $is_new_budget_item_status = $this->is_new_budget_item_status($current_budget_item['budget_item_status_id'], $budget_item_marked_for_review);

    if(!$is_new_budget_item_status){
      $this->create_revisions($budget_item_id, $post);
    }

    if(!empty($last_qtr_months_to_be_reviewed) && $this->config->item('review_last_quarter_after_mark_for_review')){
      $this->create_revisions($source_budget_item_id, $post);
    }

    $this->write_db->where(array('budget_item_id'=>$budget_item_id));
    $this->write_db->update('budget_item',$header);

    // Update budget item detail for current review
    
    foreach($post['fk_month_id'] as $month_id => $month_amount){
      $this->upsert_budget_item_detail($budget_item_id, $month_id, $month_amount);
    }

    // Update budget item detail for recent past qtr review

    if(!empty($last_qtr_months_to_be_reviewed) && $this->config->item('review_last_quarter_after_mark_for_review')){

      $past_header['budget_item_quantity'] = $post['budget_item_quantity'];
      $past_header['budget_item_unit_cost'] = $post['budget_item_unit_cost'];
      $past_header['budget_item_often'] = $post['budget_item_often'];

      $this->write_db->where(array('budget_item_id'=>$source_budget_item_id));
      $this->write_db->update('budget_item',$past_header);

      foreach($post['fk_month_id'] as $month_id => $month_amount){
        $this->upsert_budget_item_detail($source_budget_item_id, $month_id, $month_amount);
      }

    }

    // $this->create_revisions($budget_item_id, $original_data, $new_data);

    $this->write_db->trans_complete();

    if ($this->write_db->trans_status() === FALSE)
    {
      echo "Budget Item Update failed";
    }else{
      echo "Budget Item Updated successfully";
    }
  }

  function create_revisions($budget_item_id, $new_data){
    
    $update_data = [];
    $old_data = [];

    // Old data
    $this->read_db->select(array(
      'budget_item_id',
      'budget_item_total_cost',
      'fk_expense_account_id',
      'budget_item_description',
      'budget_item_quantity',
      'budget_item_unit_cost',
      'budget_item_often',
      'budget_item_marked_for_review',
      'budget_item.fk_status_id as status_id',
      'budget_item_source_id',
      'fk_project_allocation_id',
      'fk_month_id',
      'budget_item_detail_amount',
      'fk_budget_id',
      'budget_item_source_id',
      'budget_item_revisions'
    ));

    $this->read_db->where(array('budget_item_id' => $budget_item_id));
    $this->read_db->join('budget_item_detail','budget_item_detail.fk_budget_item_id=budget_item.budget_item_id');
    $budget_item_obj = $this->read_db->get('budget_item');

    if($budget_item_obj->num_rows() > 0){
      $old_data_array = $budget_item_obj->result_array();

      foreach($old_data_array as $row){ 
        $old_data[$row['budget_item_id']]['budget_item_revisions'] = $row['budget_item_revisions'];
        $old_data[$row['budget_item_id']]['budget_item_marked_for_review'] = $row['budget_item_marked_for_review'];
        $old_data[$row['budget_item_id']]['budget_item_source_id'] = $row['budget_item_source_id'];
        $old_data[$row['budget_item_id']]['fk_budget_id'] = $row['fk_budget_id'];
        $old_data[$row['budget_item_id']]['fk_expense_account_id'] = $row['fk_expense_account_id'];
        $old_data[$row['budget_item_id']]['budget_item_description'] = $row['budget_item_description'];
        $old_data[$row['budget_item_id']]['budget_item_quantity'] = $row['budget_item_quantity'];
        $old_data[$row['budget_item_id']]['budget_item_unit_cost'] = $row['budget_item_unit_cost'];
        $old_data[$row['budget_item_id']]['budget_item_total_cost'] = $row['budget_item_total_cost'];
        $old_data[$row['budget_item_id']]['budget_item_often'] = $row['budget_item_often'];
        $old_data[$row['budget_item_id']]['fk_month_id'][$row['fk_month_id']] = $row['budget_item_detail_amount'];
      }
    }

    $revision_data = $this->prepare_revision_data($old_data[$budget_item_id], $new_data);

    $this->write_db->where(array('budget_item_id' => $budget_item_id));
    $update_data['budget_item_revisions'] = json_encode($revision_data);  
    $this->write_db->update('budget_item', $update_data);
  }

  function prepare_revision_data($original_data, $new_data){
    $revision_data = [];
    $revision_number = 1;
    $budget_item_revisions = [];

    //Check if there is a existing revisions for the budget item
    if($original_data['budget_item_revisions'] != NULL && $original_data['budget_item_revisions'] != "" && $original_data['budget_item_revisions'] != '[]'){
      $budget_item_revisions = json_decode($original_data['budget_item_revisions'], true);
      $revision_numbers = array_column($budget_item_revisions, 'revision_number');
      sort($revision_numbers);
      $last_revision_number = end($revision_numbers);

      $revision_number = $last_revision_number + 1;
    }

    $build_new_revisions = [];
    $original = $this->revision_data($original_data); // Preset original data for new unlocked original data

    // Remove the last unlocked revision - There can only be one unlocked revision per budget item object
    if(!empty($budget_item_revisions)){
      foreach($budget_item_revisions as $budget_item_revision){
        if(array_key_exists('locked', $budget_item_revision) && $budget_item_revision['locked'] == false){
          $revision_number = $budget_item_revision['revision_number']; // Prevent replacing revision number when updating unlocked revision
          $original = $budget_item_revision['data']['original']; // Prevent replacing original data in unlocked revision
          continue;
        }

        array_push($build_new_revisions, (object)$budget_item_revision);
      }
    }


    $revision_data['revision_number'] = $revision_number;
    $revision_data['revision_date'] = date('Y-m-d h:i:s');
    $revision_data['locked'] = false;

    // $original = $this->revision_data($original_data);
    $revised = $this->revision_data($new_data);

    $revision_data['data']['original'] = $original;
    $revision_data['data']['revised']  = $revised;

    $month_spread_different = $this->areArraysDifferent($original['month_spread'], $revised['month_spread']);

    // Prevent updating revisions if no change in spread was made. Change in description, unit cost, quantity and often if not affecting
    // spread will not trigger revision update.
 
    if($month_spread_different){
        array_push($build_new_revisions, $revision_data);
    }
    
    return $build_new_revisions;

  }

  function array_fill_keys(&$array){
    if(sizeof($array) != 12){
      for($i = 1; $i < 13; $i++){
        if(!array_key_exists($i, $array)){
          $array[$i] = 0;
        }
      }
    }

    return $array;
  }

  function areArraysDifferent($array1, $array2) {
    
    $this->array_fill_keys($array1);
    $this->array_fill_keys($array2);

    if (count($array1) != count($array2)) {
        return true; // If arrays have different lengths, they are different.
    }

    foreach ($array1 as $key => $value) {
        if ($value !== $array2[$key]) {
            return true; // If any element is different, the arrays are different.
        }
    }

    return false; // If the arrays have the same elements, they are not different.
}

  function revision_data($data){
    $revision_data['budget_item_description'] = $data["budget_item_description"];
    $revision_data['budget_item_quantity'] = $data["budget_item_quantity"];
    $revision_data['budget_item_unit_cost'] = $data["budget_item_unit_cost"];
    $revision_data['budget_item_often'] = $data["budget_item_often"];
    $revision_data['budget_item_total_cost'] = $data["budget_item_total_cost"];

    $amounts = [];
    foreach($data['fk_month_id'] as $month_id => $amount){
      if($amount > 0){
        $amounts[$month_id] = $amount;
      }
    }

    $revision_data['month_spread'] = $amounts;

    return $revision_data;
  }

  function insert_budget_item(){
    
    $post = $this->input->post();

    $this->write_db->trans_start();

    if(isset($this->session->system_settings['use_pca_objectives']) && $this->session->system_settings['use_pca_objectives']){
      $strategy = [];

      $this->read_db->select(array('pca_strategy_objective_id','pca_strategy_objective_name','pca_strategy_intervention_id','pca_strategy_intervention_name'));
      $this->read_db->where(array('pca_strategy_intervention_id' => $post['pca_intervention']));
      $strategy_obj = $this->read_db->get('pca_strategy');

      if($strategy_obj->num_rows() > 0){
        $strategy = $strategy_obj->row_array();
      }

      if(!empty($strategy)){
        $objective_array = [
          'pca_strategy_objective_id' => $post['pca_objective'], 
          'pca_strategy_objective_name' => $strategy['pca_strategy_objective_name'], 
          'pca_strategy_intervention_id' => $post['pca_intervention'], 
          'pca_strategy_intervention_name' => $strategy['pca_strategy_intervention_name']];
    
        $header['budget_item_objective'] = json_encode($objective_array);
      }

    }


    $header['budget_item_track_number'] = $this->grants_model->generate_item_track_number_and_name('budget_item')['budget_item_track_number'];
    $header['budget_item_name'] = $this->grants_model->generate_item_track_number_and_name('budget_item')['budget_item_name'];
    $header['fk_budget_id'] = $post['fk_budget_id'];
    $header['budget_item_total_cost'] = $post['budget_item_total_cost'];
    $header['fk_expense_account_id'] = $post['fk_expense_account_id'];
    $header['budget_item_description'] = $post['budget_item_description'];
    $header['fk_project_allocation_id'] = $post['fk_project_allocation_id'];

    $header['budget_item_quantity'] = $post['budget_item_quantity'];
    $header['budget_item_unit_cost'] = $post['budget_item_unit_cost'];
    $header['budget_item_often'] = $post['budget_item_often'];

    $header['budget_item_created_by'] = $this->session->user_id;
    $header['budget_item_last_modified_by'] = $this->session->user_id;
    $header['budget_item_created_date'] = date('Y-m-d');

    $header['fk_approval_id'] = $this->grants_model->insert_approval_record('budget_item');
    $header['fk_status_id'] = $this->grants_model->initial_item_status('budget_item');

    $this->write_db->insert('budget_item',$header);
    $header_id = $this->write_db->insert_id();
    
    $row = [];
    
    foreach($post['fk_month_id'] as $month_id => $month_amount){

      if($month_amount > 0){
        $body['budget_item_detail_track_number'] = $this->grants_model->generate_item_track_number_and_name('budget_item_detail')['budget_item_detail_track_number'];
        $body['budget_item_detail_name'] = $this->grants_model->generate_item_track_number_and_name('budget_item_detail')['budget_item_detail_name'];
        $body['fk_budget_item_id'] = $header_id;

        $body['budget_item_detail_amount'] = $month_amount;
        $body['fk_month_id'] = $month_id;

        $body['budget_item_detail_created_by'] = $this->session->user_id;
        $body['budget_item_detail_last_modified_by'] = $this->session->user_id;
        $body['budget_item_detail_created_date'] = date('Y-m-d');

        $body['fk_approval_id'] = $this->grants_model->insert_approval_record('budget_item_detail');
        $body['fk_status_id'] = $this->grants_model->initial_item_status('budget_item_detail');
        
        $row[] = $body;
      }
    }

    if(sizeof($row) > 0){
      $this->write_db->insert_batch('budget_item_detail',$row);
    }
    
    
    $this->write_db->trans_complete();

    if ($this->write_db->trans_status() === FALSE)
    {
      //echo json_encode($row);
      echo "Budget Item posting failed";
    }else{
      //echo json_encode($row);
      echo "Budget Item posted successfully";
    }

  }

  function project_budgetable_expense_accounts($project_allocation_id){
    
    $this->read_db->join('income_account','income_account.income_account_id=expense_account.fk_income_account_id');
    $this->read_db->join('project_income_account','project_income_account.fk_income_account_id=income_account.income_account_id');
    $this->read_db->join('project','project.project_id=project_income_account.fk_project_id');
    $this->read_db->join('project_allocation','project_allocation.fk_project_id=project.project_id');
    $this->read_db->where(array('project_allocation_id'=>$project_allocation_id,'expense_account_is_budgeted'=>1, 'expense_account_is_active' => 1));
    $this->read_db->select(array('expense_account_id','expense_account_name'));
    $accounts = $this->read_db->get('expense_account')->result_array();

    echo json_encode($accounts);
  }

  function mark_for_review($mark, $budget_item_id){  
    $alt_mark = 0;

    if($mark == 0){
      $alt_mark = 1;
    }

    $this->write_db->where(array('budget_item_id' => $budget_item_id));
    $update_data['budget_item_marked_for_review'] = $alt_mark;
    $this->write_db->update('budget_item',$update_data);

    if($this->write_db->affected_rows() > 0){
      $new_data = ['budget_item_id' => $budget_item_id, 'marked_for_review' => $alt_mark];
      $old_data = ['budget_item_id' => $budget_item_id, 'marked_for_review' => $mark];
      parent::create_change_history($new_data, $old_data, 'budget_item');
      echo  $alt_mark;
    }else{
      echo $mark;
    }
    
  }

  function get_budget_item_notes_history($budget_item_id){
    $this->load->model('message_model');
    echo $this->message_model->notes_history($budget_item_id);
  }

  function post_budget_item_note(){
    $post = $this->input->post();

    extract($post);

    $this->load->model('message_model');
    
    $response = 0;
    
    if($post['update']['message_detail_id'] == ""){
      $this->message_model->post_new_message('budget_item',$budget_item_id,$note);
    }else{
      $this->message_model->update_message($post['update']['message_detail_id'],$note);
    }

    echo $response;

  }

  static function get_menu_list(){}
}
