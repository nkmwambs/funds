<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Account_system_language extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('account_system_language_library');
  }

  function index(){}

  static function get_menu_list(){}

}