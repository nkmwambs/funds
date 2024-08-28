<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Voucher_type_effect_model extends MY_Model{

    public $table = 'voucher_type_effect'; 
    public $dependant_table = '';
    public $name_field = 'voucher_type_effect_name';
    public $create_date_field = "voucher_type_effect_created_date";
    public $created_by_field = "voucher_type_effect_created_by";
    public $last_modified_date_field = "voucher_type_effect_last_modified_date";
    public $last_modified_by_field = "voucher_type_effect_last_modified_by";
    public $deleted_at_field = "voucher_type_effect_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array();
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}

    function intialize_table(Array $foreign_keys_values = []){

        $voucher_type_effects = ['income','expense','bank_contra','cash_contra'];

        $insert_ids = [];

        foreach($voucher_type_effects as $voucher_type_effect){
            $voucher_type_effect_data['voucher_type_effect_track_number'] = $this->grants_model->generate_item_track_number_and_name('voucher_type_effect')['voucher_type_effect_track_number'];
            $voucher_type_effect_data['voucher_type_effect_name'] = ucfirst($voucher_type_effect);
            $voucher_type_effect_data['voucher_type_effect_code'] = $voucher_type_effect;
            
            $voucher_type_effect_data_to_insert = $this->grants_model->merge_with_history_fields('voucher_type_effect',$voucher_type_effect_data,false);
            $this->write_db->insert('voucher_type_effect',$voucher_type_effect_data_to_insert);

            $insert_ids[] = $this->write_db->insert();
        }

        return $insert_ids;
    }
}