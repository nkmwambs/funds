<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Permission_label_model extends MY_Model
{
  public $table = 'permission_label'; // you MUST mention the table name
  //public $dependant_table = "permission";


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
    
  }

  function detail_tables(){
    return array('permission');
  }

  function view(){

  }

  function show_add_button(){
    return false;
  }

  function intialize_table(Array $foreign_keys_values = []){ 
    
    $labels = [2=>'create',1=>'read',3=>'update',4=>'delete'];

    $cnt = 0;

    foreach($labels as $depth => $label){
      $permission_label_data[$cnt]['permission_label_track_number'] = $this->grants_model->generate_item_track_number_and_name('permission_label')['permission_label_track_number'];
      $permission_label_data[$cnt]['permission_label_name'] = $label;
      $permission_label_data[$cnt]['permission_label_description'] = 'Label for '.$label.' permissions';
      $permission_label_data[$cnt]['permission_label_depth'] = $depth;
      $permission_label_data[$cnt]['fk_approval_id'] = 0;
      $permission_label_data[$cnt]['fk_status_id'] = 0;
      $permission_label_data[$cnt]['permission_label_created_date'] = date('Y-m-d');
      $permission_label_data[$cnt]['permission_label_created_by'] = 1;
      $permission_label_data[$cnt]['permission_label_last_modified_date'] = date('Y-m-d h:i:s');
      $permission_label_data[$cnt]['permission_label_last_modified_by'] = 1;

      $cnt++;
    }

    
    $this->write_db->insert_batch('permission_label',$permission_label_data);

    return 2;
}

}
