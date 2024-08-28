<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Office_bank_project_allocation_model extends MY_Model{

    public $table = 'office_bank_project_allocation'; 
    public $dependant_table = '';
    public $name_field = 'office_bank_project_allocation_name';
    public $create_date_field = "office_bank_project_allocation_created_date";
    public $created_by_field = "office_bank_project_allocation_created_by";
    public $last_modified_date_field = "office_bank_project_allocation_last_modified_date";
    public $last_modified_by_field = "office_bank_project_allocation_last_modified_by";
    public $deleted_at_field = "office_bank_project_allocation_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array('office_bank','project_allocation');
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}

    function office_bank_project_allocation_office_id(){
        $result = $this->read_db->get_where('office_bank',
        array('office_bank_id'=>hash_id($this->id,'decode')))->row()->fk_office_id;

        return $result;
    }

    public function detail_list_table_visible_columns(){
        return [
            'office_bank_project_allocation_track_number',
            'office_bank_name',
            'project_allocation_name',
            'office_bank_project_allocation_created_date'
        ];
    }

    // public function lookup_values_where(){
    //     return [
    //         'project_allocation'=>['fk_office_id'=>$this->office_bank_project_allocation_office_id()]
    //     ];
    // }

    function single_form_add_visible_columns(){
        return ['office_bank_name','project_allocation_name'];
    }

    function lookup_values(){

        $lookup_values = parent::lookup_values();

        if($this->id !== null && $this->sub_action != null){

            // Get office id for the project allocation
            
            $office_id = 0;

            if($this->sub_action == 'project_allocation'){
                $this->read_db->where(array('project_allocation_id' => hash_id($this->id, 'decode')));
                $office_id = $this->read_db->get('project_allocation')->row()->fk_office_id;
            }elseif($this->sub_action == 'office_bank'){
                $this->read_db->where(array('office_bank_id' => hash_id($this->id, 'decode')));
                $office_id = $this->read_db->get('office_bank')->row()->fk_office_id;
            }

            $this->read_db->where(array('fk_office_id' => $office_id));
            // $this->read_db->where(array('office_bank_is_active' => 1));
            $lookup_values['office_bank'] = $this->read_db->get('office_bank')->result_array();
            
            $this->read_db->select(array('project_allocation_id', 'project_name as project_allocation_name'));
            $this->read_db->join('project', 'project.project_id=project_allocation.fk_project_id');
            $this->read_db->where(array('fk_office_id' => $office_id));
            $lookup_values['project_allocation']  = $this->read_db->get('project_allocation')->result_array(); 
            
            
        }
        return $lookup_values;
      }

    function multi_select_field()
    {
        return "project_allocation";
    }  

    function show_add_button(){
        if($this->config->item('link_new_project_allocations_only_to_default_bank_accounts') || $this->session->system_admin){
            return true;
        }else{
            return false;
        }
    }

    function transaction_validate_duplicates_columns(){
        // if(!$this->config->item('allow_project_allocation_to_multiple_bank_accounts')){
            return ['fk_office_bank_id','fk_project_allocation_id'];
        // }
    }

    function get_office_project_allocation_without_office_bank_linkage(){
        $offices = array_column($this->session->hierarchy_offices,'office_id');

        $this->read_db->select(array('project_allocation_id','project_allocation_name','project_name','office_id','office_name'));
        $this->read_db->where('NOT EXISTS (SELECT * FROM office_bank_project_allocation WHERE office_bank_project_allocation.fk_project_allocation_id=project_allocation.project_allocation_id)', '', FALSE);
        $this->read_db->where_in('project_allocation.fk_office_id',$offices);
        $this->read_db->join('project','project.project_id=project_allocation.fk_project_id');
        $this->read_db->join('office','office.office_id=project_allocation.fk_office_id');
        $project_allocation = $this->read_db->get('project_allocation')->result_array();

        return $project_allocation;
    }

    // function add(){
    //     return json_encode($this->input->post());
    // }

    function add(){
        $post = $this->input->post();
        $message =  get_phrase('insert_successful');

        if(array_key_exists('header',$post)){

            $message = $this->grants_model->add();

        }else{
            // This is for autmatic creation of association records without an add form
            $condition = array('fk_office_bank_id'=>$post['fk_office_bank_id'],
            'fk_project_allocation_id'=>$post['fk_project_allocation_id']);

            $this->read_db->where($condition);
            $office_bank_project_allocation = $this->read_db->get('office_bank_project_allocation');

            $this->write_db->trans_start();

            $data['office_bank_project_allocation_name'] = $this->grants_model->generate_item_track_number_and_name('office_bank_project_allocation')['office_bank_project_allocation_name'];;
            $data['office_bank_project_allocation_track_number'] = $this->grants_model->generate_item_track_number_and_name('office_bank_project_allocation')['office_bank_project_allocation_track_number'];;
            $data['fk_office_bank_id'] = $post['fk_office_bank_id'];
            $data['fk_project_allocation_id'] = $post['fk_project_allocation_id'];
            $data['office_bank_project_allocation_created_date'] = date('Y-m-t');
            $data['office_bank_project_allocation_created_by'] = $this->session->user_id;
            $data['office_bank_project_allocation_last_modified_by'] = $this->session->user_id;

            $data['fk_approval_id'] = $this->grants_model->insert_approval_record('office_bank_project_allocation');
            $data['fk_status_id'] = $this->grants_model->initial_item_status('office_bank_project_allocation');

            if($office_bank_project_allocation->num_rows() == 0){
                $this->write_db->insert('office_bank_project_allocation',$data);
            }else{
                $this->write_db->where($condition);
                $this->write_db->update('office_bank_project_allocation',$data);
            }
            

            $this->write_db->trans_complete();

            if($this->write_db->trans_status() == false){
            $message = get_phrase('insert_failed');
            }
        }
        return $message;
    }

}