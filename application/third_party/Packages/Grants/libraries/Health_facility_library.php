<?php


if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Health_facility_library extends Grants
{

  private $CI;

  function __construct(){
    parent::__construct();
    $this->CI =& get_instance(); 
  }

  function index(){}

  function change_field_type(){

    $change_field_type = array();

    $change_field_type['support_docs_needed']['field_type'] = 'select';
    $change_field_type['support_docs_needed']['options'] = array(get_phrase('no'),get_phrase('yes'));

    return $change_field_type;
  }

} 