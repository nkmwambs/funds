<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Account_system_setting_model extends MY_Model{

    public $table = 'account_system_setting'; 
    public $dependant_table = '';
    public $name_field = 'account_system_setting_name';
    public $create_date_field = "account_system_setting_created_date";
    public $created_by_field = "account_system_setting_created_by";
    public $last_modified_date_field = "account_system_setting_last_modified_date";
    public $last_modified_by_field = "account_system_setting_last_modified_by";
    public $deleted_at_field = "account_system_setting_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return [];
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}

    function get_account_system_settings($account_system_id){

        $this->read_db->select(array('account_system_setting_name as setting_name','account_system_setting_value as setting_value','account_system_setting_accounts'));
        $account_system_setting_obj = $this->read_db->get('account_system_setting');
    
        $account_system_setting = [];
    
        if($account_system_setting_obj->num_rows() > 0){
          $account_system_setting_array = $account_system_setting_obj->result_array();
    
          $cnt = 0;
          foreach($account_system_setting_array as $settings){
            $account_system_setting_accounts_settings = [];
            if($settings['account_system_setting_accounts'] != null){
              $account_system_setting_accounts_settings = $settings['account_system_setting_accounts'];
            }
            $account_systems = json_decode($account_system_setting_accounts_settings);
    
            if(is_array($account_systems) && in_array($account_system_id, $account_systems)){
              $account_system_setting[$settings['setting_name']] = $settings['setting_value'];
              $cnt++;
            }
          }
        }
    
        return $account_system_setting;
      }
}