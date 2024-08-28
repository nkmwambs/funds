<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Livingstone Onduso
 *	@date		: 20th Aug, 2021
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *  londuso@ke.ci.org
 */


class Health_facility extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('health_facility_library');
  }

  function index(){}

  static function get_menu_list(){}

}