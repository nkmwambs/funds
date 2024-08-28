<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Livingstone Onduso
 *	@date		: 27th March, 2022
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	londuso@ke.ci.org 
 */

class Beneficiary_model extends MY_Model{

    public $table = 'beneficiary'; 
    public $dependant_table = '';
    public $name_field = 'beneficiary_name';
    public $create_date_field = "beneficiary_created_date";
    public $created_by_field = "beneficiary_created_by";
    public $last_modified_date_field = "beneficiary_last_modified_date";
    public $last_modified_by_field = "beneficiary_last_modified_by";
    public $deleted_at_field = "beneficiary_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database(); 
    }

    function index(){}

    function lookup_tables(){
        return ['account_system'];
      }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}

  /**
   * list_table_visible_columns(): This method ensures that we can only display columns 
   * @author Livingstone Onduso
   * @access public
   * @return void
   */
    public function list_table_visible_columns(){

      return ['beneficiary_id','beneficiary_number','beneficiary_gender','beneficiary_dob','account_system_name'];
    }
 /**
   * list_table_where(): This method ensures that if NOT admin you are able to only see things of ur country
   * @author Livingstone Onduso
   * @access public
   * @return void
   */

    public function list_table_where():void{
        if(!$this->session->system_admin){
            $this->read_db->where(array('fk_account_system_id'=>$this->session->user_account_system_id));
        }
    }

 /**
   * get_national_offices(): This method gets a national offices
   * @author Livingstone Onduso
   * @access public
   * @return array
   */
    public function get_national_offices():array{

        $this->read_db->select(array('account_system_id','account_system_name'));

        $this->read_db->where(['account_system_is_active'=>1]);
        
        $country_offices_and_ids=$this->read_db->get('account_system')->result();

        $account_system_id=array_column($country_offices_and_ids,'account_system_id');

        $account_system_name=array_column($country_offices_and_ids,'account_system_name');

        return array_combine($account_system_id,$account_system_name);

    }
}