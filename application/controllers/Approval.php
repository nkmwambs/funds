<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Approval extends MY_Controller
{

  function __construct(){
    parent::__construct();
  }

  function index(){

  }

  function page_title():String{
    return "Listing";
  }

  static function get_menu_list(){

  }

}
