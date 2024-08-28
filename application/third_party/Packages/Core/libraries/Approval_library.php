<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Approval_library extends Grants
{

  private $CI;

  public $menu_icon = 'fa fa-check';

  function __construct(){
    parent::__construct();
    $this->CI =& get_instance();
  }

  function index(){

  }

}
