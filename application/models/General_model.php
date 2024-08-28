<?php

class General_model extends CI_Model
{
  function __construct()
  {
    parent::__construct();

    $this->load->database();
  }

  function test()
  {
    return "Hello";
  }


  /***
   * *********************************************************************************************************
   * THESE ARE SYSTEM METHODS, PLEASE BE CONSCIOUS WHEN ALTERING THEM SINCE THE MAY AFFECT THE GENERAL 
   * PERFORMANCE OF THIS FRAMEWORK.
   *
   ***********************************************************************************************************
   */

  /**
   * get_status_id
   * 
   * Gives you the status id of a selected item
   * 
   * @param $table String
   * 
   * @param $primary  Int - Item primary key
   * 
   * @return int
   */
  function get_status_id($table, $primary_key)
  {
    $fk_status_id = 0;

    $record_object = $this->read_db->get_where($table, array($table . '_id' => $primary_key));

    if ($record_object->num_rows() > 0 && array_key_exists('fk_status_id', $record_object->row_array())) {
      $fk_status_id = $record_object->row()->fk_status_id;
    }

    return $fk_status_id;
  }

  /**
   * The method produces an array of the valid approval status ids for the listed items
   * These methods need to taken to the approval modal
   * 
   **/

  function range_of_status_approval_sequence($approve_item_name)
  {
    $this->read_db->select('MAX(status_approval_sequence) as status_approval_sequence');
    $this->read_db->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id');
    $this->read_db->join('approve_item', 'approve_item.approve_item_id=approval_flow.fk_approve_item_id');
    $max_range = $this->read_db->get_where('status', array(
      'approve_item_name' => $approve_item_name,
      'approval_flow.fk_account_system_id' => $this->session->user_account_system_id
    ))->row()->status_approval_sequence;
    return $max_range;
  }

  function get_approveable_item_id_by_status($item_status)
  {
    $this->read_db->select(array('approve_item_id'));
    $this->read_db->join('approval_flow', 'approval_flow.fk_approve_item_id=approve_item.approve_item_id');
    $this->read_db->join('status', 'status.fk_approval_flow_id=approval_flow.approval_flow_id');
    $result =  $this->read_db->get_where('approve_item', array('status_id' => $item_status))->row()->approve_item_id;

    return $result;
  }

  function get_approve_item_name_by_status($item_status)
  {

    $this->read_db->select(array('approve_item_name'));
    $this->read_db->join('approval_flow', 'approval_flow.fk_approve_item_id=approve_item.approve_item_id');
    $this->read_db->join('status', 'status.fk_approval_flow_id=approval_flow.approval_flow_id');
    $result =  $this->read_db->get_where(
      'approve_item',
      array('status_id' => $item_status)
    )->row()->approve_item_name;

    return $result;
  }

