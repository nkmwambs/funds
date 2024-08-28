<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Livingstone Onduso
 *	@date		: 20th Aug, 2021
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *  londuso@ke.ci.org  
 */

class Medical_claim_setting_model extends MY_Model
{

    public $table = 'medical_claim_setting';
    public $dependant_table = '';
    public $name_field = 'medical_claim_setting_name';
    public $create_date_field = "medical_claim_setting_created_date";
    public $created_by_field = "medical_claim_setting_created_by";
    public $last_modified_date_field = "medical_claim_setting_last_modified_date";
    public $last_modified_by_field = "medical_claim_setting_last_modified_by";
    public $deleted_at_field = "medical_claim_setting_deleted_at";

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
        return array('account_system', 'medical_claim_admin_setting');
    }

    public function get_threshold_amount_or_reimburse_all_flag($setting_id='')
    {
        $medical_setting_id=$setting_id;

        if($setting_id==''){
            $medical_setting_id=5;
        }
        
        $this->read_db->where(['fk_medical_claim_admin_setting_id' => $medical_setting_id, 'fk_account_system_id' => $this->session->user_account_system_id]);
        $test = $this->read_db->get('medical_claim_setting');

        $value = 0;

        if ($test->num_rows() > 0) {
            $value = $test->row()->medical_claim_setting_value;
        }

        return $value;
    }

    public function check_if_record_exists($fk_admin_claim_setting_value)
    {

        $setting_value = 0;
        $this->read_db->select(['fk_medical_claim_admin_setting_id']);
        $this->read_db->where(['fk_medical_claim_admin_setting_id' => $fk_admin_claim_setting_value, 'fk_account_system_id' => $this->session->user_account_system_id]);
        $result = $this->read_db->get('medical_claim_setting')->row_array();

        if (!empty($result)) {
            $setting_value = $result['fk_medical_claim_admin_setting_id'];
        }

        return $setting_value;
    }

    public function detail_tables()
    {
    }

    public function detail_multi_form_add_visible_columns()
    {
    }

    public function list_table_visible_columns()
    {

        return ['medical_claim_admin_setting_name', 'medical_claim_setting_value'];
    }
    

    function get_medical_setting_for_edit(){

        $this->read_db->select(array('medical_claim_setting_id','medical_claim_setting_name','fk_medical_claim_admin_setting_id','medical_claim_setting_value','medical_claim_admin_setting_name','fk_account_system_id','account_system_name'));
       
        $this->read_db->where(['medical_claim_setting_id'=>hash_id($this->id,'decode')]);

        if(!$this->session->system_admin){
            $this->read_db->where(array('fk_account_system_id'=>$this->session->user_account_system_id));
        }
        $this->read_db->join('medical_claim_admin_setting', 'medical_claim_admin_setting.medical_claim_admin_setting_id=medical_claim_setting.fk_medical_claim_admin_setting_id');
        $this->read_db->join('account_system','account_system.account_system_id=medical_claim_setting.fk_account_system_id');
        $medical_records=$this->read_db->get('medical_claim_setting')->row_array();

        return $medical_records;
    }
    public function edit_medical_claim_setting_record(array $post_arr){

        $message=0;
        //Update/Modify medical claim setting records
        $medical_claim_setting_id=$post_arr['medical_claim_setting_id'];

        $edit_data['medical_claim_setting_name']=$post_arr['medical_claim_setting_name'];
        $edit_data['fk_medical_claim_admin_setting_id']=$post_arr['medical_claim_setting_type_id'];
        $edit_data['medical_claim_setting_value']=$post_arr['medical_claim_setting_value'];
        $edit_data['fk_account_system_id']=$post_arr['fk_account_system_id'];

        //Include history fields
        $update_data = $this->grants_model->merge_with_history_fields('medical_claim_setting', $edit_data, false);

        $this->write_db->where(array('medical_claim_setting_id'=>$medical_claim_setting_id));
        $this->write_db->update('medical_claim_setting',$update_data);

        if($this->write_db->affected_rows()>0){
            $message= 1;
        }
        return $message;
        

    }

    function retrieve_account_systems(){

        $this->read_db->select(array('account_system_id', 'account_system_name'));

        $this->read_db->where(['account_system_is_active'=>1]);

        $account_systems=$this->read_db->get('account_system')->result_array();

        $account_system_id=array_column($account_systems,'account_system_id');

        $account_system_name=array_column($account_systems,'account_system_name');

        return array_combine($account_system_id, $account_system_name);
    }

    public function admin_settings()
    {

        $this->read_db->select(['medical_claim_admin_setting_id', 'medical_claim_admin_setting_name']);

        // if(!$this->session->system_admin){
        //     $this->read_db->where(array('medical_claim_admin_setting.fk_account_system_id'=>$this->session->user_account_system_id));
        // }

        $admin_settings = $this->read_db->get('medical_claim_admin_setting');

        $settings_at_admin_level_arr_combined = [];

        if ($admin_settings) {
            $settings_at_admin_level = $admin_settings->result_array();

            $admin_setting_ids = array_column($settings_at_admin_level, 'medical_claim_admin_setting_id');

            $admin_setting_names = array_column($settings_at_admin_level, 'medical_claim_admin_setting_name');

            $settings_at_admin_level_arr_combined = array_combine($admin_setting_ids, $admin_setting_names);
        }


        return $settings_at_admin_level_arr_combined;
    }
}
