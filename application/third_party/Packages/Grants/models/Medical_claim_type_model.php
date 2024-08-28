<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Livingstone Onduso
 *	@date		: 20th Aug, 2021
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *  londuso@ke.ci.org 
 */

class Medical_claim_type_model extends MY_Model{

    public $table = 'medical_claim_type'; 
    public $dependant_table = '';
    public $name_field = 'medical_claim_type_name';
    public $create_date_field = "medical_claim_type_created_date";
    public $created_by_field = "medical_claim_type_created_by";
    public $last_modified_date_field = "medical_claim_type_last_modified_date";
    public $last_modified_by_field = "medical_claim_type_last_modified_by";
    public $deleted_at_field = "medical_claim_type_deleted_at";
    
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
}