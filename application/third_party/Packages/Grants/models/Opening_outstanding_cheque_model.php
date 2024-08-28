<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Opening_outstanding_cheque_model extends MY_Model{

    public $table = 'opening_outstanding_cheque'; 
    public $dependant_table = '';
    public $name_field = 'opening_outstanding_cheque_name';
    public $create_date_field = "opening_outstanding_cheque_created_date";
    public $created_by_field = "opening_outstanding_cheque_created_by";
    public $last_modified_date_field = "opening_outstanding_cheque_last_modified_date";
    public $last_modified_by_field = "opening_outstanding_cheque_last_modified_by";
    public $deleted_at_field = "opening_outstanding_cheque_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    function action_before_edit($post_array){
    
        $error_array = check_submitted_financial_report_error_message($post_array);
        
        return $error_array;
        
    }

    public function lookup_tables(){
        return array('system_opening_balance','office_bank');
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}

    function single_form_add_visible_columns(){
        return [
                //'opening_outstanding_cheque_name',
                'opening_outstanding_cheque_description',
                'opening_outstanding_cheque_date',
                'system_opening_balance_name',
                'office_bank_name',
                'opening_outstanding_cheque_number',
                'opening_outstanding_cheque_amount',
            ];
    }

    function list_table_visible_columns()
    {
        return [
            'opening_outstanding_cheque_description',
            'opening_outstanding_cheque_date',
            'system_opening_balance_name',
            'office_bank_name',
            'opening_outstanding_cheque_number',
            'opening_outstanding_cheque_amount',
            'opening_outstanding_cheque_is_cleared',
            'opening_outstanding_cheque_cleared_date',
            'opening_outstanding_cheque_created_date'
        ];
    }

    function edit_visible_columns(){
        return [
                //'opening_outstanding_cheque_name',
                'opening_outstanding_cheque_description',
                'opening_outstanding_cheque_date',
                'system_opening_balance_name',
                'office_bank_name',
                'opening_outstanding_cheque_number',
                'opening_outstanding_cheque_amount',
            ];
    }

    function detail_list_table_visible_columns()
    {
        return [
            "opening_outstanding_cheque_track_number",
            'opening_outstanding_cheque_description',
            'opening_outstanding_cheque_date',
            'system_opening_balance_name',
            'office_bank_name',
            'opening_outstanding_cheque_number',
            'opening_outstanding_cheque_amount',
        ];
    }

    function transaction_validate_duplicates_columns(){
        return ['fk_office_bank_id','opening_outstanding_cheque_number'];
      }

    function lookup_values(){

        $lookup_values = parent::lookup_values();

        $this->read_db->select(array('office_bank_id','office_bank_name'));
        
        $this->read_db->join('office','office.office_id=office_bank.fk_office_id');
        $this->read_db->join('system_opening_balance','system_opening_balance.fk_office_id=office.office_id');
        
        if($this->action== 'edit'){
            $this->read_db->join('opening_outstanding_cheque','opening_outstanding_cheque.fk_system_opening_balance_id=system_opening_balance.system_opening_balance_id');
            $this->read_db->where(array('opening_outstanding_cheque_id'=>hash_id($this->id,'decode')));
        }else{
            $this->read_db->where(array('system_opening_balance_id'=>hash_id($this->id,'decode')));
        }
        
        $lookup_values['office_bank'] = $this->read_db->get('office_bank')->result_array();

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
            'opening_outstanding_cheque_track_number',
            'opening_outstanding_cheque_description',
            'opening_outstanding_cheque_date',
            'opening_outstanding_cheque_cleared_date',
            'office_bank_name',
            'opening_outstanding_cheque_number',
            'opening_outstanding_cheque_amount'
          ];

          return $columns;
      }
}