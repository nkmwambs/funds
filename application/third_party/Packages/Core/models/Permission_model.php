<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Permission_model extends MY_Model
{
  public $table = 'permission'; // you MUST mention the table name


  function __construct(){
    parent::__construct();
    $this->load->database();

  }
  
  function delete($id = null){

  }
  
  function index(){

  }


  function list(){

  }

  function lookup_tables(){
    return array('menu','permission_label');
  }

  function detail_multi_form_add_visible_columns(){
    
  }

  function detail_tables(){
    return array('role_permission');
  }

  function view(){

  }

  function show_add_button(){
    // These items are automatically added by the system
    if($this->session->system_admin){
      return true;
    }
      
  }

  function add($data_array){
    
    $crud_operations = $this->read_db->get('permission_label')->result_object();


      foreach($crud_operations as $crud_operation){
        // Check if a permission exists before insert
        $permission_obj = $this->read_db->get_where('permission',
        array('fk_menu_id'=>$data_array['menu_id'],'fk_permission_label_id'=>$crud_operation->permission_label_id));

        if($permission_obj->num_rows() > 0) continue;

        $permission_data['permission_track_number'] = $this->grants_model->generate_item_track_number_and_name('permission')['permission_track_number'];
        $permission_data['permission_name'] = ucfirst($crud_operation->permission_label_name)." ".str_replace('_',' ',$data_array['table_name']);
        $permission_data['permission_description'] = ucfirst($crud_operation->permission_label_name)." ".str_replace('_',' ',$data_array['table_name']);
        $permission_data['permission_is_active'] = 1;
        $permission_data['fk_permission_label_id'] = $crud_operation->permission_label_id;
        $permission_data['permission_type'] = 1;// 1 = Page Access, 2 = Field Access
        $permission_data['permission_field'] = '';
        $permission_data['permission_is_global'] = 1;
        $permission_data['fk_menu_id'] = $data_array['menu_id'];//$menu_obj->row()->menu_id;
        
        $permission_data_to_insert = $this->grants_model->merge_with_history_fields('permission',$permission_data,false);
        
        // Check if the permission exists
        $permission_count = $this->write_db->get_where('permission',
        array('fk_menu_id'=>$data_array['menu_id'],
        'fk_permission_label_id'=>$crud_operation->permission_label_id))->num_rows();
        
        if($permission_count === 0){
          $this->write_db->insert('permission',$permission_data_to_insert);
        }
        
      }
    

    return $this->write_db->insert();
}

}
