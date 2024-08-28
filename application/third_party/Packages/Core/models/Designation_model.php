<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Designation_model extends MY_Model{

    public $table = 'designation'; 
    public $dependant_table = '';
    public $name_field = 'designation_name';
    public $create_date_field = "designation_created_date";
    public $created_by_field = "designation_created_by";
    public $last_modified_date_field = "designation_last_modified_date";
    public $last_modified_by_field = "designation_last_modified_by";
    public $deleted_at_field = "designation_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array('context_definition');
    }

    public function detail_tables(){}

    function intialize_table(Array $foreign_keys_values = []){  

        $context_definitions = $this->config->item('context_definitions');
        $global_context_key = count($context_definitions) + 1;

        $designation_data['designation_track_number'] = $this->grants_model->generate_item_track_number_and_name('designation')['designation_track_number'];
        $designation_data['designation_name'] = 'Super System Administrator';
        $designation_data['fk_context_definition_id'] = 6;//$global_context_key;
      
        $designation_data_to_insert = $this->grants_model->merge_with_history_fields('designation',$designation_data,false);
        $this->write_db->insert('designation',$designation_data_to_insert);
    
        return $this->write_db->insert_id();
    }

    /**
   * Get List of Designation 
   * 
   * This method retrives design e.g. Country Administrator, project staff, Partnership 
   * @return Array - array
   * @Author :Livingstone Onduso
   * @Date: 09/09/2022
   */

   function retrieve_designations($context_definition_id){

    $this->read_db->select(array('designation_id', 'designation_name'));
    $this->read_db->where(['fk_context_definition_id'=>$context_definition_id]);
    $designations=$this->read_db->get('designation')->result_array();

    $designations_ids=array_column($designations,'designation_id');

    $designations_names=array_column($designations,'designation_name');

    $designations_ids_and_names=array_combine($designations_ids,$designations_names);

    return  $designations_ids_and_names;
   }
 
}