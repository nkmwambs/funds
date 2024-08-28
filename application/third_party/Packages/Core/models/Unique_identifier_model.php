<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Unique_identifier_model extends MY_Model
{

    public $table = 'unique_identifier';
    public $dependant_table = '';
    public $name_field = 'unique_identifier_name';
    public $create_date_field = "unique_identifier_created_date";
    public $created_by_field = "unique_identifier_created_by";
    public $last_modified_date_field = "unique_identifier_last_modified_date";
    public $last_modified_by_field = "unique_identifier_last_modified_by";
    public $deleted_at_field = "unique_identifier_deleted_at";

    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    function index()
    {
    }

    function action_before_insert($post_array): array
    {
      $account_system_id = $post_array['header']['fk_account_system_id'];

      $active_unique_identifiers = $this->get_account_system_unique_identifier($account_system_id);

      if(!empty($active_unique_identifiers)){
        // Deactivate the current active identifier
        $this->deactivate_active_unique_identifier($active_unique_identifiers['unique_identifier_id']);
      }
  
      return $post_array;
    }

    function action_before_edit($post_array): array
    {
      $account_system_id = $post_array['header']['fk_account_system_id'];

      $active_unique_identifiers = $this->get_account_system_unique_identifier($account_system_id);

      if(!empty($active_unique_identifiers)){
        // Deactivate the current active identifier
        $this->deactivate_active_unique_identifier($active_unique_identifiers['unique_identifier_id']);
      }
  
      return $post_array;
    }

    function action_after_insert($post_array, $approval_id, $header_id)
    {

        // Check all users in the account system that have not provided unique identification information and disapprove them for all center users
        // $account_system_id = $post_array['fk_account_system_id'];

        // $update_data['fk_status_id'] = $this->grants_model->initial_item_status('user', $account_system_id);
        // $this->write_db->where(['fk_account_system_id' => $account_system_id, 'user_unique_identifier' => NULL, 'fk_context_definition_id' => 1]);
        // $this->write_db->update('user', $update_data);

        // if($this->write_db->affected_rows() > 0){
        //     return true;
        // }else{
        //     return false;
        // }

        $turn_user_on_off = $this->turn_user_on_off($post_array['fk_account_system_id'], $post_array['unique_identifier_is_active'] ? 'activate' : 'deactivate');
        return $turn_user_on_off;

    }

    function deactivate_active_unique_identifier($unique_identifier_id){
        $this->write_db->where(array('unique_identifier_id' => $unique_identifier_id));
        $this->write_db->update('unique_identifier',['unique_identifier_is_active' => 0]);        
    }

    function action_after_edit($post_array, $approval_id, $header_id){
        $turn_user_on_off = $this->turn_user_on_off($post_array['fk_account_system_id'], $post_array['unique_identifier_is_active'] ? 'activate' : 'deactivate');
        return $turn_user_on_off;
    }

    function turn_user_on_off($account_system_id, $action = 'deactivate'){ // Options = activate or deactivate 
        // Turn all users that are missing active and not fully approved to fully approved if there is not any active unique identifer set
        $max_approval_id = $this->general_model->get_max_approval_status_id('user', [], $account_system_id)[0];// 3644;
        $initial_status_id = $this->grants_model->initial_item_status('user', $account_system_id); // 3643;

        $this->read_db->where(array('fk_account_system_id' => $account_system_id, 'unique_identifier_is_active' => 1));
        $count_account_system_active_unique_identifiers = $this->read_db->get('unique_identifier')->num_rows();

        if($action == 'deactivate'){
            if($count_account_system_active_unique_identifiers == 0){
                $update_data = ['fk_status_id' => $max_approval_id];
                $this->write_db->where(array('fk_status_id' => $initial_status_id, 'user_is_active' => 1, 'fk_context_definition_id' => 1));
                $this->write_db->update('user', $update_data);
            }
        }else{
            if($count_account_system_active_unique_identifiers == 0){
                // log_message('error', json_encode(['Hello']));
                $update_data = ['fk_status_id' => $initial_status_id];
                $this->write_db->where(['fk_account_system_id' => $account_system_id, 'user_unique_identifier' => NULL, 'fk_context_definition_id' => 1]);
                $this->write_db->update('user', $update_data);
            } 
        }

        if($this->write_db->affected_rows() > 0){
            return true;
        }else{
            return false;
        }
        
    }

    public function lookup_tables()
    {
        return array('account_system');
    }

    public function detail_tables()
    {
    }

    public function detail_multi_form_add_visible_columns()
    {
    }

    function get_account_system_unique_identifier($account_system_id)
    {

        $this->read_db->select(array('unique_identifier_id', 'unique_identifier_name'));
        $this->read_db->where(array('fk_account_system_id' => $account_system_id, 'unique_identifier_is_active' => 1));
        $unique_identifier_obj = $this->read_db->get('unique_identifier');

        $unique_identifier = [];

        if ($unique_identifier_obj->num_rows() > 0) {
            $unique_identifier = $unique_identifier_obj->row_array();
        }

        return $unique_identifier;
    }


    function get_office_context_allowed_unique_identifier($context_definition_id, $context_office_id)
    {
        $context_name = 'center';

        switch ($context_definition_id) {
            case 1:
                $context_name = 'center';
                break;
            case 2:
                $context_name = 'cluster';
                break;
            case 3:
                $context_name = 'cohort';
                break;
            case 4:
                $context_name = 'country';
                break;
            case 5:
                $context_name = 'region';
                break;
            case 6:
                $context_name = 'global';
                break;
            default:
                $context_name = 'center';
        }

        $this->read_db->select(array('unique_identifier_id', 'unique_identifier_name'));
        $this->read_db->join('account_system', 'account_system.account_system_id=unique_identifier.fk_account_system_id');
        $this->read_db->join('office', 'office.fk_account_system_id=account_system.account_system_id');
        $this->read_db->join('context_' . $context_name, 'context_' . $context_name . '.fk_office_id=office.office_id');
        $this->read_db->where(array('context_' . $context_name . '_id' => $context_office_id, 'unique_identifier_is_active' => 1));
        $active_unique_identifier_obj = $this->read_db->get('unique_identifier');

        $active_unique_identifier = [];

        if ($active_unique_identifier_obj->num_rows() > 0) {
            $active_unique_identifier = $active_unique_identifier_obj->row_array();
        }

        return $active_unique_identifier;
    }
    public function list_table_visible_columns()
    {
        return [
            'unique_identifier_track_number',
            'unique_identifier_name',
            'unique_identifier_is_active',
            'account_system_name',
            'unique_identifier_created_date'
        ];
    }

    function valid_user_unique_identifier($user_id){

        $user_info = $this->user_model->get_user_info($user_id);

        $valid_user_unique_identifier = [];

        $user_unique_identifier = ['unique_identifier_id' => $user_info['unique_identifier_id'], 'unique_identifier_name' => $user_info['unique_identifier_name']];
        $account_system_unique_identifier = $this->get_account_system_unique_identifier($user_info['account_system_id']);
        
        if(!empty($account_system_unique_identifier)){
            if($user_info['unique_identifier_id'] != null){
                if($user_unique_identifier['unique_identifier_id'] == $account_system_unique_identifier['unique_identifier_id']){
                    $valid_user_unique_identifier = $account_system_unique_identifier;
                }else{
                    $valid_user_unique_identifier = $user_unique_identifier;
                }
            }else{
                $valid_user_unique_identifier = $account_system_unique_identifier;
            }
            
        }

        return $valid_user_unique_identifier;
    }

    function user_unique_identifier_uploads($user_id){

        $attachment_type_name = 'user_unique_identifier_document';
        $this->load->model('unique_identifier_model');
        $account_system_unique_identifier = $this->unique_identifier_model->get_account_system_unique_identifier($this->session->user_account_system_id);
        $unique_identifier_id = isset($account_system_unique_identifier['unique_identifier_id']) ? $account_system_unique_identifier['unique_identifier_id'] : 0 ;
        $attachment_url = "uploads/attachments/user/" . $user_id . "/user_identifier_document/" . $unique_identifier_id;
    
        // log_message('error', $attachment_type_name);
    
        $this->read_db->select(array('attachment_url','attachment_name'));
        $this->read_db->where(array('attachment_type_name' => $attachment_type_name, 'attachment_url' => $attachment_url));
        $this->read_db->join('attachment_type','attachment_type.attachment_type_id=attachment.fk_attachment_type_id');
        $attachment_obj = $this->read_db->get('attachment');
    
        $attachments = [];
    
        if($attachment_obj->num_rows() > 0){
          $attachments =  $attachment_obj->result_array();
        }
    
        return $attachments;
      }
}
