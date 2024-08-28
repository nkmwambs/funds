<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class User_library extends Grants
{

  private $CI;

  function __construct(){
    parent::__construct();
    $this->CI =& get_instance();
  }

  function index(){

  }

  function change_field_type(){
    $change_field_type = array();

    $change_field_type['user_system_admin']['field_type'] = 'select';
    $change_field_type['user_system_admin']['options'] = array(get_phrase('no'),get_phrase('yes'));

    $change_field_type['user_password']['field_type'] = 'password';

    $change_field_type['user_email']['field_type'] = 'email';

    $change_field_type['user_is_center_group_manager']['field_type'] = 'select';
    $change_field_type['user_is_center_group_manager']['options'] = array(get_phrase('no'),get_phrase('yes'));

    return $change_field_type;
  }


}
