<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Onduso Livingstone
 *	@date		: 17th Feb, 2023
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	LOnduso@ke.ci.org
 */

class Reimbursement_illiness_category extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('reimbursement_illiness_category_library');
  }

  function index(){}

  static function get_menu_list(){}

}