  function default_role_id()
  {

    $default_role_id = 0;

    $result_obj = $this->read_db->get_where('role', array('role_is_new_status_default' => 1));

    if ($result_obj->num_rows() > 0) {
      $default_role_id = $result_obj->row()->role_id;
    }

    return $default_role_id;
  }

  
  //Used for label naming e.g. Submit to [next_role_name]
  function next_approval_actor($item_status)
  {

    //Get approval item name using the status_id
    $approve_item_name = $this->get_approve_item_name_by_status($item_status);

    // Can remain empty array if this is the last status
    //$next_approval_actor_role_id = []; 

    $range_of_status_approval_sequence = $this->range_of_status_approval_sequence($approve_item_name);

    $approveable_item_id = $this->get_approveable_item_id_by_status($item_status);

    // 1.Get the status record
    $status_record = $this->read_db->get_where('status', array('status_id' => $item_status))->row();

    // 2.Get the value of the approval direction
    $status_approval_direction = $status_record->status_approval_direction;

    // // 3.Get the record of the next status in the sequence and it's related role_id
    $next_possible_sequence_number = $status_record->status_approval_sequence + 1;

    $previous_possible_sequence_number = $status_record->status_approval_sequence - 1;


    // Check if this is not the last status
    //$next_status_record = array();

    $next_approval_actor_role_ids = [$this->default_role_id()];
    //echo $range_of_status_approval_sequence;exit;
    if ($next_possible_sequence_number <= $range_of_status_approval_sequence) {

      if ($status_approval_direction == 1) {
        $this->read_db->select(array('status_role.fk_role_id as fk_role_id'));
        $this->read_db->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id');
        $this->read_db->join('status_role', 'status_role.status_role_status_id=status.status_id');
        $this->read_db->where(array(
          'status_approval_sequence' => $next_possible_sequence_number,
          'fk_approve_item_id' => $approveable_item_id, 'approval_flow.fk_account_system_id' => $this->session->user_account_system_id
        ));
        $next_status_record_obj = $this->read_db->get('status');

        $next_approval_actor_role_ids = $next_status_record_obj->num_rows() > 0 ? array_unique(array_column($next_status_record_obj->result_array(), 'fk_role_id')) : [$this->default_role_id()];
      } elseif ($status_approval_direction == -1) {
        $this->read_db->select(array('status_role.fk_role_id as fk_role_id'));
        $this->read_db->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id');
        $this->read_db->join('status_role', 'status_role.status_role_status_id=status.status_id');

        $this->read_db->where(array(
          'status_approval_sequence' => $status_record->status_approval_sequence,
          'fk_approve_item_id' => $approveable_item_id,
          'approval_flow.fk_account_system_id' => $this->session->user_account_system_id,
          'status_role_status_id' => $item_status
        ));

        $next_status_record_obj = $this->read_db->get('status');

        $next_approval_actor_role_ids = $next_status_record_obj->num_rows() > 0 ? array_unique(array_column($next_status_record_obj->result_array(), 'fk_role_id')) : [$this->default_role_id()];
      }
    }

    return $next_approval_actor_role_ids;
  }

  function current_approval_actor($item_status)
  {
    
    $this->read_db->where(array('status_id'=>$item_status));
    $status = $this->read_db->get('status')->row(); 
    
    $roles_obj = [];

    if($status->status_approval_direction == 1 || $status->status_approval_direction == 0){
      $this->read_db->where(array('status_approval_sequence'=>$status->status_approval_sequence));
    }else{
      $this->read_db->where(array('status_approval_sequence'=>$status->status_backflow_sequence));      
    }
    
    $this->read_db->select(array('fk_role_id'));
    $this->read_db->where(array('fk_approval_flow_id'=> $status->fk_approval_flow_id,'status_role_is_active'=>1,'status_approval_direction'=>1));

    $this->read_db->join('status','status.status_id=status_role.status_role_status_id');
    $roles_obj = $this->read_db->get('status_role'); 

    $roles = [];

    if($roles_obj->num_rows() > 0){
      $roles = array_column($roles_obj->result_array(),'fk_role_id');
    }

    // log_message('error', json_encode($roles));
    return $roles;
  }

  function get_status_name($item_status)
  {
    return $this->read_db->get_where(
      'status',
      array('status_id' => $item_status)
    )->row()->status_name;
  }

  function get_role_name($role_id)
  {
    $role_name = "";

    $role = $this->read_db->get_where('role', array('role_id' => $role_id));

    if ($role->num_rows() > 0) {
      $role_name = $role->row()->role_name;
    }

    return $role_name;
  }

  function get_account_system_roles($user_account_system_id)
  {
    $this->read_db->select(array('role_id', 'role_name'));

    if (!$this->session->system_admin) {
      $this->read_db->where(array('fk_account_system_id' => $user_account_system_id));
    }

    $roles = $this->read_db->get('role')->result_array();

    return $roles;
  }

