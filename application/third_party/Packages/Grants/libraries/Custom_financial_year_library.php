<?php


if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Custom_financial_year_library extends Grants
{

  private $CI;

  function __construct(){
    parent::__construct();
    $this->CI =& get_instance();
  }

  function change_field_type(){
    $fields = [];

    $this->CI->read_db->select(array('month_number','month_name'));
    $months = $this->CI->read_db->get('month')->result_array();

    $fields['custom_financial_year_start_month']['field_type'] = 'select';

    foreach($months as $month){
      $fields['custom_financial_year_start_month']['options'][$month['month_number']] = $month['month_name'];
    }
    
    // $fields['custom_financial_year_reset_date']['field_type'] = 'text';

    return $fields;
  }

  // function change_field_type(){
  //   return array('budget_item_note'=>array('field_type'=>'longtext'));
  // }

  function index(){}

} 