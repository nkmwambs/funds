<?php defined('BASEPATH') or exit('No direct script access allowed');


class MY_Model extends CI_Model
{

  public function __construct()
  {
    parent::__construct();
    $this->load->database();
  }

  function multi_select_field()
  {
    return '';
  }

  function lookup_tables()
  {
    //$table_name = $this->controller;
    //return $this->_derived_lookup_tables($table_name);
    
    return list_lookup_tables();
  }

  function list_table_where()
  {

    $this->_list_table_where_by_account_system();
  }

  function _list_table_where_by_account_system()
  {
    $tables_with_account_system_relationship = tables_with_account_system_relationship();

    $lookup_tables = $this->lookup_tables();

    $account_system_table = '';

    if (!empty($lookup_tables)) {
      foreach ($lookup_tables as $lookup_table) {
        if (in_array($lookup_table, $tables_with_account_system_relationship)) {
          $account_system_table = $lookup_table;
          break;
        }
      }

      if (!$this->session->system_admin && $account_system_table !== '') {
        $this->read_db->where(array($account_system_table . '.fk_account_system_id' => $this->session->user_account_system_id));
      }
    }
  }

  public function detail_tables()
  {
    return list_detail_tables();
  }

  // Works in the approve method of My_Controller to help in putting on actions in an approval process
  // E.g. Update a certain table after approval
  public function post_approve_action()
  {
  }

  public function edit_visible_columns()
  {
  }

  public function master_table_visible_columns()
  {
  }

  public function list_table_visible_columns()
  {
  }

  public function list_table_hidden_columns()
  {
  }

  public function detail_list_table_visible_columns()
  {
  }

  public function detail_list_table_hidden_columns()
  {
  }

  public function single_form_add_visible_columns()
  {
  }

  public function order_list_page(): String
  {
    return '';
  }

  public function access_add_form_from_main_menu(): bool
  {
    return false;
  }

  // Lists/ Array of detail tables of the current controller that you would like to use their 
  // single_form_add_visible_columns in the current controller's single form add forms

  public function detail_tables_single_form_add_visible_columns()
  {
  }

  public function single_form_add_hidden_columns()
  {
  }

  public function master_multi_form_add_visible_columns()
  {
  }

  public function detail_multi_form_add_visible_columns()
  {
  }

  public function master_multi_form_add_hidden_columns()
  {
  }

  public function detail_multi_form_add_hidden_columns()
  {
  }

  //public function add(){} //Had a problem of creating duplicates with the status_role add form on post

  public function edit()
  {
  }

  public function delete()
  {
  }

  public function master_table_additional_fields($record_id)
  {
    return [];
  }

  public function transaction_validate_duplicates_columns()
  {
    return []; // Must pass an empty array to prevent add method failure in grants_model
  }

  public function transaction_validate_by_computation_flag($array_to_check)
  {
    return VALIDATION_SUCCESS;
  }

  public function currency_fields()
  {
    return [];
  }

  function lookup_values()
  {

    $current_table =  strtolower($this->controller);

    $lookup_tables = $this->grants->lookup_tables($current_table);

    $lookup_values = [];

    foreach ($lookup_tables as $lookup_table) {

      $this->read_db->select(array($lookup_table . '_id', $lookup_table . '_name'));

      if ($lookup_table == 'office') {
        $this->read_db->group_start();

        // Show drop offices that are not readonly i.e. transacting offices
        if ($this->config->item('drop_transacting_offices')) {
          $this->read_db->where(array('office_is_readonly' => 0));
        } else {
          $this->read_db->where(array('office_is_readonly' => 1));
        }

        //This ensure only lowest level offices e.g. center - To be decaprecated in favor of of the previous condition

        if ($this->config->item('drop_only_center') && in_array($current_table, $this->config->item('tables_allowing_drop_only_centers'))) {

          $this->read_db->or_where(array('office.fk_context_definition_id' => $this->user_model->get_lowest_office_context()->context_definition_id));
        }

        $this->read_db->group_end();
      }

      if (!$this->session->system_admin) {


        if (strtolower($this->controller) !== 'account_system') {
          $this->grants->join_tables_with_account_system($lookup_table);
        }

        if ($this->read_db->field_exists($lookup_table . '_is_active', $lookup_table)) {
          $this->read_db->where(array($lookup_table . '_is_active' => 1));
        }
        $lookup_values[$lookup_table] = $this->read_db->get($lookup_table)->result_array();
      } else {

        $lookup_values[$lookup_table] = $this->read_db->get($lookup_table)->result_array();
      }
    }

    return $lookup_values;
  }

  /**
   * Use is a master table to filter the values of the lookup columns
   * Lookup tables are keys of the condition arrays
   */
  function lookup_values_where()
  {
  }

  function _derived_lookup_tables($table_name)
  {
    $fields = $this->grants_model->get_all_table_fields($table_name);

    $foreign_tables_array_padded_with_false = array_map(function ($elem) {
      return substr($elem, 0, 3) == 'fk_' ? substr($elem, 3, -3) : false;
    }, $fields);

    // Prevent listing false values and status or approval tables for lookup. 
    // Add status_name and approval_name to the correct visible_columns method in models to see these fields in a page
    $foreign_tables_array = array_filter($foreign_tables_array_padded_with_false, function ($elem) {
      return $elem ? $elem : false;
    });

    return $foreign_tables_array;
  }

  // Can be overriden in the specific model or extended
  function table_hidden_columns()
  {
    $hidden_columns = array(
      $this->table . '_last_modified_date', $this->table . '_created_date',
      $this->table . '_last_modified_by', $this->table . '_created_by', $this->table . '_deleted_at'
    );

    return $hidden_columns;
  }

  function master_table_hidden_columns()
  {
    $hidden_columns = array(
      $this->table . '_last_modified_date', $this->table . '_created_date',
      $this->table . '_last_modified_by', $this->table . '_created_by', $this->table . '_deleted_at'
    );

    return $hidden_columns;
  }

  function show_add_button()
  {
    return true;
  }

  function action_after_insert($post_array, $approval_id, $header_id)
  {
    return true;
  }

  function show_list_edit_action($record):bool{
    return true;
  }

  function post_approval_action_event(array $item){

  }
}
