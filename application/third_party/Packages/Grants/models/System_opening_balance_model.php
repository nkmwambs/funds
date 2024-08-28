<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class System_opening_balance_model extends MY_Model
{

    public $table = 'system_opening_balance';
    public $dependant_table = '';
    public $name_field = 'system_opening_balance_name';
    public $create_date_field = "system_opening_balance_created_date";
    public $created_by_field = "system_opening_balance_created_by";
    public $last_modified_date_field = "system_opening_balance_last_modified_date";
    public $last_modified_by_field = "system_opening_balance_last_modified_by";
    public $deleted_at_field = "system_opening_balance_deleted_at";

    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    function index()
    {
    }

    public function lookup_tables()
    {
        return array('office');
    }

    function show_add_button()
    {
        if (!$this->session->system_admin) {
            return false;
        } else {
            return true;
        }
    }

    // public function detail_tables(){
    //     return ['opening_fund_balance','opening_cash_balance','opening_bank_balance','opening_allocation_balance','opening_outstanding_cheque','opening_deposit_transit'];
    // }

    public function detail_multi_form_add_visible_columns()
    {
    }

    function single_form_add_visible_columns()
    {
        return ['system_opening_balance_name', 'month', 'office_name'];
    }

    function list_table_where()
    {
        if (!$this->session->system_admin) {

            $hierarchy_offices = array_column($this->session->hierarchy_offices, 'office_id');
            $this->read_db->where_in('fk_office_id', $hierarchy_offices);
            $this->read_db->where(array('office.office_is_readonly' => 0));
            return $this->read_db->where(array('office.fk_account_system_id' => $this->session->user_account_system_id));
        }
    }

    function get_system_opening_balance_office_bank_ids($system_opening_balance_id){
        
        $this->read_db->select(array('office_bank_id'));
        $this->read_db->join('office','office.office_id=office_bank.fk_office_id');
        $this->read_db->join('system_opening_balance','system_opening_balance.fk_office_id=office.office_id');
        $office_banks = $this->read_db->get_where('office_bank',
        ['system_opening_balance_id' =>$system_opening_balance_id])->result_array();
  
        $office_bank_ids = array_column($office_banks,'office_bank_id');
  
        return $office_bank_ids;
      }
}