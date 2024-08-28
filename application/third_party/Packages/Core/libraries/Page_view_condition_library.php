<?php


if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Page_view_condition_library extends Grants
{

  private $CI;

  function __construct(){
    parent::__construct();
    $this->CI =& get_instance();
  }

  function index(){}

  function change_field_type(){
    $change_field_type['page_view_condition_operator']['field_type'] = 'select';
    $change_field_type['page_view_condition_operator']['options'] = condition_operators();

    return $change_field_type;
  }

} 