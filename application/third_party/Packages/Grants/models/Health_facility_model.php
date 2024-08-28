<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org 
 */

class Health_facility_model extends MY_Model{

    public $table = 'health_facility';  
    public $dependant_table = '';
    public $name_field = 'health_facility_name';
    public $create_date_field = "health_facility_created_date";
    public $created_by_field = "health_facility_created_by";
    public $last_modified_date_field = "health_facility_last_modified_date";
    public $last_modified_by_field = "health_facility_last_modified_by";
    public $deleted_at_field = "health_facility_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array('account_system');
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}

    function list_table_where()
    {
        if(!$this->session->system_admin){
            $this->read_db->where(array($this->controller . '.fk_account_system_id'=>$this->session->user_account_system_id));

        }
        
    }
    public function get_support_needed_docs_flag($health_facility_id){

        $this->read_db->select(array('support_docs_needed'));
        $this->read_db->where(array('health_facility_id'=>$health_facility_id));
        return $this->read_db->get('health_facility')->row_array()['support_docs_needed'];
    }

    public function single_form_add_visible_columns(){
        return [
          'health_facility_name',
          'health_facility_type',
          'support_docs_needed',
          'account_system_name',
        ];
      }
}