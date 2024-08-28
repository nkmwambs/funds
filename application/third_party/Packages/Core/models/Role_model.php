<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Role_model extends MY_Model
{
  public $table = 'role'; // you MUST mention the table name
  //public $dependant_table = "role_permission";


  function __construct()
  {
    parent::__construct();
    $this->load->database();
  }

  function delete($id = null)
  {
  }

  function index()
  {
  }


  function list()
  {
  }

  function lookup_tables()
  {
    return array('account_system','context_definition');
  }

  function detail_tables()
  {
    return ['role_permission', 'role_group_association']; //, 'page_view_role', 'role_user'
  }


  function list_table_visible_columns(){
    return ['role_track_number','role_name','role_description','role_is_active','account_system_name'];
  }

  function single_form_add_visible_columns(){
    $columns = ['role_name','role_shortname','role_description','role_is_new_status_default','role_is_department_strict','context_definition_name','account_system_name'];

    if(!$this->session->system_admin){
        $columns = ['role_track_number','role_name','role_description','context_definition_name'];
    }

    return $columns;
  }

  function edit_visible_columns(){
    $columns = ['role_name','role_shortname','role_description','role_is_active','role_is_new_status_default','role_is_department_strict','account_system_name'];

    if(!$this->session->system_admin){
        $columns = ['role_track_number','role_name','role_description','role_is_active','account_system_name'];
    }

    return $columns;
  }


  function view()
  {
  }

  function action_before_insert($post_array)
  {
    $post_array['header']['role_is_active'] = 1;
    !isset($post_array['header']['fk_account_system_id']) ? $post_array['header']['fk_account_system_id'] = $this->session->user_account_system_id : null;
    return $this->grants->sanitize_post_value_before_insert($post_array, 'role_shortname');
  }

  /**
   * Get List of Roles
   * 
   * This method retrives roles e.g. Accountant, country admin
   * @return Array - array
   * @Author :Livingstone Onduso
   * @Date: 08/09/2022
   */

  function retrieve_roles($context_definition){

    $this->read_db->select(array('role_id', 'role_name'));
    if(!$this->session->system_admin){
      $this->read_db->where(['fk_account_system_id'=>$this->session->user_account_system_id]);

    }
    $this->read_db->where(array('role_is_active'=>1,  'fk_context_definition_id'=>$context_definition));
    $roles=$this->read_db->get('role')->result_array();

    $roles_ids=array_column($roles,'role_id');

    $roles_names=array_column($roles,'role_name');

    $roles_ids_and_names=array_combine($roles_ids,$roles_names);

    return $roles_ids_and_names;
   }


  public function list_table_where()
  {
    if (!$this->session->system_admin) {
      $this->read_db->where('fk_account_system_id', $this->session->user_account_system_id);
      $this->read_db->where_not_in('role_id', $this->session->role_ids);
    }
  }

  function lookup_values()
  {
    $lookup_values = parent::lookup_values();

    // Show context definitions below the user's
    if(!$this->session->system_admin){
      $user_context_definition_id = $this->session->context_definition['context_definition_id'];
      $this->read_db->select(array('context_definition_id', 'context_definition_name'));
      $this->read_db->where(array('context_definition_id <= ' => $user_context_definition_id));
      $context_definitions = $this->read_db->get('context_definition')->result_array();

      $lookup_values['context_definition'] = $context_definitions;
    }

    return $lookup_values;
  }


  function intialize_table(array $foreign_keys_values = [])
  {

    $role_data['role_track_number'] = $this->grants_model->generate_item_track_number_and_name('role')['role_track_number'];
    $role_data['role_name'] = 'Super System Administrator';
    $role_data['role_shortname'] = 'superadmin';
    $role_data['role_description'] = 'Super System Administrator';
    $role_data['role_is_active'] = 1;
    $role_data['role_is_new_status_default'] = 1;
    $role_data['role_is_department_strict'] = 0;
    $role_data['fk_account_system_id'] = 1;

    $role_data_to_insert = $this->grants_model->merge_with_history_fields('role', $role_data, false);
    $this->write_db->insert('role', $role_data_to_insert);

    return $this->write_db->insert_id();
  }
}
