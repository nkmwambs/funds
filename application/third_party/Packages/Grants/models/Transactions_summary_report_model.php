<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Transactions_summary_report_model extends MY_Model{

    public $table = 'transactions_summary_report'; 
    public $dependant_table = '';
    public $name_field = 'transactions_summary_report_name';
    public $create_date_field = "transactions_summary_report_created_date";
    public $created_by_field = "transactions_summary_report_created_by";
    public $last_modified_date_field = "transactions_summary_report_last_modified_date";
    public $last_modified_by_field = "transactions_summary_report_last_modified_by";
    public $deleted_at_field = "transactions_summary_report_deleted_at";
    
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