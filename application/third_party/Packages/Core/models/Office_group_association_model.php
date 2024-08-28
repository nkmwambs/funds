<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Office_group_association_model extends MY_Model{

    public $table = 'office_group_association'; 
    public $dependant_table = '';
    public $name_field = 'office_group_association_name';
    public $create_date_field = "office_group_association_created_date";
    public $created_by_field = "office_group_association_created_by";
    public $last_modified_date_field = "office_group_association_last_modified_date";
    public $last_modified_by_field = "office_group_association_last_modified_by";
    public $deleted_at_field = "office_group_association_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array('office_group','office');
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}

    function single_form_add_visible_columns()
    {
        return [
            "office_group_name",
            "office_name",
            "office_group_association_is_lead"
        ];
    }

    function multi_select_field()
    {
        return "office";
    }

    function lookup_values()
    {
        $lookup_values = parent::lookup_values();

        if(!$this->session->system_admin){
            $office_ids = array_column($this->session->hierarchy_offices,"office_id");
            $this->read_db->where_in('office_id',$office_ids);
            $lookup_values['office'] = $this->read_db->get('office')->result_array();
        }

        return $lookup_values;
    }
}