  function decline_button_label($item_status){

    $this->read_db->join('approval_flow','approval_flow.approval_flow_id=status.fk_approval_flow_id');
    $this->read_db->join('approve_item','approve_item.approve_item_id=approval_flow.fk_approve_item_id');
    $this->read_db->where(array('status_id'=>$item_status));
    $status_obj = $this->read_db->get('status');
    $label = '';

    if($status_obj->num_rows() > 0){
      $status = $status_obj->row();

      $status_id = $status->status_id;
      $status_decline_button_label = $status->status_decline_button_label;
      $initial_item_status = $this->grants_model->initial_item_status($status->approve_item_name);
      $label = get_phrase('decline');

      if($status_decline_button_label != '' && $initial_item_status != $status_id){
        $label = $status_decline_button_label;
      }
    }

    return $label;
  }

  function user_action_label($item_status){

    $this->read_db->where(array('status_id'=>$item_status));
    $status_obj = $this->read_db->get('status');
    $label = '';

    if($status_obj->num_rows() > 0){
      $status = $status_obj->row();
      $label = $status->status_name;
      if($status->status_button_label != ''){
        $label = $status->status_button_label;
      }
    }

    return $label;
  }

  function next_status($item_status)
  {

    $next_status_id = 0;

    $approve_item_name = $this->get_approve_item_name_by_status($item_status);

    $range_of_status_approval_sequence = $this->range_of_status_approval_sequence($approve_item_name);

    $approveable_item_id = $this->get_approveable_item_id_by_status($item_status);

    //Get status record
    $status_record = $this->read_db->get_where('status', array('status_id' => $item_status))->row();

    // //Get the status id
    $status_approval_sequence = $status_record->status_approval_sequence;

    // // Get the backflow seq
    $backflow_sequence = $status_record->status_backflow_sequence;

    //Check if is not the last approval step
    if (($status_approval_sequence < $range_of_status_approval_sequence) && $backflow_sequence == 0) {

      $next_approval_seq = $status_approval_sequence + 1;

      $this->read_db->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id');
      $this->read_db->where(array(
        'status_approval_sequence' => $next_approval_seq,
        'fk_approve_item_id' => $approveable_item_id, 'approval_flow.fk_account_system_id' => $this->session->user_account_system_id
      ));
      $next_status_id_obj = $this->read_db->get('status');

      if ($next_status_id_obj->num_rows() > 0) {
        $next_status_id = $next_status_id_obj->row()->status_id;
      }
    } elseif (($status_approval_sequence == $range_of_status_approval_sequence) && $backflow_sequence == 0) {

      $this->read_db->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id');
      $this->read_db->where(array(
        'status_approval_sequence' => $status_approval_sequence,
        'fk_approve_item_id' => $approveable_item_id,
        'approval_flow.fk_account_system_id' => $this->session->user_account_system_id, 'status_approval_direction' => 1
      ));
      $next_status_id_obj = $this->read_db->get('status');

      if ($next_status_id_obj->num_rows() > 0) {
        $next_status_id = $next_status_id_obj->row()->status_id;
      }
    }

    // If backflow is > 0, get the actors of the sequence that equals the backflow e.g. backflow = 1, actors of seq 1 are 4 , 2

    // If the current role is in the actors above, get the sequence of the current status and find the status id of this seq that has a direction of 0
    if ($backflow_sequence > 0) {
      $this->read_db->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id');
      $this->read_db->join('status_role', 'status_role.status_role_status_id=status.status_id');
      $this->read_db->where(array('status_approval_sequence' => $backflow_sequence));
      $this->read_db->where(array(
        'fk_role_id' => $this->session->role_id,
        'fk_account_system_id' => $this->session->user_account_system_id
      ));
      $roles_ids = $this->read_db->get('status');

      if ($roles_ids->num_rows() > 0) {
        $this->read_db->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id');
        $this->read_db->where(array(
          'status_approval_sequence' => $status_approval_sequence,
          'status_approval_direction' => 0, 'fk_approve_item_id' => $approveable_item_id,
          'approval_flow.fk_account_system_id' => $this->session->user_account_system_id
        ));
        $next_status_id = $this->read_db->get('status')->row()->status_id;
      } else {
        $this->read_db->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id');
        $next_status_id = $this->read_db->get_where(
          'status',
          array(
            'status_approval_sequence' => $status_approval_sequence,
            'fk_approve_item_id' => $approveable_item_id, 'status_approval_direction' => 1,
            'approval_flow.fk_account_system_id' => $this->session->user_account_system_id
          )
        )->row()->status_id;
      }
    }

    return $next_status_id;
  }

