<?php


if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Email_template_library extends Grants
{

  private $CI;

  function __construct(){
    parent::__construct();
    $this->CI =& get_instance();
  }

  function index(){}

  function change_field_type(){

    $permission_labels = ['1' => 'create', '2' => 'approval', '3' => 'update', '4' => 'delete'];

    $field_type['permission_label_name']['field_type'] = 'select';
    $field_type['permission_label_name']['options'] = $permission_labels;

    return $field_type;
  }


} 