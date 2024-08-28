<?php


if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Onduso Livingstone
 *	@date		: 17th Feb, 2023
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	LOnduso@ke.ci.org
 */

class Reimbursement_diagnosis_type_library extends Grants
{

  private $CI;

  function __construct(){
    parent::__construct();
    $this->CI =& get_instance();
  }

  function index(){}

} 