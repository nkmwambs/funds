<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Cheque_injection_model extends MY_Model
{

    public $table = 'cheque_injection';
    public $dependant_table = '';
    public $name_field = 'cheque_injection_name';
    public $create_date_field = "cheque_injection_created_date";
    public $created_by_field = "cheque_injection_created_by";
    public $last_modified_date_field = "cheque_injection_last_modified_date";
    public $last_modified_by_field = "cheque_injection_last_modified_by";
    public $deleted_at_field = "cheque_injection_deleted_at";

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
        return array('office_bank');
    }

    public function detail_tables()
    {
    }

    public function detail_multi_form_add_visible_columns()
    {
    }

    public function single_form_add_visible_columns()
    {
        return [
            "office_bank_name",
            "cheque_injection_number",
            'item_reason_name'
        ];
    }

    public function lookup_values()
    {
        $lookup_values = parent::lookup_values();

        if (!$this->session->system_admin) {
            $office_ids = array_column($this->session->hierarchy_offices, "office_id");
            $this->read_db->where_in('fk_office_id', $office_ids);
            $lookup_values['office_bank'] = $this->read_db->get('office_bank')->result_array();
        }

        $this->read_db->join('approve_item','approve_item.approve_item_id=item_reason.fk_approve_item_id');
        $lookup_values['item_reason'] = $this->read_db->get_where('item_reason', 
        array('approve_item_name' => 'cheque_injection'))->result_array();

        return $lookup_values;
    }

    function get_injected_cheque_leaves($office_bank_id)
    {

        $cheque_injection = [];

        $this->read_db->select(['cheque_injection_number']);
        $this->read_db->where(['fk_office_bank_id' => $office_bank_id]);
        $cheque_injection_obj =  $this->read_db->get('cheque_injection');

        if ($cheque_injection_obj->num_rows() > 0) {
            $cheque_injection = array_column($cheque_injection_obj->result_array(), "cheque_injection_number");
        }

        return $cheque_injection;
    }

    function is_injected_cheque_number($office_bank_id, $cheque_number)
    {

        $is_injected_cheque_number = true;

        $this->read_db->where(array(
            "fk_office_bank_id" => $office_bank_id,
            'cheque_injection_number' => $cheque_number
        ));
        $cheque_injection_obj = $this->read_db->get('cheque_injection');

        if ($cheque_injection_obj->num_rows() == 0) {
            $is_injected_cheque_number = false;
        }

        return $is_injected_cheque_number;
    }

    function list_table_where()
    {
        if (!$this->session->system_admin) {
            $office_ids = array_column($this->session->hierarchy_offices, "office_id");
            $this->read_db->where_in('office_bank.fk_office_id', $office_ids);
        }
    }

    public function list_table_visible_columns()
    {
        return [
            "cheque_injection_track_number",
            "cheque_injection_number",
            "office_bank_name",
            "cheque_injection_created_date",
            'item_reason_name'
        ];
    }

    function action_before_insert($post_array)
    {

        $office_bank_id = $post_array['header']['fk_office_bank_id'];
        $cheque_injection_number = $post_array['header']['cheque_injection_number'];

            $cheque_condition = array(
                'fk_office_bank_id' => $office_bank_id,
                'voucher_cheque_number' => $cheque_injection_number,
                
            );
    
            $this->read_db->select(['voucher_id']);
            $this->read_db->where($cheque_condition);
            $this->read_db->where(['voucher_type_is_cheque_referenced'=>1]);
            $this->read_db->join('voucher_type','voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
            $count_of_unusable_cheque_leaves = $this->read_db->get('voucher')->result_array();

            // log_message('error',json_encode( $count_of_unusable_cheque_leaves));
    
            if (count($count_of_unusable_cheque_leaves) >0) {

                $data['voucher_cheque_number'] = -$cheque_injection_number;

                $this->write_db->where('voucher_id',$count_of_unusable_cheque_leaves[0]['voucher_id']);
                
                $this->write_db->update('voucher',$data);
            }
        
        return $post_array;
        
    }

    // function transaction_validate_duplicates_columns()
    // {
    //     return ['fk_office_bank_id', 'cheque_injection_number'];
    // }
}
