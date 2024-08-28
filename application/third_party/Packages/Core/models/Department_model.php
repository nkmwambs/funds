<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Department_model extends MY_Model{

    public $table = 'department'; 
    public $dependant_table = '';
    public $name_field = 'department_name';
    public $create_date_field = "department_created_date";
    public $created_by_field = "department_created_by";
    public $last_modified_date_field = "department_last_modified_date";
    public $last_modified_by_field = "department_last_modified_by";
    public $deleted_at_field = "department_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array('approval','status','context_definition');
    }

    /**
   * single_form_add_visible_columns
   * 
   * Visible or selected columns to the single_form_add action page
   * 
   * @return Array
   */

  function single_form_add_visible_columns(): array
  {
    return array(
      'department_name', 'department_description', 'context_definition_name', 'department_is_active',
    );
  }


    public function detail_tables(){
        return array('department_user');
    }

    function intialize_table(Array $foreign_keys_values = []){
        
        $department_data['department_id'] = 1;
        $department_data['department_track_number'] = $this->grants_model->generate_item_track_number_and_name('department')['department_track_number'];
        $department_data['department_name'] = "Administration";
        $department_data['department_description'] = "Administration";
        $department_data['department_is_active'] = 1;
        
        $department_data_to_insert = $this->grants_model->merge_with_history_fields('account_system',$department_data,false);
        $this->write_db->insert('department',$department_data_to_insert);

        return $this->write_db->insert();
    }

  /**
   * Get List of Departments 
   * 
   * This method retrives departments e.g. program Support, church partner, Partnership
   * @return Array - array
   * @Author :Livingstone Onduso
   * @Date: 08/09/2022
   */

   function retrieve_departments($context_definition){

    $this->read_db->select(array('department_id', 'department_name'));
    $this->read_db->where(array('department_is_active'=>1,'fk_context_definition_id'=>$context_definition));
    $departments=$this->read_db->get('department')->result_array();

    $departments_ids=array_column($departments,'department_id');

    $departments_names=array_column($departments,'department_name');

    $departments_ids_and_names=array_combine($departments_ids,$departments_names);

    return $departments_ids_and_names;
   }

   /**
   * Get List of Department's user 
   * This method retrives department's user
   * @return Array - array
   * @Author :Livingstone Onduso
   * @Date: 23/09/2022
   */

  function retrieve_user_department($user_id){

    // $this->read_db->select(array('department_id', 'department_name'));
    // $this->read_db->join('department','department.department_id=department_user.fk_department_id');
    // $this->read_db->where(array('fk_user_id'=>$user_id));
    // $departments=$this->read_db->get('department_user')->result_array();

    // $departments_ids=array_column($departments,'department_id');

    // $departments_names=array_column($departments,'department_name');

    // $departments_ids_and_names=array_combine($departments_ids,$departments_names);

    $this->read_db->select(array('department_id','department_name'));
    $this->read_db->join('department_user','department_user.fk_department_id=department.department_id');
    $result = $this->read_db->get_where('department',array('department_is_active'=>1,'fk_user_id'=>$user_id))->result_array();

    return $result;
   }
}