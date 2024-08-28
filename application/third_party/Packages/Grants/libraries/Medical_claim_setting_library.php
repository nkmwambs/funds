<?php


if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Livingstone Onduso
 *	@date		: 20th Aug, 2021
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *  londuso@ke.ci.org
 */

class Medical_claim_setting_library extends Grants
{

  private $CI;

  function __construct(){
    parent::__construct();
    $this->CI =& get_instance();
  }

  function index(){}


  function change_field_type(){
    
    $fields = []; 


    $this->CI->read_db->select(array('medical_claim_admin_setting_id','medical_claim_admin_setting_name'));
    $medical_claim_admin_setting_names=$this->CI->read_db->get('medical_claim_admin_setting')->result_array();

    $setting_names=array_column($medical_claim_admin_setting_names,'medical_claim_admin_setting_name');

    $setting_ids=array_column($medical_claim_admin_setting_names,'medical_claim_admin_setting_id');

    $combine_ids_and_names=array_combine($setting_ids,$setting_names);

    $fields['medical_claim_setting_name']['field_type'] = 'select';

    foreach($combine_ids_and_names as $setting_id=>$setting_name){
      $fields['medical_claim_setting_name']['options'][$setting_id] = $setting_name;
    }

    

    return $fields;
  }

  // function change_field_type(){
    
  //   $fields = [];


  //   $this->CI->read_db->select(array('medical_claim_admin_setting_name'));
  //   $medical_claim_admin_setting_names=$this->CI->read_db->get('medical_claim_admin_setting')->result_array();

  //   $setting_names=array_column($medical_claim_admin_setting_names,'medical_claim_admin_setting_name');

  //   $fields['medical_claim_setting_name']['field_type'] = 'select';

  //   $fields['medical_claim_setting_name']['options'] = $setting_names;
    
  //   return $fields;
  // }



} 