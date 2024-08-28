<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Project_library extends Grants
{

  private $CI;

  function __construct(){
    parent::__construct();
    $this->CI =& get_instance();
  }

  function index(){}

  function default_field_value(){
    $default_field_values = [];

    if(!$this->CI->session->system_admin){
      $default_field_values['fk_funding_status_id'] = $this->CI->read_db->get_where('funding_status',
      array('fk_account_system_id'=>$this->CI->session->user_account_system_id,
        'funding_status_is_active'=>1,'funding_status_is_available'=>1))->row()->funding_status_id;
    }   

    return $default_field_values;
  }


}
