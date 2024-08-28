<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Approve_item_model extends MY_Model
{
  public $table = 'approve_item'; // you MUST mention the table name
  //public $dependant_table = "status";


  function __construct()
  {
    parent::__construct();
    $this->load->database();
  }

  function index()
  {
  }

  function delete($id = null)
  {
  }

  function list()
  {
  }

  function lookup_tables()
  {
  }

  function detail_tables()
  {
    return array('approval_flow');
  }

  function view()
  {
  }

  function show_add_button()
  {
    // These items are automatically added by the system
    if ($this->session->system_admin) {
      return true;
    }
  }

  function approveable_items(){
    $this->read_db->select(array('approve_item_name'));
    $approveable_items_array = $this->read_db->get_where(
        'approve_item',
        array('approve_item_is_active' => 1)
      )->result_array();

    $approveable_items = array_column($approveable_items_array,'approve_item_name');

    return $approveable_items;
  }
}
