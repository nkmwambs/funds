<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Project_allocation_model extends MY_Model 
{
  public $table = 'project_allocation'; // you MUST mention the table name
  public $is_multi_row = false;

  function __construct(){
    parent::__construct();
  }

  function delete($id = null){

  }

  function index(){}

  function action_before_insert($post_array){
    /**
     * {"header":{"fk_project_id":"16","fk_office_id":["9"],"project_allocation_is_active":"1"}}
     */

    $office_ids = $post_array['header']['fk_office_id'];
    $project_id = $post_array['header']['fk_project_id'];

    $project_name = $this->read_db->get_where('project',array('project_id'=>$project_id))->row()->project_name;

    if(is_array($office_ids) && !empty($office_ids)){
      
      $this->read_db->where_in('office_id',$office_ids);
      $office_records = $this->read_db->get('office')->result_array();

      $_office_ids = array_column($office_records,'office_id');
      $office_names = array_column($office_records,'office_name');

      $offices = array_combine($_office_ids,$office_names);


      /**
       * $offices = [
       *  [1=>'ABC'],
       *  [2=>'CDE']
       * ];
       */

      foreach($office_ids as $office_id){
        
        $post_array['header']['project_allocation_name'][$office_id] = $project_name.' '.get_phrase('allocation_for').' '.$offices[$office_id];
      }

    }elseif(!is_array($office_ids)){

      $this->read_db->where(array('office_id'=>$office_ids));
      $office_record = $this->read_db->get('office')->row()->office_name;

      //echo json_encode($offices);exit;
      $post_array['header']['project_allocation_name'] = $project_name.' '.get_phrase('allocation_for').' '.$office_record;
    }

    //echo json_encode($post_array);exit;
    return $post_array;
  }

  function action_after_insert($post_array,$approval_id,$header_id){

    $this->write_db->trans_start();

    $this->link_new_project_allocation_to_default_bank($post_array,$header_id);

    $office_id = $post_array['fk_office_id'];
    $office_group_id = $this->check_office_belongs_to_office_group($office_id);
    $office_group_lead_id = $this->get_office_group_lead_office_id($office_group_id);

    if($office_group_lead_id > 0){
      $this->link_project_allocation_to_leading_office_in_office_group($post_array,$office_group_lead_id,$header_id);
    }

    $this->write_db->trans_complete();

    if ($this->write_db->trans_status() === FALSE)
      {
        return false;
      }else{
        return true;
      }
    
  }

  function link_project_allocation_to_leading_office_in_office_group($post_array,$office_id,$project_allocation_id){

    $project_name = $this->read_db->get_where('project',array('project_id'=>$post_array['fk_project_id']))->row()->project_name;
    $office_name = $this->read_db->select('office_name')->get_where('office',array('office_id'=>$office_id))->row()->office_name;
    $project_allocation_name = $project_name.' '.get_phrase('allocation_for').' '.$office_name;

    // Check if the project allocation is present for the office group lead

    $count_project_allocations = $this->write_db->get_where('project_allocation',
    array('fk_office_id'=>$office_id,'fk_project_id'=>$post_array['fk_project_id']))->num_rows();

    if($count_project_allocations == 0){
     
      $lead_post_array = array_replace($post_array,['fk_office_id'=>$office_id,'project_allocation_name'=>$project_allocation_name]);
      $this->write_db->insert('project_allocation',$lead_post_array);
  
      $header_id = $this->write_db->insert_id();
  
      $this->link_new_project_allocation_to_default_bank($lead_post_array,$header_id);

    }

  }

  function check_office_belongs_to_office_group($office_id){
      
      $check_office_belongs_to_office_group = 0;

      $office_group_association_obj = $this->read_db->get_where('office_group_association',
      array("fk_office_id"=>$office_id));

      if($office_group_association_obj->num_rows() > 0){
        $check_office_belongs_to_office_group = $office_group_association_obj->row()->fk_office_group_id;
      }

      return $check_office_belongs_to_office_group;

  }

  function get_office_group_lead_office_id($office_group_id){

    $lead_office_id = 0;

    $office_group_association_is_lead_obj = $this->read_db->get_where('office_group_association',
    array('fk_office_group_id'=>$office_group_id,"office_group_association_is_lead"=>1));

    if($office_group_association_is_lead_obj->num_rows() > 0){
      $lead_office_id = $office_group_association_is_lead_obj->row()->fk_office_id;

    }

    return $lead_office_id;
  }

  function link_new_project_allocation_to_default_bank($post_array,$header_id){
      // Get the insert allocation account system id
      $this->write_db->join('project','project.fk_funder_id=funder.funder_id');
      $this->write_db->where(array('project_id'=>$post_array['fk_project_id']));
      $account_system_id = $this->write_db->get('funder')->row()->fk_account_system_id;

      // Get all active default bank accounts for an account system
      $this->write_db->join('bank','bank.bank_id=office_bank.fk_bank_id');
      $this->write_db->where(array('fk_account_system_id'=>$account_system_id,
      'fk_office_id'=>$post_array['fk_office_id']));
      
      if($this->config->item('link_new_project_allocations_only_to_default_bank_accounts')){
        $this->write_db->where(array('office_bank_is_default'=>1));
      }

      $account_system_office_banks = $this->write_db->get('office_bank')->result_array();

      //echo json_encode($account_system_id);exit;


      // Insert office_bank_project_allocation table
      foreach($account_system_office_banks as $account_system_office_bank){
        $office_bank_project_allocation_data['office_bank_project_allocation_name'] = $this->grants_model->generate_item_track_number_and_name('office_bank_project_allocation')['office_bank_project_allocation_name'];
        $office_bank_project_allocation_data['office_bank_project_allocation_track_number'] = $this->grants_model->generate_item_track_number_and_name('office_bank_project_allocation')['office_bank_project_allocation_track_number'];
        $office_bank_project_allocation_data['fk_office_bank_id'] = $account_system_office_bank['office_bank_id'];
        $office_bank_project_allocation_data['fk_project_allocation_id'] = $header_id;
        
        $office_bank_project_allocation_data_to_insert = $this->grants_model->merge_with_history_fields('office_bank_project_allocation',$office_bank_project_allocation_data,false);
        $this->write_db->insert('office_bank_project_allocation',$office_bank_project_allocation_data_to_insert);

      }
  }
  

  public function lookup_tables(){
    return array('office','project');
  }

  public function detach_detail_table(){
    return true;
  }

  public function detail_tables(){
    return ['office_bank_project_allocation'];
  }
    public function master_table_visible_columns(){}

    public function master_table_hidden_columns(){}

    public function list_table_visible_columns(){}

    public function list_table_hidden_columns(){}

    public function detail_list_table_visible_columns(){
      return [
        'project_allocation_track_number',
        'project_name',
        'office_name',
        'project_allocation_is_active',
        'project_allocation_amount',
        'project_allocation_extended_end_date',
        'project_allocation_created_date'
    ];
    }

    public function detail_list_table_hidden_columns(){}

    public function single_form_add_visible_columns(){
      return array('project_name','office_name');//project_allocation_amount
    }


    public function edit_visible_columns(){
      return [
        'project_name',
        'office_name',
        'project_allocation_is_active',
        //'project_allocation_amount',
        'project_allocation_extended_end_date'
      ];
    }

    public function single_form_add_hidden_columns(){}

    public function master_multi_form_add_visible_columns(){
     
    }

    public function detail_multi_form_add_visible_columns(){}

    public function master_multi_form_add_hidden_columns(){}

    public function detail_multi_form_add_hidden_columns(){}

    function detail_list(){}

    function master_view(){}

    public function list(){}

    public function view(){}
    
   public function transaction_validate_by_computation_flag($insert_array){
      
      $project_id = $insert_array['fk_project_id'];

      $project_cost = $this->read_db->select_sum('project_cost')->get_where('project',
      array('project_id'=>$project_id))->row()->project_cost;

      $sum_allocation = $this->read_db->select_sum('project_allocation_amount')->get_where('project_allocation',
      array('fk_project_id'=>$project_id))->row()->project_allocation_amount;

      if($project_cost < $sum_allocation){
        return VALIDATION_ERROR;
      }else{
        return VALIDATION_SUCCESS;
      }
   }

   public function transaction_validate_duplicates_columns(){
     return ['fk_office_id','fk_project_id'];
   }

   function multi_select_field(){
     return "office";
   }

   function lookup_values(){
    $lookup_values = parent::lookup_values();

    if(!$this->session->system_admin){
      $this->read_db->where(array('fk_account_system_id'=>$this->session->user_account_system_id));
      $this->read_db->join('funder','funder.funder_id=project.fk_funder_id');
      $lookup_values['project'] = $this->read_db->get_where('project')->result_array();

      $not_exist_string_condition = "AND fk_project_id = ".hash_id($this->id,'decode');
      $this->grants_model->get_unused_lookup_values($lookup_values,'office','project_allocation',$not_exist_string_condition);
    }else{
      $lookup_values['project'] = $this->read_db->get_where('project')->result_array();
    }

    return $lookup_values;
   }

   function columns(){
     $columns = [
      'project_allocation_track_number',
      'project_name',
      'office_name',
      'project_allocation_is_active',
      'project_allocation_extended_end_date',
      'project_allocation_created_date'
     ];

     return $columns;
   }

   function deactivate_default_allocation($office_id, $suspension_status){

      $this->read_db->select(array('project_allocation_id'));
      $this->read_db->where(array('fk_office_id' => $office_id, 'project_is_default' => 1));
      $this->read_db->join('project_allocation','project_allocation.fk_project_id=project.project_id');
      $default_project_allocation_ids_obj = $this->read_db->get('project');

      $default_project_allocation_ids = [];

      if($default_project_allocation_ids_obj->num_rows() > 0){
        $default_project_allocation_ids_raw = $default_project_allocation_ids_obj->result_array();

        $default_project_allocation_ids = array_column($default_project_allocation_ids_raw, 'project_allocation_id');

        $data['project_allocation_is_active'] = $suspension_status;
        $this->write_db->where_in('project_allocation_id', $default_project_allocation_ids);
        $this->write_db->update('project_allocation', $data);
      }

   }
   
}
