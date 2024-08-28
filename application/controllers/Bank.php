<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Bank extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('bank_library');
  }

  function index(){}

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
      'bank_id',
      'bank_track_number',
      'bank_name',
      'bank_swift_code',
      'bank_is_active',
      'account_system_name'
    ];

    return $columns;
  }


  function get_banks(){

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
      $this->read_db->order_by('bank_id DESC');
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
      $this->read_db->where(array('bank.fk_account_system_id'=>$this->session->user_account_system_id));
    }

    $this->read_db->select($columns);
    $this->read_db->join('status','status.status_id=bank.fk_status_id');
    $this->read_db->join('account_system','account_system.account_system_id=bank.fk_account_system_id');

    $result_obj = $this->read_db->get('bank');
    
    $results = [];

    if($result_obj->num_rows() > 0){
      $results = $result_obj->result_array();
    }

    return $results;
  }

  function count_banks(){

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
      $this->read_db->where(array('bank.fk_account_system_id'=>$this->session->user_account_system_id));
    }

    $this->read_db->join('status','status.status_id=bank.fk_status_id');
    $this->read_db->join('account_system','account_system.account_system_id=bank.fk_account_system_id');
    

    $this->read_db->from('bank');
    $count_all_results = $this->read_db->count_all_results();

    return $count_all_results;
  }

  function show_list(){
   
    $draw =intval($this->input->post('draw'));
    $banks = $this->get_banks();
    $count_banks = $this->count_banks();

    $result = [];

    $cnt = 0;
    foreach($banks as $bank){
      $bank_id = array_shift($bank);
      $bank_track_number = $bank['bank_track_number'];
      $bank['bank_track_number'] = '<a href="'.base_url().$this->controller.'/view/'.hash_id($bank_id).'">'.$bank_track_number.'</a>';
      $bank['bank_is_active'] = $bank['bank_is_active'] == 1 ? get_phrase('yes') : get_phrase('no');
      $row = array_values($bank);

      $result[$cnt] = $row;

      $cnt++;
    }

    $response = [
      'draw'=>$draw,
      'recordsTotal'=>$count_banks,
      'recordsFiltered'=>$count_banks,
      'data'=>$result
    ];
    
    echo json_encode($response);
  }

  static function get_menu_list(){}

}
