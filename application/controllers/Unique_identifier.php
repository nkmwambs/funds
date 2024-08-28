<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Unique_identifier extends MY_Controller
{

  function __construct()
  {
    parent::__construct();
    $this->load->library('unique_identifier_library');
  }

  function index()
  {
  }

  function get_office_allowed_unique_identifier(){
    $post = $this->input->post();

    $context_definition_id = $post['context_definition_id'];
    $context_office_id = $post['context_office_id'];

    $this->load->model('unique_identifier_model');
    $active_unique_identifier = $this->unique_identifier_model->get_office_context_allowed_unique_identifier($context_definition_id, $context_office_id);

    echo json_encode($active_unique_identifier);
  }

  function result($id = 0)
  {

    $result = [];

    if ($this->action == 'list') {
      $columns = $this->columns();
      array_shift($columns);
      $result['columns'] = $columns;
      $result['has_details_table'] = false;
      $result['has_details_listing'] = false;
      $result['is_multi_row'] = false;
      $result['show_add_button'] = true;
    } else {
      $result = parent::result($id);
    }

    return $result;
  }

  function columns()
  {
    $columns = [
      'unique_identifier_id',
      'unique_identifier_track_number',
      'unique_identifier_name',
      'unique_identifier_is_active',
      'account_system_name',
      'unique_identifier_created_date'
    ];

    return $columns;
  }


  function get_unique_identifiers()
  {

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

    if (!empty($order)) {
      $col = $order[0]['column'];
      $dir = $order[0]['dir'];
    }

    if ($col == '') {
      $this->read_db->order_by('unique_identifier_id DESC');
    } else {
      $this->read_db->order_by($columns[$col], $dir);
    }

    // Searching

    $search = $this->input->post('search');
    $value = $search['value'];

    array_shift($search_columns);

    if (!empty($value)) {
      $this->read_db->group_start();
      $column_key = 0;
      foreach ($search_columns as $column) {
        if ($column_key == 0) {
          $this->read_db->like($column, $value, 'both');
        } else {
          $this->read_db->or_like($column, $value, 'both');
        }
        $column_key++;
      }
      $this->read_db->group_end();
    }

    if (!$this->session->system_admin) {
      $this->read_db->where(array('unique_identifier.fk_account_system_id' => $this->session->user_account_system_id));
    }

    $this->read_db->select($columns);
    $this->read_db->join('status', 'status.status_id=unique_identifier.fk_status_id');
    $this->read_db->join('account_system', 'account_system.account_system_id=unique_identifier.fk_account_system_id');

    $result_obj = $this->read_db->get('unique_identifier');

    $results = [];

    if ($result_obj->num_rows() > 0) {
      $results = $result_obj->result_array();
    }

    return $results;
  }

  function count_unique_identifiers()
  {

    $columns = $this->columns();
    $search_columns = $columns;

    // Searching

    $search = $this->input->post('search');
    $value = $search['value'];

    array_shift($search_columns);

    if (!empty($value)) {
      $this->read_db->group_start();
      $column_key = 0;
      foreach ($search_columns as $column) {
        if ($column_key == 0) {
          $this->read_db->like($column, $value, 'both');
        } else {
          $this->read_db->or_like($column, $value, 'both');
        }
        $column_key++;
      }
      $this->read_db->group_end();
    }

    if (!$this->session->system_admin) {
      $this->read_db->where(array('unique_identifier.fk_account_system_id' => $this->session->user_account_system_id));
    }

    $this->read_db->join('status', 'status.status_id=unique_identifier.fk_status_id');
    $this->read_db->join('account_system', 'account_system.account_system_id=unique_identifier.fk_account_system_id');


    $this->read_db->from('unique_identifier');
    $count_all_results = $this->read_db->count_all_results();

    return $count_all_results;
  }

  function show_list()
  {

    $draw = intval($this->input->post('draw'));
    $unique_identifiers = $this->get_unique_identifiers();
    $count_unique_identifiers = $this->count_unique_identifiers();

    $result = [];

    $cnt = 0;
    foreach ($unique_identifiers as $unique_identifier) {
      $unique_identifier_id = array_shift($unique_identifier);
      $unique_identifier_track_number = $unique_identifier['unique_identifier_track_number'];
      $unique_identifier['unique_identifier_track_number'] = '<a href="' . base_url() . $this->controller . '/view/' . hash_id($unique_identifier_id) . '">' . $unique_identifier_track_number . '</a>';
      $unique_identifier['unique_identifier_is_active'] = $unique_identifier['unique_identifier_is_active'] == 1 ? get_phrase('yes') : get_phrase('no');
      $row = array_values($unique_identifier);

      $result[$cnt] = $row;

      $cnt++;
    }

    $response = [
      'draw' => $draw,
      'recordsTotal' => $count_unique_identifiers,
      'recordsFiltered' => $count_unique_identifiers,
      'data' => $result
    ];

    echo json_encode($response);
  }

  static function get_menu_list()
  {
  }
}
