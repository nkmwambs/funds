<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Account_system_model extends MY_Model
{

    public $table = 'account_system';
    public $dependant_table = '';
    public $name_field = 'account_system_name';
    public $create_date_field = "account_system_created_date";
    public $created_by_field = "account_system_created_by";
    public $last_modified_date_field = "account_system_last_modified_date";
    public $last_modified_by_field = "account_system_last_modified_by";
    public $deleted_at_field = "account_system_deleted_at";

    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }


    function index()
    {
    }

    function action_before_insert($post_array)
    {

        return $this->grants->sanitize_post_value_before_insert($post_array, 'account_system_code');
    }

    function edit_visible_columns()
    {
        return [
            'account_system_name',
            'account_system_code',
            'account_system_is_active',
        ];
    }

    function action_after_insert($post_array, $approval_id, $header_id)
    {
        // Create default funding status

        $this->write_db->trans_start();

        $funding_status['funding_status_track_number'] =  $this->grants_model->generate_item_track_number_and_name('funding_status')['funding_status_track_number'];
        $funding_status['funding_status_name'] = get_phrase('fully_funded');
        $funding_status['funding_status_is_active'] = 1;
        $funding_status['fk_account_system_id'] = $header_id;
        $funding_status['funding_status_is_available'] = 1;


        $funding_status_data_to_insert = $this->grants_model->merge_with_history_fields('funding_status', $funding_status, false);
        $this->write_db->insert('funding_status', $funding_status_data_to_insert);

        //Create budget review count 

        $budget_review_count['budget_review_count_track_number']=$this->grants_model->generate_item_track_number_and_name('budget_review_count')['budget_review_count_track_number'];
        $budget_review_count['budget_review_count_name']='Budget_review_count # '.$this->grants_model->generate_item_track_number_and_name('budget_review_count')['budget_review_count_track_number'];
        $budget_review_count['budget_review_count_number']=4;
        $budget_review_count['fk_account_system_id']=$header_id;
        $budget_review_count['budget_review_count_created_by']=$this->session->user_id;

        $budget_review_count_data_to_insert = $this->grants_model->merge_with_history_fields('budget_review_count', $budget_review_count, false);
        
        $this->write_db->insert('budget_review_count', $budget_review_count_data_to_insert);

        // Create an account system language pack directory

        $lang_code = $this->language_model->default_language()['language_code'];

        $account_system_code = $post_array['account_system_code'];

        $lang_path = APPPATH.'language'.DIRECTORY_SEPARATOR.$account_system_code.DIRECTORY_SEPARATOR; 

        if(!file_exists($lang_path.$lang_code.'_lang.php')){

            $this->language_library->create_language_files($lang_code, $account_system_code);
            
        }


        $this->write_db->trans_complete();

        if ($this->write_db->trans_status() === FALSE) {
            return false;
        } else {
            return true;
        }
    }

    function transaction_validate_duplicates_columns()
    {
        return ['account_system_code'];
    }


    public function lookup_tables()
    {
        return array();
    }

    public function detail_tables()
    {
        return list_detail_tables();
    }


    function list_table_visible_columns(){
        return [
            'account_system_track_number',
            'account_system_name',
            'account_system_is_active',
        ];
    }
    function single_form_add_visible_columns(){
        return [
            'account_system_name',
            'account_system_code',
            'account_system_is_active'
        ];
    }

    function lookup_values()
    {

        $lookup_values = [];

        if (!$this->session->system_admin) {
            $results = $this->read_db->select(array('account_system_id', 'account_system_name'))->get_where('account_system', array('account_system_code' => $this->session->user_account_system));

            if ($results->num_rows() > 0) {
                $lookup_values = $results->result_array();
            }

            return $lookup_values;
        }
    }

    public function detail_multi_form_add_visible_columns()
    {
    }

    function intialize_table(array $foreign_keys_values = [])
    {

        $account_system_data['account_system_track_number'] = $this->grants_model->generate_item_track_number_and_name('account_system')['account_system_track_number'];
        $account_system_data['account_system_name'] = "Global Account System";
        $account_system_data['account_system_code'] = "global";
        $account_system_data['account_system_is_allocation_linked_to_account'] = 0;

        $account_system_data_to_insert = $this->grants_model->merge_with_history_fields('account_system', $account_system_data, false);
        $this->write_db->insert('account_system', $account_system_data_to_insert);

        return $this->write_db->insert();
    }

    function show_add_button()
    {
        if (!$this->session->system_admin) {
            return false;
        } else {
            return true;
        }
    }

    function list_table_where()
    {
        if (!$this->session->system_admin) {
            $this->read_db->where(array('account_system_code' => $this->session->user_account_system));
        }
    }

    /**
   * get_account_systes() 
   * This method returns the account_systems
   *@return Array
   * @Author: Livingstone Onduso
   * @Dated: 10/09/2022
   */
  public function get_account_systems()
  {

      $this->read_db->select(array('account_system_id','account_system_name'));

      if (!$this->session->system_admin) {
          $this->read_db->where(array('account_system_id' => $this->session->user_account_system_id));
      }

      $account_system_id_and_names =$this->read_db->get('account_system')->result_array();

      $account_system_ids=array_column($account_system_id_and_names,'account_system_id');

      $account_names=array_column($account_system_id_and_names,'account_system_name');

      $account_ids_and_names=array_combine($account_system_ids,$account_names);

      
      return   $account_ids_and_names;
  }
      

}
