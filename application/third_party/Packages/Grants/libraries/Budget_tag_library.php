<?php


if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Budget_tag_library extends Grants
{

  private $CI;

  function __construct(){
    parent::__construct();
    $this->CI =& get_instance();
  }

  function index(){}

  function change_field_type(){

    $budget_review_count_obj = $this->CI->read_db->get_where('budget_review_count',
    array('fk_account_system_id'=>$this->CI->session->user_account_system_id));

    $max_review_count = 4;

    if($budget_review_count_obj->num_rows() > 0){
      $max_review_count = $budget_review_count_obj->row()->budget_review_count_number;
    }

    $range_of_review_count = range(1, $max_review_count);

    $budget_tag_level = [];

    foreach($range_of_review_count as $review_count){
      $budget_tag_level[$review_count] = $review_count == 1 ? get_phrase('initial_budget') : addOrdinalNumberSuffix($review_count - 1). ' ' .get_phrase('budget_review');
    }

    $field_type['budget_tag_level']['field_type'] = 'select';
    $field_type['budget_tag_level']['options'] = $budget_tag_level;

    return $field_type;
  }

} 