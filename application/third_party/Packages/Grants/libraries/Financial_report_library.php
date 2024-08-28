<?php


if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *  @author   : Nicodemus Karisa
 *  @date   : 27th September, 2018
 *  Finance management system for NGOs
 *  https://techsysnow.com
 *  NKarisa@ke.ci.org
 */

class Financial_report_library extends Grants
{

  private $CI;

  function __construct(){
    parent::__construct();
    $this->CI =& get_instance();

  }

  function index(){}

  function financial_report_information($report_id, $offices_ids = [],$reporting_month = ''){
    // print_r($report_id); exit;
    return $this->CI->financial_report_model->financial_report_information($report_id, $offices_ids, $reporting_month);
  }

  function month_income_account_receipts($office_ids, $start_date_of_month,$project_ids = [],$office_bank_ids = []){
    return $this->CI->financial_report_model->month_income_account_receipts($office_ids, $start_date_of_month,$project_ids,$office_bank_ids);
  }

  function month_income_account_expenses($office_ids, $start_date_of_month,$project_ids = [],$office_bank_ids = []){
    return $this->CI->financial_report_model->month_income_account_expenses($office_ids, $start_date_of_month,$project_ids, $office_bank_ids);
  }

  function month_income_opening_balance($office_ids, $start_date_of_month, $income_accounts, $project_ids = [], $office_bank_ids = []){
    return $this->CI->financial_report_model->month_income_opening_balance($office_ids, $start_date_of_month,  $income_accounts, $project_ids, $office_bank_ids);
  }

  function income_accounts($office_ids, $project_ids = []){
    return $this->CI->financial_report_model->income_accounts($office_ids, $project_ids);
  }

  function get_month_active_projects($office_ids,$reporting_month,$show_active_only = false){
    return $this->CI->financial_report_model->get_month_active_projects($office_ids,$reporting_month);
  }

  // function page_position(){

  //   $widget['position_1'][] = Widget_base::load('button','Show Combined Report',base_url().'financial_report/view/');

  //   return $widget;
  // }
  function page_position(){
    
    $widget['position_1']['view'][] = $this->CI->grants_package_library->list_project_allocation_without_office_bank_linkage();
    
    return $widget;
  }

} 