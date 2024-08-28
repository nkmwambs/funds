<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 *	@author Livingstone Onduso <londuso@ke.ci.org>
 *	@date		20th Aug, 2021
 *  @package grants <Finance management system for NGOs
 *  @method void get_menu_list()empty methods
 *  @method void index() empty methods
 *  @method __construct() main method
 *	@see  https://techsysnow.com
 */


class Medical_claim_admin_setting extends MY_Controller
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
    $this->load->library('medical_claim_admin_setting_library');
  }

  public function index()
  {

    //empty
  }

  public static function get_menu_list()
  {

    //empty
  }

}