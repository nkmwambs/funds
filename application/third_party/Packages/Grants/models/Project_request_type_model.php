<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Project_request_type_model extends MY_Model{

    public $table = 'project_request_type'; 
    public $dependant_table = '';
    public $name_field = 'project_request_type_name';
    public $create_date_field = "project_request_type_created_date";
    public $created_by_field = "project_request_type_created_by";
    public $last_modified_date_field = "project_request_type_last_modified_date";
    public $last_modified_by_field = "project_request_type_last_modified_by";
    public $deleted_at_field = "project_request_type_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array('request_type','project');
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}

    function lookup_values()
    {
        $lookup_values = parent::lookup_values();

        if(!$this->session->system_admin){
            $this->read_db->select(array('project_id','project_name'));
            $this->read_db->where(array('fk_account_system_id'=>$this->session->user_account_system_id));
            $this->read_db->join('funder','funder.funder_id=project.fk_funder_id');
            $lookup_values['project'] = $this->read_db->get('project')->result_array();

            $this->read_db->select(array('request_type_id','request_type_name'));
            $this->read_db->where(array('fk_account_system_id'=>$this->session->user_account_system_id));
            $lookup_values['request_type'] = $this->read_db->get('request_type')->result_array();
        }

        return $lookup_values;
    }

    function columns(){
        $columns = [
            'project_request_type_track_number',
            'project_request_type_name',
            'project_name',
            'request_type_name',
            'project_request_type_created_date'
        ];

        return $columns;
    }
}