<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Office extends MY_Controller
{

  function __construct()
  {
    parent::__construct();
    //$this->load->library('office_library');
    $this->load->model('country_currency_model');
   
  }

  function index()
  {
  }

  function get_reporting_office_context($context_definition)
  {

    $reporting_context_definition_level = $context_definition->context_definition_level + 1;

    $reporting_context_definition = $this->read_db->get_where(
      'context_definition',
      array('context_definition_level' => $reporting_context_definition_level)
    )->row();

    return $reporting_context_definition;
  }

  public function edit_office(){
    
    $message_returned=-1;
   
   $post = $this->input->post()['header'];

   //echo json_encode($post['fk_user_id']);
   $this->write_db->trans_begin();

    $office['office_name'] = $post['office_name'];
    $office['office_description'] = $post['office_description'];
    $office['office_code'] = $post['office_code'];
    $office['fk_context_definition_id'] = $post['fk_context_definition_id'];
    
    $office['office_start_date'] = $post['office_start_date'];
    $office['fk_country_currency_id'] = $post['fk_country_currency_id'];

    $office['office_is_active'] = $post['office_is_active'];

    if($post['office_is_active']==0){
      $message_returned=$this->office_model->deactivate_or_activate_users_when_office_inactive($post['office_id'],$post['fk_context_definition_id'], []);


    }
    elseif(!empty($post['fk_user_id'])){
      //Update user table and context users table
      $message_returned=$this->office_model->deactivate_or_activate_users_when_office_inactive($post['office_id'],$post['fk_context_definition_id'],$post['fk_user_id']);
      
        //Context
      
    }
    $office['fk_account_system_id'] = $post['fk_account_system_id'];

    $office['fk_country_currency_id'] = $post['fk_country_currency_id'];

    $office['office_is_readonly'] = 1;
    //Modify this to 0 if the office==center
    if($post['fk_context_definition_id']==1){
      $office['office_is_readonly'] = 0;
    }
   
    $office_to_update = $this->grants_model->merge_with_history_fields($this->controller, $office, false, false);
    
    switch($post['fk_context_definition_id']){
      case 1:
        $office_column_name='context_center_name';
        $office_column_description='context_center_description';
        $office_column_fk_id='fk_context_cluster_id';
        $reporting_office='context_center';

        break;


      case 2:

        $office_column_name='context_cluster_name';
        $office_column_description='context_cluster_description';
        $office_column_fk_id='fk_context_cohort_id';
        $reporting_office='context_cluster';

        break;

      case 3:
        $office_column_name='context_cohort_name';
        $office_column_description='context_cohort_description';
        $office_column_fk_id='fk_context_country_id';
        $reporting_office='context_cohort';

        break;

      case 4:
        $office_column_name='context_country_name';
        $office_column_description='context_country_description';
        $office_column_fk_id='fk_context_region_id';
        $reporting_office='context_country';

        break;


    }

     //Update context
     //Update the office table and context_center/context_cluster / context_cohort/ context_country
     $this->write_db->where(array('office_id'=>$post['office_id']));
     $this->write_db->update('office', $office_to_update);

     //Update report tables
    $data_to_update[$office_column_name]='Context for office '.$post['office_name'];
    $data_to_update[$office_column_description]='Context for office '.$post['office_description']; 
    $data_to_update[$office_column_fk_id]=$post['office_context'];

    $context_update = $this->grants_model->merge_with_history_fields($reporting_office, $data_to_update, false, false);
    
    $this->write_db->where(array('fk_office_id'=>$post['office_id']));
    $this->write_db->update($reporting_office, $context_update);

    //$error_messages['office']=$this->write_db->error();

    if ($this->write_db->trans_status() == false && $message_returned==0) {
      

      $this->write_db->trans_rollback();

      $message='office_updated_not_updated';

      //alert_error_message($error_messages);
      
    } else {
      
      $this->write_db->trans_commit();

      $message='office_updated_successfully';

    }

   echo json_encode(get_phrase($message));


  }
 /**
   * Bulky update for FCPs to a cluster
   * This method updates fcps to a cluster in bulk
   * @return Array - array of a array
   * @Author :Livingstone Onduso
   * @Date: 08/21/2022
   */

  function mass_update_for_fcps(){

    $post=$this->input->post();

    $fcp_office_ids=$post['office_ids'];

    $cluster_office_id=$post['cluster_office_id'];

    $this->write_db->trans_begin();

    $data['fk_context_cluster_id']=$this->read_db->get_where('context_cluster', array('fk_office_id'=>$cluster_office_id))->row()->context_cluster_id;
    
    $this->write_db->where_in('fk_office_id',$fcp_office_ids);

    $this->write_db->update('context_center',$data);

    if ($this->write_db->trans_status() == false) {

      $this->write_db->trans_rollback();

      $message=0;
      
    } else {
      
      $this->write_db->trans_commit();

      $message=1;

    }

   echo json_encode($message);
  }

  function create_new_office()
  {

    $error_messages=[];

    $this->write_db->trans_begin();

    $post = $this->input->post()['header'];

    $office['office_name'] = $post['office_name'];
    $office['office_description'] = $post['office_description'];
    $office['office_code'] = $post['office_code'];
    $office['fk_context_definition_id'] = $post['fk_context_definition_id'];
    $office['office_start_date'] = $post['office_start_date'];
    $office['fk_country_currency_id'] = $post['fk_country_currency_id'];
    $office['office_is_active'] = $post['office_is_active'];
    $office['fk_account_system_id'] = $post['fk_account_system_id'];
    $office['fk_country_currency_id'] = $post['fk_country_currency_id'];

    $office['office_is_readonly'] = 1;
    //Modify this to 0 if the office==center
    if($post['fk_context_definition_id']==1){
      $office['office_is_readonly'] = 0;
    }
   

    $office_to_insert = $this->grants_model->merge_with_history_fields($this->controller, $office, false);

    $this->write_db->insert('office', $office_to_insert);

    $error_messages['office']=$this->write_db->error();

    $inserted_office_id = $this->write_db->insert_id();

    // Create an office context 
    $context_definition = $this->read_db->get_where(
      'context_definition',
      array('context_definition_id' => $post['fk_context_definition_id'])
    )->row();

    $context_definition_name = $context_definition->context_definition_name;

    $reporting_context_definition_name = $this->get_reporting_office_context($context_definition)->context_definition_name;

    $reporting_context_definition_table = 'context_' . $reporting_context_definition_name;

    $office_context['context_' . $context_definition_name . '_name'] = "Context for office " . $post['office_name'];
    $office_context['context_' . $context_definition_name . '_description'] = "Context for office " . $post['office_name'];
    $office_context['fk_' . $reporting_context_definition_table . '_id'] = $post['office_context'];
    $office_context['fk_context_definition_id'] = $post['fk_context_definition_id'];
    $office_context['fk_office_id'] = $inserted_office_id;

    //echo json_encode($office_context);
    $office_context_to_insert = $this->grants_model->merge_with_history_fields('context_' . $context_definition_name, $office_context, false);

    $this->write_db->insert('context_' . $context_definition_name, $office_context_to_insert);

    $error_messages['context']=$this->write_db->error();

    // Create office System Opening Balance Record
    $system_opening_balance['system_opening_balance_name'] = 'Financial Opening Balance for ' . $post['office_name'];
    $system_opening_balance['fk_office_id'] = $inserted_office_id;
    $system_opening_balance['month'] = $post['office_start_date'];

    $system_opening_balance_to_insert = $this->grants_model->merge_with_history_fields('system_opening_balance', $system_opening_balance, false);

    $this->write_db->insert('system_opening_balance', $system_opening_balance_to_insert);

    $error_messages['system_openning']=$this->write_db->error();

    if ($this->write_db->trans_status() == false) {
      

      //$error_message_explode=explode(':',$error['message']);

      $this->write_db->trans_rollback();

     // echo "Office insert failed " .$error_message_explode[0]; 

     alert_error_message($error_messages);
     
     //echo json_encode( array_column($error_messages,'message'));
      
    } else {
      
      $this->write_db->trans_commit();

      // Append office to user session after creating an office to allow user see the office immediately the create it without the need to log out
      $hierarchy_offices = $this->session->hierarchy_offices;
      array_push($hierarchy_offices, ['office_name' => $post['office_name'], 'office_id' => $inserted_office_id, 'office_is_active' => 1]);
      $this->session->set_userdata(
        'hierarchy_offices',
        $hierarchy_offices
      );
      
      echo "Office inserted successfully ";

    }

  }

  function create_error_message($message, $key){

    $explode_msq=explode(':',$message)[0];

    if($explode_msq!=''){
      echo '=>'.$explode_msq."\n";
    }
    
  }

  function get_ajax_responses_for_context_definition()
  {

    $post = $this->input->post();

    /** Remove this */
    $context_definition = $this->read_db->get_where(
      'context_definition',
      array('context_definition_id' => $post['context_definition_id'])
    )->row();

    //$context_definition_name = $context_definition->row()->context_definition_name;

    $reporting_context_definition_level = 6;

    if ($context_definition->context_definition_level < 6) {
      $reporting_context_definition_level = $context_definition->context_definition_level + 1;
    }


    $reporting_context_definition = $this->read_db->get_where(
      'context_definition',
      array('context_definition_level' => $reporting_context_definition_level)
    )->row();

    /**Remove the above and replace with below. Unknown error occurs */

    //$reporting_context_definition = $this->get_reporting_office_context($post['context_definition_id']);

    $reporting_context_definition_table = 'context_' . $reporting_context_definition->context_definition_name;

    $this->read_db->select(array($reporting_context_definition_table . '_id', $reporting_context_definition_table . '_name'));
    $this->read_db->join('office', 'office.office_id=' . $reporting_context_definition_table . '.fk_office_id');

    if (!$this->session->system_admin) {
      $this->read_db->join('account_system', 'account_system.account_system_id=office.fk_account_system_id');
      $this->read_db->where(array('account_system_code' => $this->session->user_account_system));
    }

    $result = $this->read_db->get_where($reporting_context_definition_table, array('office_is_active' => 1))->result_array();

    $office_contexts_combine = combine_name_with_ids($result, $reporting_context_definition_table . '_id', $reporting_context_definition_table . '_name');

    $office_context = $this->grants->select_field('office_context', $office_contexts_combine);

    echo json_encode(array('office_context' => $office_context));
    // echo json_encode($office_contexts_combine );
  }

  function result($id = '')
  {

    if ($this->action == 'single_form_add') {
      $result['country_currency_id']=$this->country_currency_model->get_country_currency_id();
    }elseif($this->action == 'edit'){

      $office_id=hash_id($this->id,'decode');

      $result['office_record_to_edit']=$this->office_model->get_edit_office_records($office_id);

      $result['defination_contexts']=$this->office_model->retrieve_ids_and_names_records(['context_definition_id','context_definition_name'],'context_definition');

      $result['account_systems']=$this->office_model->retrieve_ids_and_names_records(['account_system_id','account_system_name'],'account_system');

      $result['country_currency']=$this->office_model->retrieve_ids_and_names_records(['country_currency_id','country_currency_name'],'country_currency');

      //$result['office_context_users']=$this->office_model->get_office_context_users($office_id);
     

    }elseif($this->action=='list'){
      
      $result['has_details_table'] = false; 
      $result['has_details_listing'] = false;
      $result['is_multi_row'] = false;
      $result['show_add_button'] = true;


      $result['fcp_offices']=$this->office_model->get_list_of_offices(1);
      $result['cluster_offices']=$this->office_model->get_list_of_offices(2);
      $result['cohort_offices']=$this->office_model->get_list_of_offices(3);
      if($this->session->system_admin){
        $result['country_offices']=$this->office_model->get_list_of_offices(4);
      }
      
    }
    
    else{

    }
    
    return $result;
  }

  function reload_fcps_after_switching_clusters(){
    echo json_encode($this->office_model->get_list_of_offices(1));
  }

  function get_office_context_users(int $office_id, int $definition_id){

    echo json_encode($this->office_model->get_office_context_users($office_id, $definition_id));
  }

  function suspend_office(){
    $post = $this->input->post();
    $office_id = $post['office_id'];
    $suspension_status = $post['suspension_status'];
    $message = false;

    $this->write_db->trans_start();

    $status_to_update = $suspension_status == 0 ? 1 : 0;
    
    $data['office_is_suspended'] = $status_to_update;
    $this->write_db->where(array('office_id' => $office_id));
    $this->write_db->update('office', $data);

    $this->load->model('project_allocation_model');
    $this->project_allocation_model->deactivate_default_allocation($office_id, $suspension_status);

    $this->write_db->trans_complete();
      
    if($this->write_db->trans_status() == true){
      $message = true;
    }

    echo $message;
  }
  static function get_menu_list()
  {
  }
}
