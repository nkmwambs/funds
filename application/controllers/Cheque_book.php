<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *  @author   : Nicodemus Karisa
 *  @date   : 27th September, 2018
 *  Finance management system for NGOs
 *  https://techsysnow.com
 *  NKarisa@ke.ci.org/ LOnduso@ke.ci.org
 */


class Cheque_book extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('Cheque_book_library');
    $this->load->model('Cheque_book_model');
  }

  function index(){}

  function validate_start_serial_number(){
    
    $post = $this->input->post();
    $validate_start_serial_number = 0;

    $last_cheque_serial_number = $this->cheque_book_model->office_bank_last_cheque_serial_number($post['office_bank_id']);

    $next_new_cheque_book_start_serial = $last_cheque_serial_number + 1;

    if(($next_new_cheque_book_start_serial != $post['start_serial_number']) && $last_cheque_serial_number > 0){
      $validate_start_serial_number = $next_new_cheque_book_start_serial;
    }

    echo $validate_start_serial_number;
  }

  // function get_active_cheque_book_reset($office_bank_id){

  //   $get_active_cheque_book_reset = [];

  //   $this->read_db->where(array('fk_office_bank_id'=>$office_bank_id,'cheque_book_reset_is_active'=>1));
  //   $cheque_book_reset = $this->read_db->get('cheque_book_reset');

  //   if($cheque_book_reset->num_rows() > 0){
  //     $get_active_cheque_book_reset = $cheque_book_reset->row();
  //   }

  //   return $get_active_cheque_book_reset;
  // }

  function new_cheque_book_start_serial(){

    $post = $this->input->post();

    $office_bank_id = $post['office_bank_id'];

    $last_cheque_serial_number = $this->cheque_book_model->office_bank_last_cheque_serial_number($office_bank_id);
    
    $next_new_cheque_book_start_serial = 0;

    $active_cheque_book_reset = $this->cheque_book_model->get_active_cheque_book_reset($post['office_bank_id']);
    $is_active_cheque_book_reset = 0;//$active_cheque_book_reset->cheque_book_reset_status;
    $reset_start_serial_number = 0;//$active_cheque_book_reset->cheque_book_reset_serial;

    if(!empty($active_cheque_book_reset)){
      $is_active_cheque_book_reset = $active_cheque_book_reset->cheque_book_reset_is_active;
      $reset_start_serial_number = $active_cheque_book_reset->cheque_book_reset_serial;
    }

    if($last_cheque_serial_number > 0 && $is_active_cheque_book_reset == 0){
      $next_new_cheque_book_start_serial = $last_cheque_serial_number + 1;
    }else{
      $next_new_cheque_book_start_serial = $reset_start_serial_number;
    }
    
    echo $next_new_cheque_book_start_serial;
  }
  function get_active_chequebooks($office_bank_id){

    $active_cheque_book_exists = 0;

    // Deactivate active cheque book that were not deactivated when creating reset since they were not fully approved. 
    // This is a workaround for a legacy error but has been resolved by uptdating the method deactivate_cheque_book in cheque_book_model

    // First check if we have any active cheque book reset before doing the deactivate
    $this->read_db->where(array('fk_office_bank_id' => $office_bank_id, 'cheque_book_reset_is_active' => 1));
    $ctive_cheque_book_reset_obj = $this->read_db->get('cheque_book_reset');

    if($ctive_cheque_book_reset_obj->num_rows() > 0){
      $this->load->model('cheque_book_model');
      $this->cheque_book_model->deactivate_cheque_book($office_bank_id);
    }

    $active_cheque_book_exists=$this->cheque_book_model->get_active_chequebooks($office_bank_id);

    echo json_encode($active_cheque_book_exists);
  }

  function get_office_chequebooks($office_bank_id){

    $cheque_books=$this->cheque_book_model->get_office_chequebooks($office_bank_id);

    echo json_encode($cheque_books);
  }

  function get_max_id_cheque_book_for_office($office_bank_id){
    echo hash_id($this->cheque_book_model->get_max_id_cheque_book_for_office($office_bank_id),'encode');
  }

  function status_change($change_type = 'approve'){

    if (method_exists($this->cheque_book_model, 'post_approve_action')) {
      $this->cheque_book_model->post_approve_action();
    }
    
    parent::status_change($change_type);
    
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
    }
    elseif($this->action=='single_form_add'){
      // $user_offices=$this->user_model->user_hierarchy_offices($this->session->user_id, true);

      // $user_context_office=[];

      // foreach( $user_offices as  $user_office){
      //   $user_context_office=$user_office;
      // }
      // $office_ids=array_column($user_context_office, 'office_id');
      // $this->load->model('office_bank_model');

      $office_ids = array_column($this->session->hierarchy_offices, 'office_id');
      //$result['offices']=   $office_ids;
      
      $result['office_banks']=$this->cheque_book_model->retrieve_office_bank($office_ids);
    }
    
    else{
      $result = parent::result($id);
    }

    return $result;
  }

    /**
   * get_cheque_book_size(): returns a json string carring cheque_book_size and is_first_cheque_book
   * @author Nicodemus Karisa; Modified by Livingstone Onduso
   * @access public
   * @return void
   * @param int $office_bank_id
   */

  public function get_cheque_book_size(int $office_bank_id):void
  {
    $this->load->model('office_bank_model');

    $is_first_cheque_book = $this->cheque_book_model->is_first_cheque_book($office_bank_id);

    $cheque_book_size = $this->office_bank_model->get_cheque_book_size($office_bank_id);

    echo json_encode(['cheque_book_size' => $cheque_book_size, 'is_first_cheque_book' => $is_first_cheque_book]);
  }

  // public function is_first_cheque_book($office_bank_id)
  // {

  //   $this->read_db->where(array('fk_office_bank_id' => $office_bank_id));
  //   $office_bank_cheque_books_obj = $this->read_db->get('cheque_book');

  //   $is_first_cheque_book = true;

  //   if ($office_bank_cheque_books_obj->num_rows() > 1) {
  //     $is_first_cheque_book = false;
  //   }

  //   return $is_first_cheque_book;
  // }

  function columns(){
    $columns = [
      'cheque_book_id',
      'cheque_book_track_number',
      'office_bank_name',
      'cheque_book_use_start_date',
      'cheque_book_is_active',
      'cheque_book_start_serial_number',
      'cheque_book_count_of_leaves',
      'status_name'
    ];

    return $columns;
  }

  // function checkIfPreviousBookIsApproved($office_bank_id){
  //   $isPreviousBookApproved = true;

  //   $cheque_book_max_status = $this->general_model->get_max_approval_status_id('cheque_book');

  //   // log_message('error', json_encode($cheque_book_max_status));

  //   $this->read_db->where(array('fk_office_bank_id' => $office_bank_id));
  //   $this->read_db->where_not_in('fk_status_id', $cheque_book_max_status);
  //   $unapproved_books_count = $this->read_db->get('cheque_book')->num_rows();

  //   if($unapproved_books_count > 0) {
  //     $isPreviousBookApproved = false;
  //   }

  //   return $isPreviousBookApproved;
  // }

  function post_cheque_book(){

    $post=$this->input->post();

    // Check if the previous book is fully approved, if not deny creating a new book
    $isPreviousBookApproved = $this->cheque_book_model->checkIfPreviousBookIsApproved($post['fk_office_bank_id']);

    $last_id = 0;

    if($isPreviousBookApproved){
      $data['header']['cheque_book_track_number']=$this->grants_model->generate_item_track_number_and_name('cheque_book')['cheque_book_track_number'];
      $data['header']['cheque_book_name']=$this->grants_model->generate_item_track_number_and_name('cheque_book')['cheque_book_name'];
      $data['header']['fk_office_bank_id']=$post['fk_office_bank_id'];
      $data['header']['cheque_book_is_active']=0;
      $data['header']['cheque_book_start_serial_number']=$post['cheque_book_start_serial_number'];
      $data['header']['cheque_book_count_of_leaves']=$post['cheque_book_count_of_leaves'];
      $data['header']['cheque_book_use_start_date']=$post['cheque_book_use_start_date'];
      // $data['cheque_book_start_serial_number']=$post['cheque_book_start_serial_number'];
      $data['header']['cheque_book_created_date']=date('Y-m-d');
      $data['header']['cheque_book_created_by']=$this->session->user_id;
      $data['header']['cheque_book_last_modified_by'] = $this->session->user_id;
      $data['header']['cheque_book_created_date'] = date('Y-m-d');
      $data['header']['fk_approval_id'] = $this->grants_model->insert_approval_record('cheque_book');
      $data['header']['fk_status_id'] = $this->grants_model->initial_item_status('cheque_book');
  
      $last_id = $this->cheque_book_model->post_cheque_book($data);
    }


    echo $last_id;
  }

  public function redirect_to_voucher_after_approval($cheque_book_id)
    {
        // $cheque_book_id = hash_id($this->id, 'decode');

        $redirect = false;

        $this->write_db->where(array('cheque_book_id' => $cheque_book_id));
        $current_status_id = $this->write_db->get('cheque_book')->row()->fk_status_id;

        $has_voucher_create_permission = $this->user_model->check_role_has_permissions('Voucher', 'create');
        $max_cheque_book_status_ids = $this->general_model->get_max_approval_status_id('Cheque_book');
        $next_status_id = $this->general_model->next_status($current_status_id);

        $is_next_status_full_approval = in_array($next_status_id,$max_cheque_book_status_ids) ? true : false;
        
        if($has_voucher_create_permission && $is_next_status_full_approval){
            // $redirect_to_voucher_form = base_url() . 'voucher/multi_form_add';
            // header("Location:" . $redirect_to_voucher_form);
            $redirect = true;
        }
        
        echo $redirect;
    }


  function get_cheque_books(){

    $columns = $this->columns();
    array_push($columns, 'status_id');
    array_push($columns, 'cheque_book_is_used');
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
      $this->read_db->order_by('cheque_book_id DESC');
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
      $this->read_db->where_in('fk_office_id', array_column($this->session->hierarchy_offices,'office_id'));
    }

    $this->read_db->select($columns);
    $this->read_db->join('status','status.status_id=cheque_book.fk_status_id');
    $this->read_db->join('office_bank','office_bank.office_bank_id=cheque_book.fk_office_bank_id');
    $this->read_db->join('office','office.office_id=office_bank.fk_office_id');

    $result_obj = $this->read_db->get('cheque_book');
    
    $results = [];

    if($result_obj->num_rows() > 0){
      $results = $result_obj->result_array();
    }

    return $results;
  }

  function count_cheque_books(){

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
      $this->read_db->where_in('fk_office_id', array_column($this->session->hierarchy_offices,'office_id'));
    }

    $this->read_db->join('status','status.status_id=cheque_book.fk_status_id');
    $this->read_db->join('office_bank','office_bank.office_bank_id=cheque_book.fk_office_bank_id');
    $this->read_db->join('office','office.office_id=office_bank.fk_office_id');
    

    $this->read_db->from('cheque_book');
    $count_all_results = $this->read_db->count_all_results();

    return $count_all_results;
  }

  function show_list(){
   
    $draw =intval($this->input->post('draw'));
    $cheque_books = $this->get_cheque_books();
    $count_cheque_books = $this->count_cheque_books();

    $status_data = $this->general_model->action_button_data($this->controller);
    extract($status_data);

    $result = [];

    $cnt = 0;
    foreach($cheque_books as $cheque_book){
      $cheque_book_id = array_shift($cheque_book);
      $cheque_book_is_used = array_pop($cheque_book);
      $cheque_book_status = array_pop($cheque_book);

      $cheque_book_track_number = $cheque_book['cheque_book_track_number'];
      $cheque_book['cheque_book_track_number'] = '<a href="'.base_url().$this->controller.'/view/'.hash_id($cheque_book_id).'">'.$cheque_book_track_number.'</a>';
      $cheque_book['cheque_book_is_active'] = $cheque_book['cheque_book_is_active'] == 1 ? get_phrase('yes') : get_phrase('no');
      $row = array_values($cheque_book);

      $deactivate_action_buttons = $cheque_book_is_used ? true : false;
      $action = approval_action_button($this->controller, $item_status, $cheque_book_id, $cheque_book_status, $item_initial_item_status_id, $item_max_approval_status_ids, $deactivate_action_buttons);
      
      array_unshift($row, $action);

      $result[$cnt] = $row;

      $cnt++;
    }

    $response = [
      'draw'=>$draw,
      'recordsTotal'=>$count_cheque_books,
      'recordsFiltered'=>$count_cheque_books,
      'data'=>$result
    ];
    
    echo json_encode($response);
  }

  static function get_menu_list(){}

}