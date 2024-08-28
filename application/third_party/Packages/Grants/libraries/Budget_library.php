<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Budget_library extends Grants
{

  private $CI;

  function __construct(){
    parent::__construct();
    $this->CI =& get_instance();
  }

  function index(){

  }

  function change_field_type(){
    $fields = [];

    $current_year = date('y');
    $year_range = range($current_year - 1,$current_year + 3);

    $fields['budget_year']['field_type'] = 'select';

    foreach($year_range as $year){
      $fields['budget_year']['options'][$year] = 'FY'.$year;
    }
    return $fields;
  }

}
