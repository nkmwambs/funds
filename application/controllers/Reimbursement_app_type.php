<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 *	@author Livingstone Onduso <londuso@ke.ci.org>
 *	@date		18th Feb, 2023
 *  @package grants <Finance management system for NGOs
 *  @method void get_menu_list()empty methods
 *  @method void index() empty method
 *  @method return type not applicable __construct() main method
 *	@see  https://techsysnow.com
 */

class Reimbursement_app_type extends MY_Controller
{

   /**
   * __construct(): This is the primary or main method that initializes variables to be used other methods
   * @author Livingstone Onduso
   * @access public
   * @return not applicable
   */

  public function __construct()
  {
    parent::__construct();

    $this->load->library('reimbursement_app_type_library');
  }

   /**
   * index(): empty method <no body>
   * @author Livingstone Onduso
   * @access public
   * @return void
   */

  public function index()
  {
    //Empty
  }

  public static function get_menu_list()
  {
    //Empty
  }

}