  function item_has_declined_state($item_id, $table){
    
    $item_has_declined_state = false;

    if($item_id != null){
      $this->read_db->where(array($table.'_id'=>$item_id));
      $this->read_db->join($table,$table.'.fk_status_id=status.status_id');
      $status_approval_direction = $this->read_db->get('status')->row()->status_approval_direction;

      $item_has_declined_state = $status_approval_direction == -1 ? true : false;
    }

    return $item_has_declined_state;
  }

  function account_system_decline_states($approve_item, $account_system_id){
    $this->read_db->join('approval_flow','approval_flow.approval_flow_id=status.fk_approval_flow_id');
    $this->read_db->join('approve_item','approve_item.approve_item_id=approval_flow.fk_approve_item_id');
    $this->read_db->where(['approve_item.approve_item_name' => $approve_item, 'approval_flow.fk_account_system_id' => $account_system_id]);
    $this->read_db->where(['status_approval_direction' => -1]);
    $status_obj = $this->read_db->get('status');

    $decline_states = [];
    if ($status_obj->num_rows() > 0) {
      foreach ($status_obj->result_object() as $row) {
        $decline_states[] = $row->status_id;
      }
    }

    return $decline_states;
  }

  function decline_status($item_status)
  {
    $next_decline_status = 0;

    // Same approval seq but has -1 direction 
    $status_record = $this->read_db->get_where('status', array('status_id' => $item_status))->row();
    $approveable_item_id = $this->get_approveable_item_id_by_status($item_status);


    // Approval seq
    $approval_sequence = $status_record->status_approval_sequence;

    // Decline status
    $this->read_db->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id');
    $decline_status_record = $this->read_db->get_where(
      'status',
      array(
        'status_approval_sequence' => $approval_sequence, 'status_approval_direction' => -1,
        'fk_approve_item_id' => $approveable_item_id,
        'approval_flow.fk_account_system_id' => $this->session->user_account_system_id
      )
    );

    if ($decline_status_record->num_rows() > 0) {
      $next_decline_status = $decline_status_record->row()->status_id;
    }
    //echo $next_decline_status;exit;
    return $next_decline_status;
  }

  function show_decline_button($item_status, $logged_role_id)
  {
    // Just return the value of the actor receiving the declined item.
    $status_record = $this->read_db->get_where('status', array('status_id' => $item_status))->row();

    // Get the approval direction
    $approval_direction  = $status_record->status_approval_direction;

    $has_decline_button = false;

    $current_actors = $this->current_approval_actor($item_status);

    // print_r($item_status);
    // print_r("===================");
    // print_r($logged_role_id);
    // print_r("==========================");
    // print_r($current_approval_actors);
    // print_r("=================");
    // print_r(array_intersect($logged_role_id, $current_approval_actors));exit;

    if (($approval_direction == 1 || $approval_direction == 0) &&
      count($current_actors) > 0 && !empty(array_intersect($logged_role_id, $current_actors))  && 
      $status_record->status_approval_sequence != 1
    ) {
      $has_decline_button = true;
    }

    return $has_decline_button;
  }

