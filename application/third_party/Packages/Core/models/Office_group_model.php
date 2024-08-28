<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Office_group_model extends MY_Model{

    public $table = 'office_group'; 
    public $dependant_table = '';
    public $name_field = 'office_group_name';
    public $create_date_field = "office_group_created_date";
    public $created_by_field = "office_group_created_by";
    public $last_modified_date_field = "office_group_last_modified_date";
    public $last_modified_by_field = "office_group_last_modified_by";
    public $deleted_at_field = "office_group_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array('account_system');
    }

    public function detail_tables(){
        return ['office_group_association'];
    }

    public function detail_multi_form_add_visible_columns(){}

    public function check_if_office_is_office_group_lead($office_id){

        $is_group_lead = false;

        $this->read_db->where(["fk_office_id"=>$office_id,'office_group_association_is_lead'=>1]);
        $this->read_db->join('office_group_association','office_group_association.fk_office_group_id=office_group.office_group_id');
        $office_group_obj = $this->read_db->get('office_group');

        if($office_group_obj->num_rows() > 0){
            $is_group_lead = true;
        }

        return $is_group_lead;

    }
}