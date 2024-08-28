<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 *	@author Livingstone Onduso <londuso@ke.ci.org>
 *	@date		20th Aug, 2021
 *  @package grants <Finance management system for NGOs
 *  @method void get_menu_list()empty methods
 *  @method void index() empty method
 *  @method return type not applicable __construct() main method

 *	@see  https://techsysnow.com
 */

class Medical_claim_type extends MY_Controller
{

  public function __construct()
  {
    parent::__construct();

    $this->load->library('medical_claim_type_library');
  }

  public function index()
  {
    //Empty
  }

  public static function get_menu_list()
  {
    //Empty

  }

}