  function show_label_as_button($item_status, $logged_role_id, $table, $primary_key)
  {
    //print_r($this->get_max_approval_status_id($table));exit;
    $max_approval_status_id = $this->get_max_approval_status_id($table)[0];
    $current_approval_actors = $this->current_approval_actor($item_status); // This is an array of current status role actors

    $logged_user_centers = array_column($this->session->hierarchy_offices, 'office_id');

    $is_approveable_item = $this->grants->approveable_item($table);

    $show_label_as_button = false;

    // print_r($item_status);
    // print_r("===================");
    // print_r($logged_role_id);
    // print_r("==========================");
    // print_r($current_approval_actors);
    // print_r("=================");
    // print_r(array_intersect($logged_role_id, $current_approval_actors));exit;

    // log_message('error', json_encode($is_approveable_item));

    if(is_array($logged_role_id)){
      if ((
        (is_array($logged_user_centers) &&
          (is_array($current_approval_actors) && !empty(array_intersect($logged_role_id, $current_approval_actors)))) &&
        $is_approveable_item) || $this->session->system_admin) {
        $show_label_as_button = true;
      }
    }else{
      if ((
        (is_array($logged_user_centers) &&
          (is_array($current_approval_actors) && in_array($logged_role_id, $current_approval_actors))) &&
        $is_approveable_item) || $this->session->system_admin) {
        $show_label_as_button = true;
      }
    }

    if($max_approval_status_id == $item_status){
      $show_label_as_button = false;
    }
   

    return $show_label_as_button;
  }

  function status_require_originator_action($status_id)
  {

    $status_require_originator_action = false;

    $this->read_db->where(['status_id' => $status_id]);
    $this->read_db->group_start();
    $this->read_db->where(array('status_approval_sequence' => 1));
    $this->read_db->or_where(array('status_backflow_sequence' => 1));
    $this->read_db->group_end();
    $status_obj = $this->read_db->get('status');

    if ($status_obj->num_rows() > 0) {
      $status_require_originator_action = true;
    }

    return $status_require_originator_action;
  }

  function approval_button_action($table, $primary_key){

    // $logged_role_ids = $this->session->role_ids;
    // $approval_button_info = [];
    // $item_status = $this->get_status_id($table, $primary_key);

    // $approval_button_info['show_label_as_button'] = $this->show_label_as_button($item_status, $logged_role_ids, $table, $primary_key);
    // $approval_button_info['button_label'] = $this->user_action_label($item_status);
    // $approval_button_info['decline_button_label'] = $this->decline_button_label($item_status);
    // $approval_button_info['show_decline_button'] = $this->show_decline_button($item_status, $logged_role_ids);
    // $approval_button_info['status_name'] = $this->get_status_name($item_status);

    // return $approval_button_info;

    
  }

  function display_approver_status_action($logged_role_id, $table, $primary_key)
  {
    /**
     * Given the status find the following:
     * 
     * - Who is the next actor? - The next actor is the role id represented by the next approval sequence number.
     *    But if the next status has an approval direction of -1, then the next actor is the role_id directly
     *    Next actor for all declines is derived by the value of backflow sequence status item of an item
     *    the same approval sequence with a direction of -1, get the related role id
     * 
     * - What is the currect status label or both the actor and others viewers? For the actor use 
     *  (Submit to or Decline to [role_name] and for  and for others use Submitted to or Declined to [role_name]
     *  except for the last in the sequence to the label Completed) - Status Action Label field is redundant since labels will be generic
     *  If status has backflow value > 0 then use Reinstate to [role_name] when accessing as s the current user
     * 
     * - Who is the current actor? Use the role id directly. But when value of approval direction is -1 and 
     *  backflow sequence has a value, then used the role id represented by the value in the backflow sequence.
     * 
     * - Show a decline button when? Show when the current approval sequence has 1 0r 0 approval directions
     * 
     * - What is the next status? - Use the status id of the next approval sequence but if the 
     *   approval direction is -1 then use the status represented by the backflow sequence
     * 
     * - The last status is irreversible
     *  
     */

    $approval_button_info = [];

    $item_status = $this->get_status_id($table, $primary_key);
    //echo $primary_key;
    if ($item_status > 0) {

      $approval_button_info['current_actor_role_id'] = $this->current_approval_actor($item_status);
      $approval_button_info['next_actor_role_id'] = $this->next_approval_actor($item_status);
      $approval_button_info['status_name'] = $this->get_status_name($item_status);
      $approval_button_info['button_label'] = $this->user_action_label($item_status);
      $approval_button_info['show_decline_button'] = $this->show_decline_button($item_status, $logged_role_id);
      $approval_button_info['next_approval_status'] = $this->next_status($item_status);
      $approval_button_info['next_decline_status'] = $this->decline_status($item_status);
      $approval_button_info['show_label_as_button'] = $this->show_label_as_button($item_status, $logged_role_id, $table, $primary_key);
      $approval_button_info['is_max_approval_status'] = $this->is_max_approval_status_id($table, $item_status);
      $approval_button_info['decline_button_label'] = $this->decline_button_label($item_status);
    }
    // print_r($approval_button_info);
    // exit;
    return $approval_button_info;
  }


