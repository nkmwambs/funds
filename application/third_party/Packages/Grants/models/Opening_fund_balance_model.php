<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Opening_fund_balance_model extends MY_Model{

    public $table = 'opening_fund_balance'; 
    public $dependant_table = '';
    public $name_field = 'opening_fund_balance_name';
    public $create_date_field = "opening_fund_balance_created_date";
    public $created_by_field = "opening_fund_balance_created_by";
    public $last_modified_date_field = "opening_fund_balance_last_modified_date";
    public $last_modified_by_field = "opening_fund_balance_last_modified_by";
    public $deleted_at_field = "opening_fund_balance_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    function edit_visible_columns(){
        return [
                'opening_fund_balance_name',
                'opening_fund_balance_amount',
                'project_name',
                'income_account_name',
                'office_bank_name',
                'system_opening_balance_name'
            ];
    }

    function action_after_edit($post_array, $approval_id, $header_id){
        $this->write_db->trans_start();

        $project_id = $post_array['fk_project_id'];
        $system_opening_balance_id = $post_array['fk_system_opening_balance_id'];
        $opening_fund_balance_amount = $post_array['opening_fund_balance_amount'];

        $this->read_db->select(array('opening_allocation_balance_id','project_name','project_end_date','project_allocation_id'));
        $this->read_db->join('project_allocation','project_allocation.fk_project_id=project.project_id');
        $this->read_db->join('office','office.office_id=project_allocation.fk_office_id');
        $this->read_db->join('system_opening_balance','system_opening_balance.fk_office_id=office.office_id');
        $this->read_db->join('opening_allocation_balance','opening_allocation_balance.fk_project_allocation_id=project_allocation.project_allocation_id');
        $this->read_db->where(array('system_opening_balance_id' => $system_opening_balance_id));
        $this->read_db->where(array('project_id' => $project_id));
        $project_obj = $this->read_db->get('project');

        if($project_obj->num_rows() > 0){

            $this->load->model('opening_allocation_balance_model');

            $project = $project_obj->row();

            $update_data['opening_allocation_balance_amount'] = $opening_fund_balance_amount;
            $update_data['opening_allocation_balance_name'] = $project->project_name;
            
            $this->opening_allocation_balance_model->edit_opening_allocation_balance($project->opening_allocation_balance_id, $update_data);
        }


        $this->write_db->trans_complete();

        if ($this->write_db->trans_status() === FALSE) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @todo:
     * Awaiting documentation
     */

    function get_project_for_office_using_system_opening_balance_id_by_project_id($project_id, $system_opening_balance_id){
        // This code must read from write node for it to work
        $this->write_db->select(array('project_name','project_end_date','project_allocation_id'));
        $this->write_db->join('project_allocation','project_allocation.fk_project_id=project.project_id');
        $this->write_db->join('office','office.office_id=project_allocation.fk_office_id');
        $this->write_db->join('system_opening_balance','system_opening_balance.fk_office_id=office.office_id');
        $this->write_db->where(array('system_opening_balance_id' => $system_opening_balance_id));
        $this->write_db->where(array('project_id' => $project_id));
        $project_obj = $this->write_db->get('project');

        return $project_obj;
    }

    /**
     * @todo:
     * Awaiting documentation
     */

    function action_after_insert($post_array, $approval_id, $header_id){

        $this->write_db->trans_start();

        $project_id = $post_array['fk_project_id'];
        $system_opening_balance_id = $post_array['fk_system_opening_balance_id'];
        $opening_fund_balance_amount = $post_array['opening_fund_balance_amount'];

        $project_obj = $this->get_project_for_office_using_system_opening_balance_id_by_project_id($project_id, $system_opening_balance_id);

        // log_message('error', json_encode($project_obj->num_rows()));
        
        if($project_obj->num_rows() == 0){
            // log_message('error', 10);
            // log_message('error', json_encode($project_obj->row()));

            // Create a Project allocation if missing. Should happen before the flow proceed
            $this->read_db->where(array('system_opening_balance_id' => $system_opening_balance_id));
            $office_id = $this->read_db->get('system_opening_balance')->row()->fk_office_id;

            $allocation_data['project_allocation_track_number'] = $this->grants_model->generate_item_track_number_and_name('opening_fund_balance')['opening_fund_balance_track_number'];
            $allocation_data['project_allocation_name'] = $this->grants_model->generate_item_track_number_and_name('opening_fund_balance')['opening_fund_balance_name'];
            $allocation_data['fk_project_id'] = $project_id;
            $allocation_data['project_allocation_amount'] = 0;
            $allocation_data['project_allocation_is_active'] = 1;
            $allocation_data['fk_office_id'] = $office_id;
            $allocation_data['project_allocation_extended_end_date'] = NULL;
            $allocation_data['project_allocation_created_date'] = date('Y-m-d');
            $allocation_data['project_allocation_created_by'] =  $this->session->user_id;
            $allocation_data['project_allocation_last_modified_by'] = $this->session->user_id;
            $allocation_data['fk_status_id'] = $this->grants_model->initial_item_status('opening_fund_balance');

            $this->write_db->insert('project_allocation', $allocation_data);

            // Resetting the $project_obj object after insert
            $project_obj = $this->get_project_for_office_using_system_opening_balance_id_by_project_id($project_id, $system_opening_balance_id);

            $project = $project_obj->row();

            // log_message('error', json_encode($project));

            $is_individual_intervention = $project->project_end_date != '0000-00-00' && $project->project_end_date != NULL ? true : false;
        
            if($is_individual_intervention){
                $this->load->model('opening_allocation_balance_model');
            
                $opening_allocation_balance['system_opening_balance_id'] =  $system_opening_balance_id;
                $opening_allocation_balance['project_allocation_id'] = $project->project_allocation_id;
                $opening_allocation_balance['opening_allocation_balance_name'] = $project->project_name;
                $opening_allocation_balance['opening_allocation_balance_amount'] = $opening_fund_balance_amount;
            
                $this->opening_allocation_balance_model->create_opening_allocation_balance($opening_allocation_balance);
            }

        }else{
            // log_message('error', 20);
            $project = $project_obj->row();

            $is_individual_intervention = $project->project_end_date != '0000-00-00' && $project->project_end_date != NULL ? true : false;
        
            if($is_individual_intervention){
                $this->load->model('opening_allocation_balance_model');
                
                // This is a code repeat
                $opening_allocation_balance['system_opening_balance_id'] =  $system_opening_balance_id;
                $opening_allocation_balance['project_allocation_id'] = $project->project_allocation_id;
                $opening_allocation_balance['opening_allocation_balance_name'] = $project->project_name;
                $opening_allocation_balance['opening_allocation_balance_amount'] = $opening_fund_balance_amount;
            
                $this->opening_allocation_balance_model->create_opening_allocation_balance($opening_allocation_balance);
            }
        }

        // return $post_array;

        $this->write_db->trans_complete();

        if ($this->write_db->trans_status() === FALSE) {
            return false;
        } else {
            return true;
        }
    }

    // function action_before_insert($post_array){

    //     $project_id = $post_array['header']['fk_project_id'];
    //     $system_opening_balance_id = $post_array['header']['fk_system_opening_balance_id'];
    //     $opening_fund_balance_amount = $post_array['header']['opening_fund_balance_amount'];

    //     $this->read_db->select(array('project_name','project_end_date','project_allocation_id'));
    //     $this->read_db->join('project_allocation','project_allocation.fk_project_id=project.project_id');
    //     $this->read_db->join('office','office.office_id=project_allocation.fk_office_id');
    //     $this->read_db->join('system_opening_balance','system_opening_balance.fk_office_id=office.office_id');
    //     $this->read_db->where(array('system_opening_balance_id' => $system_opening_balance_id));
    //     $this->read_db->where(array('project_id' => $project_id));
    //     $project = $this->read_db->get('project')->row();

    //     $is_individual_intervention = $project->project_end_date != '0000-00-00' ? true : false;

    //     if($is_individual_intervention){
    //         $this->load->model('opening_allocation_balance_model');

    //         $opening_allocation_balance['system_opening_balance_id'] =  $system_opening_balance_id;
    //         $opening_allocation_balance['project_allocation_id'] = $project->project_allocation_id;
    //         $opening_allocation_balance['opening_allocation_balance_name'] = $project->project_name;
    //         $opening_allocation_balance['opening_allocation_balance_amount'] = $opening_fund_balance_amount;

    //         $this->opening_allocation_balance_model->create_opening_allocation_balance($opening_allocation_balance);
    //     }

    //     return $post_array;
    // }

    function action_before_edit($post_array){

        $error_array = check_submitted_financial_report_error_message($post_array);
        
        return $error_array;
    }

    public function lookup_tables(){
        return ['system_opening_balance','income_account','office_bank','status'];
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}

    function detail_list_table_visible_columns(){
        return ['opening_fund_balance_track_number','opening_fund_balance_name','income_account_name','opening_fund_balance_amount','office_bank_name','status_name'];
    }

    function lookup_values_where(){
        return [
            'income_account'=>['income_account_is_donor_funded'=>0,'income_account_is_active'=>1]
        ];
    }

    // function transaction_validate_duplicates_columns(){
    //     return ['fk_system_opening_balance_id','fk_income_account_id'];
    // }

    function lookup_values()
    {
        $lookup_values = parent::lookup_values();

        
        // Office Banks
        $this->read_db->select(array('office_bank_id','office_bank_name', 'office_start_date'));
        
        $this->read_db->join('office','office.office_id=office_bank.fk_office_id');
        $this->read_db->join('system_opening_balance','system_opening_balance.fk_office_id=office.office_id');
        
        if($this->action== 'edit'){
            $this->read_db->join('opening_fund_balance','opening_fund_balance.fk_system_opening_balance_id=system_opening_balance.system_opening_balance_id');
            $this->read_db->where(array('opening_fund_balance_id'=>hash_id($this->id,'decode')));
        }else{
            $this->read_db->where(array('system_opening_balance_id'=>hash_id($this->id,'decode')));
        }
        
        $office_bank =  $this->read_db->get('office_bank')->result_array();

        // Projects
        $this->read_db->select(array('project_id','project_name'));
        $this->read_db->join('project_allocation','project_allocation.fk_project_id=project.project_id');
        $this->read_db->join('office','office.office_id=project_allocation.fk_office_id');
        $this->read_db->join('system_opening_balance','system_opening_balance.fk_office_id=office.office_id');
        if(!$this->session->system_admin){
            // $query_condition = " ((project_end_date >= '".$office_bank['office_start_date']."' OR project_end_date = '0000-00-00' OR project_end_date = NULL) OR  project_allocation_extended_end_date >= '".$office_bank['office_start_date']."')";
            $this->read_db->join('funder','funder.funder_id=project.fk_funder_id');
            $this->read_db->where(array('funder.fk_account_system_id' => $this->session->user_account_system_id));
        }
        $project = $this->read_db->get('project')->result_array();

        // Income Accounts
        // if($this->action== 'edit'){
        //     $this->read_db->select(array('income_account_id','income_account_name'));
        //     $this->read_db->join('project_income_account','project_income_account.fk_income_account_id=income_account.income_account_id');
        //     $this->read_db->join('project','project.project_id=project_income_account.fk_project_id');
        //     $this->read_db->join('opening_fund_balance','opening_fund_balance.fk_project_id=project.project_id');
        //     $this->read_db->where(array('opening_fund_balance_id' => hash_id($this->id, 'decode')));
        //     $income_acount = $this->read_db->get('income_account')->result_array();

        //     // log_message('error', json_encode($income_acount));

        //     $lookup_values['income_acount'] = $income_acount;
        // }

        $lookup_values['office_bank'] = $office_bank;
        $lookup_values['project'] = $project;

        return $lookup_values;
    }

    function transaction_validate_duplicates_columns(){
        return ["fk_income_account_id","fk_project_id", 'fk_office_bank_id','opening_fund_balance_amount'];
    }

    function list_table_where(){
        if(!$this->session->system_admin){
            $office_ids = array_column($this->session->hierarchy_offices,'office_id');
            $this->read_db->where_in('system_opening_balance.fk_office_id',$office_ids);
        }
      }

      function columns(){
        $columns = [
          'opening_fund_balance_track_number',
          'opening_fund_balance_name',
          'income_account_name',
          'office_bank_name',
          'project_name',
          'opening_fund_balance_amount'
        ];
    
        return $columns;
    }

    function single_form_add_visible_columns(){
        return [
            'opening_fund_balance_name',
            'opening_fund_balance_amount',
            'project_name',
            'income_account_name',
            'office_bank_name',
            'system_opening_balance_name'
        ];
    }

    function get_office_project_id_for_an_income_account($office_id){

        $fund_balance_income_account_ids = [];
        $system_opening_balance_id = 0;

        $this->read_db->select(['fk_income_account_id as income_account_id', 'system_opening_balance_id']);
        $this->read_db->where(array('system_opening_balance.fk_office_id' => $office_id));
        $this->read_db->join('system_opening_balance','system_opening_balance.system_opening_balance_id=opening_fund_balance.fk_system_opening_balance_id');
        $income_account_ids_obj = $this->read_db->get('opening_fund_balance');

        if($income_account_ids_obj){
            $income_account_ids_array = $income_account_ids_obj->result_array();
            $fund_balance_income_account_ids = array_column($income_account_ids_array, 'income_account_id');
            $system_opening_balance_id = isset($income_account_ids_array[0]['system_opening_balance_id']) ? $income_account_ids_array[0]['system_opening_balance_id'] : 0;
        }

        $project_income_account_ids_raw = [];
        $project_income_account_ids = [];

        $this->read_db->select(array('project_income_account.fk_income_account_id as income_account_id', 'project.project_id as project_id'));
        $this->read_db->join('project','project.project_id=project_income_account.fk_project_id');
        $this->read_db->join('project_allocation','project_allocation.fk_project_id=project.project_id');
        $this->read_db->where(array('project_allocation.fk_office_id' => $office_id));
        $project_income_account_obj = $this->read_db->get('project_income_account');

        if($project_income_account_obj){
            $project_income_account_ids_raw = $project_income_account_obj->result_array();
        }

        if(count($project_income_account_ids_raw) > 0){
            foreach($project_income_account_ids_raw as $project_income_account_id){
                $project_income_account_ids[$project_income_account_id['income_account_id']][$project_income_account_id['project_id']] = $project_income_account_id['project_id'];
            }
        }

        $count_of_updates = 0;

        if(count($fund_balance_income_account_ids) > 0){
            foreach( $fund_balance_income_account_ids as  $fund_balance_income_account_id){
                
                $data['fk_project_id'] = 0;

                if(array_key_exists($fund_balance_income_account_id, $project_income_account_ids)){
                    $data['fk_project_id'] = end($project_income_account_ids[$fund_balance_income_account_id]);
                }
                
                $this->write_db->where(array('fk_system_opening_balance_id' => $system_opening_balance_id, 
                'fk_income_account_id' => $fund_balance_income_account_id));
                $this->write_db->update('opening_fund_balance', $data);

                $count_of_updates++;
            }
        }

        return $count_of_updates;
    }
}