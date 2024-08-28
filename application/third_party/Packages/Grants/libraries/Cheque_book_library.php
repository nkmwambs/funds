<?php


if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *  @author   : Nicodemus Karisa
 *  @date   : 27th September, 2018
 *  Finance management system for NGOs
 *  https://techsysnow.com
 *  NKarisa@ke.ci.org
 */

class Cheque_book_library extends Grants
{

  private $CI;

  function __construct(){
    parent::__construct();
    $this->CI =& get_instance();
  }

  function index(){}

  function page_position(){

    $widget = [];

    if($this->CI->action == 'view'){
      $cheque_book_id = hash_id($this->CI->id,'decode');

      $this->CI->read_db->where(array('cheque_book_id'=>$cheque_book_id));
      $current_status_id = $this->CI->read_db->get('cheque_book')->row()->fk_status_id;

      $max_cheque_book_status_ids = $this->CI->general_model->get_max_approval_status_id('Cheque_book');
      $next_status_id = $this->CI->general_model->next_status($current_status_id);
      $current_actors = $this->CI->general_model->current_approval_actor($current_status_id);
      $is_current_actor = count(array_intersect($this->CI->session->role_ids,$current_actors)) == 0 ? false : true;
      $is_not_final_action = $this->CI->general_model->show_label_as_button($current_status_id, $current_actors,'cheque_book', $cheque_book_id);

      if(in_array($next_status_id,$max_cheque_book_status_ids) && !$is_current_actor && $is_not_final_action){
        $status_message = "<div class='text-center' style='margin-bottom:20px;color:red;font-weight:bold;'>The cheque book is waiting approval after reinstatement</div>";
        $widget['position_1']['view'][] =  $status_message;
      }
    }
    
    return $widget;
  }

} 