  /**
   * get_max_approval_status_id
   * 
   * Gets the max approval status id
   * 
   * @param String $approveable_item
   * @return Int 
   */
  function get_max_approval_status_id(String $approveable_item, $office_ids = [], $account_system_id = 0): array
  {
    $max_status_ids = [];
    // log_message('error', json_encode($office_ids));
    //https://codeigniter.com/userguide3/database/query_builder.html
    $this->read_db->reset_query();

    if(empty($office_ids )){
      if (empty($this->session->hierarchy_offices)) {
        $message = "Your account is improperly set. Your user context assignment misses a related record in the correct context user table. Contact the administrator";
        $message .= "<br/><a href='" . base_url() . "login/logout'>" . get_phrase('log_out') . "</a>";
        show_error($message);
      }
    }

    
    //Get the maximum status_approval_sequence of an approveable item
    $this->read_db->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id');
    $this->read_db->join('approve_item', 'approve_item.approve_item_id=approval_flow.fk_approve_item_id');
    $this->read_db->join('account_system', 'account_system.account_system_id=approval_flow.fk_account_system_id');
    $this->read_db->join('office', 'office.fk_account_system_id=account_system.account_system_id');
    
    if($account_system_id > 0){
      $this->read_db->where(array('account_system.account_system_id' => $account_system_id));
    }else{
      $hierarchy_offices = !empty($office_ids) ? $office_ids : array_column($this->session->hierarchy_offices, 'office_id');
      $this->read_db->where_in('office_id', $hierarchy_offices);
    }

    $max_status_approval_sequence_obj = $this->read_db->select(array('status_id', 'status_approval_sequence'))
      //->order_by('status_approval_sequence DESC')
      ->where(array(
        'approve_item_name' => $approveable_item,
        'status_backflow_sequence' => 0, 'status_approval_direction' => 1, 'status_is_requiring_approver_action' => 0
      ))
      ->get('status');

    //print_r($max_status_approval_sequence_obj->row());exit;

    if (
      $max_status_approval_sequence_obj->num_rows() > 0 &&
      $max_status_approval_sequence_obj->row()->status_approval_sequence > 0
    ) {
      // Get the status_id
      $max_status_ids_with_seq = $max_status_approval_sequence_obj->result_array();

      $max_status_ids = array_unique(array_column($max_status_ids_with_seq, 'status_id'));
    } elseif (in_array($approveable_item, $this->config->item('table_that_dont_require_history_fields'))) {
      // Nothing to do
    } else {
      $message = "You have no initial status set for the feature " . $approveable_item . ". Please check if all approval workflow related tables are correctly set</br>";

      // Attempt creating approve item if not present then followed by approval work flow and status
      // Attempt creating an approval work flow record if missing and create its initial status

      if (!$this->grants_model->insert_status_if_missing($approveable_item)) {
        show_error($message, 500, 'An Error was Encountered');
      }
    }
    //print_r($max_status_id);exit;

    return $max_status_ids;
  }

  public function is_status_id_max(String $approveable_item, Int $item_id) {
    $is_status_id_max = false;

    $this->read_db->where(array('approve_item_name' => $approveable_item, $approveable_item.'_id' => $item_id));
    $this->read_db->where(array('status_approval_direction' => 1, 'status_is_requiring_approver_action' => 0));
    $this->read_db->join('approval_flow','approval_flow.approval_flow_id=status.fk_approval_flow_id');
    $this->read_db->join('approve_item','approve_item.approve_item_id=approval_flow.fk_approve_item_id');
    $this->read_db->join($approveable_item,$approveable_item.'.fk_status_id=status.status_id');
    $max_status_obj = $this->read_db->get('status');

    if($max_status_obj->num_rows() > 0){
      $is_status_id_max = true;
    }

    return $is_status_id_max;
  }
  
