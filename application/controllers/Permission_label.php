<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Permission_label extends MY_Controller
{

  function __construct(){
    parent::__construct();

    $this->grants->table_setup('permission');
  }
  function index(){}

  function result($id = ""){

    // Create a missing CRUD Page Access permission
    //$this->grants_model->create_missing_page_access_permission();

    $permissions = $this->_get_permissions_grouped_by_label();

    // Permission labels
    $permission_labels =  array_column($this->_get_permission_labels(),'permission_label_name');
    $permission_label_ids =  array_column($this->_get_permission_labels(),'permission_label_id');
    $permission_labels_with_id_keys = array_combine($permission_label_ids,$permission_labels);

    // Permission labels with their permissions
    $permissions = $this->_get_permissions_grouped_by_label();

    $roles_permission = $this->_get_roles_with_permission();
    
    return array('permission_labels'=>$permission_labels_with_id_keys,'permissions'=>$permissions,'roles_permission'=>$roles_permission);
  }

  private function _get_permission_labels(){
    $this->read_db->select(array('permission_label_id','permission_label_name'));
    return $this->read_db->get('permission_label')->result_array();
  }

  private function _get_permissions_grouped_by_label(){

    $this->read_db->select(array('permission_label_id','permission_label_name','permission_id','permission_name','menu_id','menu_name','permission_type'));
    $this->read_db->join('permission_label','permission_label.permission_label_id=permission.fk_permission_label_id');
    $this->read_db->join('menu','menu.menu_id=permission.fk_menu_id');
    $permissions = $this->read_db->get_where('permission',array('permission_is_active'=>1))->result_array();

    $order_array = [];

    foreach($permissions as $permission){
      $permission_label_id = array_shift($permission);
      $order_array[$permission_label_id][] = $permission;
    }

    return $order_array;
  }

  function _get_roles_with_permission(){
    $this->read_db->select(array('fk_permission_id','role_id','role_name','role_permission_is_active'));
    $this->read_db->join('role','role.role_id=role_permission.fk_role_id');
    $roles_with_permission = $this->read_db->get('role_permission')->result_array();

    $ordered_array = [];

    foreach($roles_with_permission as $role_with_permission){
      $permission_id = array_shift($role_with_permission);
      $ordered_array[$permission_id][] = $role_with_permission;
    }

    return $ordered_array;

  }

  function get_available_roles_for_permission(){
    
    $permission_id  = $this->input->post('permission_id');

    // Used roles in the current permission
    $this->read_db->select(array('fk_role_id as role_id'));
    $used_roles_obj = $this->read_db->get_where('role_permission',
    array('fk_permission_id'=>$permission_id));

    $this->read_db->select(array('role_id','role_name'));
    if($used_roles_obj->num_rows() > 0){
      $this->read_db->where_not_in('role_id',array_column($used_roles_obj->result_array(),'role_id'));
    }
    
    $role = $this->read_db->get_where('role',array('role_is_active'=>1))->result_object();

    $ids = array_column($role,'role_id');
    $names = array_column($role,'role_name');
    $select_array = array_combine($ids,$names);

    echo select_element($select_array,get_phrase('available_roles'),['available-roles'],$permission_id);
    
  }

  function create_a_new_role_permission(){
    $post = $this->input->post();
    
    $permission = $this->read_db->get_where('permission',array('permission_id'=>$post['permission_id']))->row();

    $this->write_db->trans_start();

    $roles_permission_data['role_permission_name'] = $permission->permission_name.' permission assigned to '.$post['role_name']. 'role';
    $roles_permission_data['role_permission_is_active'] = 1;
    $roles_permission_data['fk_role_id'] = $post['role_id'];
    $roles_permission_data['fk_permission_id'] = $post['permission_id'];
    
    $roles_permission_data_to_insert = $this->grants_model->merge_with_history_fields('role_permission',$roles_permission_data,false);

    $this->write_db->insert('role_permission',$roles_permission_data_to_insert);

    $this->write_db->trans_complete();

    if($this->write_db->trans_status() == false){
      echo false;
    }else{
      echo true;
    }

  }

  function update_role_permission(){
    $post = $this->input->post();

    $this->write_db->trans_start();

    $this->write_db->where(array('fk_role_id'=>$post['role_id'],'fk_permission_id'=>$post['permission_id']));
    $role_permission_data = array('role_permission_is_active'=>$post['role_permission_is_active']);
    $this->write_db->update('role_permission',$role_permission_data);

    $this->write_db->trans_complete();

    if($this->write_db->trans_status() == false){
      echo "Update failure";
    }else{
      echo "Update successful";
    }
  }

  static function get_menu_list(){

  }

}
