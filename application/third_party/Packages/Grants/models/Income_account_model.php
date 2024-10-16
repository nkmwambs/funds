<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Income_account_model extends MY_Model
{
  public $table = 'income_account'; // you MUST mention the table name
  public $primary_key = 'income_account_id'; // you MUST mention the primary key


  function __construct()
  {
    parent::__construct();
    $this->load->database();
  }


  function delete($id = null)
  {
  }

  function index()
  {
  }

  function lookup_tables()
  {
    return ['account_system','funder'];
  }

  function detail_tables()
  {
    return ['expense_account'];
  }

  // function detail_list_table_where(){
  //   $this->read_db->where(array('fk_funder_id'=> hash_id($this->id, 'decode')));
  // }

  function list_table_where()
  {
    if (!$this->session->system_admin) {
      $this->read_db->where(array('account_system_code' => $this->session->user_account_system));
    }
  }

  function transaction_validate_duplicates_columns()
  {

    return ['income_account_code', 'fk_account_system_id'];
  }

  public function master_table_visible_columns()
  {
    return [
      "income_account_name",
      "income_account_description",
      "income_account_code",
      "income_account_is_active",
      "income_account_is_budgeted",
      "funder_name",
      "income_vote_heads_category_name",
      "account_system_name",
      "income_account_created_by",
      "income_account_created_date",
      "income_account_last_modified_date",
      "income_account_last_modified_by",
    ];
  }

  public function list_table_visible_columns()
  {
    return [
      "income_account_track_number",
      "income_account_name",
      "income_account_code",
      "income_account_is_active",
      "funder_name",
      "account_system_name"
    ];
  }

  public function edit_visible_columns()
  {
    return [
      "income_account_name",
      "income_account_description",
      "income_account_code",
      "income_account_is_active",
      "income_account_is_budgeted",
      "funder_name",
      "income_vote_heads_category_name",
      "account_system_name"
    ];
  }

  public function single_form_add_visible_columns()
  {
    return [
      "income_account_name",
      "income_account_description",
      "income_account_code",
      // "income_account_is_active",
      "income_account_is_budgeted",
      "funder_name",
      "income_vote_heads_category_name",
      "account_system_name"
    ];
  }

  function list()
  {
  }

  function view()
  {
  }
 /**
  * Enhancement
   *get_project_allocation_income_account(): Returns  income account numeric value
   * @author Livingstone Onduso: Dated 29-06-2023
   * @access public
   * @param Int Int $project_allocation_id
   * @return int
   **/
  function get_project_allocation_income_account(int $project_allocation_id):int
  {
    $this->read_db->select(['fk_income_account_id']);
    $this->read_db->join('project_allocation', 'project_allocation.fk_project_id=project_income_account.fk_project_id');
    $this->read_db->where(['project_allocation_id' => $project_allocation_id]);
    $income_account_id=$this->read_db->get('project_income_account')->row()->fk_income_account_id;

    return $income_account_id;
  }

  function get_expense_income_account($expense_income_id)
  {

    $this->read_db->select(array('income_account_id', 'income_account_name'));
    $this->read_db->join('expense_account', 'expense_account.fk_income_account_id=income_account.income_account_id');
    $this->read_db->where(array('expense_account_id' => $expense_income_id));
    $income_account = $this->read_db->get('income_account')->row();

    return $income_account;
  }
  //This piece code/ function added by Onduso 28/7/2022
  function action_before_insert($post_array)
  {

    // $fk_account_system_id = $post_array['header']['fk_account_system_id'];

    // $income_account = $this->grants_model->overwrite_field_value_on_post(
    //   $post_array,
    //   'income_account',
    //   'income_account_is_active',
    //   1,
    //   0,
    //   [
    //     'fk_account_system_id' => $fk_account_system_id,
    //     'income_account_is_active' => 1
    //   ]
    // );

    // Get funder code 
    $this->load->model('funder_model');
    $funder = $this->funder_model->get_funder_by_id($post_array['header']['fk_funder_id']);
    $funder_code = !empty($funder) ? $funder['funder_code'] : '';

    $post_array['header']['income_account_code'] = $funder_code.'-'.return_sanitized_code($post_array['header']['income_account_code']);

    return $post_array;
  }

  function get_income_account_by_id($income_account_id){
    $income_account = $this->read_db->get_where('income_account', array('income_account_id' => $income_account_id))->row_array();
    return $income_account;
  }

  function income_account_by_funder_id($funder_id){

    $income_accounts = [];

    $this->read_db->select(array('income_account_id','income_account_name'));
    $this->read_db->where(array('income_account_is_budgeted' => 1, 'income_account_is_active' => 1, 'fk_funder_id' => $funder_id));
    $income_account_obj = $this->read_db->get('income_account');

    if($income_account_obj->num_rows() > 0){
      $income_accounts = $income_account_obj->result_array();
    }

    return $income_accounts;
  }

  // function income_account_by_office_id($office_id){

  //   $income_accounts = [];

  //   $this->read_db->select(array('income_account_id','income_account_name'));
  //   $this->read_db->where(array('income_account_is_budgeted' => 1, 'income_account_is_active' => 1, 'office_id' => $office_id));
  //   $this->read_db->join('account_system','account_system.account_system_id=income_account.fk_account_system_id');
  //   $this->read_db->join('office','office.fk_account_system_id=account_system.account_system_id');
  //   $income_account_obj = $this->read_db->get('income_account');

  //   if($income_account_obj->num_rows() > 0){
  //     $income_accounts = $income_account_obj->result_array();
  //   }

  //   return $income_accounts;
  // }
 
  // function lookup_values_where($table = ''){
  //   return array('income_account_is_donor_funded'=>1);
  // }

}
