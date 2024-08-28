<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Message extends MY_Controller
{

  function __construct(){
    parent::__construct();

  }

  // function delete($id = null){

  // }

  function index(){}

  // static function get_menu_list(){
  //
  // }

    function delete_note($message_id, $message_detail_id){

      // ALL QUERIES MUST USE write_db HANDLE. DO NOT CHANGE

      $this->write_db->where(array('message_detail_id' => $message_detail_id));
      $this->write_db->delete('message_detail');
      
      $this->write_db->where(array('fk_message_id' => $message_id));
      $available_remaining_message_details = $this->write_db->get('message_detail')->num_rows();

      if($available_remaining_message_details == 0){
          $this->write_db->where(array('message_id' => $message_id));
          $this->write_db->delete('message');
      }

      if($this->write_db->affected_rows() > 0){
        echo get_phrase('deletion_successful');
      }else{
        echo get_phrase('deletion_error_occurred');
      }
    }
}
