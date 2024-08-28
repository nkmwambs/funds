<?php


if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Livingstone Onduso
 *	@date		: 20th Aug, 2021
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *  londuso@ke.ci.org 
 */

class Beneficiary_library extends Grants
{

  private $CI;

  function __construct(){
    parent::__construct();
    $this->CI =& get_instance(); 
  }

  function index(){}

} 