<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Budget_projection_income_account_model extends MY_Model{

    public $table = 'budget_projection_income_account'; 
    public $dependant_table = '';
    public $name_field = 'budget_projection_income_account_name';
    public $create_date_field = "budget_projection_income_account_created_date";
    public $created_by_field = "budget_projection_income_account_created_by";
    public $last_modified_date_field = "budget_projection_income_account_last_modified_date";
    public $last_modified_by_field = "budget_projection_income_account_last_modified_by";
    public $deleted_at_field = "budget_projection_income_account_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array('budget_projection','income_account');
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}

    public function single_form_add_visible_columns(){
        return [
            'income_account_name',
            'budget_projection_income_account_amount'
        ];
    }

    public function detail_list_table_visible_columns(){
        return [
            'budget_projection_income_account_track_number',
            'income_account_name',
            'budget_projection_income_account_amount',
            'budget_projection_income_account_created_date'
        ];
    }
}