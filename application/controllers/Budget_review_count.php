<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Budget_review_count extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('budget_review_count_library');
  }

  function index(){}

  static function get_menu_list(){}

}