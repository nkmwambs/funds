<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Voucher_detail_model extends MY_Model 
{
  public $table = 'voucher_detail'; // you MUST mention the table name
  public $name_field = "voucher_detail_name";
  //public $primary_key = "voucher_detail_id";

  function __construct(){
    parent::__construct();
    $this->load->database();

  }

  function delete($id = null){

  }

  function index(){}

  public function lookup_tables(){
    return array('voucher');
  }

  public function detail_tables(){}

  // public function false_keys(){

  //     return array(
  //       'fk_voucher_type_id'=>array(
  //         'representing_key'=>'detail_account',
  //         'lookup_keys'=>array(
  //           'expense_account_name'=>array(2,3,6),
  //           'income_account_name'=>array(5,7)
  //         )
  //       )
  //     );
  //   }

  public function master_table_visible_columns(){}

  public function master_table_hidden_columns(){}

  public function list_table_visible_columns(){}

  public function list_table_hidden_columns(){}

  public function detail_list_table_visible_columns(){}

  public function detail_list_table_hidden_columns(){}

  public function single_form_add_visible_columns(){}

  public function single_form_add_hidden_columns(){}

  public function master_multi_form_add_visible_columns(){}

  public function detail_multi_form_add_visible_columns(){
    return array('voucher_detail_description','voucher_detail_quantity',
    'voucher_detail_unit_cost','voucher_detail_total_cost','project_allocation_name');

  }

  public function master_multi_form_add_hidden_columns(){}

  public function detail_multi_form_add_hidden_columns(){}

  function detail_list(){}

  function master_view(){}

  public function list(){}

  public function view(){}
  
  function show_add_button(){
      return false;
   }
   

}
