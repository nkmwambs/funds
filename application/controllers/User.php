<?php

use phpDocumentor\Reflection\Types\Boolean;

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class User extends MY_Controller
{

  function __construct()
  {
    parent::__construct();

    $this->load->library('user_library');
    $this->load->model('office_model');
    $this->load->model('department_model');
    $this->load->model('role_model');
    $this->load->model('designation_model');
    $this->load->model('country_currency_model');
    $this->load->model('account_system_model');
  }

  function index()
  {
  }

  private function _get_user_info()
  {
    $user = $this->user_model->get_user_info();

    return $user;
  }


  /**
   * @todo - This will change with the approval status update [Completed]
   */
  private function _get_approval_assignments($role_id)
  {
    $this->db->select(array('status_name', 'approve_item_name'));

    $this->db->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id');
    $this->db->join('approve_item', 'approve_item.approve_item_id=approval_flow.fk_approve_item_id');
    $this->db->join('status_role', 'status_role.status_role_status_id=status.status_id');
    $this->db->where(array('status_role.fk_role_id' => $role_id, 'status_approval_direction' => 1));
    $status = $this->db->get('status')->result_array();

    return $status;
  }

  function _get_user_roles($role_ids)
  {
    $role_names = [];

    $this->read_db->select('role_name');
    $this->read_db->where_in('role_id', $role_ids);
    $roles = $this->read_db->get('role');

    if ($roles->num_rows() > 0) {
      $role_names = array_column($roles->result_array(), 'role_name');
    }

    return $role_names;
  }

  function get_user_data($context_id){
    $result['offices'] = $this->get_offices($context_id, 0, true);
    $result['departments'] = $this->retrieve_departments($context_id, true);
    $result['context_roles'] = $this->retrieve_roles($context_id, true);
    $result['designations'] = $this->retrieve_designations($context_id, true);
    $result['account_systems'] = $this->get_account_systems(true);
    $result['country_currency'] = $this->get_country_currency(true);

    echo json_encode($result);
  }

  function user_unique_identifier_uploads($user_id){
    $this->load->model('unique_identifier_model');
    $attachments = $this->unique_identifier_model->user_unique_identifier_uploads($user_id);
    // $attachment_type_name = 'user_unique_identifier_document';
    // $this->load->model('unique_identifier_model');
    // $account_system_unique_identifier = $this->unique_identifier_model->get_account_system_unique_identifier($this->session->user_account_system_id);
    // $unique_identifier_id = isset($account_system_unique_identifier['unique_identifier_id']) ? $account_system_unique_identifier['unique_identifier_id'] : 0 ;
    // $attachment_url = "uploads/attachments/user/" . $user_id . "/user_identifier_document/" . $unique_identifier_id;

    // // log_message('error', $attachment_type_name);

    // $this->read_db->select(array('attachment_url','attachment_name'));
    // $this->read_db->where(array('attachment_type_name' => $attachment_type_name, 'attachment_url' => $attachment_url));
    // $this->read_db->join('attachment_type','attachment_type.attachment_type_id=attachment.fk_attachment_type_id');
    // $attachment_obj = $this->read_db->get('attachment');

    // $attachments = [];

    // if($attachment_obj->num_rows() > 0){
    //   $attachments =  $attachment_obj->result_array();
    // }

    return $attachments;
  }

  function result($id = "")
  {
    $result = [];

    $this->load->model('unique_identifier_model');

    $user_info = [];

    if($this->action == 'view' || $this->action == 'edit'){
      $user_info = $this->user_model->get_user_info(hash_id($this->id,'decode'));
      // $result['account_system_identifier'] = $this->unique_identifier_model->get_account_system_unique_identifier($user_info['account_system_id']);
      
      $this->load->model('unique_identifier_model');
      $result['valid_user_unique_identifier'] = $this->unique_identifier_model->valid_user_unique_identifier(hash_id($this->id,'decode'));
    }
    

    if ($this->action == 'view') {
      $result['user_info'] = $user_info;
      // log_message('error', json_encode($user_info));
      // $result['user_info']['account_system_identifier'] = $this->unique_identifier_model->get_account_system_unique_identifier($user_info['account_system_id']);
      $user_id = hash_id($this->id, 'decode');
      $status_data = $this->general_model->action_button_data($this->controller, $user_info['account_system_id']);
      
      $user_id = $user_info['user_id'];
      $role_id = $user_info['role_id'];
      $role_ids = array_keys($this->user_model->user_role_ids($user_id));
      $departments = array_column($this->department_model->retrieve_user_department($user_id), 'department_name');
      
      $result['status_data'] = $status_data;
      $result['user_info']['role_name'] = implode(",", $this->_get_user_roles($role_ids));
      $result['user_info']['department'] = implode(",", $departments);
      $result['user_context_id'] = $user_info['context_definition_id'];

      $result['role_permission'] = $this->user_model->get_user_permissions($role_ids);
      $result['user_hierarchy_offices'] = $this->user_model->get_user_context_offices($user_id);
      $result['approval_workflow_assignments'] = $this->_get_approval_assignments($role_id);

      // $objectKey = $bank_statements_upload['attachment_url'].'/'.$bank_statements_upload['attachment_name'];
      // $url = $this->config->item('upload_files_to_s3')?$this->grants_s3_lib->s3_preassigned_url($objectKey):$this->attachment_library->get_local_filesystem_attachment_url($objectKey);
      $user_unique_identifier_uploads = $this->user_unique_identifier_uploads($user_id);

      // log_message('error', json_encode($user_unique_identifier_uploads));
      $this->read_db->select(array('user_personal_data_consent_content'));
      $this->read_db->where(array('user_id' => $user_id));
      $result['data_consent'] = $this->read_db->get('user')->row()->user_personal_data_consent_content;

      $result['user_identity_documents'] = $user_unique_identifier_uploads; // '<a href = "#">National Identity</a>';
    } elseif ($this->action == 'single_form_add') {
      $result['all_context_offices'] = $this->office_model->get_all_office_context();
    } elseif ($this->action == 'edit') {

     
      //Get all info for user
      $result['edit_user_info']['account_system_identifier'] = $this->unique_identifier_model->get_account_system_unique_identifier($user_info['account_system_id']);
      $result['edit_user_info'] = $user_info;

      //Get user office
      $user_id = $user_info['user_id'];
      $context_id = $user_info['context_definition_id'];
      
      $user_office = $this->office_model->user_office($context_id, $user_id);
      // log_message('error', json_encode($user_office));
      $result['edit_user_info']['user_office'] = $user_office;

      $result['account_system_id'] = $user_info['account_system_id'];

      //Get user department
      $result['edit_user_info']['departments'] = $this->department_model->retrieve_user_department($user_id);

      //Get all other user related context offices, departments
      $result['all_offices'] = $this->get_offices($context_id, 0, true);
      $result['all_departments'] = $this->retrieve_departments($context_id, true);

      //Get context definitions e.g. center, fcp
      $result['all_context_offices'] = $this->office_model->get_all_office_context();

      //Get user_user_roles
      $result['edit_user_info']['user_seconday_roles'] = $this->user_model->user_role_ids_with_expiry_dates($user_id);
      $result['edit_user_info']['user_primary_role'] = $this->user_model->user_roles($user_id);
      
      // log_message('error', json_encode($this->user_model->user_roles($user_id)));

      //Get all other roles of the user context
      $result['all_context_roles'] = $this->retrieve_roles($context_id, true);

      //Designition 
      $result['edit_user_info']['user_designation'] = $this->user_model->user_designation($user_id, $context_id);

      //All other designitions
      $result['all_designations'] = $this->retrieve_designations($context_id, true);
    } else {
      $result['has_details_table'] = false;
      $result['has_details_listing'] = false;
      $result['is_multi_row'] = false;
      $result['show_add_button'] = true;
    }

    return $result;
  }

  function show_list()
  {

    $users = $this->get_users();

    $draw = intval($this->input->post('draw'));
    $count = $this->get_user_count();

    $records = [];

    $cnt = 0;

    foreach ($users as $user) {
      $user_id = array_shift($user);
      // $action_buttons = $this->load->view('templates/list_action_button',['primary_key'=>$user_id],true);
      //$action_buttons = "";

      $user_track_number = $user['user_track_number'];
      $user['user_track_number'] = '<a href="' . base_url() . $this->controller . '/view/' . hash_id($user_id) . '">' . $user_track_number . '</a>';
      $user['user_is_system_admin'] = $user['user_is_system_admin'] == 1 ? get_phrase('yes') : get_phrase('no');
      $user['user_is_active'] = $user['user_is_active'] == 1 ? get_phrase('yes') : get_phrase('no');
      $user['user_first_time_login'] = $user['user_first_time_login'] == 1 ? get_phrase('yes') : get_phrase('no');

      $row = array_values($user);

      //array_unshift($row,$action_buttons);

      $records[$cnt] = $row;

      $cnt++;
    }


    $response = array(
      'draw' => $draw,
      'recordsTotal' => $count,
      'recordsFiltered' => $count,
      'data' => $records
    );

    echo json_encode($response);
  }

  function get_user_count()
  {

    $columns = array(
      'user_track_number',
      'user_firstname',
      'user_lastname',
      'user_email',
      'user_employment_date',
      'context_definition_name',
      'user_is_system_admin',
      'language_name',
      'user_is_active',
      'status_name',
      'role_name',
      'account_system_name',
      'user_first_time_login'
    );

    $search = $this->input->post('search');
    $value = $search['value'];

    if (!empty($value)) {
      $this->read_db->group_start();
      $column_key = 0;
      foreach ($columns as $column) {
        if ($column_key == 0) {
          $this->read_db->like($column, $value, 'both');
        } else {
          $this->read_db->or_like($column, $value, 'both');
        }
        $column_key++;
      }
      $this->read_db->group_end();
    }

    $this->read_db->select(array(
      'user_id',
      'user_track_number',
      'user_firstname',
      'user_lastname',
      'user_email',
      'user_employment_date',
      'context_definition_name',
      'user_is_system_admin',
      'language_name',
      'user_is_active',
      'status_name',
      'role_name',
      'account_system_name',
      'user_first_time_login'
    ));

    $this->read_db->join('context_definition', 'context_definition.context_definition_id=user.fk_context_definition_id');
    $this->read_db->join('language', 'language.language_id=user.fk_language_id');
    $this->read_db->join('role', 'role.role_id=user.fk_role_id');
    $this->read_db->join('account_system', 'account_system.account_system_id=user.fk_account_system_id');
    $this->read_db->join('status','status.status_id=user.fk_status_id');
    $this->read_db->where(array('user_id <> ' => $this->session->user_id));
    if(!$this->session->system_admin){
      
      $user_context = $this->session->context_definition['context_definition_name'];
      
      if($user_context == 'cluster'){
        $this->read_db->join('context_center_user','context_center_user.fk_user_id=user.user_id');
        $this->read_db->join('context_center','context_center.context_center_id=context_center_user.fk_context_center_id');
        $this->read_db->where_in('context_center.fk_office_id',array_column($this->session->hierarchy_offices,'office_id'));
      
      }
    
      $this->read_db->where(array('user.fk_account_system_id' => $this->session->user_account_system_id));
    }

    $this->read_db->from('user');
    $count = $this->read_db->count_all_results();

    return $count;
  }

  function get_users()
  {
    $users = [];

    $columns = array(
      'user_track_number',
      'user_firstname',
      'user_lastname',
      'user_email',
      'user_employment_date',
      'context_definition_name',
      'user_is_system_admin',
      'language_name',
      'user_is_active',
      'status_name',
      'role_name',
      'account_system_name',
      'user_first_time_login'
    );

    // Limiting Server Datatable Results
    $start = intval($this->input->post('start'));
    $length = intval($this->input->post('length'));

    $this->read_db->limit($length, $start);


    // Ordering Server Datatable Results

    $order = $this->input->post('order');
    $col = '';
    $dir = 'desc';

    if (!empty($order)) {
      $col = $order[0]['column'];
      $dir = $order[0]['dir'];
    }

    if ($col == '') {
      $this->read_db->order_by('user_id DESC');
    } else {
      $this->read_db->order_by($columns[$col], $dir);
    }

    $search = $this->input->post('search');
    $value = $search['value'];

    if (!empty($value)) {
      $this->read_db->group_start();
      $column_key = 0;
      foreach ($columns as $column) {
        if ($column_key == 0) {
          $this->read_db->like($column, $value, 'both');
        } else {
          $this->read_db->or_like($column, $value, 'both');
        }
        $column_key++;
      }
      $this->read_db->group_end();
    }

    $this->read_db->select(array(
      'user_id',
      'user_track_number',
      'user_firstname',
      'user_lastname',
      'user_email',
      'user_employment_date',
      'context_definition_name',
      'user_is_system_admin',
      'language_name',
      'user_is_active',
      'status_name',
      'role_name',
      'account_system_name',
      'user_first_time_login'
    ));

    $this->read_db->join('context_definition', 'context_definition.context_definition_id=user.fk_context_definition_id');
    $this->read_db->join('language', 'language.language_id=user.fk_language_id');
    $this->read_db->join('role', 'role.role_id=user.fk_role_id');
    $this->read_db->join('account_system', 'account_system.account_system_id=user.fk_account_system_id');
    $this->read_db->join('status','status.status_id=user.fk_status_id');
    $this->read_db->where(array('user_id <> ' => $this->session->user_id));
    if(!$this->session->system_admin){
      
      $user_context = $this->session->context_definition['context_definition_name'];
      
      if($user_context == 'cluster'){
        $this->read_db->join('context_center_user','context_center_user.fk_user_id=user.user_id');
        $this->read_db->join('context_center','context_center.context_center_id=context_center_user.fk_context_center_id');
        $this->read_db->where_in('context_center.fk_office_id',array_column($this->session->hierarchy_offices,'office_id'));
      
      }
    
      $this->read_db->where(array('user.fk_account_system_id' => $this->session->user_account_system_id));
    }

    $obj = $this->read_db->get('user');

    if ($obj->num_rows() > 0) {
      $users = $obj->result_array();
    }

    return $users;
  }
  /**
   * get_account_systes() 
   * This method returns the account_systems
   *@return Array
   * @Author: Livingstone Onduso
   * @Dated: 10/09/2022
   */
  public function get_account_systems($ajax_use = false)
  {
    $account_system_ids_and_names = $this->account_system_model->get_account_systems();

    if($ajax_use){
      return $account_system_ids_and_names;
    }

    echo json_encode($account_system_ids_and_names);
  }
  /**
   * get_country_currency() 
   * This method returns the currenncy of a country
   *@return Array
   * @Author: Livingstone Onduso
   * @Dated: 09/09/2022
   */
  public function get_country_currency($ajax_use = false)
  {

    $curreny_ids_and_names = $this->country_currency_model->get_country_currency();

    if($ajax_use){
      return $curreny_ids_and_names;
    }

    echo json_encode($curreny_ids_and_names);
  }
  /**
   * Get List of designation records
   * 
   * This method retrives records
   * @return Array - array of a row
   * @Author :Livingstone Onduso
   * @Date: 05/09/2022
   */
  function retrieve_designations($context_definition_id, $ajax_use = false)
  {

    $designations_ids_and_names = $this->designation_model->retrieve_designations($context_definition_id);

    if ($ajax_use) {
      return $designations_ids_and_names;
    }
    echo json_encode($designations_ids_and_names);
  }

  /**
   * Get List of roles records
   * 
   * This method retrives records
   * @return Array - array of a row
   * @Author :Livingstone Onduso
   * @Date: 08/09/2022
   */
  function retrieve_roles($context_definition, $ajax_use = false)
  {

    $roles_ids_and_names = $this->role_model->retrieve_roles($context_definition);

    if ($ajax_use) {
      return  $roles_ids_and_names;
    }
    echo json_encode($roles_ids_and_names);
  }

  /**
   * Get List of Offices records
   * 
   * This method retrives records
   * @return Array - array of a row
   * @Author :Livingstone Onduso
   * @Date: 08/09/2022
   */
  function retrieve_departments($context_definition, $ajax_use = false)
  {

    $department_ids_and_names = $this->department_model->retrieve_departments($context_definition);

    if ($ajax_use) {
      return  $department_ids_and_names;
    }
    echo json_encode($department_ids_and_names);
  }

  /**
   * Get List of Offices records
   * This method retrives records
   * @return Array - array of a row
   * @Author :Livingstone Onduso
   * @Date: 07/09/2022
   */

  function get_offices($context_definition_id, $add_user_form, $ajax_use = false)
  {

    $offices = [];
    switch ($context_definition_id) {
      case 1:
        $offices = $this->office_model->get_clusters_or_cohorts_or_countries('context_center', 'context_center_id', 'office_name', true, $add_user_form);
        break;
      case 2:
        $offices = $this->office_model->get_clusters_or_cohorts_or_countries('context_cluster', 'context_cluster_id', 'office_name', true, $add_user_form);
        break;
      case 3:
        $offices = $this->office_model->get_clusters_or_cohorts_or_countries('context_cohort', 'context_cohort_id', 'office_name', true, $add_user_form);
        break;
      case 4:
        $offices = $this->office_model->get_clusters_or_cohorts_or_countries('context_country', 'context_country_id', 'office_name', true, $add_user_form);
        break;
      case 5:
        $offices = $this->office_model->get_clusters_or_cohorts_or_countries('context_region', 'context_region_id', 'office_name', true, $add_user_form);
        break;
      case 6:
        $offices = $this->office_model->get_clusters_or_cohorts_or_countries('context_global', 'context_global_id', 'office_name', true, $add_user_form);
        break;
    }
    if ($ajax_use) {
      return $offices;
    } else {
      echo json_encode($offices);
    }
  }


  function get_ajax_response_for_selected_definition()
  {

    $post = $this->input->post();

    $result = $this->user_model->get_available_office_user_context_by_email_context_definition($post['user_email'], $post['context_definition_id']);

    $list_of_contexts = $result['result'];
    $message = $result['message'];
    $list_of_departments = $this->get_user_available_department_department_by_email($post);
    $list_of_designation = $this->get_user_designation($post['context_definition_id']);

    $office_contexts_by_id = combine_name_with_ids($list_of_contexts, 'context_table_id', 'context_table_name');
    $departments_by_id = combine_name_with_ids($list_of_departments, 'department_id', 'department_name');
    $user_designation = combine_name_with_ids($list_of_designation, 'designation_id', 'designation_name');


    //$select_office_context = $this->grants->select_field('office_context',$office_contexts_by_id);
    $select_departments = $this->grants->select_field('department', $departments_by_id);
    $select_designation = $this->grants->select_field('designation', $user_designation);

    echo json_encode(array('select_designation' => $select_designation, 'select_department' => $select_departments, 'message' => $message));
  }

  function get_user_designation($context_definition_id)
  {
    $this->db->select(array('designation_id', 'designation_name'));
    return $this->db->get_where('designation', array('fk_context_definition_id' => $context_definition_id))->result_array();
  }

  function get_user_available_department_department_by_email($post_array)
  {

    $user_obj = $this->db->get_where('user', array('user_email' => $post_array['user_email']));
    $departments = [];

    if ($user_obj->num_rows() > 0) {
      $user_department_assigned_obj = $this->db->get_where('department_user', array('fk_user_id' => $user_obj->row()->user_id));

      if ($user_department_assigned_obj->num_rows() > 0) {

        $department_ids = array_column($user_department_assigned_obj->result_array(), 'fk_department_id');

        $this->db->select(array('department_id', 'department_name'));
        $this->db->where_not_in('department_id', $department_ids);
        $this->db->where(array('department_is_active' => 1));
        $departments  = $this->db->get('department')->result_array();
      } else {

        $this->db->select(array('department_id', 'department_name'));
        $this->db->where(array('department_is_active' => 1));
        $departments  = $this->db->get('department')->result_array();
      }
    } else {
      $this->db->select(array('department_id', 'department_name'));
      $this->db->where(array('department_is_active' => 1));
      $departments  = $this->db->get('department')->result_array();
    }


    return $departments;
  }
  /**
   * check_if_email_is_used(): This method verifies the email
   * @author Karisa and Onduso 
   * @access public
   * @return void
   */
  public function check_if_email_is_used()
  {

    $post = $this->input->post();

    // log_message('error', json_encode($post));

    // $this->db->or_where(array('user_name' => $post['user_name'], 'user_email' => strtolower(trim($post['user_email']))));
    $this->db->or_where(array('user_email' => strtolower(trim($post['user_email']))));

    $count_of_users_with_email = $this->db->get('user')->num_rows();

    $valid_email = true;

    if ($count_of_users_with_email > 0) {
      $valid_email = false;
    }

    echo $valid_email;
  }

  // function list_role_permissions()
  // {
  //   $post = $this->input->post('role_ids');
  //   $role_ids = explode(',', $post);
  //   echo json_encode($this->user_model->get_user_permissions($role_ids[0]));
  // }

  // function list_role_permissions()
  // {
  //   $post = $this->input->post('role_ids');
  //   $role_ids = explode(',', $post);
  //   echo json_encode($this->user_model->get_user_permissions($role_ids[0]));
  // }

  function update_user_consent(){
    $post = $this->input->post();

    $this->load->model('unique_identifier_model');
    $unique_identifier = $this->unique_identifier_model->get_account_system_unique_identifier($this->session->user_account_system_id);

    $data['user_employment_date'] = $post['user_employment_date'];
    $data['user_unique_identifier'] = $post['user_unique_identifier'];
    $data['fk_unique_identifier_id'] = $unique_identifier['unique_identifier_id'];
    $data['user_personal_data_consent_date'] = date('Y-m-d');
    $data['user_personal_data_consent_content'] = $post['consent_content'];

    $this->write_db->where(array('user_id' => $this->session->user_id));
    $this->write_db->update('user', $data);

    if($this->write_db->affected_rows() > 0){
      $this->session->set_userdata('data_privacy_consented', $this->user_model->data_privacy_consented($this->session->user_id, false));
    }

    // echo json_encode($post);
    echo $this->session->data_privacy_consented;
  }

  public function update_user_consent_document(){
    $result = [];

    $this->load->model('attachment_model');
    $this->load->model('unique_identifier_model');

    $unique_identifier_id = $this->unique_identifier_model->get_account_system_unique_identifier($this->session->user_account_system_id)['unique_identifier_id'];
    $storeFolder = upload_url('user', $this->session->user_id, ['user_identifier_document', $unique_identifier_id]); 
    
    if (
        is_array($this->attachment_model->upload_files($storeFolder, 'user_unique_identifier_document')) &&
        count($this->attachment_model->upload_files($storeFolder, 'user_unique_identifier_document')) > 0
      ) {
        $result = $this->attachment_model->upload_files($storeFolder, 'user_unique_identifier_document');
        }

    
    echo empty($result) ? 0 : 1;
  }

  function edit_user($user_id)
  {

    $this->load->model('unique_identifier_model');
    $post = $this->input->post()['header'];
    // log_message('error', json_encode($post));

    $user_info = $this->user_model->get_user_info($user_id);

    $this->write_db->trans_start();

    // Track change history. This line should be placed befire actual editing happens 
    
    $this->grants_model->create_change_history($post, true, ['user_password']);

    // log_message('error', json_encode($post));
    
    $user['user_firstname'] = $post['user_firstname'];
    $user['user_name'] = sanitize_characters($post['user_name']);
    $user['user_lastname'] = $post['user_lastname'];
    $user['user_email'] = $post['user_email'];
    $user['fk_context_definition_id'] = $post['fk_context_definition_id'];
    $user['user_is_context_manager'] = isset($post['user_is_context_manager']) ? $post['user_is_context_manager'] : 0;
    $user['user_is_system_admin'] = isset($post['user_is_system_admin']) ? $post['user_is_system_admin'] : 0;
    $user['fk_language_id'] = isset($post['fk_language_id']) ? $post['fk_language_id'] : 1;
    $user['user_is_active'] = 1;
    $user['md5_migrate'] = 1; // For migrating fro use of php MD5 to complex sha256 with salt
    $user['fk_role_id'] = $post['primary_role_id'];
    $user['user_is_switchable'] = $post['user_is_switchable'];
    if ($this->session->system_admin) {
      $user['fk_country_currency_id'] = $post['fk_country_currency_id'];

      $user['fk_account_system_id'] = $post['fk_account_system_id'];
    } else {
      $user['fk_country_currency_id'] = $post['currency_id'];

      $user['fk_account_system_id'] = $post['account_system_id'];
    }

    $this->load->model('unique_identifier_model');
    $unique_identifier = $this->unique_identifier_model->valid_user_unique_identifier($user_id);

    if(isset($unique_identifier['unique_identifier_id']) && $unique_identifier['unique_identifier_id'] > 0){
      $user['user_employment_date'] = isset($post['user_employment_date']) && $post['user_employment_date'] != '' ? $post['user_employment_date'] : NULL;
      $user['user_unique_identifier'] = isset($post['user_unique_identifier']) && $post['user_unique_identifier'] != '' ? $post['user_unique_identifier'] : NULL;
      $user['fk_unique_identifier_id'] = $unique_identifier['unique_identifier_id'];
    }

    $user_to_insert = $this->grants_model->merge_with_history_fields($this->controller, $user, false);

    $this->write_db->where(array('user_id' => $user_id));
    $this->write_db->update('user', $user_to_insert);

    // log_message('error', json_encode($post['secondary_role_ids']));

    // Delete secondary roles if not provided any in the edit form
    if (!isset($post['secondary_role_ids'])) {
      $this->write_db->where(array('fk_user_id' => $user_id));
      $this->write_db->delete('role_user');
    } elseif (count($post['secondary_role_ids']) > 0) {
      $this->read_db->select(array('fk_role_id'));
      $this->write_db->where(array('fk_user_id' => $user_id));
      $role_user_obj = $this->read_db->get('role_user');

      if ($role_user_obj->num_rows() > 0) {
        $current_role_ids = array_column($role_user_obj->result_array(), 'fk_role_id');

        $isEqual = array_diff($post['secondary_role_ids'], $current_role_ids) === array_diff($current_role_ids, $post['secondary_role_ids']);
        // Only update if the current secondary roles do not match the incoming ones
        if (!$isEqual) {
          $this->write_db->where(array('fk_user_id' => $user_id));
          $this->write_db->delete('role_user');

          $cnt = 0;
          foreach ($post['secondary_role_ids'] as $key => $role_id) {
            $role_user_track = $this->grants_model->generate_item_track_number_and_name('role_user');
            $insert_role_user[$cnt]['role_user_track_number'] = $role_user_track['role_user_track_number'];
            $insert_role_user[$cnt]['role_user_name'] = $role_user_track['role_user_name'];
            $insert_role_user[$cnt]['fk_user_id'] = $user_id;
            $insert_role_user[$cnt]['fk_role_id'] = $role_id;
            $insert_role_user[$cnt]['role_user_created_date'] = date('Y-m-d');
            $insert_role_user[$cnt]['role_user_created_by'] = $this->session->user_id;
            $insert_role_user[$cnt]['role_user_last_modified_by'] = $this->session->user_id;
            $insert_role_user[$cnt]['role_user_expiry_date'] = $post['expiry_dates'][$key]; // date('Y-m-d', strtotime('+30 days'));
            $insert_role_user[$cnt]['fk_status_id'] = $this->grants_model->initial_item_status('role_user');
            $insert_role_user[$cnt]['fk_approval_id'] = $this->grants_model->insert_approval_record('role_user');
            $cnt++;
          }
          $this->write_db->insert_batch('role_user', $insert_role_user);
        }
      }
    }

    // Update a user in a context table 
    $context_definition_name = $this->db->get_where('context_definition', array('context_definition_id' => $post['fk_context_definition_id']))->row()->context_definition_name;
    $context_definition_user_table = 'context_' . $context_definition_name . '_user';

    $context[$context_definition_user_table . '_name'] = "Office context for " . $post['user_firstname'] . " " . $post['user_lastname'];

    //get the context_office_id e.g. context_center_id, context_cluster_id

    switch ($post['fk_context_definition_id']) {
      case 1:
        $context_table = 'context_center';
        break;
      case 2:
        $context_table = 'context_cluster';
        break;

      case 3:
        $context_table = 'context_cohort';
        break;
      case 4:
        $context_table = 'context_country';
        break;
      case 5:
        $context_table = 'context_region';
        break;
      case 6:
        $context_table = 'context_global';
        break;
    }
    // Check if user is changing the office context
    if ($post['hold_context_definition_id'] != $post['fk_context_definition_id']) {

      switch ($post['hold_context_definition_id']) {
        case 1:
          $table_to_delete_records_from = 'context_center_user';
          break;
        case 2:
          $table_to_delete_records_from = 'context_cluster_user';
          break;
        case 3:
          $table_to_delete_records_from = 'context_cohort_user';
          break;
        case 4:
          $table_to_delete_records_from = 'context_country_user';
          break;
        case 5:
          $table_to_delete_records_from = 'context_region_user';
          break;
        case 6:
          $table_to_delete_records_from = 'context_global_user';
          break;
      }
      //Delete data from the context office where user is moving from

      $this->write_db->where(array('fk_user_id' => $user_id));
      $this->write_db->delete($table_to_delete_records_from);

      //Save record in new context table [Insert a user in a context table] 
      $context_definition_name = $this->db->get_where('context_definition', array('context_definition_id' => $post['fk_context_definition_id']))->row()->context_definition_name;
      $context_definition_user_table = 'context_' . $context_definition_name . '_user';

      //Get the context ids e.g fk_cluster_id using the office_id [BUG for switching from FCP to cluster and vice versa]
      $fk_context_column_id = $context_table . '_id';
      $this->read_db->select($fk_context_column_id);
      $this->read_db->where(array('fk_office_id' => $post['fk_user_context_office_id'][0]));
      $fk_context_id_obj = $this->read_db->get($context_table);

      $fk_context_id = 0;

      if($fk_context_id_obj->num_rows() > 0){
        $fk_context_id = $fk_context_id_obj->row()->$fk_context_column_id;
        $context['fk_context_' . $context_definition_name . '_id'] = $fk_context_id;
        $context[$context_definition_user_table. '_last_modified_by'] = $this->session->user_id;
        $context[$context_definition_user_table . '_name'] = "Office context for " . $post['user_firstname'] . " " . $post['user_lastname'];
        $context['fk_user_id'] = $user_id;
        $context['fk_designation_id'] = $post['designation'];
        $context[$context_definition_user_table . '_is_active'] = 1;

        $context_to_insert = $this->grants_model->merge_with_history_fields($context_definition_user_table, $context, false);
        $this->write_db->insert($context_definition_user_table, $context_to_insert);

      }
    } else {

      $column_id = $context_table . '_id';

      if ($post['office_context_changed'] > 0) {
        // Delete all office assignments for the user
        $this->write_db->where(array('fk_user_id' => $user_id));
        $this->write_db->delete($context_definition_user_table);

        foreach (array_unique($post['fk_user_context_office_id']) as $office_id) {
          $this->write_db->select(array($column_id));
          $this->write_db->where(array('fk_office_id' => $office_id));
          $context_office_id_obj = $this->write_db->get('context_' . $context_definition_name);

          $context_office_id = 0;

          if ($context_office_id_obj->num_rows() > 0) {
            $context_office_id = $context_office_id_obj->row()->$column_id;

            $context['fk_context_' . $context_definition_name . '_id'] = $context_office_id;
            $context['fk_designation_id'] = $post['designation'];
            $context['fk_user_id'] = $user_id;
            $context[$context_definition_user_table . '_is_active'] = 1;

            $context_to_insert = $this->grants_model->merge_with_history_fields($context_definition_user_table, $context, false);
            $this->write_db->insert($context_definition_user_table, $context_to_insert);
          }
        }
      }
    }
    // Update user department
    $department['department_user_name'] = "Department for " . $post['user_firstname'] . " " . $post['user_lastname'];
    $department['fk_department_id'] = $post['department'];

    $department_to_insert = $this->grants_model->merge_with_history_fields('department_user', $department, false);

    $this->write_db->where(array('fk_user_id' => $user_id));
    $this->write_db->update('department_user', $department_to_insert);

    $this->write_db->trans_complete();

    if ($this->write_db->trans_status() == false) {
      echo "Database Error occurred";
    } else {
      echo "User record updated";
    }
  }

  private function assign_user_active_status($user_context_definition_id, $account_system_id = null){
    $active_status = 0;
    $account_system_id = $account_system_id == null ? $this->session->user_account_system_id : $account_system_id;

    if($user_context_definition_id == 1){
      $this->load->model('unique_identifier_model');
      $account_system_unique_identifier = $this->unique_identifier_model->get_account_system_unique_identifier($account_system_id);
      if(!empty($account_system_unique_identifier)){
        $active_status = 1;
      }
    }

    return $active_status;
  }

  function create_new_user()
  {
    $post = $this->input->post()['header'];

    // log_message('error', json_encode($post));
  
    $this->load->model('unique_identifier_model');

    $this->write_db->trans_start();

    $user['user_name'] = sanitize_characters($post['user_name']);
    $user['user_firstname'] = $post['user_firstname'];
    $user['user_lastname'] = $post['user_lastname'];
    $user['user_email'] = strtolower(trim($post['user_email']));
    $user['fk_context_definition_id'] = $post['fk_context_definition_id'];
    $user['user_is_context_manager'] = isset($post['user_is_context_manager']) ? $post['user_is_context_manager'] : 0;
    $user['user_is_system_admin'] = isset($post['user_is_system_admin']) ? $post['user_is_system_admin'] : 0;
    $user['fk_language_id'] = $post['fk_language_id'];
    $user['user_is_active'] = $this->assign_user_active_status($post['fk_context_definition_id'], isset($post['fk_account_system_id']) ? $post['fk_account_system_id'] : null); // A user has to be full approved to have the record active. Normal approval flow to be followed.
    $user['md5_migrate'] = 1; //For migrating fro use of php MD5 to complex sha256 with salt
    $user['fk_role_id'] = $post['fk_role_id'];
    $user['user_is_switchable'] = isset($post['user_is_switchable']) ? $post['user_is_switchable'] : 1;
    if ($this->session->system_admin) {
      $user['fk_country_currency_id'] = $post['fk_country_currency_id'];

      $user['fk_account_system_id'] = $post['fk_account_system_id'];
    } else {
      $user['fk_country_currency_id'] = $post['currency_id'];

      $user['fk_account_system_id'] = $post['account_system_id'];
    }
    
    $unique_identifier = $this->unique_identifier_model->get_account_system_unique_identifier($user['fk_account_system_id']);

    // log_message('error', json_encode($unique_identifier));

    // if(isset($unique_identifier['unique_identifier_id']) && $unique_identifier['unique_identifier_id'] > 0){
    //   $user['user_employment_date'] = isset($post['user_employment_date']) && $post['user_employment_date'] != '' ? $post['user_employment_date'] : NULL;
    //   $user['user_unique_identifier'] = isset($post['user_unique_identifier']) && $post['user_unique_identifier'] != '' ? $post['user_unique_identifier'] : NULL;
    //   $user['fk_unique_identifier_id'] = $unique_identifier['unique_identifier_id']; 
    //   // isset($post['unique_identifier_id']) && $post['unique_identifier_id'] != '' ? $post['unique_identifier_id'] : NULL;
    // }

    // $user['user_password'] = md5($post['user_password']);
    // $password = $post['user_password'];
    // $salt      = $this->aws_parameter_library->get_parameter_value('sha256-password-salt');
    // $hashed    = hash('sha256', $password . $salt);

    $hashed = $this->user_model->password_salt($post['user_password']);

    $user['user_password'] = $hashed;



    $user_to_insert = $this->grants_model->merge_with_history_fields($this->controller, $user, false);

    $this->write_db->insert('user', $user_to_insert);

    $user_id = $this->write_db->insert_id();

    // Insert a user in a context table 
    $context_definition_name = $this->db->get_where('context_definition', array('context_definition_id' => $post['fk_context_definition_id']))->row()->context_definition_name;
    $context_definition_user_table = 'context_' . $context_definition_name . '_user';

    $context[$context_definition_user_table . '_name'] = "Office context for " . $post['user_firstname'] . " " . $post['user_lastname'];
    $context['fk_user_id'] = $user_id;
    $context['fk_context_' . $context_definition_name . '_id'] = $post['fk_user_context_office_id'];
    $context['fk_designation_id'] = $post['designation'];
    $context[$context_definition_user_table . '_is_active'] = 1;

    $context_to_insert = $this->grants_model->merge_with_history_fields($context_definition_user_table, $context, false);

    $this->write_db->insert($context_definition_user_table, $context_to_insert);

    // Insert user department
    $department['department_user_name'] = "Department for " . $post['user_firstname'] . " " . $post['user_lastname'];
    $department['fk_user_id'] = $user_id;
    $department['fk_department_id'] = $post['department'];

    $department_to_insert = $this->grants_model->merge_with_history_fields('department_user', $department, false);

    $this->write_db->insert('department_user', $department_to_insert);

    $this->write_db->trans_complete();

    if ($this->write_db->trans_status() == false) {
      if(isset($unique_identifier['unique_identifier_id'])){
        $checkIfUniqueIdDublipcates = $this->check_unique_identifier_duplicates($unique_identifier['unique_identifier_id'], $post['user_unique_identifier']);
        
        if($checkIfUniqueIdDublipcates['status']){
          echo get_phrase('duplicate_identifier','Duplicate user identification is not allowed');
        }else{
          echo get_phrase('error_occurred');
        }

      }
      
      //echo json_encode($context_to_insert);
    } else {
      echo get_phrase('user_created_successfully');
    }
  }

  function verify_user_unique_identifier(){
    $post = $this->input->post();
    
    extract($post);

    $identifier_duplicates = $this->check_unique_identifier_duplicates($unique_identifier_id, $user_unique_identifier);

    echo json_encode($identifier_duplicates);
  }

  function check_unique_identifier_duplicates($unique_identifier_id, $user_unique_identifier){

    $identifier_duplicates = ['status' => false, 'records' => []];

    $this->read_db->select(array('user_firstname','user_lastname','user_email'));
    $this->read_db->where(array('unique_identifier_id' => $unique_identifier_id, 'user_unique_identifier' => $user_unique_identifier));
    $this->read_db->join('unique_identifier','unique_identifier.unique_identifier_id=user.fk_unique_identifier_id');
    $this->read_db->join('account_system','account_system.account_system_id=unique_identifier.fk_account_system_id');
    $user_obj = $this->read_db->get('user');

    if($user_obj->num_rows() > 0){
      $identifier_duplicates = ['status' => true, 'records' => $user_obj->result_array()];
    }

    return $identifier_duplicates;
  }


  function change_user_status($user_id)
  {
    $message = 0; //"User status update unsuccessfully";

    $user_id = hash_id($user_id, 'decode');

    $this->read_db->where(array('user_id' => $user_id));
    $user = $this->read_db->get('user')->row();

    $new_status = $user->user_is_active == 1 ? 0 : 1;

    $data['user_is_active'] = $new_status;
    $data['user_last_modified_date'] = date('Y-m-d h:i:s');

    $this->grants_model->create_change_history($data);

    $this->write_db->where(array('user_id' => $user_id));
    $this->write_db->update('user', $data);

    if ($this->write_db->affected_rows() > 0) {
      $message = $new_status; //"User status update successfully";
    }

    echo $message;
  }

  function password_reset($user_id)
  {
    $message = "Password reset unsuccessfully";

    $user_id = hash_id($user_id, 'decode');

    $this->read_db->where(array('user_id' => $user_id));
    $user = $this->read_db->get('user')->row();

    $email = $user->user_email;

    // $password = $post['user_password'];
    $salt      = $this->aws_parameter_library->get_parameter_value('sha256-password-salt');
    $hashed    = hash('sha256', explode('@', $email)[0] . $salt);

    $data['user_password'] = $hashed;

    // $data['user_password'] = md5(explode('@',$email)[0]);
    $data['user_first_time_login'] = 0;
    $data['md5_migrate'] = 1;
    $data['user_last_modified_date'] = date('Y-m-d h:i:s');

    $this->grants_model->create_change_history($data);

    $this->write_db->where(array('user_id' => $user_id));
    $this->write_db->update('user', $data);

    if ($this->write_db->affected_rows() > 0) {
      $message = "Password reset successfully";
    }

    echo $message;
  }

  function remove_context_user()
  {
    $post = $this->input->post();

    extract($post);


    $message = "Context deactivated unsuccessfully";

    $this->read_db->where(array('fk_context_definition_id' => $context_definition_id, 'fk_office_id' => $office_id, 'fk_user_id' => $user_id));
    $this->read_db->join('context_' . $context_definition_name, 'context_' . $context_definition_name . '.context_' . $context_definition_name . '_id=context_' . $context_definition_name . '_user.fk_context_' . $context_definition_name . '_id');
    $result = $this->read_db->get('context_' . $context_definition_name . '_user')->result_array();

    $context_user_ids = array_column($result, 'context_' . $context_definition_name . '_user_id');

    $this->write_db->where_in('context_' . $context_definition_name . '_user_id', $context_user_ids);
    $this->write_db->delete('context_' . $context_definition_name . '_user');

    if ($this->write_db->affected_rows() > 0) {
      $message = "Context deactivated successfully";
    }

    echo $message;
  }

  public function reset_data_privacy_consent($user_id){

    $message = get_phrase('data_privacy_consent_reset_success','User data privacy consent reset successful');
    $condition = array('user_id' => $user_id);

    $this->write_db->trans_start();

    // Update change history
    $this->read_db->select(array('user_id','user_unique_identifier','user_personal_data_consent_date','user_personal_data_consent_content'));
    $this->read_db->where($condition);
    $user_consent = $this->read_db->get('user')->row_array();

    if($user_consent['user_personal_data_consent_content'] == ''){
      $message = get_phrase('data_privacy_consent_not_available','User data privacy consent is not available');
    }

    parent::create_change_history(
      [
        '$user_id' => $user_id,
        'user_unique_identifier' => NULL, 
        'user_personal_data_consent_date' => NULL, 
        'user_personal_data_consent_content' => NULL
      ], 
      $user_consent, 
      'user');

    // Reset data privacy 
    $data = [
      'user_unique_identifier' => NULL, 
      'user_personal_data_consent_date' => NULL, 
      'user_personal_data_consent_content' => NULL
    ];

    $this->write_db->where($condition);
    $this->write_db->update('user',$data);

    // Get approve aitem id for user
    $this->read_db->select(array('approve_item_id'));
    $this->read_db->where(array('approve_item_name' => 'user'));
    $approve_item_id = $this->read_db->get('approve_item')->row()->approve_item_id;

    // Get user_unique_identifier_document attachment type id
    $this->read_db->select(array('attachment_type_id'));
    $this->read_db->where(array('attachment_type_name' => 'user_unique_identifier_document'));
    $attachment_type_id = $this->read_db->get('attachment_type')->row()->attachment_type_id;

    // Delete files uploaded
    $this->write_db->where(array('fk_approve_item_id' => $approve_item_id, 'fk_attachment_type_id' => $attachment_type_id, 'attachment_primary_id' => $user_id));
    $this->write_db->delete('attachment');

    $this->write_db->trans_complete();

    // Check success and send a message back to the user

    if ($this->write_db->trans_status() === FALSE)
    {
      $message = get_phrase('data_privacy_consent_reset_failed','User data privacy consent reset failed');
    }

    echo $message;
  }

  static function get_menu_list()
  {
  }
}
