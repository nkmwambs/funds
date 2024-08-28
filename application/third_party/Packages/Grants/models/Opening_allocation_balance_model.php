<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Opening_allocation_balance_model extends MY_Model{

    public $table = 'opening_allocation_balance'; 
    public $dependant_table = '';
    public $name_field = 'opening_allocation_balance_name';
    public $create_date_field = "opening_allocation_balance_created_date";
    public $created_by_field = "opening_allocation_balance_created_by";
    public $last_modified_date_field = "opening_allocation_balance_last_modified_date";
    public $last_modified_by_field = "opening_allocation_balance_last_modified_by";
    public $deleted_at_field = "opening_allocation_balance_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array(
            'project_allocation',
            'system_opening_balance',
        );
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}

    function detail_list_table_visible_columns(){
        return ['opening_allocation_balance_track_number','opening_allocation_balance_name','project_allocation_name','opening_allocation_balance_amount'];
    }

    function opening_allocation_balance_project_allocation_id(){
        //echo hash_id($this->id,'decode');exit;
        $this->read_db->select(array('fk_project_allocation_id'));
        $result = $this->read_db->get_where('opening_allocation_balance',
        array('opening_allocation_balance_id'=>hash_id($this->id,'decode')))->row()->fk_project_allocation_id;

        return $result;
    }

    function lookup_values_where($table = ''){
        return [
                 'project_allocation'=>['project_allocation_id'=>$this->opening_allocation_balance_project_allocation_id()]
               ];
      }

     function lookup_values(){
         $lookup_values = parent::lookup_values();
         
         if($this->id !== null){

            if($this->action  == 'single_form_add'){
                // $system_opening_balance = $this->read_db->get_where('system_opening_balance',
                // array('system_opening_balance_id'=>hash_id($this->id,'decode')))->row();
                
                // $this->read_db->join('opening_allocation_balance','opening_allocation_balance.fk_project_allocation_id=project_allocation.project_allocation_id');
                // $this->read_db->join('system_opening_balance','system_opening_balance.system_opening_balance_id=opening_allocation_balance.fk_system_opening_balance_id');

                // $this->read_db->where(array('system_opening_balance_id'=>hash_id($this->id,'decode'),
                // 'project_end_date<>'=>'0000-00-00'));

                $lookup_values['system_opening_balance'] = $this->read_db->get_where('system_opening_balance',
                array('system_opening_balance_id'=>hash_id($this->id,'decode')))->result_array();

                $this->read_db->where(array('fk_office_id'=>$lookup_values['system_opening_balance'][0]['fk_office_id']));

                $condition = "project_end_date NOT LIKE '0000-00-00' OR project_end_date IS NOT NULL"; // ONDUSO Added: 'OR project_end_date NOT LIKE NULL' due nulling
                $this->read_db->where($condition);

            }elseif($this->action  == 'edit'){

                $this->read_db->join('opening_allocation_balance','opening_allocation_balance.fk_system_opening_balance_id=system_opening_balance.system_opening_balance_id');
                $lookup_values['system_opening_balance'] = $this->read_db->get_where('system_opening_balance',
                array('opening_allocation_balance_id'=>hash_id($this->id,'decode')))->result_array();

                $this->read_db->join('opening_allocation_balance','opening_allocation_balance.fk_project_allocation_id=project_allocation.project_allocation_id');
                $this->read_db->where(array('opening_allocation_balance_id'=>hash_id($this->id,'decode')));

                $condition = "project_end_date NOT LIKE '0000-00-00' OR project_end_date IS NOT NULL";
                $this->read_db->where($condition);
            }
           
            
            
            $this->read_db->group_start();
                $this->read_db->where(array('project_allocation_extended_end_date>='=>date('Y-m-t')));
                $this->read_db->or_where(array('project_end_date>='=>date('Y-m-t')));
            $this->read_db->group_end();

            $this->read_db->join('project','project.project_id=project_allocation.fk_project_id');
            $lookup_values['project_allocation']= $this->read_db->get('project_allocation')->result_array();
            
         }

         return $lookup_values;
         
     } 

     function list_table_where(){
        if(!$this->session->system_admin){
            $office_ids = array_column($this->session->hierarchy_offices,'office_id');
            $this->read_db->where_in('system_opening_balance.fk_office_id',$office_ids);
        }
      }

      function columns(){
          $columns = [
            'opening_allocation_balance_track_number',
            'opening_allocation_balance_name',
            'project_allocation_name',
            'opening_allocation_balance_amount'
          ];

          return $columns;
      }

      function create_opening_allocation_balance($data) {

        $balance['fk_system_opening_balance_id'] = $data['system_opening_balance_id'];
        $balance['opening_allocation_balance_track_number'] = $this->grants_model->generate_item_track_number_and_name('opening_allocation_balance')['opening_allocation_balance_track_number'];
        $balance['opening_allocation_balance_name'] = $data['opening_allocation_balance_name'];
        $balance['fk_project_allocation_id'] = $data['project_allocation_id'];
        $balance['opening_allocation_balance_amount'] = $data['opening_allocation_balance_amount'];
        $balance['opening_allocation_balance_created_date'] = date('Y-m-d');
        $balance['opening_allocation_balance_created_by'] = $this->session->user_id;
        $balance['opening_allocation_balance_last_modified_by'] = $this->session->user_id;
        $balance['opening_allocation_balance_last_modified_date'] = date('Y-m-d h:i:s');
        $balance['fk_status_id'] = $this->grants_model->initial_item_status('opening_allocation_balance');

        $this->write_db->insert('opening_allocation_balance', $balance);

      }

      function edit_opening_allocation_balance($opening_allocation_balance_id, $update_data){
            $this->write_db->where(array('opening_allocation_balance_id' => $opening_allocation_balance_id));
            $this->write_db->update('opening_allocation_balance', $update_data);
      }

      function show_add_button(){
        return false;
      }
}