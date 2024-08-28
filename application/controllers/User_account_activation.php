<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 *  @package grants <Finance management system for NGOs>
 *	@author  Onduso <londuso@ke.ci.org>
 *	@date	18th August, 2023
 *  @method void __construct() main method, first to be executed and initializes variables.
 *  @method void show_list(): returns perform server loading with datables.
 *  @method array columns(): returns columns to be used select clause in DB.
 *  @method array result(): returns list of items as array.
 *  @method void index().
 *  @method void activate_new_user_account(): activate_new_user_account(): activates newly created account and deletes the very user from user_account_activation.This is done either by admin/pf/super admins
 *  @method void reject_activating_new_user_account(): deletes the user from user, context user related table and department_user table.
 *	@see https://techsysnow.com
 */


class User_account_activation extends MY_Controller
{

  function __construct()
  {
    parent::__construct();
    $this->load->library('user_account_activation_library');

    $this->load->model('user_account_activation_model');
  }

  function index()
  {
  }
  /**
   * result(): returns list of items as array.
   * @author Onduso 
   * @access public 
   * @return array
   * @Dated: 17/8/2023
   * @param $id
   */
  public function result($id = 0):array
  {
    $result = [];

    if ($this->action == 'list') {
      $columns = $this->columns();
      array_shift($columns);
      $result['columns'] = $columns;
      $result['has_details_table'] = false;
      $result['has_details_listing'] = false;
      $result['is_multi_row'] = false;
      $result['show_add_button'] = false;
    } else {
      //$result = parent::result($id);
    }

    return $result;
  }
  /**
   * columns(): returns columns to be used select clause in DB.
   * @author Onduso 
   * @access public 
   * @return array
   * @Dated: 17/8/2023
   */
  public function columns():array
  {
    $columns = [
      'user_account_activation_id',
      'user_account_activation_reject_reason',
      'user_account_activation_name',
      'user_email',
      'role_name',
      'user_works_for',
      'user_account_activation_created_date',
      'user_activator_ids'
      
    ];

    return $columns;
  }

   /**
     * activate_new_user_account(): activates newly created account and deletes the very user from user_account_activation.This is done either by admin/pf/super admins
     * @author Onduso 
     * @access public 
     * @return void
     * @Dated: 18/8/2023
     */
    public function activate_new_user_account(): void
    {

      $user_activation_id=$this->input->post('userIdToActivate');

       echo $this->user_account_activation_model->activate_new_user_account($user_activation_id);
    }

  
     /**
     * reject_activating_new_user_account(): deletes the user from user, context user related table and department_user table.
     * @author Onduso 
     * @access public 
     * @return void
     * @Dated: 18/8/2023
     */
    public function reject_activating_new_user_account(): void
    {

      $user_activation_id=$this->input->post('rejectedUserId');
      $rejectReason=$this->input->post('rejectReason');

      if($rejectReason==''){
        $rejectReason='Do not know the new user';
      }
      

       echo json_encode($this->user_account_activation_model->reject_activating_new_user_account($user_activation_id,$rejectReason));
    }

    
 /**
   * show_list(): returns perform server loading with datables
   * @author Onduso 
   * @access public 
   * @return array
   * @Dated: 18/8/2023
   */
  public function show_list():void
  {

    $draw = intval($this->input->post('draw'));
    $users_to_activate = $this->get_users_for_activation();
   // $count_office_banks = $this->count_users_for_activation();

    $result = [];

    $cnt = 0;

    //Logged in user
    $logged_user_id = $this->session->user_id;

    foreach ($users_to_activate as $user) {

      //Split the activator Ids and check if it exits in every row loop
      $user_activator_ids = explode(':', $user['user_activator_ids']);

      //log_message('error', json_encode($user_activator_ids ));

      if (in_array($logged_user_id ,$user_activator_ids)) {

        $user_account_activation_id = $user['user_account_activation_id'];

        $user['user_account_activation_id'] = '<div style="white-space:nowrap;"><input class="form-check-input" type="checkbox" value="" id="chechbox_' . $user_account_activation_id . '"> <button class="btn btn-success"  id="activate_' . $user_account_activation_id . '">Activate?</button> <button class="btn btn-danger"  id="reject_' . $user_account_activation_id . '">Reject?</button> </div>';
        $user['user_account_activation_reject_reason'] = ' <select class="form-control hidden" id="rejectreason_'.$user_account_activation_id .'">';
        $user['user_account_activation_reject_reason'] .= ' <option value="0">select reason </option>';
        $user['user_account_activation_reject_reason'] .= ' <option value="1">Uknown new user</option>';
        $user['user_account_activation_reject_reason'] .= ' <option value="2">User existed before</option>';
        $user['user_account_activation_reject_reason'] .= ' <option value="3">Others</option></select>';
       // $user['user_account_activation_track_number'] .= '<a href="' . base_url() . $this->controller . '/view/' . hash_id($user_account_activation_id) . '">' . $user_account_activation_track_number . '</a>';
        $row = array_values($user);

        $result[$cnt] = $row;

        $cnt++;
      }
     
    }

    $response = [
      'draw' => $draw,
      'recordsTotal' => $cnt,//$count_office_banks,
      'recordsFiltered' => $cnt,//$count_office_banks,
      'data' => $result
    ];

    echo json_encode($response);
  }
 /**
   * get_users_for_activation(): returns an array of users to be activated by either admin/pf/super admins
   * @author Onduso 
   * @access public 
   * @return array
   * @Dated: 18/8/2023
   */
  public function get_users_for_activation():array
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
      $this->read_db->order_by('user_account_activation_id DESC');
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
    //Get records
    $this->read_db->select($columns);
    $this->read_db->where(['deleted_at'=> NULL]);
    $this->read_db->join('user','user.user_id=user_account_activation.fk_user_id');
    $this->read_db->join('role','role.role_id=user.fk_role_id');
    $result_obj = $this->read_db->get('user_account_activation');

    $results = [];

    if ($result_obj->num_rows() > 0) {
      $results = $result_obj->result_array();
    }

    return $results;
  }

  // function count_users_for_activation()
  // {

  //   $columns = $this->columns();
  //   $search_columns = $columns;

  //   // Searching

  //   $search = $this->input->post('search');
  //   $value = $search['value'];

  //   array_shift($search_columns);

  //   if (!empty($value)) {
  //     $this->read_db->group_start();
  //     $column_key = 0;
  //     foreach ($search_columns as $column) {
  //       if ($column_key == 0) {
  //         $this->read_db->like($column, $value, 'both');
  //       } else {
  //         $this->read_db->or_like($column, $value, 'both');
  //       }
  //       $column_key++;
  //     }
  //     $this->read_db->group_end();
  //   }

  //   $this->read_db->from('user_account_activation');
  //   $count_all_results = $this->read_db->count_all_results();

  //   return $count_all_results;
  // }



  static function get_menu_list()
  {
  }
}