  /**
   * is_max_status_id
   * 
   * Check if the provided approval status id is maximum
   * 
   * @param String $approveable_item 
   * @param Int $status_id
   * 
   * @return Bool - True is Max while False is not 
   */
  function is_max_approval_status_id(String $approveable_item, Int $status_id): Bool
  {
    $is_max_status_id = false;

    $max_status_id = $this->get_max_approval_status_id($approveable_item);
    // log_message('error', json_encode($max_status_id));
    if ($status_id == $max_status_id) {
      $is_max_status_id = true;
    }

    return $is_max_status_id;
  }

  function get_min_approval_status_id(String $approveable_item): Int
  {
    $min_status_id = 0;

    //https://codeigniter.com/userguide3/database/query_builder.html
    $this->read_db->reset_query();
    //Get the maximum status_approval_sequence of an approveable item
    $this->read_db->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id');
    $this->read_db->join('approve_item', 'approve_item.approve_item_id=approval_flow.fk_approve_item_id');

    $min_status_approval_sequence_obj = $this->read_db->select_min('status_approval_sequence')
      ->get_where('status', array('approve_item_name' => $approveable_item));

    if (
      $min_status_approval_sequence_obj->num_rows() > 0 &&
      $min_status_approval_sequence_obj->row()->status_approval_sequence > 0
    ) {
      // Get the status_id
      $min_status_approval_sequence = $min_status_approval_sequence_obj->row()->status_approval_sequence;
      $this->read_db->select('status_id');
      $this->read_db->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id');
      $this->read_db->join('approve_item', 'approve_item.approve_item_id=approval_flow.fk_approve_item_id');

      $min_status_id = $this->read_db->get_where(
        'status',
        array(
          'status_approval_sequence' => $min_status_approval_sequence,
          'approve_item_name' => $approveable_item
        )
      )->row()->status_id; //'fk_account_system_id'=>$this->session->user_account_system_id

    } elseif (in_array($approveable_item, $this->config->item('table_that_dont_require_history_fields'))) {
      // Nothing to do
    } else {
      $message = "You have no initial status set for the feature " . $approveable_item . ". Please check if all approval workflow related tables are correctly set</br>";

      show_error($message, 500, 'An Error was Encountered');
    }
    //print_r($max_status_id);exit;
    return $min_status_id;
  }

  function is_min_approval_status_id(String $approveable_item, Int $status_id): Bool
  {
    $is_min_status_id = false;

    $min_status_id = $this->get_min_approval_status_id($approveable_item);

    if ($status_id == $min_status_id) {
      $is_min_status_id = true;
    }

    return $is_min_status_id;
  }

  /**
   * has_approval_status_been_set
   * 
   * Check if the approvaeable item has other approval statuses other than the "New" status 
   * auto-created by the system
   * 
   * @param String $approveable_item
   * @return Bool
   */

  function has_approval_status_been_set(String $approveable_item): Bool
  {

    $has_approval_status_been_set = false;

    $this->read_db->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id');
    $this->read_db->join('approve_item', 'approve_item_id=approval_flow.fk_approve_item_id');
    $count_of_status = $this->read_db
      ->get_where('status', array('approve_item_name' => $approveable_item))
      ->num_rows();

    if ($count_of_status > 0) {
      $has_approval_status_been_set = true;
    }

    return $has_approval_status_been_set;
  }

