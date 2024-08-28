<?php


if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Funds_transfer_library extends Grants
{

  private $CI;

  function __construct(){
    parent::__construct();
    $this->CI =& get_instance();
  }

  function index(){}

  function change_field_type(){
    $change_field_type = array();

    $change_field_type['funds_transfer_type']['field_type'] = 'select';
    $change_field_type['funds_transfer_type']['options'] = ['1' => get_phrase('income_type'), '2' => get_phrase('expense_type')];

    return $change_field_type;
  }

} 