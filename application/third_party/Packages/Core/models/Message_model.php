<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Message_model extends MY_Model
{
  public $table = 'message'; // you MUST mention the table name
  public $dependant_table = 'message_detail';
  public $primary_key = 'message_id'; // you MUST mention the primary key

  function __construct(){
    parent::__construct();
    $this->load->database();

  }

  function index(){

  }

  public function lookup_tables(){
    return ['fk_approve_item_id'];
  }

 
  public function detail_tables(){
    return ['message_detail'];
  }

  function test(){
    return "Test";
  }

  function get_chat_messages($approve_item_name,$record_primary_key){

    $approve_item_id = $this->read_db->get_where('approve_item',
    array('approve_item_name'=>$approve_item_name))->row()->approve_item_id;


    $this->read_db->select(array(
      'fk_user_id as author',
      'message_detail_content as message',
      'message_detail_created_date as message_date'));
    
    $this->read_db->join('message','message.message_id=message_detail.fk_message_id');  
    $this->read_db->order_by('message_detail_created_date DESC');
    
    $chat_messages = $this->read_db->get_where('message_detail',
    array('fk_approve_item_id'=>$approve_item_id,
    'message_record_key'=>hash_id($this->id,'decode')))->result_array();
   
    return $chat_messages;
    
  }

  function update_message($message_detail_id, $note){

    $data['message_detail_content'] = $note;
    $data['message_detail_last_modified_date'] = date('Y-m-d h:i:s');
    $data['message_detail_last_modified_by'] = $this->session->user_id;
    $data['message_detail_readers'] = NULL;
    
    $this->write_db->where(array('message_detail_id' => $message_detail_id));
    $this->write_db->update('message_detail', $data);

    $response = 0;

    if($this->write_db->affected_rows()){
      $response = 1;
    }

    return $response;
  }

  function post_new_message($approve_item, $primary_key, $message_body){

    $message_track = $this->grants_model->generate_item_track_number_and_name('message');
    $message_detail_track = $this->grants_model->generate_item_track_number_and_name('message_detail');

    $this->read_db->select(array('approve_item_id'));
    $this->read_db->where(array('approve_item_name' => $approve_item));
    $approve_item_id = $this->read_db->get('approve_item')->row()->approve_item_id;

    $this->write_db->trans_start();

    $insert_message_data = [
      'message_track_number' => $message_track['message_track_number'],
      'message_name' => $message_track['message_name'],
      'fk_approve_item_id' => $approve_item_id,
      'message_record_key' => $primary_key,
      'message_created_by' => $this->session->user_id,
      'message_created_date' => date('Y-m-d h:i:s')
    ];

    $this->write_db->insert('message', $insert_message_data);

    $message_id = $this->write_db->insert_id();

    $insert_detail_data = [
      'message_detail_track_number' => $message_detail_track['message_detail_track_number'],
      'message_detail_name' => $message_detail_track['message_detail_name'],
      'fk_user_id' => $this->session->user_id,
      'message_detail_content' => $message_body,
      'fk_message_id' => $message_id,
      'message_detail_created_date' => date('Y-m-d h:i:s'),
      'message_detail_created_by' => $this->session->user_id
    ];

    $this->write_db->insert('message_detail', $insert_detail_data);

    $this->write_db->trans_complete();
    
    $response = 0;
    
    if($this->write_db->trans_status() == true){
      $response = 1;
    }

    return $response;
  }

  function notes_history($item_id){
    
    $data['notes'] = [];

    $this->read_db->select(array('user_id','message_detail_last_modified_by as last_modified_by',
    "message_id",'message_detail_last_modified_date as last_modified_date','message_record_key',
    "message_detail_id","CONCAT(user_firstname, ' ', user_lastname) as creator",
    'message_detail_content as body', 'message_detail_created_date as created_date', 'message_detail_readers as message_readers'));
    $this->read_db->where(array('approve_item_name' => 'budget_item', 'message_record_key' => $item_id));
    $this->read_db->join('message','message.message_id=message_detail.fk_message_id');
    $this->read_db->join('user','user.user_id=message_detail.fk_user_id');
    $this->read_db->join('approve_item','approve_item.approve_item_id=message.fk_approve_item_id');
    $messages_obj = $this->read_db->get('message_detail');

    if($messages_obj->num_rows() > 0){
      $data['notes'] = $messages_obj->result_array();
    }

    return $this->load->view('message/message_holder', $data, true);
  }

  function mark_note_as_read($reader_user_id, $message_detail_id){

    $readers = "[]";

    $this->read_db->select(array("CONCAT(user_firstname,' ',user_lastname) as fullname"));
    $this->read_db->where(array('user_id' => $reader_user_id));
    $reader_user_fullname = $this->read_db->get('user')->row()->fullname;

    $this->read_db->select(array('message_detail_readers'));
    $this->read_db->where(array('message_detail_id' => $message_detail_id));
    $message_detail_readers_array = $this->read_db->get('message_detail')->row_array();

    $message_detail_readers = $message_detail_readers_array['message_detail_readers'];

    if($message_detail_readers == "" && $message_detail_readers == NULL){
      $readers = json_encode([['user_id' => $reader_user_id, 'fullname' => $reader_user_fullname]]);
    }else{

      $current_readers = json_decode($message_detail_readers);
      $readers_ids = array_column($current_readers, 'user_id');

      $readers = $message_detail_readers;

      if(!in_array($reader_user_id, $readers_ids)){
        array_push($current_readers, ['user_id' => $reader_user_id, 'fullname' => $reader_user_fullname]);
        $readers = json_encode($current_readers);
      }

    }

    $data['message_detail_readers'] = $readers;
    $this->write_db->where(array('message_detail_id' => $message_detail_id));
    $this->write_db->update('message_detail', $data);
  }

}
