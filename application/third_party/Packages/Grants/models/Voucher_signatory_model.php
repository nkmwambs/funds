<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Voucher_signatory_model extends MY_Model{

    public $table = 'voucher_signatory'; 
    public $dependant_table = '';
    public $name_field = 'voucher_signatory_name';
    public $create_date_field = "voucher_signatory_created_date";
    public $created_by_field = "voucher_signatory_created_by";
    public $last_modified_date_field = "voucher_signatory_last_modified_date";
    public $last_modified_by_field = "voucher_signatory_last_modified_by";
    public $deleted_at_field = "voucher_signatory_deleted_at";
    
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

    public function list_table_where()
    {
        if(!$this->session->system_admin){
            $this->read_db->where(array('fk_account_system_id'=>$this->session->user_account_system_id));
        }
    }
}