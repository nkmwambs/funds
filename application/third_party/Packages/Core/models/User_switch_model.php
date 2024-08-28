<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class User_switch_model extends MY_Model{

    public $table = 'user_switch'; 
    public $dependant_table = '';
    public $name_field = 'user_switch_name';
    public $create_date_field = "user_switch_created_date";
    public $created_by_field = "user_switch_created_by";
    public $last_modified_date_field = "user_switch_last_modified_date";
    public $last_modified_by_field = "user_switch_last_modified_by";
    public $deleted_at_field = "user_switch_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array();
    }

   
     /**
     * active_center_users_with_office_codes
     * 
     * List all FCP user Ids with their associated offices
     * 
     * @author Nicodemus Karisa
     * @reviewed_by None
     * @reviewed_date None
     * @access private
     * 
     * @return array Array of user ids and their associated offices
     * 
     * @todo:
     * Ready for Peer Review
     */

     private function active_center_users_with_office_codes(): array {

        $users = [];

        $this->read_db->select(array('user_id','office_code'));
        $this->read_db->join('context_center_user','context_center_user.fk_user_id=user.user_id');
        $this->read_db->join('context_center','context_center.context_center_id=context_center_user.fk_context_center_id');
        $this->read_db->join('office','office.office_id=context_center.fk_office_id');

        if(!$this->session->system_admin){
            $this->read_db->where(array('user.fk_account_system_id'=>$this->session->user_account_system_id));
        }

        $this->read_db->where(array('user_is_active' => 1));
        $users_obj = $this->read_db->get('user');

        if($users_obj->num_rows() > 0){
            $users_raw = $users_obj->result_array();

            foreach($users_raw as $user){
                $users[$user['user_id']][] = $user['office_code'];
            }
        }

        return $users;
    }
    

    /**
     * get_switchable_users
     * 
     * List user ids with a formatted email, role and office codes labels
     * 
     * @author Nicodemus Karisa Mwambire
     * @reviewed_by None
     * @reviewed_date None
     * @access public
     * 
     * @return array Array of user ids with email, role and office code
     * 
     * @todo:
     * Ready for Peer Review
     */

    public function get_switchable_users(): array {
        
        $active_center_users = $this->active_center_users_with_office_codes();

        $this->read_db->select(array('user_id',"CONCAT(user_firstname, ' ',user_lastname,' [',user_email, ' - ', role_name, ']') as user_name",'role_id','role_name'));
        $this->read_db->join('role','role.role_id=user.fk_role_id');
        if(!$this->session->system_admin){
            $this->read_db->where(array('user.fk_account_system_id'=>$this->session->user_account_system_id));
        }

        $context_definition = $this->session->context_definition;
        $context_definition_name = $context_definition['context_definition_name'];
        $context_definition_id = $context_definition['context_definition_id'];
        $user_offices = array_column($this->session->hierarchy_offices,'office_id');

        // Prevent PFs and FCP users form switching to users outside the hierarchy offices
        if($context_definition_name == 'center' || $context_definition_name == 'cluster'){
            $this->read_db->join('context_center_user','context_center_user.fk_user_id=user.user_id');
            $this->read_db->join('context_center','context_center.context_center_id=context_center_user.fk_context_center_id');
            $this->read_db->join('office','office.office_id=context_center.fk_office_id');
            $this->read_db->where_in('context_center.fk_office_id', $user_offices);
        }
        // Prevent users from switching users with context above themselves
        $this->read_db->where(array('user.fk_context_definition_id <=' => $context_definition_id));

        // Prevent switching to yourself
        $this->read_db->where_not_in('user_id',[$this->session->user_id]);

        // Preventing switching to users marked as inactive and not switchable
        $this->read_db->where(array('user_is_active'=>1, 'user_is_switchable' => 1));
        $users_list = $this->read_db->get('user')->result_array();

        $user_ids = array_column($users_list,'user_id');
        $user_names = array_column($users_list,'user_name');

        $users = array_combine($user_ids, $user_names); 

        foreach($users as $user_id => $user_detail){
            $user_detail = isset($active_center_users[$user_id]) ? $user_detail.'['.implode(',',$active_center_users[$user_id]).']' : $user_detail;
            $users[$user_id] = $user_detail;
        }

        return $users;
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}
}