<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Office_bank_project_allocation extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('office_bank_project_allocation_library');
  }

  function index(){}

  function insert_office_bank_project_allocation(){
    echo $this->office_bank_project_allocation_model->add();
  }

  static function get_menu_list(){}

}