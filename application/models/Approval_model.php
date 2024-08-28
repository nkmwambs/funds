<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Approval_model extends MY_Model
{
  public $table = 'approval'; // you MUST mention the table name

  function __construct(){
    parent::__construct();
  }

  function index(){}

  function lookup_tables(){
    return array('approve_item','status');
  }

  function detail_tables(){
    //,'budget','voucher','center','bank','permission','role_permission','project_allocation'
    return array('request','budget','voucher','office','bank','permission','role_permission','project_allocation');
  }

  function delete($id = null){

  }

  function detail_list(){}

  function master_view(){}

  public function list(){}

  public function view(){}

    // Access methods
  public function show_add_button(){
      return false;
  }

/**
 *  List table where
 * 
 *  Used in the run_list_query and ajax_model. Here it lists only approvaeable items
 * 
 */
  function list_table_where(){
    $this->read_db->where(array('approve_item.approve_item_is_active'=>1));
  }


}
