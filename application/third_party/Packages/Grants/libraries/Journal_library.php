<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Journal_library extends Grants
{

  private $CI;

  function __construct(){
    parent::__construct();
    $this->CI =& get_instance();
  }

  function index(){

  }

  function journal_records($office_id,$transacting_month,$project_allocation_ids = [], $office_bank_id = 0){
    return $this->CI->journal_model->journal_records($office_id,$transacting_month, $project_allocation_ids, $office_bank_id);
  }

  function month_opening_bank_cash_balance($office_id,$transacting_month,$office_bank_id = 0){
    // print_r($office_bank_id); exit;
    return $this->CI->journal_model->month_opening_bank_cash_balance($office_id,$transacting_month,$office_bank_id);
  }

  // function month_opening_cash_balance($office_id){
  //   return $this->CI->journal_model->month_opening_cash_balance($office_id);
  // }

  function get_office_data_from_journal($journal_id){
    return $this->CI->journal_model->get_office_data_from_journal($journal_id);
  }  

  function journal_navigation($office_id, $transacting_month){
    return $this->CI->journal_model->journal_navigation($office_id, $transacting_month);
  }

  function financial_accounts($office_id, $transacting_month){
    return $this->CI->journal_model->financial_accounts($office_id, $transacting_month);
  }

  private function empty_journal_cells($office_id, $transacting_month, $account_type = 'income'){
    
    $spread_cells = '';

    $financial_accounts = $this->CI->journal_model->financial_accounts($office_id, $transacting_month);
    
    for($i=0;$i<count($financial_accounts[$account_type]);$i++){
      $spread_cells .= "<td class='align-right'>0.00</td>";
    }
      
    return $spread_cells; 
  }

  function journal_spread($office_id, $spread, $transacting_month,$account_type = 'bank',$transaction_effect = 'income'){

    // log_message('error', json_encode([$account_type,$transaction_effect,$spread]));

    $financial_accounts = $this->CI->journal_model->financial_accounts($office_id, $transacting_month);

    $accounts = $transaction_effect == 'income'?$financial_accounts['income']:$financial_accounts['expense'];

    $spread_cells = "";
    
    if($transaction_effect == 'expense'){
      
      $spread_cells = "";
      
      // Fill up empty cells in spread when the account type is an expense type
      $spread_cells .= $this->empty_journal_cells($office_id, $transacting_month,'income');

      foreach($accounts as $account_id => $account_code){
          $transacted_amount = 0;
          foreach($spread as $spread_transaction){
              if(in_array($account_id,$spread_transaction) && $transaction_effect == 'expense'){
                  $transacted_amount += $spread_transaction['transacted_amount'];
              }
          }
          //if( !strpos($transaction_effect,'contra')){
            $spread_cells .=  "<td class='align-right spread_".$transaction_effect." spread_".$transaction_effect."_".$account_id."'>".number_format($transacted_amount,2)."</td>";
          //}
          
      } 

    }elseif($transaction_effect == 'income'){
      
      $spread_cells = "";

      foreach($accounts as $account_id => $account_code){
          $transacted_amount = 0;
          foreach($spread as $spread_transaction){
              if(in_array($account_id,$spread_transaction) && $transaction_effect == 'income'){
                  $transacted_amount += $spread_transaction['transacted_amount'];
              }
          }
          //if( !strpos($transaction_effect,'contra')){
            $spread_cells .=  "<td class='align-right spread_".$transaction_effect." spread_".$transaction_effect."_".$account_id."'>".number_format($transacted_amount,2)."</td>";
          //}
          
      } 
       // Fill up empty cells in spread when the account type is an income type
      $spread_cells .= $this->empty_journal_cells($office_id, $transacting_month,'expense');
    }elseif($transaction_effect == 'cash_contra' || $transaction_effect == 'bank_contra' || $transaction_effect == 'bank_to_bank_contra' || $transaction_effect == 'cash_to_cash_contra'){

      $spread_cells = "";
      $spread_cells .= $this->empty_journal_cells($office_id, $transacting_month,'income');
      $spread_cells .= $this->empty_journal_cells($office_id, $transacting_month,'expense');

    }
      
     
    

    return $spread_cells;
  }

  // function get_voucher_max_approval_status_id(){
  //   $this->CI->load->model('approval_model');
  //   return $this->CI->approval_model->get_max_approval_status_id('voucher');
  // }

   function page_position(){
    
    $widget['position_1']['view'][] = $this->CI->grants_package_library->list_project_allocation_without_office_bank_linkage();
    
    return $widget;
  }
}
