<?php


if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Department_library extends Grants
{

  private $CI;

  function __construct(){
    parent::__construct();
    $this->CI =& get_instance();
  }

  function index(){}

  function change_field_type(){
    
    $fields = []; 


    $this->CI->read_db->select(array('context_definition_id','context_definition_name'));
    $context_definition_names=$this->CI->read_db->get('context_definition')->result_array();

    $names=array_column($context_definition_names,'context_definition_name');

    $ids=array_column($context_definition_names,'context_definition_id');

    $combine_ids_and_names=array_combine($ids,$names);

    $fields['context_definition_name']['field_type'] = 'select';

    foreach($combine_ids_and_names as $id=>$name){
      $fields['context_definition_name']['options'][$id] = $name;
    }

    

    return $fields;
  }

} 