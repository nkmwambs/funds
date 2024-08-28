<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Onduso Livingstone
 *	@date		: 17th Feb, 2023
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	LOnduso@ke.ci.org
 */

class Reimbursement_funding_type_model extends MY_Model{

    public $table = 'reimbursement_funding_type'; 
    public $dependant_table = '';
    public $name_field = 'reimbursement_funding_type_name';
    public $create_date_field = "reimbursement_funding_type_created_date";
    public $created_by_field = "reimbursement_funding_type_created_by";
    public $last_modified_date_field = "reimbursement_funding_type_last_modified_date";
    public $last_modified_by_field = "reimbursement_funding_type_last_modified_by";
    public $deleted_at_field = "reimbursement_funding_type_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array();
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}
}