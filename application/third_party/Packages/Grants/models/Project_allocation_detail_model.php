<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Project_allocation_detail_model extends MY_Model{

    public $table = 'project_allocation_detail'; 
    public $dependant_table = '';
    public $name_field = 'project_allocation_detail_name';
    public $create_date_field = "project_allocation_detail_created_date";
    public $created_by_field = "project_allocation_detail_created_by";
    public $last_modified_date_field = "project_allocation_detail_last_modified_date";
    public $last_modified_by_field = "project_allocation_detail_last_modified_by";
    public $deleted_at_field = "project_allocation_detail_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array('project_allocation');
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){
        return ['project_allocation_detail_month','project_allocation_detail_amount'];
    }
}