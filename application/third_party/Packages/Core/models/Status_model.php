<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Status_model extends MY_Model
{
  public $table = 'status'; // you MUST mention the table name
  public $dependant_table = '';
  public $name_field = 'status_name';
  public $create_date_field = "status_created_date";
  public $created_by_field = "status_created_by";
  public $last_modified_date_field = "status_last_modified_date";
  public $last_modified_by_field = "status_last_modified_by";
  public $deleted_at_field = "status_deleted_at";

  function __construct()
  {
    parent::__construct();
    
    $this->check_decline_reinstate_status();
  }

  function check_decline_reinstate_status(){

    $id = hash_id($this->id,'decode');

    $this->db->select(array(
      'status_id',
      'status_name',
      'fk_approval_flow_id',
      'status_approval_sequence',
      'status_backflow_sequence',
      'status_approval_direction',
      'status_is_requiring_approver_action'
    ));
    $this->db->where(array('fk_approval_flow_id'=>$id));
    $approval_flow_status = $this->db->get('status')->result_array(); // Read db has refused to work, I don't know why?

    $status_grouped_by_level = [];

    foreach ($approval_flow_status as $status) {
      if ($status['status_approval_sequence'] == 1) {
        continue;

      }

      $status_grouped_by_level[$status['status_approval_sequence']][$status['status_approval_direction']] = $status;
    }

    $directions = [-1, 0, 1];

    $new_status_to_add = [];

    $cnt = 0;

    foreach($status_grouped_by_level as $status_level => $status){
      foreach($directions as $direction){
        if(!array_key_exists($direction,$status)){

          $status_name = get_phrase("fully_approve");
          $status_button_label = '';

          if($direction == -1){
            $status_name = get_phrase("declined");
            $status_button_label = get_phrase('reinstate');
          }elseif($direction == 0){
            $status_name = get_phrase("reinstated");
            $status_button_label = get_phrase("approve_after_reinstate");
          }

          $new_status_to_add[$cnt]['status_name'] = $status_name;
          $new_status_to_add[$cnt]['	status_button_label'] = $status_button_label;
          $new_status_to_add[$cnt]['fk_approval_flow_id'] = $id;
          $new_status_to_add[$cnt]['status_approval_sequence'] = $status_level;
          $new_status_to_add[$cnt]['status_approval_direction'] = $direction;
          $new_status_to_add[$cnt]['status_backflow_sequence'] = $direction == -1 ? 1 : 0;
          $new_status_to_add[$cnt]['status_is_requiring_approver_action'] = 1;

          $new_status_to_add[$cnt]['status_track_number'] = $this->grants_model->generate_item_track_number_and_name('status')['status_track_number'];
          $new_status_to_add[$cnt]['status_created_date'] = date('Y-m-d');
          $new_status_to_add[$cnt]['status_created_by'] = $this->session->user_id;
          $new_status_to_add[$cnt]['status_last_modified_by'] = $this->session->user_id;


        }
        $cnt++;
      }
    }

    if(!empty($new_status_to_add)){
      $this->write_db->insert_batch('status',$new_status_to_add);
    }

    //print_r($new_status_to_add);exit;
  }

  // function get_table_for_status($id){
  //   $this->read_db->where(array('status_id'=>$id));
  //   $status = $this->read_db->get('status')->row();
  //   return 'user';
  // }

  function get_status($id){

    $this->read_db->select(array('status_approval_direction','approve_item_name','status_approval_sequence','status_is_requiring_approver_action'));
    $this->read_db->where(array('status_id'=>$id));
    $this->read_db->join('approval_flow','approval_flow.approval_flow_id=status.fk_approval_flow_id');
    $this->read_db->join('approve_item','approve_item.approve_item_id=approval_flow.fk_approve_item_id');
    $status = $this->read_db->get('status')->row();

    return $status;
  }

  function edit_visible_columns(){

    return ['status_name','status_button_label','status_decline_button_label', 'status_signatory_label'];
  }

  function delete($id = null)
  {
  }

  function index()
  {
  }

  public function lookup_tables()
  {
    return array('approval_flow');
  }


  function is_status_actionable_by_user($status_id,$feature){

    return in_array($status_id, $this->session->role_status) || !$this->is_feature_approveable($feature) ? true : false;
  }

  function is_feature_approveable($feature){
    $this->read_db->where(array('approve_item_name'=>$feature));
    return $this->read_db->get('approve_item')->row()->approve_item_is_active;
  }


  public function detail_tables()
  {
    $id = hash_id($this->id,'decode');
    $status = $this->get_status($id );

    $direction = $status->status_approval_direction;
    $is_first_level = $status->status_approval_sequence == 1 ? true : false;
    $is_final_level = $status->status_is_requiring_approver_action == 0 ? true : false;
    $table = $status->approve_item_name;

    $lookup_tables = list_lookup_tables($table);
    $fields = $this->grants->list_fields($table);

    foreach($lookup_tables as $lookup_table){
      if($lookup_table == 'status' || $lookup_table == 'approval') continue;
      $lookup_table_fields = $this->grants->list_fields($lookup_table);
      $fields = array_merge($fields , $lookup_table_fields);
    }

    $cols = ['status_role'];

    if(in_array('fk_office_id', $fields) && !$is_first_level && !$is_final_level){
      $cols = ['status_role','approval_exemption'];
    }
  
    if($direction == 1){
      return $cols;
    }
   
  }

  public function table_visible_columns()
  {
  }

  public function table_hidden_columns()
  {
  }

  public function master_table_visible_columns()
  {
  }

  public function master_table_hidden_columns()
  {
  }

  public function list()
  {
  }

  public function view()
  {
  }

  // function transaction_validate_duplicates_columns()
  // {
  //   return ['approval_flow_name', 'status_approval_sequence', 'status_approval_direction'];
  // }

  function action_after_insert($post_array, $approval_id, $header_id)
  {
    // Get approve item name of the of the created status
    $this->read_db->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id');
    $this->read_db->join('approve_item', 'approve_item.approve_item_id=approval_flow.fk_approve_item_id');
    $approve_item_name = $this->read_db->get_where('status', array('status_id' => $header_id))->row()->approve_item_name;

    // Get the dependant/ detail table of the approve item name
    $approve_item_detail_name = $this->grants->dependant_table($approve_item_name);

    $this->read_db->join('approve_item', 'approve_item.approve_item_id=approval_flow.fk_approve_item_id');
    $dependant_table_approval_flow = $this->read_db->get_where(
      'approval_flow',
      array('approve_item_name' => $approve_item_detail_name)
    )->row();

    if ($approve_item_detail_name !== "") {
      $this->write_db->trans_start();
      $data['status_track_number'] = $this->grants_model->generate_item_track_number_and_name('status')['status_track_number'];
      $data['status_name'] = $post_array['status_name'];
      $data['fk_approval_flow_id'] = $dependant_table_approval_flow->approval_flow_id;
      $data['status_approval_sequence'] = $post_array['status_approval_sequence'];
      $data['status_backflow_sequence'] = $post_array['status_backflow_sequence'];
      $data['status_approval_direction'] = $post_array['status_approval_direction'];
      $data['status_is_requiring_approver_action'] = $post_array['status_is_requiring_approver_action'];
      $data['status_created_date'] = $post_array['status_created_date'];
      $data['status_created_by'] = $post_array['status_created_by'];
      $data['status_last_modified_by'] = $post_array['status_last_modified_by'];
      $data['fk_approval_id'] = $post_array['fk_approval_id'];
      $data['fk_status_id'] = $post_array['fk_status_id'];

      $this->write_db->insert('status', $data);
      $this->write_db->trans_complete();

      if ($this->write_db->trans_status() == false) {
        return false;
      } else {
        return true;
      }
    }
  }

  function status_approval_sequencies($change_field_type_sequencies)
  {
    $lookup_values = [];

    if ($this->id != null) {
      $this->read_db->select(array('status_approval_sequence'));
      $this->read_db->where(array(
        'approval_flow_id' => hash_id($this->id, 'decode'),
        'status_is_requiring_approver_action' => 1, 'status_approval_direction' => 1
      ));
      $this->read_db->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id');
      $status_approval_sequence_obj = $this->read_db->get('status');

      if ($status_approval_sequence_obj->num_rows() > 0) {
        $status_approval_sequence = array_flip(array_column($status_approval_sequence_obj->result_array(), 'status_approval_sequence'));

        $all_status_approval_sequence = $change_field_type_sequencies; //$this->change_field_type()['status_approval_sequence'];

        foreach ($status_approval_sequence as $status_approval_sequence_id => $status_approval_sequence_label) {
          if (array_key_exists($status_approval_sequence_id, $all_status_approval_sequence)) {
            unset($all_status_approval_sequence[$status_approval_sequence_id]);
          }
        }
      }

      $lookup_values =  $all_status_approval_sequence;
    }

    return $lookup_values;
  }

  function add()
  {
    $post = $this->input->post()['header'];
    // log_message('error', json_encode($post));
    //$post_status_role = $this->input->post()['detail_header']['status_role'];

    $jumps = [1, 0, -1]; // 1 = Submitted new Item, 0 = Submitted Reinstated Item, -1 = Declined Item

    //$data = [];

    $message = get_phrase('insert_successful');
    $insert_array = [];

    $this->write_db->trans_start();

    $status_approval_sequence = $post['status_approval_sequence'];
    $approval_flow_id = $post['fk_approval_flow_id'];

    // Insert a fully approved status/ final status
    $this->insert_final_approval_status($approval_flow_id, $status_approval_sequence);

    $cnt = 0;

    foreach ($jumps as $jump) {

      $insert_array[$cnt]['status_approval_sequence'] = $post['status_approval_sequence'];
      $insert_array[$cnt]['fk_approval_flow_id'] = $post['fk_approval_flow_id'];
      $insert_array[$cnt]['status_name'] = $post['status_name'];
      $insert_array[$cnt]['status_button_label'] = $post['status_button_label'];
      $insert_array[$cnt]['status_decline_button_label'] = "";
      $insert_array[$cnt]['status_signatory_label'] = NULL;


      if ($jump == 0) {
        $insert_array[$cnt]['status_name'] = 'Reinstated for ' . $insert_array[$cnt]['status_name'];
        $insert_array[$cnt]['status_button_label'] =  get_phrase("approve");
        $insert_array[$cnt]['status_decline_button_label'] = $post['status_decline_button_label'];
      } elseif ($jump == 1) {
        $insert_array[$cnt]['status_signatory_label'] = $post['status_signatory_label'];
        $insert_array[$cnt]['status_decline_button_label'] = $post['status_decline_button_label'];
      } elseif ($jump == -1) {
        $insert_array[$cnt]['status_name'] = 'Declined from ' . $insert_array[$cnt]['status_name'];
        $insert_array[$cnt]['status_button_label'] = get_phrase("reinstate");
      }


      //$status_approval_sequence = $status_approval_sequence_level;
      $insert_array[$cnt]['status_backflow_sequence'] =  $jump == -1 ? 1 : 0;
      $insert_array[$cnt]['status_approval_direction'] = $jump;
      $insert_array[$cnt]['status_is_requiring_approver_action'] = 1; // All custom status require an action from a user

      $insert_array[$cnt]['status_track_number'] = $this->grants_model->generate_item_track_number_and_name('status')['status_track_number'];

      $insert_array[$cnt]['status_created_date'] = date('Y-m-d');
      $insert_array[$cnt]['status_created_by'] = $this->session->user_id;
      $insert_array[$cnt]['status_last_modified_by'] = $this->session->user_id;

      $cnt++;
    }

    $this->write_db->insert_batch('status',$insert_array);

    $this->write_db->trans_complete();

    if (!$this->write_db->trans_status()) {
      $message = get_phrase('insert_failed');
    }

    echo json_encode(['message' => $message]);
  }

  // function get_account_system_roles($user_account_system_id)
  // {
  //   $this->read_db->select(array('role_id', 'role_name'));

  //   if (!$this->session->system_admin) {
  //     $this->read_db->where(array('fk_account_system_id' => $user_account_system_id));
  //   }

  //   $roles = $this->read_db->get('role')->result_array();

  //   return $roles;
  // }

  function insert_final_approval_status($approval_flow_id, $status_approval_sequence)
  {

    // Is final approval status
    $this->read_db->where(array(
      'fk_approval_flow_id' => $approval_flow_id,
      'status_is_requiring_approver_action' => 0,
      'status_approval_direction' => 1,
      'status_backflow_sequence' => 0
    ));
    $final_approval_status = $this->read_db->get('status');

    $max_sequency_level = $status_approval_sequence + 1;

    if ($final_approval_status->num_rows() == 0) {
      $this->grants_model->insert_status($this->session->user_id, get_phrase('fully_approved'), $approval_flow_id, $max_sequency_level, 0, 1, 0);
      $this->grants_model->insert_status($this->session->user_id, get_phrase('reinstate_after_allow_edit'), $approval_flow_id, $max_sequency_level, 1, -1, 1);
      $this->grants_model->insert_status($this->session->user_id, get_phrase('reinstated_after_edit'), $approval_flow_id, $max_sequency_level, 0, 0, 1);
    } else {
      // Update to the status_approval_sequence to the last sequence
      $update_data['status_approval_sequence'] = $max_sequency_level;
      //$this->write_db->where(array('status_id'=>$final_approval_status->row()->status_id));
      $this->write_db->where(array(
        'status_approval_sequence' => $status_approval_sequence,
        'fk_approval_flow_id' => $approval_flow_id
      ));
      $this->write_db->update('status', $update_data);
    }
  }


  function insert_status_role($post_status_role, $role_id, $status_id, $status_name)
  {
    $status_role_data['status_role_track_number'] = $this->grants_model->generate_item_track_number_and_name('status_role')['status_role_track_number'];
    $status_role_data['status_role_name'] = $status_name . ' [' . $this->read_db->get_where('role', array('role_id' => $role_id))->row()->role_name . ']';
    $status_role_data['fk_role_id'] = $role_id;
    $status_role_data['status_role_status_id'] = $status_id;

    $status_role_data['status_role_created_by'] = $this->session->user_id;
    $status_role_data['status_role_created_date'] = date('Y-m-d');
    $status_role_data['status_role_last_modified_by'] = $this->session->user_id;

    $status_role_data['fk_approval_id'] = $this->grants_model->insert_approval_record('status_role');
    $status_role_data['fk_status_id'] = $this->grants_model->initial_item_status('status_role');

    $this->write_db->insert('status_role', $status_role_data);
  }

  // function detail_tables_single_form_add_visible_columns()
  // {
  //   return ['status_role'];
  // }
  
  function detail_list_table_visible_columns()
  {
    return ['status_track_number','status_name','status_button_label','status_decline_button_label', 'status_signatory_label','status_approval_sequence'];
  }

  public function single_form_add_visible_columns()
  {
    return ['status_name','status_button_label', 'status_decline_button_label', 'status_signatory_label', 'approval_flow_name', 'status_approval_sequence'];
  }

  function order_list_page(): String
  {
    return 'status_approval_sequence ASC';
  }

  function detail_list_table_where()
  {
    if (!$this->session->system_admin) {
      $this->read_db->group_start();
      $this->read_db->where(array('status_approval_sequence <> ' => 1));
      $this->read_db->or_where(array('status_is_requiring_approver_action <> ' => 0));
      $this->read_db->group_end();

      $this->read_db->where(array('status_approval_sequence <> ' => 0));
      $this->read_db->where(array('status_approval_direction ' => 1));
      $this->read_db->order_by('status_approval_sequence ASC');
    }

  }

  // function stating($table){

  //   $this->read_db->select(array('status_id','status_name','status_button_label','status_decline_button_label',
  //   'status_approval_sequence','status_approval_direction','status_role.fk_role_id as role_id'));
  //   $this->read_db->where(array('approve_item_name'=>$table,'approval_flow.fk_account_system_id'=>$this->session->user_account_system_id));
  //   $this->read_db->join('approval_flow','approval_flow.approval_flow_id=status.fk_approval_flow_id');
  //   $this->read_db->join('approve_item','approve_item.approve_item_id=approval_flow.fk_approve_item_id');
  //   $this->read_db->join('status_role','status_role.status_role_status_id=status.status_id');
  //   $status = $this->read_db->get('status')->result_array();

  //   $status_records_with_status_id_key = [];

  //   foreach($status as $status_record){
  //     $status_id = array_shift( $status_record);
  //     unset($status_record['role_id']);
  //     $status_records_with_status_id_key[$status_id] = $status_record;
  //   }

  //   foreach($status as $status_record){
  //     $status_id = array_shift( $status_record);
  //     $status_records_with_status_id_key[$status_id]['status_role'][] = $status_record['role_id'];
  //   }

  //   return $status_records_with_status_id_key;
  // }

  // function status_require_originator_action($table){

  //   $this->read_db->select();

  //   $this->read_db->group_start();
  //   $this->read_db->where(array('status_approval_sequence' => 1));
  //   $this->read_db->or_where(array('status_backflow_sequence' => 1));
  //   $this->read_db->group_end();

  //   $this->read_db->where(array('approve_item_name'=>$table,'approval_flow.fk_account_system_id'=>$this->session->user_account_system_id));
  //   $this->read_db->join('approval_flow','approval_flow.approval_flow_id=status.fk_approval_flow_id');
  //   $this->read_db->join('approve_item','approve_item.approve_item_id=approval_flow.fk_approve_item_id');
  //   $this->read_db->join('status_role','status_role.status_role_status_id=status.status_id');
  //   $status = $this->read_db->get('status')->result_array();

  //   foreach($status as $status_record){

  //   }

  // }
  
  function get_item_status_roles($table){

    $this->read_db->select(array('status_id','status_role.fk_role_id as role_id','status_approval_sequence'));
    $this->read_db->where(array('approve_item_name'=>$table, 'status_role_is_active' => 1,'approval_flow.fk_account_system_id'=>$this->session->user_account_system_id));
    $this->read_db->join('status','status.status_id=status_role.status_role_status_id');
    $this->read_db->join('approval_flow','approval_flow.approval_flow_id=status.fk_approval_flow_id');
    $this->read_db->join('approve_item','approve_item.approve_item_id=approval_flow.fk_approve_item_id');
    $status_roles_obj = $this->read_db->get('status_role');

    $roles = [];
    $level_roles = [];

    if($status_roles_obj->num_rows() > 0){
      $status_roles = $status_roles_obj->result_array();
      foreach($status_roles as $status_role){
        $roles[$status_role['status_id']][] = $status_role['role_id']; 
        $level_roles[$status_role['status_approval_sequence']][] = $status_role['role_id']; 
      }
    }

    return ['status_roles'=>$roles,'level_roles'=>$level_roles];
  }

  function exempt_status($table_name, $item_id, $current_next_status_id){

      $this->load->model('office_model');
    
      $action_button_data = $this->general_model->action_button_data($table_name);
      // log_message('error', json_encode($action_button_data));
      $states = $action_button_data['item_status'];
      // log_message('error', json_encode([$table_name, $item_id, $current_next_status_id]));
      $next_status = $states[$current_next_status_id];
      $next_status_exemptions = $next_status['approval_exemptions'];

      $alt_next_status_id = $current_next_status_id;
      
      // Skipped status based on office exemption - Office Based Exemption
      if(is_array($next_status_exemptions) && sizeof($next_status_exemptions) > 0){ // Check if next status has exemptions
        if($this->office_model->check_if_table_has_relationship_with_office($table_name)){ // Check if the table of the record has a relationship with office
          $item_office_id = $this->get_item_office_id($table_name, $item_id);
          $ordered_steps = $this->get_positive_direction_status_without_exemption($states, $item_office_id, $next_status);      
          $alt_next_status_id = array_shift($ordered_steps)['status_id'];
          $next_status = $states[$alt_next_status_id]; // We are resetting this for the use in the next skip - By Logged user
        }
      }

      // log_message('error', json_encode(['2-next_status' => $next_status, '2-alt_next_status_id' => $alt_next_status_id]));
      
      // Skip status if the next status has the logged user as an actor
      // if(sizeof(array_intersect($next_status['status_role'], $this->session->role_ids)) > 0){
      //   // log_message('error', json_encode('Hello'));
      //   $this->read_db->where(array($table_name . '_id' => $item_id));
      //   $item_obj = $this->read_db->get($table_name);
      //   if($item_obj->num_rows() > 0){
      //     $creator_user_id = $item_obj->row()->{$table_name.'_created_by'};
      //     if($this->session->user_id == $creator_user_id){ // Skip the next status if you are the same person who created the record
      //       $ordered_steps_for_user = $this->get_positive_direction_status_without_current_role_as_actor($states, $this->session->role_ids, $next_status);
      //       $alt_next_status_id = array_shift($ordered_steps_for_user)['status_id'];
      //     }
      //   }
      // }

    if(sizeof(array_intersect($next_status['status_role'], $this->session->role_ids)) > 0){
      $this->read_db->where(array($table_name . '_id' => $item_id));
      
      $item_obj = $this->read_db->get($table_name);
      
      if($item_obj->num_rows() > 0){
      
        $creator_user_id = $item_obj->row()->{$table_name.'_created_by'};
      
        if($this->session->user_id == $creator_user_id){
      
          foreach($states as $status_id => $status){ // Gain 1 more level beyond the exempted. Compute new next status
      
            if(
              ($status['status_approval_sequence'] == $next_status['status_approval_sequence'] + 1) &&
              $status['status_approval_direction'] == 1
            ){
              $alt_next_status_id = $status_id;
            }
          }
        }
      }
    }

    // log_message('error', json_encode(['2-next_status' => $next_status, '2-alt_next_status_id' => $alt_next_status_id]));

      return $alt_next_status_id;
      
  }

//   function exempt_status($table_name, $item_id, $current_next_status_id){

//     // log_message('error', json_encode([$table_name, $item_id, $current_next_status_id]));
  
//     $action_button_data = $this->general_model->action_button_data($table_name);
//     $states = $action_button_data['item_status'];
//     $next_status = $states[$current_next_status_id];
//     $next_status_exemptions = $next_status['approval_exemptions'];

//     $alt_next_status_id = $current_next_status_id;

//     // log_message('error',json_encode($next_status));
    
//     if(is_array($next_status_exemptions) && sizeof($next_status_exemptions) > 0){ // Check if next status has exemptions
//       $fields = $this->grants->list_fields($table_name);
//       $lookup_tables = list_lookup_tables($table_name);

//       foreach($lookup_tables as $lookup_table){
//         if($lookup_table == 'status' || $lookup_table == 'approval') continue;
//         $lookup_table_fields = $this->grants->list_fields($lookup_table);
//         $fields = array_merge($fields , $lookup_table_fields);
//       }

//       // Office based exemption
      
//       if(in_array('fk_office_id', $fields)){ // Check if the table of the record has a relationship with office

//         foreach($lookup_tables as $lookup_table){
//           $deep_lookup_tables = list_lookup_tables($lookup_table);
//           if(in_array('office',$deep_lookup_tables)){
//             $this->read_db->join($lookup_table, $lookup_table.'.'.$lookup_table.'_id='.$table_name.'.fk_'.$lookup_table.'_id');
//             break;
//           }
//         }

//         $this->read_db->where(array($table_name . '_id' => $item_id));
//         $item_obj = $this->read_db->get($table_name);
        
//         if($item_obj->num_rows() > 0){
//           $item_office_id = $item_obj->row()->fk_office_id;

//           if(in_array($item_office_id,  $next_status_exemptions)){ // Check if the record office id is part of the exempted offices
//             foreach($states as $status_id => $status){ // Gain 1 more level beyond the exempted. Compute new next status

//               if(
//                 ($status['status_approval_sequence'] == $next_status['status_approval_sequence'] + 1) &&
//                 $status['status_approval_direction'] == 1
//               ){
//                 $alt_next_status_id = $status_id;
//               }

//             }
            
//           }
//         }
//       }
//     }

//     // Skip status if the next status has the logged user as an actor
//     if(sizeof(array_intersect($next_status['status_role'], $this->session->role_ids)) > 0){

//       $this->read_db->where(array($table_name . '_id' => $item_id));
//       $item_obj = $this->read_db->get($table_name);

//       if($item_obj->num_rows() > 0){
        
//         $creator_user_id = $item_obj->row()->{$table_name.'_created_by'};

//         if($this->session->user_id == $creator_user_id){

//           foreach($states as $status_id => $status){ // Gain 1 more level beyond the exempted. Compute new next status
  
//             if(
//               ($status['status_approval_sequence'] == $next_status['status_approval_sequence'] + 1) &&
//               $status['status_approval_direction'] == 1
//             ){
//               $alt_next_status_id = $status_id;
//             }
  
//           }
//         }

        
//       }

//     }

//     return $alt_next_status_id;
    
// }

  function get_item_office_id($table_name, $item_id){
    $lookup_tables = list_lookup_tables($table_name);
          foreach($lookup_tables as $lookup_table){
            $deep_lookup_tables = list_lookup_tables($lookup_table);
            if(in_array('office',$deep_lookup_tables)){
              $this->read_db->join($lookup_table, $lookup_table.'.'.$lookup_table.'_id='.$table_name.'.fk_'.$lookup_table.'_id');
              break;
            }
          }

          $this->read_db->where(array($table_name . '_id' => $item_id));
          $item_obj = $this->read_db->get($table_name);
          
          $item_office_id = 0;

          if($item_obj->num_rows() > 0){
            $item_office_id = $item_obj->row()->fk_office_id;
          }
    return $item_office_id;
  }

  function get_positive_direction_status_without_current_role_as_actor($states, $role_ids, $next_status){
    // filter +ve steps with no exemptions
    $ordered_steps = [];
    foreach($states as $status_id => $status){
      if( 
          $status['status_approval_direction'] == 1 
          && 
          sizeof(array_intersect($status['status_role'], $role_ids)) == 0
          && $status['status_approval_sequence'] >= $next_status['status_approval_sequence']
        ){
        $status['status_id'] = $status_id;
        $ordered_steps[$status['status_approval_sequence']] = $status;
      }
    }
    ksort($ordered_steps);

    return $ordered_steps;
  }

  function get_positive_direction_status_without_exemption($states, $office_id, $next_status){
    // filter +ve steps with no exemptions
    $ordered_steps = [];
    foreach($states as $status_id => $status){
      if( 
          $status['status_approval_direction'] == 1 
          && !in_array($office_id, $status['approval_exemptions'])
          && $status['status_approval_sequence'] >= $next_status['status_approval_sequence']
        ){
        $status['status_id'] = $status_id;
        $ordered_steps[$status['status_approval_sequence']] = $status;
      }
    }
    ksort($ordered_steps);

    return $ordered_steps;
  }

  function item_status($table, $item_initial_item_status_id, $account_system_id = 0){

    $item_status_roles = $this->get_item_status_roles($table);
    $status_roles = $item_status_roles['status_roles'];
    $level_roles = $item_status_roles['level_roles'];

    if($account_system_id == 0){
      $account_system_id = $this->session->user_account_system_id;
    }
   
    $this->read_db->select(array('status_id','status_name','status_button_label','status_decline_button_label',
    'status_approval_sequence','status_approval_direction','status_backflow_sequence'));
    $this->read_db->where(array('approve_item_name'=>$table,'approval_flow.fk_account_system_id'=> $account_system_id));
    $this->read_db->join('approval_flow','approval_flow.approval_flow_id=status.fk_approval_flow_id');
    $this->read_db->join('approve_item','approve_item.approve_item_id=approval_flow.fk_approve_item_id');
    $status = $this->read_db->get('status')->result_array();


    $this->read_db->select(array('status_id','fk_office_id as office_id'));
    $this->read_db->join('status','status.status_id=approval_exemption.approval_exemption_status_id');
    $this->read_db->join('approval_flow','approval_flow.approval_flow_id=status.fk_approval_flow_id');
    $this->read_db->join('approve_item','approve_item.approve_item_id=approval_flow.fk_approve_item_id');
    $this->read_db->where(array('approve_item_name'=>$table, 'approval_exemption_is_active' => 1,
    'approval_flow.fk_account_system_id'=>$account_system_id));
    $this->read_db->where_in('fk_office_id', array_column($this->session->hierarchy_offices,'office_id'));
    $exemptions_obj = $this->read_db->get('approval_exemption');

    $exemptions = [];

    if($exemptions_obj->num_rows() > 0){
      $exemptions_raw = $exemptions_obj->result_array();

      $cnt = 0;
      foreach($exemptions_raw as $exempt_row){
        $exemptions[$exempt_row['status_id']][$cnt] = $exempt_row['office_id'];
        $cnt ++;
      }
    }

    $status_records_with_status_id_key = [];

    foreach($status as $status_record){
      $status_id = array_shift($status_record);
      //unset($status_record['role_id']);
      $status_records_with_status_id_key[$status_id] = $status_record;
      $status_records_with_status_id_key[$status_id]['approval_exemptions'] = isset($exemptions[$status_id]) ? $exemptions[$status_id] : [];
      
    }

    foreach($status_records_with_status_id_key as $status_id => $status_record){
      //$status_id = array_shift($status_record);
          
      if($status_record['status_approval_direction'] == -1){
        $status_records_with_status_id_key[$status_id]['status_role'] = isset($status_roles[$item_initial_item_status_id]) ? $status_roles[$item_initial_item_status_id] : [];
      }elseif($status_record['status_approval_direction'] == 0){
        $status_records_with_status_id_key[$status_id]['status_role'] =  isset($level_roles[$status_record['status_approval_sequence']]) ? $level_roles[$status_record['status_approval_sequence']] : [];
      }else{
        $status_records_with_status_id_key[$status_id]['status_role'] = isset($status_roles[$status_id]) ? $status_roles[$status_id] : [];
      }
      
    }

    return $status_records_with_status_id_key;
  }

  function action_before_edit($post_array){

    $this->write_db->trans_start();

      $status_id = hash_id($this->id, 'decode');

      $this->read_db->select(['fk_approval_flow_id','status_approval_sequence']);
      $this->read_db->where(array('status_id' => $status_id));
      $status_obj = $this->read_db->get('status')->row();

      $current_seq =  $status_obj->status_approval_sequence;
      // $updated_seq = $post_array['header']['status_approval_sequence'];

      // Prevent from updating the approval sequencies with an edit.
      $post_array['header']['status_approval_sequence'] = $current_seq;

      // if($updated_seq > 0){
      //   $this->write_db->where(array('status_approval_sequence' => $updated_seq));
      //   $this->write_db->where(array('fk_approval_flow_id' => $status_obj->fk_approval_flow_id));
      //   $this->write_db->update('status', array('status_approval_sequence' => -$updated_seq));
      // }

    // Prevent giving a decline button label for step number 1
    if($status_obj->status_approval_sequence == 1){
      $post_array['header']['status_decline_button_label'] = NULL;
    }

    $this->write_db->trans_complete();

    if ($this->write_db->trans_status() === FALSE) {
        return [];
    } else {
        return $post_array;
    }
  }


  function action_after_edit($post_array, $approval_id, $header_id)
  {

      $this->write_db->trans_start();

      $status_id = hash_id($this->id, 'decode');

      $this->read_db->select(['fk_approval_flow_id','status_approval_sequence']);
      $this->read_db->where(array('status_id' => $status_id));
      $status_obj = $this->read_db->get('status')->row();

      $new_status_approval_sequence = $post_array['status_approval_sequence'];

      // Get approve otem name
      $this->read_db->join('approval_flow','approval_flow.fk_approve_item_id=approve_item.approve_item_id');
      $this->read_db->join('status','status.fk_approval_flow_id=approval_flow.approval_flow_id');
      $this->read_db->where(array('fk_approval_flow_id' => $status_obj->fk_approval_flow_id));
      $approve_item_name = $this->read_db->get('approve_item')->row()->approve_item_name;

      // This block of code is invoked when the status is deactivated

      if($new_status_approval_sequence == 0){
        // Order matters here

        // Deactivate the status
        $this->write_db->where(array('status_approval_sequence' => $status_obj->status_approval_sequence));
        $this->write_db->where(array('fk_approval_flow_id' => $status_obj->fk_approval_flow_id));
        $this->write_db->update('status', array('status_approval_sequence' => 0));
        
        // Re-position all states above the deactivated one by one step back
        $this->write_db->set('status_approval_sequence', 'status_approval_sequence - 1', FALSE);
        $this->write_db->where(array('status_approval_sequence > ' => $status_obj->status_approval_sequence));
        $this->write_db->where(array('fk_approval_flow_id' => $status_obj->fk_approval_flow_id));
        $this->write_db->update('status');

        // Prevent updating status if the resulting sequence will be less than 1
        if($status_obj->status_approval_sequence - 1 > 0){
          
          // Updated all items with the deactivated status to the status before it except max approved items

          // Get previous sequence status id
          $this->write_db->where(array('status_approval_sequence' => $status_obj->status_approval_sequence - 1));
          $this->write_db->where(array('fk_approval_flow_id' => $status_obj->fk_approval_flow_id));
          $previous_status = $this->write_db->get('status')->row()->status_id;  
          
          // Update all items that had the deactivated status to the immediate previous one
          $this->write_db->where(array('fk_status_id' => hash_id($this->id, 'decode')));
          $this->write_db->update($approve_item_name, array('fk_status_id' => $previous_status));
        }
      }else{
        $old_sequence = $status_obj->status_approval_sequence;
        $updated_sequence = $post_array['status_approval_sequence'];

        $this->write_db->where(array('status_approval_sequence' => $old_sequence));
        $this->write_db->where(array('fk_approval_flow_id' => $status_obj->fk_approval_flow_id));
        $this->write_db->update('status', array('status_approval_sequence' => $updated_sequence));
        
        // log_message('error', json_encode([$old_sequence, $updated_sequence]));

        $this->write_db->where(array('status_approval_sequence' => -$updated_sequence));
        $this->write_db->where(array('fk_approval_flow_id' => $status_obj->fk_approval_flow_id));
        $this->write_db->update('status', array('status_approval_sequence' => $old_sequence));
      }

      $this->write_db->trans_complete();

      if ($this->write_db->trans_status() === FALSE) {
          return false;
      } else {
          return true;
      }
  }

  // function get_approval_levels_for_account_system_approve_item($account_system_id, $approveable_item_name){
  //   $levels = [];

  //   $steps_array = $this->get_approval_steps_for_account_system_approve_item($account_system_id, $approveable_item_name);

  //   foreach($steps_array as $step){
  //     $levels[$step['status_approval_sequence']][] =  $step;
  //   }
  // }

  function get_approval_steps_for_account_system_approve_item($account_system_id, $approveable_item_name){

    $this->read_db->select(array('status_id','status_name','status_signatory_label','status_approval_sequence','status_approval_direction'));
    $this->read_db->where(array('fk_account_system_id' => $account_system_id, 'approve_item_name' => $approveable_item_name));
    $this->read_db->where(array('status_is_requiring_approver_action' => 1));
    $this->read_db->where_in('status_approval_direction', [1]);
    $this->read_db->join('approval_flow','approval_flow.approval_flow_id=status.fk_approval_flow_id');
    $this->read_db->join('approve_item','approve_item.approve_item_id=approval_flow.fk_approve_item_id');
    $this->read_db->order_by('status_approval_sequence','ASC');
    $status_obj = $this->read_db->get('status');

    $status = [];

    if($status_obj->num_rows() > 0){
      $status = $status_obj->result_array();
    }

    return $status;
  }

}
