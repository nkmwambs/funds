<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Onduso
 *	@date		: 20th Feb, 2023
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	londuso@ke.ci.org
 */


class Reimbursement_comment extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('reimbursement_comment_library');
  }

  function index(){}

  static function get_menu_list(){}

}