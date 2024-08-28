<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Office_user_model extends MY_Model{

    public $table = 'office_user'; 
    public $dependant_table = '';
    public $name_field = 'office_user_name';
    public $create_date_field = "office_user_created_date";
    public $created_by_field = "office_user_created_by";
    public $last_modified_date_field = "office_user_last_modified_date";
    public $last_modified_by_field = "office_user_last_modified_by";
    public $deleted_at_field = "office_user_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array('user','office');
    }

    function multi_select_field()
    {
        return 'office';
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}

    function single_form_add_visible_columns()
    {
        return ['office_name','user_name'];
    }

    function list_table_visible_columns()
    {
        return ['user_name','office_name','office_user_is_active'];
    }
}