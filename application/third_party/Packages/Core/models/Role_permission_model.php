<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Role_permission_model extends MY_Model
{
  public $table = 'role_permission'; // you MUST mention the table name


  function __construct(){
    parent::__construct();
    $this->load->database();

  }

  function action_before_insert($post_array): array
  {
    //$post_array['header']['user_password'] = md5($post_array['header']['user_password']);
    $menus = $this->menu_library->getMenuItems();
    $this->menu_model->upsert_menu($menus);

    return $post_array;
  }

  function delete($id = null){

  }
  
  function index(){

  }

  function single_form_add_visible_columns(){
    return array('role_name','permission_name');
  }

  function edit_visible_columns(){
    return array('role_name','permission_name','role_permission_is_active');
  }



  function list(){

  }

  // function list_table_visible_columns(){
  //   return array('role_permission_track_number','role_permission_is_active',
  //   'permission_field','role_name','permission_name','permission_description');
  // }

  function master_table_visible_columns(){
    return array('role_permission_track_number','permission_name','role_permission_is_active',
    'permission_field','role_name','permission_description');
  }

  function lookup_tables(){
    return array('role','permission');
  }

  function detail_multi_form_add_visible_columns(){
    
  }

  function detail_tables(){
    
  }

  function view(){

  }

  function transaction_validate_duplicates_columns(){
    return ['fk_role_id','fk_permission_id'];
  }

  function multi_select_field(){
    return 'permission';
  }

  function lookup_values()
  {
      $lookup_values = parent::lookup_values();

      // log_message('error', json_encode($this->session->system_admin));

      if(!$this->session->system_admin){
          $this->read_db->select(array('permission_id','permission_name'));
          $this->read_db->where(array('permission_is_global'=>0));
          $this->read_db->where('NOT EXISTS (SELECT * FROM role_permission WHERE role_permission.fk_permission_id=permission.permission_id AND fk_role_id = '.hash_id($this->id,'decode').')','',FALSE);
          $lookup_values['permission'] = $this->read_db->get('permission')->result_array();
      }

      return $lookup_values;
  }

  // function lookup_values()
  // {
  //   $lookup_values = parent::lookup_values();

  //   $lookup_values = array_merge($lookup_values,[['pemission_id'=>1,'pemission_name'=>'Create Status'],['permission_id'=>2,'permission_name'=>'Update Status']]);
    
  //   return $lookup_values;
  // }
  
}
