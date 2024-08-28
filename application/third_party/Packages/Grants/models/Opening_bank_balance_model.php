<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Opening_bank_balance_model extends MY_Model
{

    public $table = 'opening_bank_balance';
    public $dependant_table = '';
    public $name_field = 'opening_bank_balance_name';
    public $create_date_field = "opening_bank_balance_created_date";
    public $created_by_field = "opening_bank_balance_created_by";
    public $last_modified_date_field = "opening_bank_balance_last_modified_date";
    public $last_modified_by_field = "opening_bank_balance_last_modified_by";
    public $deleted_at_field = "opening_bank_balance_deleted_at";

    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    function index()
    {
    }

    function action_before_edit($post_array){
    
        $error_array = check_submitted_financial_report_error_message($post_array);
        
        return $error_array;
        
    }

    public function lookup_tables()
    {
        return array('system_opening_balance', 'office_bank');
    }

    public function detail_tables()
    {
    }

    public function detail_multi_form_add_visible_columns()
    {
    }

    function lookup_values()
    {
        $lookup_values = parent::lookup_values();

        if ($this->id !== null) {

            if ($this->action == 'single_form_add') {
                $lookup_values['system_opening_balance'] = $this->read_db->get_where(
                    'system_opening_balance',
                    array('system_opening_balance_id' => hash_id($this->id, 'decode'))
                )->result_array();

                $this->read_db->join('office', 'office.office_id=office_bank.fk_office_id');
                $this->read_db->join('system_opening_balance', 'system_opening_balance.fk_office_id=office.office_id');

                $lookup_values['office_bank'] = $this->read_db->get_where(
                    'office_bank',
                    array('system_opening_balance_id' => hash_id($this->id, 'decode'))
                )->result_array();
            } else {

                $this->read_db->join('opening_bank_balance', 'opening_bank_balance.fk_system_opening_balance_id=system_opening_balance.system_opening_balance_id');
                $lookup_values['system_opening_balance'] = $this->read_db->get_where(
                    'system_opening_balance',
                    array('opening_bank_balance_id' => hash_id($this->id, 'decode'))
                )->result_array();


                $this->read_db->join('opening_bank_balance', 'opening_bank_balance.fk_office_bank_id=office_bank.office_bank_id');
                $lookup_values['office_bank'] = $this->read_db->get_where(
                    'office_bank',
                    array('opening_bank_balance_id' => hash_id($this->id, 'decode'))
                )->result_array();
            }
        }

        return $lookup_values;
    }

    function list_table_where()
    {
        if (!$this->session->system_admin) {
            $office_ids = array_column($this->session->hierarchy_offices, 'office_id');
            $this->read_db->where_in('system_opening_balance.fk_office_id', $office_ids);
        }
    }

    function columns(){
        $columns = [
            'opening_bank_balance_track_number',
            'opening_bank_balance_name',
            'office_bank_name',
            'opening_bank_balance_created_date',
            'opening_bank_balance_amount'
        ];

        return $columns;
    }
}