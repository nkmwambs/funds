<?php


if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Onduso
 *	@date		: 20th Feb, 2023
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	londuso@ke.ci.org
 */

class Reimbursement_comment_library extends Grants
{

  private $CI;

  function __construct(){
    parent::__construct();
    $this->CI =& get_instance();
  }

  function index(){}

} 