<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Bank_model extends MY_Model
{
  public $table = 'bank'; // you MUST mention the table name
  public $primary_key = 'bank_id'; // you MUST mention the primary key
  public $fillable = array(); // If you want, you can set an array with the fields that can be filled by insert/update
  public $protected = array(); // ...Or you can set an array with the fields that cannot be filled by insert/update
  public $hidden_columns = array();

  function __construct()
  {
    parent::__construct();
    $this->load->database();
  }

  function delete($id = null)
  {
    return true;
  }

  function lookup_tables()
  {
    return ['account_system'];
  }

  function detail_tables()
  {
    return ['office_bank'];
  }

  public function list_table_visible_columns()
  {
    return ['bank_track_number', 'bank_name', 'bank_swift_code', 'bank_is_active', 'account_system_name'];
  }

  function single_form_add_visible_columns()
  {
    return ['bank_name ', 'bank_swift_code ', 'bank_is_active ', 'account_system_name'];
  }

  /**
   * transaction_validate_duplicates_columns
   * 
   * This is an override method. It lists all fields that needs to be checked if duplicate value
   * is about to be posted in the database.
   * 
   * @return Array
   */
  function transaction_validate_duplicates_columns()
  {
    return ['bank_swift_code'];
  }

  function list_table_where()
  {
    if (!$this->session->system_admin) {
      $this->read_db->where(array('account_system_code' => $this->session->user_account_system));
    }
  }

  public function show_list_edit_action($record):bool{
    $this->read_db->where(array('fk_bank_id' => $record['bank_id'], 'office_bank_is_active' => 1));
    $count_active_office_banks_for_bank = $this->read_db->get('office_bank')->num_rows();

    if($count_active_office_banks_for_bank > 0){
      return false;
    }

    return true;
  }

  // function show_list(){
  //   return Output_base::load($this->action);
  // }

}
