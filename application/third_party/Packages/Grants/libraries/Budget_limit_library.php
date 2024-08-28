<?php


if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Budget_limit_library extends Grants
{

  private $CI;

  function __construct(){
    parent::__construct();
    $this->CI =& get_instance();
  }

  function index(){}

  // function change_field_type(){
  //   $fields = [];

  //   $current_year = date('y');
  //   $year_range = range($current_year - 1,$current_year + 3);

  //   $fields['budget_limit_year']['field_type'] = 'select';

  //   foreach($year_range as $year){
  //     $fields['budget_limit_year']['options'][$year] = 'FY'.$year;
  //   }

  //   return $fields;
  // }

  function load_budget_list_view($budget_id){

    $this->CI->load->model('budget_limit_model');
    
    $data['data'] = $this->CI->budget_limit_model->get_budget_limit_by_budget_id($budget_id);
  
    $budget_limit_view = $this->CI->load->view('budget_limit/budget_limit_list', $data, true);

    // log_message('error', json_encode($budget_limit_view));

    return $budget_limit_view;
  }

  function page_position(){
    $budget_limit_view = '';

    if($this->CI->sub_action != null && $this->CI->sub_action == 'budget'){
      $budget_id = hash_id($this->CI->id, 'decode');
      $budget_limit_view = $this->load_budget_list_view($budget_id);
    }

    $widget['position_1']['single_form_add'][] =  $budget_limit_view;
    
    return $widget;
  }

} 