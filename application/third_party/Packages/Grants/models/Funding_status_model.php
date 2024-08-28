<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Funding_status_model extends MY_Model{

    public $table = 'funding_status'; 
    public $dependant_table = '';
    public $name_field = 'funding_status_name';
    public $create_date_field = "funding_status_created_date";
    public $created_by_field = "funding_status_created_by";
    public $last_modified_date_field = "funding_status_last_modified_date";
    public $last_modified_by_field = "funding_status_last_modified_by";
    public $deleted_at_field = "funding_status_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        //return array('status','approval');
    }

    public function detail_tables(){}

    function single_form_add_visible_columns(){
        //return ['funding_status_name','funding_status_is_active'];
    }

    public function list_table_where(){

        if(!$this->session->system_admin){
          
          $this->read_db->where(array('account_system_code'=>$this->session->user_account_system));
        }
    
    }

    function action_before_insert($post_array){

        $account_system_id = $post_array['header']['fk_account_system_id'];
        
        return $this->grants_model->overwrite_field_value_on_post(
            $post_array,
            'funding_status',
            'funding_status_is_available',
            1,
            0,
            [
                'fk_account_system_id'=>$account_system_id,
                'funding_status_is_available'=>1,
                'funding_status_is_active'=>1
            ]
        );
      }

      
  
}