<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Menu_user_order_model extends MY_Model
{
  public $table = 'menu_user_order'; // you MUST mention the table name


  function __construct(){
    parent::__construct();
    $this->load->database();

  }

  function delete($id = null){

  }
  
  function index(){

  }


  function list(){

  }

  function lookup_tables(){
    return array('menu','user');
  }

  function detail_tables(){

  }

  function view(){

  }

  function show_add_button(){
    return false;
  }

  function list_table_visible_columns(){
    return ['menu_name','user_name','menu_user_order_is_active','menu_user_order_level','menu_user_order_priority_item'];
  }

  function edit_visible_columns(){
    return ['menu_user_order_track_number','menu_name','menu_user_order_is_active','menu_user_order_level','menu_user_order_priority_item'];
  }


}
