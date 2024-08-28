<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Voucher_type extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('voucher_type_library');
  }

  function index(){}

  function get_voucher_type_effects($voucher_type_account_id){
     $this->read_db->where(array('voucher_type_account_id'=>$voucher_type_account_id));
     $voucher_type_account_code = $this->read_db->get('voucher_type_account')->row()->voucher_type_account_code;

     $voucher_type_effect_codes = [];

     if($voucher_type_account_code == 'bank'){
        $voucher_type_effect_codes = ['income','expense','bank_contra','bank_to_bank_contra'];
     }elseif($voucher_type_account_code == 'cash'){
        $voucher_type_effect_codes = ['income','expense','cash_contra','cash_to_cash_contra'];
     }

     if(!empty($voucher_type_effect_codes)){
        $this->read_db->where_in('voucher_type_effect_code',$voucher_type_effect_codes);
     }

     $this->read_db->select(array('voucher_type_effect_id','voucher_type_effect_name','voucher_type_effect_code'));
     $voucher_type_effect = $this->read_db->get('voucher_type_effect')->result_array();
     
     echo json_encode($voucher_type_effect);
  }

  function check_select_voucher_type_effect($voucher_type_effect_id){
     $voucher_type_effect_code = $this->read_db->get_where('voucher_type_effect',
     array('voucher_type_effect_id'=>$voucher_type_effect_id))->row()->voucher_type_effect_code;

     echo $voucher_type_effect_code;
  }

  function result($id = 0){

   $result = [];

   if($this->action == 'list'){
     $columns = $this->columns();
     array_shift($columns);
     $result['columns'] = $columns;
     $result['has_details_table'] = false; 
     $result['has_details_listing'] = false;
     $result['is_multi_row'] = false;
     $result['show_add_button'] = true;
   }else{
     $result = parent::result($id);
   }

   return $result;
 }

 function columns(){
   $columns = [
     'voucher_type_id',
     'voucher_type_track_number',
     'voucher_type_name',
     'voucher_type_abbrev',
     'voucher_type_is_active',
     'voucher_type_account_name',
     'voucher_type_effect_name',
     'voucher_type_is_cheque_referenced',
     'account_system_name'
   ];

   return $columns;
 }


 function get_voucher_types(){

   $columns = $this->columns();
   $search_columns = $columns;

   // Limiting records
   $start = intval($this->input->post('start'));
   $length = intval($this->input->post('length'));

   $this->read_db->limit($length, $start);

   // Ordering records

   $order = $this->input->post('order');
   $col = '';
   $dir = 'desc';
   
   if(!empty($order)){
     $col = $order[0]['column'];
     $dir = $order[0]['dir'];
   }
         
   if( $col == ''){
     $this->read_db->order_by('voucher_type_id DESC');
   }else{
     $this->read_db->order_by($columns[$col],$dir); 
   }

   // Searching

   $search = $this->input->post('search');
   $value = $search['value'];

   array_shift($search_columns);

   if(!empty($value)){
     $this->read_db->group_start();
     $column_key = 0;
       foreach($search_columns as $column){
         if($column_key == 0) {
           $this->read_db->like($column,$value,'both'); 
         }else{
           $this->read_db->or_like($column,$value,'both');
       }
         $column_key++;				
     }
     $this->read_db->group_end();      
   }
   
   if(!$this->session->system_admin){
     $this->read_db->where(array('voucher_type.fk_account_system_id'=>$this->session->user_account_system_id));
     $this->read_db->where(array('voucher_type.voucher_type_is_hidden' => 0));
   }

   $this->read_db->select($columns);
   $this->read_db->join('status','status.status_id=voucher_type.fk_status_id');
   $this->read_db->join('account_system','account_system.account_system_id=voucher_type.fk_account_system_id');
   $this->read_db->join('voucher_type_account','voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
   $this->read_db->join('voucher_type_effect','voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');

   $result_obj = $this->read_db->get('voucher_type');
   
   $results = [];

   if($result_obj->num_rows() > 0){
     $results = $result_obj->result_array();
   }

   return $results;
 }

 function count_voucher_types(){

   $columns = $this->columns();
   $search_columns = $columns;

   // Searching

   $search = $this->input->post('search');
   $value = $search['value'];

   array_shift($search_columns);

   if(!empty($value)){
     $this->read_db->group_start();
     $column_key = 0;
       foreach($search_columns as $column){
         if($column_key == 0) {
           $this->read_db->like($column,$value,'both'); 
         }else{
           $this->read_db->or_like($column,$value,'both');
       }
         $column_key++;				
     }
     $this->read_db->group_end();
   }
   
   if(!$this->session->system_admin){
      $this->read_db->where(array('voucher_type.fk_account_system_id'=>$this->session->user_account_system_id));
      $this->read_db->where(array('voucher_type.voucher_type_is_hidden' => 0));
    }
 
    $this->read_db->select($columns);
    $this->read_db->join('status','status.status_id=voucher_type.fk_status_id');
    $this->read_db->join('account_system','account_system.account_system_id=voucher_type.fk_account_system_id');
    $this->read_db->join('voucher_type_account','voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
    $this->read_db->join('voucher_type_effect','voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
 
   

   $this->read_db->from('voucher_type');
   $count_all_results = $this->read_db->count_all_results();

   return $count_all_results;
 }

 function show_list(){
  
   $draw =intval($this->input->post('draw'));
   $voucher_types = $this->get_voucher_types();
   $count_voucher_types = $this->count_voucher_types();

   $result = [];

   $cnt = 0;
   foreach($voucher_types as $voucher_type){
     $voucher_type_id = array_shift($voucher_type);
     $voucher_type_track_number = $voucher_type['voucher_type_track_number'];
     $voucher_type['voucher_type_track_number'] = '<a href="'.base_url().$this->controller.'/view/'.hash_id($voucher_type_id).'">'.$voucher_type_track_number.'</a>';
     $voucher_type['voucher_type_is_active'] = $voucher_type['voucher_type_is_active'] == 1 ? get_phrase('yes') : get_phrase('no');
     $voucher_type['voucher_type_effect_name'] = ucwords(str_replace('_',' ',$voucher_type['voucher_type_effect_name']));
     $voucher_type['voucher_type_is_cheque_referenced'] = $voucher_type['voucher_type_is_cheque_referenced'] == 1 ? get_phrase('yes') : get_phrase('no');
     $row = array_values($voucher_type);

     $result[$cnt] = $row;

     $cnt++;
   }

   $response = [
     'draw'=>$draw,
     'recordsTotal'=>$count_voucher_types,
     'recordsFiltered'=>$count_voucher_types,
     'data'=>$result
   ];
   
   echo json_encode($response);
 }

  static function get_menu_list(){}

}