  /**
   * check_if_item_has_max_status_by_created_date
   * 
   * Check if an item has attained the max approval status based on the date the status was created and the item created date.
   * The final approval status of any item must be lower or equal to the date the item was created to have it take effect. 
   * If the max status doesn't meet the condition above the lower status will be matched against the item status as the final
   * 
   * @param $approveable_item - Approveable item object
   * @param $item_created_date - Date item was created
   * @param $status_id - Item status
   * 
   * @return bool
   */
  function check_if_item_has_max_status_by_created_date(Object $approveable_item, String $item_created_date, int $status_id): bool
  {

    $is_approved_item =  true;

    if ($approveable_item->approve_item_is_active) {
      // The use of the status_check_date  is to prevent a new final status created in later days
      // affect old vouchers appearance or disappearance from the journal
      $this->read_db->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id');
      $max_status_approval_sequence = $this->read_db->select_max('status_approval_sequence')->get_where(
        'status',
        array(
          'fk_approve_item_id' => $approveable_item->approve_item_id,
          'status_approval_direction' => 1,
          'status_created_date <= ' => $item_created_date
        )
      )->row();

      if (!empty($max_status_approval_sequence->status_approval_sequence)) {
        $this->read_db->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id');
        $max_status_id = $this->read_db->get_where(
          'status',
          array(
            'status_approval_sequence' => $max_status_approval_sequence->status_approval_sequence,
            'fk_approve_item_id' => $approveable_item->approve_item_id
          )
        )->row()->status_id;

        if ($max_status_id != $status_id) {
          $is_approved_item = false;
        }
      }
    }

    return $is_approved_item;
  }


  function get_item_max_status_by_created_date(String $item, String $item_created_date): int
  {

    $approveable_item = $this->read_db->get_where('approve_item', array('approve_item_name' => $item))->row();

    $this->read_db->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id');
    $max_status_approval_sequence = $this->read_db->select_max('status_approval_sequence')->get_where(
      'status',
      array(
        'fk_approve_item_id' => $approveable_item->approve_item_id,
        'status_approval_direction' => 1,
        'status_created_date <= ' => $item_created_date,
        'fk_account_system_id' => $this->session->user_account_system_id
      )
    )->row();

    if (!empty($max_status_approval_sequence->status_approval_sequence)) {

      $this->read_db->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id');
      $max_status_id = $this->read_db->get_where(
        'status',
        array(
          'status_approval_sequence' => $max_status_approval_sequence->status_approval_sequence,
          'fk_approve_item_id' => $approveable_item->approve_item_id,
          'fk_account_system_id' => $this->session->user_account_system_id
        )
      )->row()->status_id;
    } else {
      $max_status_id = $this->get_max_approval_status_id('voucher');
    }
    return $max_status_id;
  }

  function max_status_id_where_condition_by_created_date($item, $item_created_date)
  {

    $max_status_id = $this->general_model->get_item_max_status_by_created_date($item, $item_created_date);

    return array($item . '.fk_status_id' => $max_status_id);
  }

  function action_button_data($item_type, $account_system_id = 0){
    $item_max_approval_status_ids = $this->general_model->get_max_approval_status_id($item_type);
    $initial_item_status = $this->grants_model->initial_item_status($item_type, $account_system_id);

    $data['item_max_approval_status_ids'] = $item_max_approval_status_ids;
    $data['item_status'] = $this->status_model->item_status($item_type,$initial_item_status, $account_system_id);
    $data['item_initial_item_status_id'] = $initial_item_status;
    $data['permissions'] = [
        'update' => $this->user_model->check_role_has_permissions($item_type, 'update'),
        'delete' => $this->user_model->check_role_has_permissions($item_type, 'delete')
    ];
    $data['active_approval_actor'] = [];

    return $data;
  }

  function get_decline_status_ids($approve_item){

    $status_ids = []; 
    $approve_item = strtolower($approve_item);

    $this->read_db->select(array('status_id'));
    $this->read_db->where(array('status_approval_direction' => -1, 'approve_item_name' => $approve_item));
    $this->read_db->join('approval_flow','approval_flow.approval_flow_id=status.fk_approval_flow_id');
    $this->read_db->join('approve_item','approve_item.approve_item_id=approval_flow.fk_approve_item_id');
    $status_ids_obj = $this->read_db->get('status');

    if($status_ids_obj->num_rows() > 0){
      $status_ids = array_column($status_ids_obj->result_array(), 'status_id');
    }

    return $status_ids;
  }
}
