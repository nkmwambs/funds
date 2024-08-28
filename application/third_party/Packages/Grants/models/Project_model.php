<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Project_model extends MY_Model 
{
  public $table = 'project';

  function __construct(){
    parent::__construct();

  }

  function delete($id = null){

  }

    function index(){}


    function lookup_tables(){
      return array('funding_status','funder');
    }

    function detail_tables(){
      return array('project_allocation','project_income_account','project_request_type');
    }
    

    public function master_table_visible_columns(){}

    public function master_table_hidden_columns(){}

    public function list_table_visible_columns(){}

    public function list_table_hidden_columns(){}

    public function detail_list_table_visible_columns(){
      //return ['project_track_number','project_name','project_code','project_start_date','project_end_date','funder_name','project_cost','funding_status_name'];
    }

    function action_before_edit($post_array){

      $post_array['header']['project_end_date'] = $post_array['header']['project_end_date'] == '' ? NULL : $post_array['header']['project_end_date'];
      $post_array['header']['project_cost'] =  $post_array['header']['project_cost'] == '' ? 0 : $post_array['header']['project_cost'];


      return $post_array;
    }

  //   /**
  //  * get_project_expenses_account
  //  * 
  //  * This return list of account 
  //  * 
  //  * @return Array - Array
  //  * @author Onduso
  //  */
  // function get_project_expenses_account($expense_income_id, $office_id)
  // {


   
  // }

    function action_before_insert($post_array){

      $funder_id = $post_array['header']['fk_funder_id'];
        
      // $project = $this->grants_model->overwrite_field_value_on_post(
      //     $post_array,
      //     'project',
      //     'project_is_default',
      //     1,
      //     0,
      //     [
      //         'fk_funder_id'=>$funder_id,
      //         'project_is_default'=>1
      //     ]
      // );

      $post_array['header']['project_end_date'] = $post_array['header']['project_end_date'] == '' ? NULL : $post_array['header']['project_end_date'];
      $post_array['header']['project_cost'] =  $post_array['header']['project_cost'] == '' ? 0 : $post_array['header']['project_cost'];
      $post_array['header']['project_is_default'] =  $post_array['header']['project_end_date'] != "" ? 0 :  $post_array['header']['project_is_default'];

      //This line is added by Onduso 28/7/2022
      $post_array['header']['project_code']=return_sanitized_code($post_array['header']['project_code']);
  
      return $post_array;
    }

    function action_after_insert($post_array, $approval_id, $header_id){
        
      // If the project is default, then allocate all active center offices
      // log_message('error', json_encode($post_array));
      if($post_array['project_is_default'] == 1){
        log_message('error', json_encode($post_array['project_is_default']));
        $this->allocate_all_centers($header_id);
      }

      return true;
  }

  private function allocate_all_centers($project_id){
    // Get all active centers

    // Note this query only works with a write_db operations although is a read query
    $this->write_db->select(array('fk_account_system_id'));
    $this->write_db->where(array('project_id' => $project_id));
    $this->write_db->join('funder',"funder.funder_id=project.fk_funder_id");
    $account_system_id = $this->write_db->get('project')->row()->fk_account_system_id;
    
    $this->read_db->select(array('office_id'));
    $this->read_db->where(array('fk_account_system_id' => $account_system_id, 
    "fk_context_definition_id" => 1, "office_is_active" => 1));
    $office_obj = $this->read_db->get('office');

    log_message('error', json_encode($office_obj->num_rows()));

    if($office_obj->num_rows() > 0){
      $office_ids = array_column($office_obj->result_array(),'office_id');

      $project_allocations = [];
      for($i = 0; $i < count($office_ids); $i++){
        $track = $this->grants_model->generate_item_track_number_and_name('project_allocation');
        $project_allocations[$i]['project_allocation_track_number'] = $track['project_allocation_track_number'];
        $project_allocations[$i]['project_allocation_name'] = $track['project_allocation_name'];
        $project_allocations[$i]['fk_project_id'] = $project_id;
        $project_allocations[$i]['fk_office_id'] = $office_ids[$i];
        $project_allocations[$i]['fk_status_id'] = $this->grants_model->initial_item_status('project_allocation');;
        $project_allocations[$i]['project_allocation_created_date'] = date('Y-m-d');
        $project_allocations[$i]['project_allocation_last_modified_date'] = date('Y-m-d H:i:s');
        $project_allocations[$i]['project_allocation_created_by'] = $this->session->user_id;
        $project_allocations[$i]['project_allocation_last_modified_by'] = $this->session->user_id;
      }

      if(!empty($project_allocations)){
        $this->write_db->insert_batch('project_allocation',$project_allocations);
      }
    }
  }

    /**
   * transaction_validate_duplicates_columns
   * 
   * This is an override method. It lists all fields that needs to be checked if duplicate value
   * is about to be posted in the database.
   * 
   * @return Array
   */
  function transaction_validate_duplicates_columns()
  {
    return ['project_code','fk_funder_id'];
  }


   

    public function detail_list_table_hidden_columns(){}

    public function single_form_add_visible_columns(){}

    public function single_form_add_hidden_columns(){}

    public function master_multi_form_add_visible_columns(){
      // return array('project_name','project_code','project_description','project_start_date','project_end_date',
      // 'project_cost','funding_status_name','funder_name');
    }

    public function detail_multi_form_add_visible_columns(){}

    public function master_multi_form_add_hidden_columns(){}

    public function detail_multi_form_add_hidden_columns(){}

    function detail_list(){}

    function master_view(){}

    public function list(){}

    public function view(){}

    function lookup_values_where($table = ''){
     return [
              'income_account'=>['income_account_is_donor_funded'=>1,'income_account_is_active'=>1]
            ];
   }

   function columns(){
     $columns = [
        'project_track_number',
        'project_name',
        'project_code',
        'project_start_date',
        'project_end_date',
        'project_created_date',
        'funder_name'
     ];

     return $columns;
   }

}
