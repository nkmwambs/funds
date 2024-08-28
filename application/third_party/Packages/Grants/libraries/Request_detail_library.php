<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Request_detail_library extends Grants
{

  private $CI;

  function __construct(){
    parent::__construct();
    $this->CI =& get_instance();
  }

  function index(){

  }

  function change_field_type(){
    //return array('request_detail_description'=>array('field_type'=>'select','options'=>array('One','Two')));
    $change_type['request_detail_conversion_set']['field_type'] = 'select';
    $change_type['request_detail_conversion_set']['options'] = array(get_phrase('no'),get_phrase('yes'));

    return $change_type;
  }

}
