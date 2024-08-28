<?php


if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Page_view_role_library extends Grants
{

  private $CI;

  function __construct(){
    parent::__construct();
    $this->CI =& get_instance();
  }

  function index(){}

  function change_field_type(){
    $change_field_type['page_view_role_is_default']['field_type'] = 'select';
    $change_field_type['page_view_role_is_default']['options'] = array(get_phrase('no'),get_phrase('yes'));

    return $change_field_type;
  }

} 