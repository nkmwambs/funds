<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Cheque_book_reset_model extends MY_Model
{

    public $table = 'cheque_book_reset';
    public $dependant_table = '';
    public $name_field = 'cheque_book_reset_name';
    public $create_date_field = "cheque_book_reset_created_date";
    public $created_by_field = "cheque_book_reset_created_by";
    public $last_modified_date_field = "cheque_book_reset_last_modified_date";
    public $last_modified_by_field = "cheque_book_reset_last_modified_by";
    public $deleted_at_field = "cheque_book_reset_deleted_at";

    function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('cheque_book_model');
    }

    function index()
    {
    }

    function action_before_insert($post_array)
    {
        $office_bank_id = $post_array['header']['fk_office_bank_id'];

        $this->read_db->where(array('fk_office_bank_id' => $office_bank_id, 'cheque_book_reset_is_active' => 1));
        $ctive_cheque_book_reset_obj = $this->read_db->get('cheque_book_reset');

        if ($ctive_cheque_book_reset_obj->num_rows() > 0) {
            //$this->write_db->where(array('fk_office_bank_id'=>$office_bank_id,'cheque_book_reset_is_active'=>1));
            //$this->write_db->update('cheque_book_reset',['cheque_book_reset_is_active'=>0]);
            $this->deactivate_cheque_book_reset($office_bank_id);
        }

        $this->cheque_book_model->deactivate_cheque_book($office_bank_id);

        return $post_array;
    }

    function deactivate_cheque_book_reset($office_bank_id)
    {

        $this->write_db->where(array('fk_office_bank_id' => $office_bank_id));
        $data['cheque_book_reset_is_active'] = 0;
        $this->write_db->update('cheque_book_reset', $data);
    }


    public function lookup_tables()
    {
        return array('office_bank', 'account_system');
    }

    public function detail_tables()
    {
    }

    public function detail_multi_form_add_visible_columns()
    {
    }

    function single_form_add_visible_columns()
    {
        return ['office_bank_name', 'cheque_book_reset_serial', 'item_reason_name'];
    }

    function edit_visible_columns()
    {
        return ['office_bank_name', 'cheque_book_reset_serial'];
    }

    function list_table_visible_columns()
    {
        return [
            'cheque_book_reset_track_number', 'office_bank_name',
            'cheque_book_reset_serial', 'cheque_book_reset_is_active', 'cheque_book_reset_created_date'
        ];
    }


    public function lookup_values()
    {
        $lookup_values = parent::lookup_values();

        $this->read_db->join('approve_item', 'approve_item.approve_item_id=item_reason.fk_approve_item_id');
        $lookup_values['item_reason'] = $this->read_db->get_where(
            'item_reason',
            array('approve_item_name' => 'cheque_book_reset')
        )->result_array();

        // Only show office bank of the offices in your hierarchy
        if(!$this->session->system_admin){
            $this->read_db->select(array('office_bank_id','office_bank_name'));
            $this->read_db->where_in('office_bank.fk_office_id',array_column($this->session->hierarchy_offices,'office_id'));
            $office_banks_obj = $this->read_db->get('office_bank');

            if($office_banks_obj->num_rows() > 0){
                $lookup_values['office_bank'] = $office_banks_obj->result_array();
            }
        }

        return $lookup_values;
    }

  

    function list_table_where()
    {
        if (!$this->session->system_admin) {

            $this->read_db->join('office_bank', 'ob.office_bank_id = cbr.fk_office_bank_id');
            $this->read_db->join('office', 'o.office_id=ob.fk_office_id');
            $this->read_db->where(array('o.fk_account_system_id' => $this->session->user_account_system));
            $this->read_db->get('cheque_book_reset')->result_array();
        }
    }
}
