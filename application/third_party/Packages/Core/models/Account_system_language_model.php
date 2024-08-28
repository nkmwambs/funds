<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Account_system_language_model extends MY_Model{

    public $table = 'account_system_language'; 
    public $dependant_table = '';
    public $name_field = 'account_system_language_name';
    public $create_date_field = "account_system_language_created_date";
    public $created_by_field = "account_system_language_created_by";
    public $last_modified_date_field = "account_system_language_last_modified_date";
    public $last_modified_by_field = "account_system_language_last_modified_by";
    public $deleted_at_field = "account_system_language_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array('account_system','language');
    }

    function action_before_edit($post_array){

        $this->read_db->where(array('account_system_language_id' => hash_id($this->id, 'decode')));
        $former_language_id = $this->read_db->get('account_system_language')->row()->fk_language_id;

        $new_language_id = $post_array['header']['fk_language_id'];


        if($former_language_id != $new_language_id){
            $this->read_db->select(array('language_name','language_code','account_system_code'));
            $this->read_db->where(array('language_id' => $former_language_id));
            $this->read_db->join('account_system_language','account_system_language.fk_language_id=language.language_id');
            $this->read_db->join('account_system','account_system.account_system_id=account_system_language.fk_account_system_id');
            $former_language = $this->read_db->get('language')->row_array();

            $account_system_code = $former_language['account_system_code'];
            $language_code = $former_language['language_code'];

            $lang_file = APPPATH.'language'.DIRECTORY_SEPARATOR.$account_system_code.DIRECTORY_SEPARATOR. $language_code.'.php';

            if(file_exists($lang_file)){
                unlink($lang_file);
            }
        }

        return $post_array;
    }

    function single_form_add_visible_columns(){
        return [
            "account_system_name",
            "language_name"
        ];
    }

    function edit_visible_columns(){
        return [
            "account_system_name",
            "language_name"
        ];
    }

    function list_table_visible_columns(){
        return [
            "account_system_track_number",
            "account_system_name",
            "language_name",
            "account_system_language_created_date"
        ];
    }

    function create_language_files($post_array){

        $lang_code = $this->read_db->get_where('language',array('language_id' => $post_array['fk_language_id']))->row()->language_code;

        $lang_group = $this->read_db->get_where('account_system',array('account_system_id' => $post_array['fk_account_system_id']))->row()->account_system_code;

        return $this->language_library->create_language_files($lang_code, $lang_group);
    }

    function action_after_insert($post_array, $approval_id, $header_id){
        
        return $this->create_language_files($post_array);
    }

    function action_after_edit($post_array, $approval_id, $header_id){

        return $this->create_language_files($post_array);
    }

    function lookup_values(){
        $lookup_values = parent::lookup_values();

        // Hide global value. Global language packs are added by creating a new language in the system
        
        $this->read_db->select(array('account_system_id','account_system_name'));
        $this->read_db->where(array('account_system_code <>'=> 'global'));
        $lookup_values['account_system'] = $this->read_db->get("account_system")->result_array();

        return $lookup_values;
    }

    function transaction_validate_duplicates_columns(){
        return ["fk_account_system_id","fk_language_id"];
    }

    function insert_new_account_system_language($language_id){
        $lang_data['account_system_language_name'] = $this->grants_model->generate_item_track_number_and_name('account_system_language')['account_system_language_name'];
        $lang_data['account_system_language_track_number'] = $this->grants_model->generate_item_track_number_and_name('account_system_language')['account_system_language_track_number'];
        $lang_data['fk_account_system_id'] = $this->session->user_account_system_id;
        $lang_data['fk_language_id'] = $language_id;
        $lang_data['account_system_language_created_date'] = date('Y-m-d');
        $lang_data['account_system_language_created_by'] = $this->session->user_id;
        $lang_data['account_system_language_last_modified_by'] = $this->session->user_id;
        $lang_data['fk_status_id'] = $this->grants_model->initial_item_status('account_system_language');

        $count_account_system_language = $this->read_db->get_where('account_system_language', array('fk_account_system_id' =>  $this->session->user_account_system_id,
        'fk_language_id' => $language_id))->num_rows();

        if($count_account_system_language == 0){
            $this->write_db->insert("account_system_language", $lang_data);
        }

        
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}
}