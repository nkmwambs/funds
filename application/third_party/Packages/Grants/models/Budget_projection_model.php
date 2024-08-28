<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Budget_projection_model extends MY_Model{

    public $table = 'budget_projection'; 
    public $dependant_table = '';
    public $name_field = 'budget_projection_name';
    public $create_date_field = "budget_projection_created_date";
    public $created_by_field = "budget_projection_created_by";
    public $last_modified_date_field = "budget_projection_last_modified_date";
    public $last_modified_by_field = "budget_projection_last_modified_by";
    public $deleted_at_field = "budget_projection_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array('budget');
    }

    public function detail_tables(){
        return ['budget_projection_income_account'];
    }

    public function list_table_visible_columns(){
        return [
            'budget_projection_track_number',
            'budget_projection_created_date',
            'budget_year'
        ];
    }

    public function detail_multi_form_add_visible_columns(){}
}