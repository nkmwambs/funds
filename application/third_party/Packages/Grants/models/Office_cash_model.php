<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Office_cash_model extends MY_Model{

    public $table = 'office_cash'; 
    public $dependant_table = '';
    public $name_field = 'office_cash_name';
    public $create_date_field = "office_cash_created_date";
    public $created_by_field = "office_cash_created_by";
    public $last_modified_date_field = "office_cash_last_modified_date";
    public $last_modified_by_field = "office_cash_last_modified_by";
    public $deleted_at_field = "office_cash_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array('account_system');
    }
  /**
   * get_active_office_cash(): get json string of voucher types
   * @author  Livingstone Onduso
   * @dated: 4/06/2023
   * @access public
   * @return void
   * @param int $account_system_id
   */
    public function get_active_office_cash(int $account_system_id){

      $this->read_db->select(['office_cash_id','office_cash_name']);
      $this->read_db->where(['office_cash_is_active'=>1,'fk_account_system_id'=>$account_system_id]);
      $cash_accounts=$this->read_db->get('office_cash')->result_array();

      return $cash_accounts;

    }

    public function get_active_office_cash_by_office_id($office_id){
      $this->read_db->select(['office_cash_id','office_cash_name']);
      $this->read_db->where(['office_cash_is_active' => 1,'office_id'=>$office_id]);
      $this->read_db->join('account_system','account_system.account_system_id=office_cash.fk_account_system_id');
      $this->read_db->join('office','office.fk_account_system_id=account_system.account_system_id');
      $cash_accounts=$this->read_db->get('office_cash')->result_array();

      return $cash_accounts;
    }

    function list_table_where(){
        if(!$this->session->system_admin){
          $this->read_db->where(array('account_system_code'=>$this->session->user_account_system));
        }
      }
    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}
}