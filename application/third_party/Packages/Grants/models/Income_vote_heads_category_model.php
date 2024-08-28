<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Income_vote_heads_category_model extends MY_Model{

    public $table = 'income_vote_heads_category'; 
    public $dependant_table = '';
    public $name_field = 'income_vote_heads_category_name';
    public $create_date_field = "income_vote_heads_category_created_date";
    public $created_by_field = "income_vote_heads_category_created_by";
    public $last_modified_date_field = "income_vote_heads_category_last_modified_date";
    public $last_modified_by_field = "income_vote_heads_category_last_modified_by";
    public $deleted_at_field = "income_vote_heads_category_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array('funding_stream');
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}
}