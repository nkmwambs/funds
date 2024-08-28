<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Permission_library extends Grants
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

    $change_field_type['permission_type']['field_type']='select';
    $change_field_type['permission_type']['options'] = array(
      '1'=>get_phrase('page_access'),
      '2'=>get_phrase('field_access')
    );

    return $change_field_type;
  }

  function default_field_value(){
    return array(
      'permission_type'=>1,
      'permission_is_active'=>1
    );
  }


}
