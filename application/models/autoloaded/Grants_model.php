<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Grants_model extends CI_Model
{

  /**
   * single_form_add_visible_columns
   * @var Array
   */
  public $single_form_add_visible_columns = [];


  /**
   * __construct
   * 
   * Intializer method
   * 
   * @return Void
   */
  function __construct()
  {
    parent::__construct();

    $this->event_tracker();
  }

  /**
   * index
   * 
   * Index method
   * 
   * @return Void
   */
  function index()
  {
  }

  function read_database_connection()
  {
    $read_db = $this->load->database('read_db', true); // Slave DB on Port 3307

    $read_db_connected = $read_db->initialize();

    // Check if Slave is Connected
    if (!$read_db_connected) {
      $read_db = $this->database->load('write_db', TRUE);
    }

    return $read_db;
  }


  /**
   * edit
   * 
   * This is the default edit method if not overwritten in the feature model
   * 
   * @param String $id : Hashed Id which is derived from the 3rd URI segment
   * @todo Use Transactions
   * @return String
   */
  function edit(String $id): String
  {

    $post_array = $this->input->post();
    
    $post_array = $this->grants->action_before_edit($post_array);

    $flag = false;
    $flag_message = "";

    if(is_array($post_array) && !empty($post_array) && !array_key_exists('error',$post_array)){
      extract($post_array);
      $id = hash_id($id, 'decode');
      $data = $header; 

      $approval_id = 0; // To be evaluated later


      // Modified by Karisa - 8th August 2022

       $model = $this->controller . '_model';

       $transaction_validate_duplicates_columns = is_array($this->$model->transaction_validate_duplicates_columns()) ? $this->$model->transaction_validate_duplicates_columns() : [];
      
       $has_duplicate_record = $this->has_duplicate_record($this->controller, $id, $transaction_validate_duplicates_columns, $data);


        if(!$has_duplicate_record){

          $this->write_db->trans_begin();
          $this->write_db->where(array($this->grants->primary_key_field($this->controller) => $id));
          $this->write_db->update($this->controller, $data);
    
          $this->create_change_history($data);
    
          if ($this->write_db->trans_status() === FALSE) {
            $this->write_db->trans_rollback();
            // $message = ['flag' => false, 'message' => get_phrase('update_not_successful')];
            $flag_message = get_phrase('update_not_successful');
          } else {

            if($this->grants->action_after_edit($data, $approval_id, $id)){
              $this->write_db->trans_commit();
              // $message = ['flag' => true, 'message' => get_phrase('update_completed')];
              $flag = true;
              $flag_message = get_phrase('update_completed');
            }else{
              $flag = false;
              $flag_message = get_phrase('update_not_successful');
              $this->write_db->trans_rollback();
            }
            

          }
        }
        
        else{
          // $message = ['flag' => false, 'message' => get_phrase('duplicates_not_allowed')];
          $flag_message = get_phrase('duplicates_not_allowed');

        }
      
    }else{
      $flag_message = get_phrase('edit_not_allowed');
      if(array_key_exists('error',$post_array)){
        $flag_message = $post_array['error'];
      }
    }

    return json_encode(['flag' => $flag, 'message' => $flag_message]); 
    // Modified by Karisa - 8th August 2022 - End
  }

  function has_duplicate_record($table, $id, $check_duplicate_columns, $post_array){

    // This query is to extract data to cater for columns that are not sourced from the edit form
    $this->read_db->select($check_duplicate_columns);
    $this->read_db->where(array($table.'_id' => $id));
    $posted_record = $this->read_db->get($table)->row_array();

    $post_array = array_merge($post_array, $posted_record);
    
    // log_message('error', json_encode($post_array));

    $has_duplicate_record = false;

    if(count($check_duplicate_columns) > 0){

      $cols = [];

      foreach($post_array as $field => $value){
        if(in_array($field, $check_duplicate_columns) || $field == 'fk_account_system_id'){
           $cols[$field] = $value; 
        }
      }

      // log_message('error', json_encode($cols));
      
      $this->read_db->where(array($table.'_id <> ' => $id));
  
      if(count($cols) > 0){
        $this->read_db->where($cols);
        $num_rows = $this->read_db->get($table)->num_rows();
        
        // log_message('error', json_encode($num_rows));

        if($num_rows > 0){
          $has_duplicate_record = true;
        }
      }
      
    }

    return $has_duplicate_record;

  }

  function create_change_history($new_data, $exclude_using_new_data_columns = false, $critical_columns_to_unset = [], $table = "", $item_id = 0)
  {

    $table = $table == "" ? $this->controller : $table;
    $item_id = $item_id == 0 ? $this->id : $item_id;
    $fields = $this->read_db->list_fields($table);

    // log_message('error', json_encode([$this->id]));

    $table_id = strtolower($table) . "_id";
    $decode_id = is_numeric($item_id) ? $item_id: hash_id($item_id, 'decode');
    $new_data[$table_id] = $decode_id;

    if(!$exclude_using_new_data_columns){
      $fields =  array_keys($new_data);
    }

    $cols = array_diff($fields,$critical_columns_to_unset);

    // log_message('error', json_encode(['$cols' => $cols, '$table' => $table, '$table_id' => $table_id, '$decode_id' => $decode_id]));

    $this->read_db->select($cols);
    $old_data = $this->read_db->get_where(
      strtolower($table),
      array($table_id => $decode_id)
    )->row_array();

    // log_message('error', json_encode([
    //   'table_id' => $table_id, 
    //   'decode_id' => $decode_id, 
    //   'new_data' => $new_data, 
    //   'keys' => array_keys($new_data),
    //   'old_data' => $old_data
    // ]));

    // Insert Update History
    $update_data['fk_approve_item_id'] = $this->read_db->get_where(
      'approve_item',
      array('approve_item_name' => strtolower($table))
    )->row()->approve_item_id;

    $update_data['fk_user_id'] = $this->session->user_id;
    $update_data['history_action'] = 1; // 1 = Update, 2 = Delete
    $update_data['history_current_body'] = json_encode($old_data);
    $update_data['history_updated_body'] = json_encode($new_data);
    $update_data['history_created_date'] = date('Y-m-d');
    $update_data['history_created_by'] = $this->session->user_id;
    $update_data['history_last_modified_by'] = $this->session->user_id;

    $this->write_db->insert('history', $update_data);
  }

  function check_item_requires_approval($approveable_item)
  {
    return $this->approveable_item($approveable_item);
  }

  function insert_approval_record($approveable_item)
  {
    $this->write_db->reset_query();
    //$is_approveable_item = $this->approveable_item($approveable_item);
    $insert_id = 0;

    // if($is_approveable_item){
    $approval_random = record_prefix('Approval') . '-' . rand(1000, 90000);
    $approval['approval_track_number'] = $approval_random;
    $approval['approval_name'] = 'Approval Ticket # ' . $approval_random;
    $approval['approval_created_by'] = $this->session->user_id ? $this->session->user_id : 1;
    $approval['approval_created_date'] = date('Y-m-d');
    $approval['approval_last_modified_by'] = $this->session->user_id ? $this->session->user_id : 1;
    $approval['fk_approve_item_id'] = $this->read_db->get_where('approve_item', array('approve_item_name' => strtolower($approveable_item)))->row()->approve_item_id;
    $approval['fk_status_id'] = $this->initial_item_status($approveable_item);

    $this->write_db->insert('approval', $approval);

    $insert_id = $this->write_db->insert_id();
    //}

    return $insert_id;
  }

  function generate_item_track_number_and_name($approveable_item)
  {
    $header_random = record_prefix($approveable_item) . '-' . rand(1000, 90000);
    $columns[$approveable_item . '_track_number'] = $header_random;
    $columns[$approveable_item . '_name'] = ucfirst($approveable_item) . ' # ' . $header_random;

    return $columns;
  }

  function add()
  {

    // There are 3 insert scenarios
    // Scenario 1: Master detail insert without a primary relationship and master requires approval
    // Scenario 2: Master detail insert without a primary relationship and master doesn't require approval
    // Scenario 3: Master detail insert with a primary relationship and master requires approval
    // Scenario 4: Master detail insert with a primary relationship and master doesn't require approval
    // Scenario 5: Single record insert that requires approval
    // Scenario 6: Single record insert that doesn't require approval

    // Asign the post input to $post_array
    $post_array = $this->input->post();

    // Check if there is a before insert method set in the feature model wrapped via grants model
    $post_array = $this->grants->action_before_insert($post_array);

    if(!array_key_exists('header',$post_array)){
      return json_encode(['flag' => false, 'message' => $post_array['message']]);      
    }
    //$detail = [];
    // Extract the post array into header and detail variables
    extract($post_array);

    // log_message('error', json_encode($post_array));

    // Determine if the input post has details or not by checking if the detail variable is set
    $post_has_detail = isset($detail) ? true : false;
    $detail = $post_has_detail ? $detail : [];

    // Check if the creation of the of the header and detail records requires an approval ticket
    $header_record_requires_approval = $this->approveable_item($this->controller);
    //$detail_records_require_approval = $this->approveable_item($this->controller.'_detail');
    $detail_records_require_approval = $this->approveable_item($this->grants->dependant_table($this->controller));

    // Get the table name of multi select field
    $multi_select_field_name = 'fk_' . $this->grants->multi_select_field() . '_id';

    $multi_select_field_values = [];

    if ($this->grants->multi_select_field() != '') {
      $multi_select_field_values = $header[$multi_select_field_name];
      //echo json_encode($multi_select_field_values);exit;
    }

    // Start a transaction
    /**
     * $post = [
     *  'header'=>[
     *    'role_permission_track_number'='CBNAJS',
     *    'role_permission_name'=>'role permission name'
     *    'fk_permission_id'=>[
     *          0=>'Read',
     *          1=>'Update',
     *      ]
     * ],
     *  'detail'=>[],
     * ]
     */
    $message = "";
    if (count($multi_select_field_values) > 0) {

      unset($header[$multi_select_field_name]);

      $onfly_created_multi_selects = [];

      // Find any available on-fly multi select values from a model action_before_insert method
      foreach ($header as $column_name => $form_values) {
        if (is_array($form_values)) {
          $onfly_created_multi_selects[$column_name] = $form_values;
        }
      }

      $success = 0;
      $failed = 0;
      foreach ($multi_select_field_values as $multi_select_field_value) {

        $header[$multi_select_field_name] = $multi_select_field_value;

        if (!empty($onfly_created_multi_selects)) {
          foreach ($onfly_created_multi_selects as $_column_name => $_column_values) {
            $header[$_column_name] = $_column_values[$multi_select_field_value];
          }
        }

        $returned_validation_message = $this->add_inserts($header_record_requires_approval, $detail_records_require_approval, $post_has_detail, $header, $detail);
        // log_message('error', $returned_validation_message);
        if (json_decode($returned_validation_message, true)['flag'] == true) {
          $success++;
        } else {
          $failed++;
        }
      }

      $message .= $success . ' ' . str_replace('_', ' ', $this->controller) . ' inserted and ' . $failed . ' failed';

      $message = json_encode(['flag' => true, 'message' => $message]);

    } else {
      $message = $this->add_inserts($header_record_requires_approval, $detail_records_require_approval, $post_has_detail, $header, $detail);
    }

    return $message;
  }


  function add_inserts($header_record_requires_approval, $detail_records_require_approval, $post_has_detail, $header, $detail = [])
  {
    //echo json_encode($header);exit;
    $initial_status = $this->initial_item_status($this->controller);

    $this->write_db->trans_begin();
    // Create the approval ticket if required by the header record
    $approval_id  = $this->insert_approval_record(strtolower($this->controller));

    $approval = array();
    $details = array();

    if ($this->id) {
      //echo $this->session->master_table;exit;
      $decoded_hash_id = hash_id($this->id, 'decode');

      $approval_id = $this->read_db->get_where(
        strtolower($this->session->master_table),
        array($this->session->master_table . '_id' => $decoded_hash_id)
      )->row()->fk_approval_id;
    }

    // This array will hold the array with values for header record insert
    $header_columns = array();

    // Insert the header record. Use the $approval_id to insert into the fk_approval_id field
    $header_random = record_prefix($this->controller) . '-' . rand(1000, 90000);
    $header_columns[strtolower($this->controller) . '_track_number'] = $header_random;
    $header_columns[strtolower($this->controller) . '_name'] = $this->input->post($this->controller . '_name') != "" ? $this->input->post($this->controller . '_name') : ucfirst($this->controller) . ' # ' . $header_random;
    
    foreach ($header as $key => $value) {
      $header_columns[$key] = $value;
    }
    if ($this->session->has_userdata('master_table')) {
      $header_columns['fk_' . strtolower($this->session->master_table) . '_id'] = hash_id($this->id, 'decode');
    }
    // log_message('error', json_encode($header_columns));
    
    $header_columns['fk_status_id'] = $initial_status;

    $header_columns['fk_approval_id'] = $approval_id;

    $header_columns[strtolower($this->controller) . '_created_date'] = date('Y-m-d');
    $header_columns[strtolower($this->controller) . '_created_by'] = $this->session->user_id;
    $header_columns[strtolower($this->controller) . '_last_modified_by'] = $this->session->user_id;

    // Insert header record
    // log_message('error', json_encode($header_columns));

    $this->write_db->insert(strtolower($this->controller), $header_columns);
    
    // Get the insert id of the header record inserted
    $header_id = $this->write_db->insert_id();

    // Proceed with inserting details after checking if $post_has_detail
    if ($post_has_detail) {

      // Table set up. Add missing mandatory fields and status
      //$this->grants->table_setup($this->grants->dependant_table($this->controller));

      // The $detail_array is initial to hold the array of the for looped variable since the original $detail will be shifted
      $detail_array = $detail;

      // This is the array that will hold the insert batch array
      $detail_columns = array();

      // Get the first element of the detail array to be used to determine the number of detail rows
      $shifted_element = array_shift($detail);

      // Construct an insert batch array using the detail array
      for ($i = 0; $i < sizeof($shifted_element); $i++) {
        foreach ($detail_array as $column => $values) {
          if (strpos($column, '_name') == true && $column !== $this->grants->dependant_table($this->controller) . '_name') {
            $column = 'fk_' . substr($column, 0, -5) . '_id';
          }
          $detail_columns[$i][$column] = $values[$i];

          $detail_random = record_prefix($this->grants->dependant_table($this->controller)) . '-' . rand(1000, 90000);
          $detail_columns[$i][$this->grants->dependant_table($this->controller) . '_track_number'] = $detail_random;
          $detail_columns[$i]['fk_' . $this->controller . '_id'] = $header_id;

          // Only insert fk_status_if is the detail record requires approval
          //if($detail_records_require_approval){
          $detail_columns[$i]['fk_status_id'] = $this->initial_item_status($this->grants->dependant_table($this->controller));
          //}

          $detail['fk_approval_id'] = $approval_id;

          $detail_columns[$i][$this->grants->dependant_table($this->controller) . '_created_date'] = date('Y-m-d');
          $detail_columns[$i][$this->grants->dependant_table($this->controller) . '_created_by'] =  $this->session->user_id;
          $detail_columns[$i][$this->grants->dependant_table($this->controller) . '_modified_by'] =  $this->session->user_id;
        }
      }
      $details = $detail_columns;


      // Insert the details using insert batch
      $this->write_db->insert_batch($this->grants->dependant_table($this->controller), $detail_columns);
    }

    $model = $this->controller . '_model';

    $transaction_validate_duplicates_columns = is_array($this->$model->transaction_validate_duplicates_columns()) ? $this->$model->transaction_validate_duplicates_columns() : [];

    $transaction_validate_duplicates = $this->transaction_validate_duplicates($this->controller, $header, $transaction_validate_duplicates_columns);
    $transaction_validate_by_computation = $this->transaction_validate_by_computation($this->controller, $header);

    return $this->transaction_validate([$transaction_validate_duplicates, $transaction_validate_by_computation], $header_columns, $header_id, $approval_id);
  }

  /**
   * upload_attachment
   * 
   * @todo - not yet in use. Intended to be used by view action pages
   * @param String - Encoded primary key of the active record
   * @return void
   */
  function upload_attachment($record_id)
  {
    return true;
  }

  function transaction_validate($validation_flags_and_failure_messages, $post_array = [], $header_id = 0, $approval_id = 0)
  {
    $message = '';

    $message_and_flag=[];

    $message_and_flag['flag']  = false;

    $validation_flags = array_column($validation_flags_and_failure_messages, 'flag');

    if ($this->write_db->trans_status() == false) {

      $this->write_db->trans_rollback();
      return get_phrase('insert_failed');
    } else {
      if (in_array(false, $validation_flags)) {
        $this->write_db->trans_rollback();

        foreach ($validation_flags_and_failure_messages as $validation_check) {
          if (!$validation_check['flag']) {
            $message .= $validation_check['error_message'] . '\n';
            $message_and_flag['flag']=$validation_check['flag'];
            $message_and_flag['message']=$message;
          }
        }
      } else {
        if ($this->grants->action_after_insert($post_array, $approval_id, $header_id)) {
          $this->write_db->trans_commit();
          $message = get_phrase('insert_successful');
          $message_and_flag['flag'] = true;
          $message_and_flag['message']=$message;
          $message_and_flag['header_id'] = hash_id($header_id,'encode');
          $message_and_flag['table'] = $this->controller;
        } else {
          $this->write_db->trans_rollback();
          $message = get_phrase('insert_failed');
          $message_and_flag['message']=$message;
        }
      }
    }

    return json_encode($message_and_flag);
  }

  // public function transaction_validate_by_computation(){
  //   return ['flag'=>true,'error_message'=>'Computation failed'];
  // }

  public function transaction_validate_by_computation(String $table_name, array $insert_array)
  {

    $validation_successful = true;
    $failure_message = get_phrase('validation_failed');

    $model = $table_name . "_model";

    if (method_exists($this->$model, 'transaction_validate_by_computation_flag')) {
      if ($this->$model->transaction_validate_by_computation_flag($insert_array) == 'VALIDATION_ERROR') {
        $validation_successful = false;
      }
    }

    return ['flag' => $validation_successful, 'error_message' => $failure_message];
  }

  public function transaction_validate_duplicates(String $table_name, array $insert_array, array $validation_fields = [], int $allowable_records = 0)
  {

    $validation_successful = true;
    $failure_message = get_phrase('no_duplicate_records');

    $model = $table_name . "_model";

    if (method_exists($this->$model, 'transaction_validate_duplicates_columns') && is_array($validation_fields) && count($validation_fields) > 0) {

      $validate_duplicates_columns = $this->$model->transaction_validate_duplicates_columns();

      $insert_array_keys = array_unique(array_merge(array_keys($insert_array), $validate_duplicates_columns));

      foreach ($insert_array_keys as $insert_column) {

        if (!array_key_exists($insert_column, $insert_array)) {
          $missing_field_in_insert_array = [$insert_column => 1];
          $insert_array = array_merge($insert_array, $missing_field_in_insert_array);
        }

        if (!in_array($insert_column, $validation_fields)) {
          unset($insert_array[$insert_column]);
        }
      }

      $this->read_db->where($insert_array);
      $result = $this->read_db->get($table_name)->num_rows();

      if ($result > $allowable_records) {
        $validation_successful = false; // Validation error flag

        $failure_message = get_phrase('duplicate_entries_not_allowed');
      }
    }

    return ['flag' => $validation_successful, 'error_message' => $failure_message];
  }

  /**
   * get_all_table_fields
   * 
   * A wrapper to CI table_exists method to check if a specified table exists in current database
   * and lists its field in an array or returns an empty array if not
   * 
   * @param String $table_name : Selected table
   * 
   * @return Array - Array of table fields
   */
  public function get_all_table_fields(String $table_name = ""): array
  {
    //$this->controller = 'dashboard';
    $table = $table_name == "" ? strtolower($this->controller) : strtolower($table_name);

    $fields = $this->grants->list_fields($table);
    return $fields;
  }

  public function get_all_tables(){

    $tables = $this->grants->list_tables();
    return $tables;
  }

  /**
   * Similar implementation in MY_Model - Prefer using the MY_Model
   */

  function lookup_tables($table_name = "")
  {
    
    if ($table_name == '') $table_name = $this->controller;

    $fields = $this->grants_model->get_all_table_fields($table_name);

    $foreign_tables_array_padded_with_false = array_map(function ($elem) {
      return substr($elem, 0, 3) == 'fk_' ? substr($elem, 3, -3) : false;
    }, $fields);

    //print_r($foreign_tables_array_padded_with_false);exit;

    // Prevent listing false values and status or approval tables for lookup. 
    // Add status_name and approval_name to the correct visible_columns method in models to see these fields in a page
    $foreign_tables_array = array_filter($foreign_tables_array_padded_with_false, function ($elem) {
      return $elem ? $elem : false;
    });

    // Just remove approval table due to its performance degradation history. This table will be removed in the future
    if (in_array('approval', $foreign_tables_array)) {
      unset($foreign_tables_array[array_search('approval', $foreign_tables_array)]);
    }

    // Hide status and approval columns if the active controller/table is not approveable

    if (!$this->grants_model->approveable_item($table_name)) {
      if (in_array('status', $foreign_tables_array)) {
        unset($foreign_tables_array[array_search('status', $foreign_tables_array)]);
      }
      
    }

    return $foreign_tables_array;
    
    
  }

  /**
   * table_fields_metadata
   * 
   * A field_data CI method wrapper to list field metadata 
   * 
   * @param String $table_name : Selected table
   * 
   * @return Array - Table fields metadata
   */
  public function table_fields_metadata($table_name = "")
  {
    $table = $table_name == "" ? $this->controller : $table_name;
  
    $result = $this->grants->field_data($table);
    return $result;
  }

  public function fields_meta_data_type_and_name($table)
  {

    $fields_meta_data = [];

    $table_names = $this->grants->lookup_tables($table);

    array_push($table_names, $table);

    foreach ($table_names as $table_name) {

      if ($table_name !== $table) {
        $this->load->library($table_name . '_library');
      }

      $feature_library = $table_name . '_library';

      $meta_data = $this->table_fields_metadata($table_name);
      $names = array_column($meta_data, 'name');
      $types = array_column($meta_data, 'type');
      $fields_meta_data = array_merge($fields_meta_data, array_combine($names, $types));

      foreach ($fields_meta_data as $field_name => $field_type) {
        if (substr($field_name, 0, 3) == 'fk_') {
          $_field_name = substr($field_name, 3, -3) . '_name';
          unset($fields_meta_data[$field_name]);
          $fields_meta_data[$_field_name] = 'varchar';
        }

        if (
          method_exists($this->{$feature_library}, 'change_field_type') &&
          array_key_exists($field_name, $this->{$feature_library}->change_field_type())
        ) {
          $fields_meta_data[$field_name] = $this->{$feature_library}->change_field_type()[$field_name]['field_type'];
        }
      }
    }

    //print_r($fields_meta_data);exit;

    return $fields_meta_data;
  }

  /**
   * table_exists
   * 
   * A wrapper method table_exists CI DB object 
   * 
   * @param String : Selected table to check if exists
   * 
   * @return Boolean
   */
  public function table_exists(String $table_name = ""): bool
  {

    $table = $table_name == "" ? $this->controller : $table_name;

    $table_exists = false;

    $list_tables = $this->grants->list_tables();

    if(in_array($table,$list_tables)){
      $table_exists = true;
    }

    return $table_exists;
  }

  /**
   * lookup_values
   * 
   * Create an array used when creating select field options as shown in the grants library detail_row_fields method
   * 
   * @todo - there is an error here when you specify the return type the insert row event in the 
   * multi_form_add action views does not work
   * 
   * @param String $table - Selected Lookup table
   * @return Array - Lookup options array
   */
  function lookup_values(String $table)
  {

    $table = strtolower($table);


    if (
      isset($this->grants->lookup_values_where()[$table]) &&
      is_array($this->grants->lookup_values_where()[$table]) &&
      count($this->grants->lookup_values_where()[$table]) > 0
    ) {
      //$this->create_table_join_statement(strtolower($this->controller),$this->grants->lookup_tables($this->controller));
      $this->read_db->where($this->grants->lookup_values_where()[$table]);
    }

    $result = $this->read_db->get($table)->result_array();

    $ids_array = array_column($result, $this->grants->primary_key_field($table));
    $value_array = array_column($result, $this->grants->name_field($table));

    return array_combine($ids_array, $value_array);
  }

  function initialize_db_schema()
  {
    if (file_exists('database/blank_db.sql')) {
      $sql = file_get_contents('database/blank_db.sql');
      $exploded_sql = explode(';', $sql);

      foreach ($exploded_sql as $query) {
        $this->write_db->query($query);
      }
    }
  }

  function populate_initial_table_data()
  {

    $initialize_tables = $this->config->item('setup_initialized_tables');

    $foreign_keys_values = [];

    foreach ($initialize_tables as $initialize_table) {

      if (!$this->read_db->table_exists($initialize_table)) continue;

      $model = $initialize_table . '_model';

      $this->load->model($model);

      if (method_exists($this->$model, 'intialize_table')) {
        $insert_id = $this->$model->intialize_table($foreign_keys_values);

        $foreign_keys_values[$initialize_table . '_id'] = $insert_id;
      }
    }

    if (count($foreign_keys_values) > 0) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * create_context_tables
   * 
   * This method creates new context tables and files if not exists
   * 
   * @return Array
   */

  function create_context_tables()
  {

    $this->load->dbforge();

    $context_definitions = $this->config->item('context_definitions');

    $reversed_array_reverse = array_reverse($context_definitions);

    $count = count($reversed_array_reverse);

    $fields = [];
    $user_fields = [];

    $app_name = 'Core';

    // Unlink old context files - models and libraries
    $this->unlink_old_context_files($app_name);

    foreach ($reversed_array_reverse as $context_definition) {

      // Create a context schema table

      $context_rel_field = $count == count($reversed_array_reverse) ? "fk_context_global_id" : "fk_context_" . array_shift($reversed_array_reverse) . "_id";

      if (!$this->read_db->table_exists('context_' . $context_definition)) {
        $fields[$context_definition] = [
          "context_" . $context_definition . "_id int(100) NOT NULL PRIMARY KEY AUTO_INCREMENT",
          "context_" . $context_definition . "_track_number VARCHAR(100) NULL",
          "context_" . $context_definition . "_name VARCHAR(100) NULL",
          "context_" . $context_definition . "_description VARCHAR(100) NULL",
          "fk_office_id VARCHAR(100) NOT NULL",
          "fk_context_definition_id VARCHAR(100) NULL",
          $context_rel_field . " INT(100) NULL",
          "context_" . $context_definition . "_created_date DATE NULL DEFAULT '0000-00-00'",
          "context_" . $context_definition . "_created_by INT(100) NULL",
          "context_" . $context_definition . "_last_modified_date TIMESTAMP NULL DEFAULT '0000-00-00'",
          "context_" . $context_definition . "_last_modified_by INT(100) NULL",
          "fk_approval_id INT(100) NULL",
          "fk_status_id INT(100) NULL"
        ];

        foreach ($fields[$context_definition] as $fld_def) {
          $this->dbforge->add_field($fld_def);
        }

        $this->dbforge->create_table('context_' . $context_definition);
      }

      // Create a context user schema table
      if (!$this->read_db->table_exists('context_' . $context_definition . '_user')) {

        $user_fields[$context_definition] = [
          "context_" . $context_definition . "_user_id int(100) NOT NULL PRIMARY KEY AUTO_INCREMENT",
          "context_" . $context_definition . "_user_track_number VARCHAR(100) NULL",
          "context_" . $context_definition . "_user_name VARCHAR(100) NULL",
          'fk_context_' . $context_definition . '_id INT(100) NOT NULL',
          'fk_user_id INT(100) NOT NULL',
          'fk_designation_id INT(100) NOT NULL',
          'context_region_user_is_active INT(100) NOT NULL',
          'context_' . $context_definition . '_user_created_by INT(100) NOT NULL',
          'context_' . $context_definition . '_user_created_date DATE NULL DEFAULT "0000-00-00"',
          'context_' . $context_definition . '_user_last_modified_date TIMESTAMP NULL DEFAULT "0000-00-00"',
          'context_' . $context_definition . '_user_last_modified_by INT(100) NOT NULL',
          'fk_approval_id INT(100) NULL',
          'fk_status_id INT(100) NULL',
        ];

        foreach ($user_fields[$context_definition] as $user_fld_def) {
          $this->dbforge->add_field($user_fld_def);
        }

        //$this->dbforge->add_key("context_".$context_definition."_user_id", TRUE);
        $this->dbforge->create_table('context_' . $context_definition . '_user');
      }

      // Create context models and libraries files
      $this->create_context_files('context_' . $context_definition, $app_name);

      // Create context models and libraries files
      $this->create_context_files('context_' . $context_definition . '_user', $app_name);

      $count--;
    }


    return ['context_fields' => $fields, 'user_fields' => $user_fields];
  }

  function create_context_files($table_name, $app_name)
  {

    // Create new models and libraries
    $table_specs['lookup_tables'] = $this->lookup_tables($table_name);
    $this->grants->create_missing_system_files_methods($table_name, $app_name, $table_specs);
  }

  function unlink_old_context_files($app_name)
  {
    $controllers_path = APPPATH . 'controllers' . DIRECTORY_SEPARATOR;
    $models_path = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . 'Packages' . DIRECTORY_SEPARATOR . ucfirst($app_name) . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR;
    $libararies_path = APPPATH . 'third_party' . DIRECTORY_SEPARATOR . 'Packages' . DIRECTORY_SEPARATOR . ucfirst($app_name) . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR;

    $path_array = [$controllers_path, $models_path, $libararies_path];

    foreach ($path_array as $path) {
      $files_array = directory_iterator($path);
      $context_files = $this->filter_context_files(array_keys($files_array));

      foreach ($context_files as $file) {
        unlink($path . DIRECTORY_SEPARATOR . $file);
      }
    }
  }

  function filter_context_files($files)
  {
    $context_tables = array_filter($files, function ($file) {
      return substr($file, 0, 8) == 'Context_' && !strpos($file, 'definition', 8) &&  !strpos($file, 'global', 8) ? $file : false;
    });

    return $context_tables;
  }

  function insert_status_for_approveable_item($approve_item_name)
  {
    //$approve_item_name = "country_currency";

    if (strlen($approve_item_name) > 2) {
      $this->write_db->trans_start();

      $user_id = $this->session->userdata('user_id') ? $this->session->user_id : 1; // User Id 1 is created by setup

      // Check if the table is in the approveable items table if not create it
      $approve_item_id = $this->insert_missing_approveable_item($approve_item_name);

      $account_systems_obj = $this->write_db->get('account_system');

      if ($account_systems_obj->num_rows() > 0) {

        $account_systems = $account_systems_obj->result_array();

        foreach ($account_systems as $account_system) {

          $approval_flow_obj = $this->write_db->get_where(
            'approval_flow',
            array(
              'fk_approve_item_id' => $approve_item_id,
              'fk_account_system_id' => $account_system['account_system_id']
            )
          );

          $approval_flow_id = 0;

          if ($approval_flow_obj->num_rows() == 0) {
            $approval_flow_id = $this->insert_approval_flow($account_system, $approve_item_id, $approve_item_name, $user_id);
          } else {
            $approval_flow_id = $approval_flow_obj->row()->approval_flow_id;
          }

          // Insert the new status

          $status = $this->write_db->get_where(
            'status',
            array('fk_approval_flow_id' => $approval_flow_id)
          ); //,'status_approval_sequence'=>1

          if ($status->num_rows() < 2) {

            $this->insert_initial_and_final_status($approval_flow_id, $user_id);
          }
        }
      }

      $this->write_db->trans_complete();

      if ($this->write_db->trans_status() == false) {
        $message = "Error occurred when creating missing status";
        show_error($message, 500, 'An Error As Encountered');
        //echo 0;
        return false;
      } else {
        //echo 1;
        return true;
      }
    } else {
      //echo 2;
      return false;
    }
  }

  function insert_approval_flow($account_system, $approve_item_id, $approve_item_name, $user_id)
  {
    $approval_flow_data['approval_flow_track_number'] = $this->generate_item_track_number_and_name('approval_flow')['approval_flow_track_number'];
    $approval_flow_data['approval_flow_name'] = $account_system['account_system_name'] . ' ' . str_replace("_", " ", $approve_item_name) . ' workflow';
    $approval_flow_data['fk_approve_item_id'] = $approve_item_id;
    $approval_flow_data['fk_account_system_id'] = $account_system['account_system_id'];

    $approval_flow_data['approval_flow_created_by'] = $user_id;
    $approval_flow_data['approval_flow_created_date'] = date('Y-m-d');
    $approval_flow_data['approval_flow_last_modified_by'] = $user_id;

    $this->write_db->insert('approval_flow', $approval_flow_data);

    return $this->write_db->insert_id();
  }

  function insert_status($user_id, $status_name, $approval_flow_id, $status_approval_sequence, $status_backflow_sequence, $status_approval_direction, $status_is_requiring_approver_action, $status_button_label = "", $status_decline_button_label = "")
  {

    $status_id = 0;

    $insert_status = $this->read_db->get_where(
      'status',
      array(
        'fk_approval_flow_id' => $approval_flow_id,
        'status_approval_sequence' => $status_approval_sequence,
        'status_backflow_sequence' => $status_backflow_sequence,
        'status_approval_direction' => $status_approval_direction,
        'status_is_requiring_approver_action' => $status_is_requiring_approver_action
      )
    );

    if ($insert_status->num_rows() == 0) {
      $data['status_track_number'] = $this->grants_model->generate_item_track_number_and_name('status')['status_track_number'];
      $data['status_name'] = $status_name;
      $data['status_button_label'] = $status_button_label;
      $data['status_decline_button_label'] = $status_decline_button_label;
      $data['fk_approval_flow_id'] = $approval_flow_id;
      $data['status_approval_sequence'] = $status_approval_sequence;
      $data['status_backflow_sequence'] = $status_backflow_sequence;
      $data['status_approval_direction'] = $status_approval_direction;
      $data['status_is_requiring_approver_action'] = $status_is_requiring_approver_action;

      $data['status_created_date'] = date('Y-m-d');
      $data['status_created_by'] = $user_id;
      $data['status_last_modified_by'] = $user_id;

      $this->write_db->insert('status', $data);

      $status_id = $this->write_db->insert_id();
    } else {
      $status_id = $insert_status->row()->status_id;
    }

    return $status_id;
  }

  function insert_initial_and_final_status($approval_flow_id, $user_id)
  {

    // Insert initial status record
    $initial_status_id = $this->insert_status($user_id, get_phrase('ready_to_submit'), $approval_flow_id, 1, 0, 1, 1);

    // Insert fully approved status
    $fully_approved_status_id = $this->insert_status($user_id, get_phrase('fully_approved'), $approval_flow_id, 2, 0, 1, 0);
    $reinstate_after_allow_edit_status_id = $this->insert_status($user_id, get_phrase('reinstate_after_allow_edit'), $approval_flow_id, 2, 1, -1, 1);
    $reinstated_after_edit_status_id = $this->insert_status($user_id, get_phrase('reinstated_after_edit'), $approval_flow_id, 2, 0, 0, 1);

    
    return $fully_approved_status_id;
  }

  function insert_status_if_missing($approve_item_name)
  {

    $res = $this->insert_status_for_approveable_item($approve_item_name);

    // Check if has dependant table
    $has_dependant_table = $this->grants->has_dependant_table($approve_item_name);
    $dependant_table = $this->grants->dependant_table($approve_item_name);

    if ($has_dependant_table) {
      $this->mandatory_fields($dependant_table);
      $this->insert_status_for_approveable_item($dependant_table);
    }

    return $res;
  }


  /**
   * mandatory_fields
   * 
   * This method adds mandatory fields in a table. All tables should contain the following fields:
   * xxxx_created_date, xxxx_created_by, xxxx_last_modified_date, xxxx_last_modified_by, fk_approval_id and
   * fk_status_id
   * 
   * Again the approve_item table should contain the name of the table as approvable item and create a default 
   * new status of this table in the status table. Give this new status an status_approval_sequence of 1
   * 
   * @param $table String : The selected table
   * 
   * @return Bool
   */

  function insert_missing_approveable_item($table)
  {

    $approve_items = $this->write_db->get_where('approve_item', array('approve_item_name' => $table));

    $approve_item_id = 0;

    $user_id = $this->session->userdata('user_id') ? $this->session->user_id : 1; // User Id 1 is created by setup

    if ($approve_items->num_rows() == 0) {
      $data['approve_item_track_number'] = $this->generate_item_track_number_and_name('approve_item')['approve_item_track_number'];
      $data['approve_item_name'] = $table;
      $data['approve_item_is_active'] = 0;
      $data['approve_item_created_date'] = date('Y-m-d');
      $data['approve_item_created_by'] = $user_id; //$this->session->user_id;
      $data['approve_item_last_modified_by'] = $user_id; //$this->session->user_id;

      //$approve_item_data_to_insert = $this->merge_with_history_fields('approve_item',$data,false);
      $this->write_db->insert('approve_item', $data);

      $approve_item_id = $this->write_db->insert_id();
    } else {
      $approve_item_id = $approve_items->row()->approve_item_id;
    }

    return $approve_item_id;
  }

  function mandatory_fields(String $table): void
  {
    
    if($table != ''){
     
      $table = strtolower($table);

      $mandatory_fields = [];

      if ($table !== 'approval' && $table !== 'approval_flow') {
        //Mandatory Fields: created_by, created_date,last_modified_by,last_modified_date,fk_approval_id,fk_status_id
        $mandatory_fields = array(
          $table . '_created_date', $table . '_created_by', $table . '_last_modified_by',
          $table . '_last_modified_date', 'fk_approval_id', 'fk_status_id'
        );
      } elseif ($table == 'approval') {
        $mandatory_fields = array(
          $table . '_created_date', $table . '_created_by', $table . '_last_modified_by',
          $table . '_last_modified_date', 'fk_status_id'
        );
      } elseif ($table == 'approval_flow') {
        $mandatory_fields = array(
          $table . '_created_date', $table . '_created_by', $table . '_last_modified_by',
          $table . '_last_modified_date'
        );
      }
      // Check if the mandatory fields exists in the listed table and if not alter the table by 
      // adding a column with default value as the newly inserted status_id

      $fields_to_add = array();

      $table_fields = $this->get_all_table_fields($table);
      //print_r($table_fields);exit;
      foreach ($mandatory_fields as $mandatory_field) {
        if (!in_array($mandatory_field, $table_fields)) {
          
          if (substr($mandatory_field, 0, 3) == 'fk_' || substr($mandatory_field, -3, 3) == '_by') {
            $fields_to_add[$mandatory_field]['type'] = 'INT';
            $fields_to_add[$mandatory_field]['constraint'] = '100';
          } elseif (strpos($mandatory_field, '_date') == true) {
            $fields_to_add[$mandatory_field]['type'] = 'date';
            //$fields_to_add[$mandatory_field]['constraint'] = '100';
          } else {
            $fields_to_add[$mandatory_field]['type'] = 'varchar';
            $fields_to_add[$mandatory_field]['constraint'] = '100';
          }
        }
      }

      if (count($fields_to_add) > 0) {
        $this->load->dbforge();
        $this->dbforge->add_column($table, $fields_to_add);
      }

    }
    
  }

  /**
   * create_table_join_statement
   * 
   * This method creates join statements of a CI query. It uses the primary tbale as first arg
   * and an array of its Secondary tables as derived from the primary table model
   * 
   * @param String $table - This is the primary table
   * @param Array $lookup_tables - This is an array of secondary tables
   * 
   * @return Mixed 
   */
  function create_table_join_statement(String $table, array $lookup_tables)
  {
    if (is_array($lookup_tables) && count($lookup_tables) > 0) {
      foreach ($lookup_tables as $lookup_table) {
        $lookup_table_id = $this->grants->primary_key_field($lookup_table);
        $this->read_db->join($lookup_table, $lookup_table . '.' . $lookup_table_id . '=' . $table . '.fk_' . $lookup_table_id);
      }
    }
  }

  function create_table_join_statement_with_depth($base_table, $labeled_tables)
  {

    //https://codeigniter.com/userguide3/database/query_builder.html

    $join_array = $this->_create_table_join_statement_with_depth($base_table, $labeled_tables);

    foreach ($join_array as $join_tables_depth) {
      foreach ($join_tables_depth as $primary_table => $join_tables) {
        foreach ($join_tables as $join_table) {
          $this->read_db->join($join_table, $join_table . '.' . $join_table . '_id=' . $primary_table . '.fk_' . $join_table . '_id');
        }
      }
    }
  }

  function test_create_table_join_statement_with_depth($base_table, $labeled_tables)
  {

    //https://codeigniter.com/userguide3/database/query_builder.html
  

    $str = "";

    $join_array = $this->_create_table_join_statement_with_depth($base_table, $labeled_tables);

    foreach ($join_array as $join_tables_depth) {
      foreach ($join_tables_depth as $primary_table => $join_tables) {
        foreach ($join_tables as $join_table) {
        
          $str .= $join_table; //.','.$join_table.'.'.$join_table.'_id='.$primary_table.'.fk_'.$join_table.'_id<br/>';
        }
      }
    }

    return $join_array;
  }


  function _create_table_join_statement_with_depth($base_table, $labeled_tables)
  {

    $leveled_fields[1][$base_table] = [];

    foreach ($labeled_tables as $labeled_table) {
      if (in_array("fk_" . $labeled_table . "_id", $this->get_all_table_fields($base_table))) {
        unset($labeled_tables[array_search($labeled_table, $labeled_tables)]);
        $level_one_labeled_tables = $labeled_tables;
        array_push($leveled_fields[1][$base_table], $labeled_table);


        //Second Level
        if (count($level_one_labeled_tables) == 0) break;
        foreach ($leveled_fields[1][$base_table] as $level_two_base_table) {
          $leveled_fields[2][$level_two_base_table] = [];
          foreach ($level_one_labeled_tables as $level_two_labeled_table) {
            if (in_array('fk_' . $level_two_labeled_table . '_id', $this->get_all_table_fields($level_two_base_table))) {
              unset($level_one_labeled_tables[array_search($level_two_labeled_table, $level_one_labeled_tables)]);
              $level_two_labeled_tables = $level_one_labeled_tables;
              array_push($leveled_fields[2][$level_two_base_table], $level_two_labeled_table);


              //Third Level
              if (count($level_two_labeled_tables) == 0) break;
              foreach ($leveled_fields[2][$level_two_base_table] as $level_three_base_table) {
                $leveled_fields[3][$level_three_base_table] = [];
                foreach ($level_two_labeled_tables as $level_three_labeled_table) {
                  if (in_array('fk_' . $level_three_labeled_table . '_id', $this->get_all_table_fields($level_three_base_table))) {
                    unset($level_two_labeled_tables[array_search($level_three_labeled_table, $level_two_labeled_tables)]);
                    $level_three_labeled_tables = $level_two_labeled_tables;
                    array_push($leveled_fields[3][$level_three_base_table], $level_three_labeled_table);


                    // Fourth level
                    if (count($level_three_labeled_tables) == 0) break;
                    foreach ($leveled_fields[3][$level_three_base_table] as $level_four_base_table) {
                      $leveled_fields[4][$level_four_base_table] = [];
                      foreach ($level_three_labeled_tables as $level_four_labeled_table) {
                        if (in_array('fk_' . $level_four_labeled_table . '_id', $this->get_all_table_fields($level_four_base_table))) {
                          unset($level_three_labeled_tables[array_search($level_four_labeled_table, $level_three_labeled_tables)]);
                          $level_four_labeled_tables = $level_three_labeled_tables;
                          array_push($leveled_fields[4][$level_four_base_table], $level_four_labeled_table);


                          // Fifth level
                          if (count($level_four_labeled_tables) == 0) break;
                          foreach ($leveled_fields[4][$level_three_base_table] as $level_five_base_table) {
                            $leveled_fields[5][$level_five_base_table] = [];
                            foreach ($level_four_labeled_tables as $level_five_labeled_table) {
                              if (in_array('fk_' . $level_five_labeled_table . '_id', $this->get_all_table_fields($level_five_base_table))) {
                                unset($level_four_labeled_tables[array_search($level_five_labeled_table, $level_four_labeled_tables)]);
                                $level_five_labeled_tables = $level_four_labeled_tables;
                                array_push($leveled_fields[5][$level_five_base_table], $level_five_labeled_table);


                                //Sixth Level
                              }
                            }
                          }
                        }
                      }
                    }
                  }
                }
              }
            }
          }
        }
        //return $labeled_tables;
      }
    }

    return $leveled_fields;
  }

  function get_record_office_id($table, $primary_key)
  {
    $lookup_tables = $this->grants->lookup_tables($table);
    $pk_field = $this->grants->primary_key_field($table);

    $office_id = 0;

    if (in_array('office', $lookup_tables)) {
      $office_id = $this->read_db->get_where(
        $table,
        array($pk_field => $primary_key)
      )->row()->fk_office_id;
    }

    return $office_id;
  }

  function center_where_condition()
  {
    $lookup_tables = $this->grants->lookup_tables($this->controller);

    if (in_array('center', $lookup_tables)) {
      $this->read_db->where_in($this->controller . '.fk_center_id', $this->session->user_centers);
    }
  }



  function page_view_where_condition(...$args)
  {

    $table = $args[0];


    $page_view_raw_conditions = array();

    if (isset($args[1])) {

      $page_view_id = $args[1];

      //Page view conditions
      $this->read_db->select(array(
        'page_view_condition_field', 'page_view_condition_operator',
        'page_view_condition_value'
      ));
      $this->read_db->join('page_view', 'page_view.page_view_id=page_view_condition.fk_page_view_id');
      $page_view_raw_conditions = $this->read_db->get_where(
        'page_view_condition',
        array('page_view_id' => $page_view_id)
      );
    } else {
      //Get the default page view (Non system admin)
      $this->read_db->select(array(
        'page_view_condition_field', 'page_view_condition_operator',
        'page_view_condition_value'
      ));

      if (!$this->session->system_admin) {
        $this->read_db->join('page_view_role', 'page_view_role.fk_page_view_id=page_view.page_view_id');
        $this->read_db->where(array('fk_role_id' => $this->session->role_id, 'page_view_role_is_default' => 1));
      } else {
        $this->read_db->where(array('page_view_is_default' => 1));
      }

      $this->read_db->join('page_view', 'page_view.page_view_id=page_view_condition.fk_page_view_id');

      $page_view_raw_conditions = $this->read_db->get('page_view_condition');
    }

    if ($page_view_raw_conditions->num_rows() > 0) {
      $page_view_raw_conditions = $page_view_raw_conditions->result_object();

      foreach ($page_view_raw_conditions as $raw_condition) {

        $value = $raw_condition->page_view_condition_value;
        if ($raw_condition->page_view_condition_value == 'USER') {
          $value = $this->session->user_id;
        }

        // If operator is equal, less than, greater than, equal or less, equal or greater
        $query_condition = array($table . '.' . $raw_condition->page_view_condition_field => $value);

        if ($raw_condition->page_view_condition_operator == 'contains') {
          //If operator is contains - Not yet tested
          $query_condition = $table . '.' . $raw_condition->page_view_condition_field . " LIKE %" . $value . "%";
        } elseif ($raw_condition->page_view_condition_operator == 'between') {
          //If operator is between - Not yet tested
          $query_condition = $table . '.' . $raw_condition->page_view_condition_field . "BETWEEN " . $value;
        }

        $this->read_db->where($query_condition);
      }
    }
  }

  function max_number_of_menu_items()
  {
    return $this->read_db->get('menu')->num_rows();
  }

  private function _run_list_query(
    $table,
    $selected_columns,
    $lookup_tables,
    $model_where_method = "list_table_where",
    $filter_where_array = array()
  ) {

    //print_r($lookup_tables);exit;
    // Run column selector
    $this->read_db->select($selected_columns);

    // Model defined where condition
    $model = $table . '_model';

    if (!$this->load->is_loaded($model)) {
      $this->load->model($model);
    }


    if (method_exists($this->$model, $model_where_method)) {
      $this->$model->$model_where_method();
    }

    if (is_array($lookup_tables) && count($lookup_tables) > 0) {
      foreach ($lookup_tables as $lookup_table) {
        
        // Catch errors of missing lookup_tables in models
        if (!$this->grants_model->table_exists($lookup_table)) {
          $message = "The table " . $lookup_table . " doesn't exist in the database. Check the lookup_tables function in the " . $table . "_model";
          show_error($message, 500, 'An Error Was Encountered');
        }
        $lookup_table_id = $lookup_table . '_id';
        $this->read_db->join($lookup_table, $lookup_table . '.' . $lookup_table_id . '=' . $table . '.fk_' . $lookup_table_id);
      }
    }

    if (method_exists($this->$model, 'order_list_page')) {
      $this->read_db->order_by($this->$model->order_list_page());
    } else {
      $this->read_db->order_by($table . '_created_date DESC');
    }

    //View OUTPUT API defined condition array
    if (is_array($filter_where_array) && count($filter_where_array) > 0) {
      $this->read_db->where($filter_where_array);
    }
  }

  function run_list_query_count_all_records(
    $table,
    $selected_columns,
    $lookup_tables,
    $model_where_method = "list_table_where",
    $filter_where_array = array()
  ){
    if (!$this->read_db->get($table)) {
      $error = $this->read_db->error();
      //print_r($error);
      $message = 'The table ' . $this->controller . ' has no relationship with ' . $table . '. Check the ' . $this->controller . '_model detail_tables method';
      show_error($message, 500, 'An Error Was Encountered');
    } else {
      
      $this->_run_list_query($table, $selected_columns, $lookup_tables, $model_where_method, $filter_where_array);

      $this->read_db->from($table);
      return  $this->read_db->count_all_results();
    }
  }

  /**
   * run_query
   * 
   * Runs an SQL query. Example used in the List_output class in the Output API
   * 
   * @param String $table - Selected table
   * @param Array $select_columns - Columns to be used in the select SQL portion
   * @param Array $lookup_tables - To be used to prepare a join statement
   * 
   * @return Array - Database result
   */
  public function run_list_query(
    $table,
    $selected_columns,
    $lookup_tables,
    $model_where_method = "list_table_where",
    $filter_where_array = array()
  ) {
    
    
    if (!$this->read_db->get($table)) {
      $error = $this->read_db->error();
      //print_r($error);
      $message = 'The table ' . $this->controller . ' has no relationship with ' . $table . '. Check the ' . $this->controller . '_model detail_tables method';
      show_error($message, 500, 'An Error Was Encountered');
    } else {
      
      $this->_run_list_query($table, $selected_columns, $lookup_tables, $model_where_method, $filter_where_array);
      //print_r($selected_columns);exit;
      /**
       * Datatables params - Start
       */

       if($this->input->post('draw')){

        // Limiting Server Datatable Results
          $start = intval($this->input->post('start'));
          $length = intval($this->input->post('length'));
    
          $this->read_db->limit($length, $start);
        
        // Ordering Server Datatable Results

          $order = $this->input->post('order');
          $col = '';
          $dir = 'desc';
    
          if(!empty($order)){
            $col = $order[0]['column'];
            $dir = $order[0]['dir'];
          }
          
          if( $col == ''){
            $this->read_db->order_by($table . '_id DESC');
          }else{
            $this->read_db->order_by($selected_columns[$col],$dir); 
          }
        
        // Searching Server Datatable Results

          $search = $this->input->post('search');
          $value = $search['value'];

          array_pop($selected_columns);

          if(!empty($value)){
            $this->read_db->group_start();
            $column_key = 0;
            foreach($selected_columns as $column){
              if($column_key == 0) {
                $this->read_db->like($column,$value,'both'); 
              }else{
                $this->read_db->or_like($column,$value,'both');
              }
              $column_key++;				
            }
            $this->read_db->group_end();
          }
          
       }

       /**
        * Datatables params - End
        */

      $result_object  = $this->read_db->get($table);

      if(!$result_object){
        clear_cache_files($table);
        return  $this->read_db->get($table)->result_array();
      }

      return  $result_object->result_array();

    }
  }


  function run_master_view_query($table, $selected_columns, $lookup_tables)
  {

    $this->read_db->select($selected_columns);

    if (is_array($lookup_tables) && count($lookup_tables) > 0) {
      foreach ($lookup_tables as $lookup_table) {
        //Create table joins
        $lookup_table_id = $this->grants->primary_key_field($lookup_table);
        $this->read_db->join($lookup_table, $lookup_table . '.' . $lookup_table_id . '=' . $table . '.fk_' . $lookup_table_id);
      }
    }


    $data = array();

    $library = $table . '_library';

    $this->load->library($library);

    if (
      method_exists($this->$library, 'master_view_table_where') &&
      is_array($this->$library->master_view_table_where()) &&
      count($this->$library->master_view_table_where()) > 0
    ) {
      $this->read_db->where($this->$library->master_view_table_where());
    }

    $data = (array)$this->read_db->get_where($table, array($this->grants->primary_key_field($table) => hash_id($this->uri->segment(3, 0), 'decode')))->row();

    // Get the name of the record creator
    $created_by = isset($data[$this->grants->history_tracking_field($table, 'created_by')]) && $data[$this->grants->history_tracking_field($table, 'created_by')] >= 1 ? $this->read_db->select('CONCAT(`user_firstname`," ",`user_lastname`) as user_name')->get_where(
      'user',
      array('user_id' => $data[$this->grants->history_tracking_field($table, 'created_by')])
    )->row()->user_name : get_phrase('creator_user_not_set');

    $data['created_by'] = $created_by;

    //Get the name of the last record modifier
    $last_modified_by = isset($data[$this->grants->history_tracking_field($table, 'last_modified_by')]) && $data[$this->grants->history_tracking_field($table, 'last_modified_by')] >= 1 ? $this->read_db->select('CONCAT(`user_firstname`," ",`user_lastname`) as user_name')->get_where(
      'user',
      array('user_id' => $data[$this->grants->history_tracking_field($table, 'last_modified_by')])
    )->row()->user_name : get_phrase('modifier_user_not_set');

    $data['last_modified_by'] = $last_modified_by;

    return $data;
  }


  /**
   * control_column_visibility
   * 
   * This method checks if a field/column has permission to with a create label
   * @todo this method doesn't interact with the database thus needs to meove to an API or Lib/ 
   * Has been moved to Access_base class/ Remove it after all Output API code is moved
   * @param $table String : Selected table
   * @param $visible_columns Array : Array of visible/ selected columns/ fields
   * @param $permission_label String : Can be create, update or read
   * 
   * @return Array
   */
  function control_column_visibility(String $table, array $visible_columns, String $permission_label = 'create'): array
  {
    $controlled_visible_columns = array();

    foreach ($visible_columns as $column) {
      if ($this->grants->check_role_has_field_permission($table, $permission_label, $column)) {
        $controlled_visible_columns[] = $column;
      }
    }

    return $controlled_visible_columns;
  }


  function grants_get($table)
  {

    $model = $table . '_model';
    $library = $table . '_library';

    $this->load->model($model);

    if (method_exists($this->$model, 'list_table_where')) {
      // This is always a $this->read_db->where statement
      $this->$library->list_table_where();
    }

    return $this->read_db->get($table)->result_array();
  }

  function grants_get_row($table)
  {

    $model = $table . '_model';

    $this->load->model($model);

    // if(method_exists($this->$model,'list_table_where') ){
    //   // This is always a $this->read_db->where statement
    //   $this->$model->list_table_where();
    // }

    return $this->read_db->get($table)->row();
  }

  function master_multi_form_add_visible_columns()
  {

    // Check if the table has list_table_visible_columns not empty
    $master_table_visible_columns = $this->grants->master_multi_form_add_visible_columns();
    $lookup_tables = $this->grants->lookup_tables();

    $get_all_table_fields = $this->get_all_table_fields();

    foreach ($get_all_table_fields as $get_all_table_field) {
      //Unset foreign keys columns, created_by and last_modified_by columns
      if (
        substr($get_all_table_field, 0, 3) == 'fk_' ||
        strpos($get_all_table_field, '_deleted_at') == true
      ) {
        unset($get_all_table_fields[array_search($get_all_table_field, $get_all_table_fields)]);
      }
    }


    $visible_columns = $get_all_table_fields;
    $lookup_columns = array();

    if (is_array($master_table_visible_columns) && count($master_table_visible_columns) > 0) {
      $visible_columns = $master_table_visible_columns;
    } else {

      if (is_array($lookup_tables) && count($lookup_tables) > 0) {
        foreach ($lookup_tables as $lookup_table) {

          $lookup_table_columns = $this->get_all_table_fields($lookup_table);

          foreach ($lookup_table_columns as $lookup_table_column) {
            // Only include the name field of the look up table in the select columns
            if (strpos($lookup_table_column, '_name') == true) {
              array_push($visible_columns, $lookup_table_column);
            }
          }
        }
      }
    }

    return $this->control_column_visibility($this->controller, $visible_columns);
  }

  function single_form_add_visible_columns()
  {

    // Check if the table has list_table_visible_columns not empty
    $master_table_visible_columns = $this->grants->single_form_add_visible_columns(); // Found in the feature model
    $lookup_tables = $this->grants->lookup_tables();
   
    $get_all_table_fields = $this->get_all_table_fields();

    foreach ($get_all_table_fields as $get_all_table_field) {
      //Unset foreign keys columns, created_by and last_modified_by columns
      if (
        substr($get_all_table_field, 0, 3) == 'fk_' ||
        strpos($get_all_table_field, '_deleted_at') == true
      ) {
        unset($get_all_table_fields[array_search($get_all_table_field, $get_all_table_fields)]);
      }
    }

    //print_r($get_all_table_fields);exit;
    $visible_columns = $get_all_table_fields;
    $lookup_columns = array();

    if (is_array($master_table_visible_columns) && count($master_table_visible_columns) > 0) {
      $visible_columns = $master_table_visible_columns;
    } else {
      
      // if(in_array('approval',$lookup_tables)) {
      //   unset($lookup_tables[array_search('approval',$lookup_tables)]);
      // }

      // if(in_array('status',$lookup_tables)) {
      //   unset($lookup_tables[array_search('status',$lookup_tables)]);
      // }

      if (is_array($lookup_tables) && count($lookup_tables) > 0) {
        foreach ($lookup_tables as $lookup_table) {

          $lookup_table_columns = $this->get_all_table_fields($lookup_table);

          foreach ($lookup_table_columns as $lookup_table_column) {
            // Only include the name field of the look up table in the select columns
            if (strpos($lookup_table_column, '_name') == true) {
              array_push($visible_columns, $lookup_table_column);
            }
          }
        }
      }
    }

    // $controlled_visible_column = array();

    // foreach ($visible_columns as $column) {
    //   if ($this->grants->check_role_has_field_permission($this->controller, 'create', $column)) {
    //     $controlled_visible_column[] = $column;
    //   }
    // }
   
    return $visible_columns;
  }

  function detail_tables_single_form_add_visible_columns()
  {

    $detail_single_form_add_visible_columns = [];

    $model = $this->controller . '_model';
    

    if (method_exists($this->$model, 'detail_tables_single_form_add_visible_columns') && !empty($this->$model->detail_tables_single_form_add_visible_columns())) {

      $detail_tables = $this->$model->detail_tables_single_form_add_visible_columns();

      foreach ($detail_tables as $detail_table) {

        $detail_model = $this->grants->load_detail_model($detail_table);

        // print_r($detail_model);
        // exit;

        if (method_exists($this->$detail_model, 'single_form_add_visible_columns') && !empty($this->$detail_model->single_form_add_visible_columns())) {
          $detail_single_form_add_visible_columns[$detail_table] = $this->$detail_model->single_form_add_visible_columns();
        }
      }
    }

    return $detail_single_form_add_visible_columns;
  }

  function edit_visible_columns()
  {

    $table = $this->controller;
    $visible_columns = [];

    // Check if the table has list_table_visible_columns not empty
    $edit_visible_columns = $this->grants->edit_visible_columns();
    // log_message('error', json_encode($edit_visible_columns));
    $lookup_tables = $this->grants->lookup_tables();

    $get_all_table_fields = $this->get_all_table_fields();

    foreach ($get_all_table_fields as $get_all_table_field) {
      //Unset foreign keys columns, created_by and last_modified_by columns
      if (
        substr($get_all_table_field, 0, 3) == 'fk_' ||
        strpos($get_all_table_field, '_deleted_at') == true
      ) {
        unset($get_all_table_fields[array_search($get_all_table_field, $get_all_table_fields)]);
      }
    }


    $visible_columns = $get_all_table_fields;
    $lookup_columns = array();

    if (is_array($edit_visible_columns) && count($edit_visible_columns) > 0) {
      $columns = [];
      foreach ($edit_visible_columns as $column) {

        if (strpos($column, '_name') == true && $column !== strtolower($table) . '_name') {
          $columns[] = substr($column, 0, -5) . '_id';
        } else {
          $columns[] = $column;
        }
      }
      $visible_columns = $columns;
      // log_message('error', json_encode($visible_columns));
    } else {

      if (is_array($lookup_tables) && count($lookup_tables) > 0) {
        foreach ($lookup_tables as $lookup_table) {

          $lookup_table_columns = $this->get_all_table_fields($lookup_table);

          foreach ($lookup_table_columns as $lookup_table_column) {
            // Only include the name field of the look up table in the select columns
            if (strpos($lookup_table_column, '_name') == true) {
              //array_push($visible_columns,$lookup_table_column);
              array_push($visible_columns, substr($lookup_table_column, 0, -5) . '_id');
            }
          }
        }
      }
    }
    // log_message('error', json_encode($lookup_tables));
    if (is_array($lookup_tables) && count($lookup_tables) > 0) {
      foreach ($lookup_tables as $lookup_table) {
        $lookup_table_id = $lookup_table . '_id';
        $this->read_db->join($lookup_table, $lookup_table . '.' . $lookup_table_id . '=' . $table . '.fk_' . $lookup_table_id);
      }
    }

    //print_r($visible_columns);exit;

    $this->read_db->select($visible_columns);
    $this->read_db->where(array($table . '_id' => hash_id($this->id, 'decode')));
    //print_r($this->grants_get_row($table));exit;
    return $this->grants_get_row($table);
  }

  function detail_multi_form_add_visible_columns($table)
  {

    // Check if the table has list_table_visible_columns not empty
    $detail_table_visible_columns = $this->grants->detail_multi_form_add_visible_columns($table);
    $lookup_tables = $this->grants->lookup_tables($table);

    $get_all_table_fields = $this->get_all_table_fields($table);

    foreach ($get_all_table_fields as $get_all_table_field) {
      //Unset foreign keys columns, created_by and last_modified_by columns
      if (
        substr($get_all_table_field, 0, 3) == 'fk_' ||
        strpos($get_all_table_field, '_deleted_at') == true
      ) {
        unset($get_all_table_fields[array_search($get_all_table_field, $get_all_table_fields)]);
      }
    }


    $visible_columns = $get_all_table_fields;
    $lookup_columns = array();

    if (is_array($detail_table_visible_columns) && count($detail_table_visible_columns) > 0) {
      $visible_columns = $detail_table_visible_columns;
    } else {
      if (is_array($lookup_tables) && count($lookup_tables) > 0) {
        foreach ($lookup_tables as $lookup_table) {

          $lookup_table_columns = $this->get_all_table_fields($lookup_table);

          foreach ($lookup_table_columns as $lookup_table_column) {
            // Only include the name field of the look up table in the select columns
            if (strpos($lookup_table_column, '_name') == true) {
              array_push($visible_columns, $lookup_table_column);
            }
          }
        }
      }
    }

    return $this->control_column_visibility($table, $visible_columns);
  }

  /**
   * approveable_item
   * 
   * Gives you true if the passed items is approveable else false
   * 
   * @param $approveable_item_name String - Object name
   * 
   * @return bool
   */

  function approveable_item($approveable_item_name = "")
  {

    $approveable_item_name = $approveable_item_name == "" ? $this->controller : $approveable_item_name;

    $approveable_item_flag = false;

    if (in_array($approveable_item_name,$this->session->approveable_items)) {
      $approveable_item_flag = true;
    }

    return $approveable_item_flag;
  }

  // This give the initial approval status when inserting a record - To be taken to approval modal

  function initial_item_status($table_name = "", $account_system_id = 0)
  {

    $this->read_db->reset_query();

    if($account_system_id == 0){
      $account_system_id = $this->session->user_account_system_id;
    }

    $table = $table_name == "" ? $this->controller : $table_name;

    $approveable_item = $this->read_db->get_where(
      'approve_item',
      array('approve_item_name' => $table)
    );

    $status_id = 0;

    if ($approveable_item->num_rows() > 0) {
      $approveable_item_id = $approveable_item->row()->approve_item_id;
      $approveable_item_is_active = $approveable_item->row()->approve_item_is_active;

      // Condition for Initial status
      $condition_array = array(
        'fk_approve_item_id' => $approveable_item_id,
        'status_approval_sequence' => 1, 'fk_account_system_id' => $account_system_id
      );

      if (!$approveable_item_is_active) {
        // Condition for fully approved status
        $condition_array = array(
          'fk_approve_item_id' => $approveable_item_id,
          'status_is_requiring_approver_action' => 0,
          'fk_account_system_id' => $account_system_id
        );
      }

      $this->read_db->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id');
      $initial_status = $this->read_db->get_where('status', $condition_array);

      if ($initial_status->num_rows() > 0) {
        $status_id = $initial_status->row()->status_id;
      }
    }

    return $status_id;
  }

  function center_start_date($center_id)
  {
    return $this->read_db->get_where('office', array('office_id' => $center_id))->row()->office_start_date;
  }

  function create_missing_page_access_permission()
  {
    // Get all menu items
    $menus = $this->read_db->get('menu');
    $page_access_permissions = $this->read_db->get_where('permission', array('permission_type' => 1));
    $permission_labels = $this->read_db->get('permission_label');

    $count_of_menus = $menus->num_rows();
    $count_of_page_access_permissions = $page_access_permissions->num_rows();
    $count_of_permission_labels = $permission_labels->num_rows();

    //$this->grants_model->mandatory_fields('permission');

    $this->write_db->trans_start();

    // Only create a permission if count of menus are more that the permissions available
    //if(($count_of_page_access_permissions * $count_of_permission_labels) < ($count_of_menus * $count_of_permission_labels) ){
    foreach ($menus->result_array() as $menu_item) {

      // Only create a missing permission for a given menu item and permission label
      foreach ($permission_labels->result_array() as $permission_label) {
        if ($this->read_db->get_where(
          'permission',
          array(
            'fk_permission_label_id' => $permission_label['permission_label_id'],
            'permission_type' => 1, 'fk_menu_id' => $menu_item['menu_id']
          )
        )->num_rows() == 0) {

          $permission_data['permission_name'] = ucfirst($permission_label['permission_label_name']) . ' ' . str_replace('_', ' ', $menu_item['menu_name']);
          $permission_data['permission_description'] = ucfirst($permission_label['permission_label_name']) . ' ' . str_replace('_', ' ', $menu_item['menu_name']) . ' permission';
          $permission_data['permission_is_active'] = 1;
          $permission_data['fk_permission_label_id'] = $permission_label['permission_label_id'];
          $permission_data['permission_type'] = 1; // Page Access
          $permission_data['permission_field'] = 0; // Always 0 for Page Access
          $permission_data['fk_menu_id'] = $menu_item['menu_id']; //str_replace('_',' ',$menu_item['menu_id']);

          $permission_data_to_insert = $this->merge_with_history_fields('permission', $permission_data, false);


          $this->write_db->insert('permission', $permission_data_to_insert);
        }
      }
      //}
    }

    $this->write_db->trans_complete();

    if ($this->write_db->trans_status() == false) {
      $message = "Error occurred when mass creating system page access permissions";
      show_error($message, 500, 'An Error As Encountered');
      return false;
    } else {
      return true;
    }
  }



  //Not yet used 

  function update_status()
  {
    // Get status of current id
    $action_labels = $this->grants->action_labels($this->controller, hash_id($this->id, 'decode'));

    $data['fk_status_id'] = $action_labels['next_approval_status'];
    $this->write_db->where(array('request_id' => hash_id($this->id, 'decode')));
    $this->write_db->update('request', $data);
  }


  function merge_with_history_fields(String $approve_item_name, array $array_to_merge, bool $add_name_to_array = true, $is_a_new_record=true)
  {

    $data = [];

    if($is_a_new_record){

      $data[$approve_item_name . '_track_number'] = $this->generate_item_track_number_and_name($approve_item_name)[$approve_item_name . '_track_number'];
      $data[$approve_item_name . '_created_by'] = $this->session->user_id ? $this->session->user_id : 1;
      $data[$approve_item_name . '_created_date'] = date('Y-m-d');
      $data['fk_approval_id'] = $this->insert_approval_record($approve_item_name);
      $data['fk_status_id'] = $this->initial_item_status($approve_item_name);

    }else{
      $data[$approve_item_name.'_last_modified_date'] = date('Y-m-d h:i:s');

      $data[$approve_item_name . '_last_modified_by'] = $this->session->user_id ? $this->session->user_id : 1;
    }

    // $data[$approve_item_name . '_track_number'] = $this->generate_item_track_number_and_name($approve_item_name)[$approve_item_name . '_track_number'];
    // $data[$approve_item_name . '_created_by'] = $this->session->user_id ? $this->session->user_id : 1;
    // $data[$approve_item_name . '_last_modified_by'] = $this->session->user_id ? $this->session->user_id : 1;
    // $data[$approve_item_name . '_created_date'] = date('Y-m-d');
    // //$data[$approve_item_name.'_last_modified_date'] = date('Y-m-d h:i:s');
    // $data['fk_approval_id'] = $this->insert_approval_record($approve_item_name);
    // $data['fk_status_id'] = $this->initial_item_status($approve_item_name);

    if ($add_name_to_array) {
      $data[$approve_item_name . '_name'] = $this->generate_item_track_number_and_name($approve_item_name)[$approve_item_name . '_name'];
    }

    return array_merge($array_to_merge, $data);
  }

  // function is_table_related_to_office($table_name){
  //     $is_table_related_to_office = false;

  //     $table_fields = $this->get_all_table_fields($table_name);

  //     if(in_array('fk_office_id',$table_fields)){
  //       $is_table_related_to_office = true;
  //     }

  //     return $is_table_related_to_office;
  // }


  /**
   * Placed here since it has a global use - To be put in API later
   */

  function office_has_active_month_projects($office_id, $reporting_month)
  {

    $start_date_of_reporting_month = date('Y-m-01', strtotime($reporting_month));
    $end_date_of_reporting_month = date('Y-m-t', strtotime($reporting_month));

    $query_condition = "(project_end_date >= '" . $start_date_of_reporting_month . "' OR  project_allocation_extended_end_date >= '" . $start_date_of_reporting_month . "')";

    $this->read_db->where($query_condition);
    $this->read_db->join('project', 'project.project_id=project_allocation.fk_project_id');

    $result = $this->read_db->select('project_allocation_id')->get_where(
      'project_allocation',
      array('fk_office_id' => $office_id)
    )->num_rows();

    $office_has_active_month_projects = $result > 0 ? true : false;

    return $office_has_active_month_projects;
  }

  function office_has_multiple_projects($office_id)
  {

    $result = $this->read_db->select('project_allocation_id')->get_where(
      'project_allocation',
      array('fk_office_id' => $office_id)
    )->num_rows();

    $office_has_projects = $result > 1 ? true : false;

    return $office_has_projects;
  }

  function office_funders($office_id)
  {

    $this->read_db->join('project', 'project.project_id=project_allocation.fk_project_id');
    $this->read_db->join('funder', 'funder.funder_id=project.fk_funder_id');

    $this->read_db->select(array('DISTINCT(funder_id)'));
    $this->read_db->select(array('funder_name'));
    $result = $this->read_db->get_where(
      'project_allocation',
      array('fk_office_id' => $office_id)
    )->result_array();


    return $result;
  }

  function office_has_multiple_bank_accounts($office_id)
  {
    $result = $this->read_db->get_where('office_bank', array('fk_office_id' => $office_id))->num_rows();

    $office_has_multiple_bank_accounts = $result > 1 ? true : false;

    return $office_has_multiple_bank_accounts;
  }

  function office_bank_accounts($office_id, $office_bank_id = 0)
  {
    $this->read_db->select(array('office_bank_id', 'office_bank_account_number', 'bank_name', 'office_bank_name'));
    $this->read_db->join('bank', 'bank.bank_id=office_bank.fk_bank_id');

    if ($office_bank_id > 0) {
      $this->read_db->where(array('office_bank_id' => $office_bank_id));
    }

    $this->read_db->where(array('fk_office_id' => $office_id));
    $result = $this->read_db->get('office_bank')->result_array();

    return $result;
  }

  function get_type_name_by_id($type, $type_id = '', $field = '')
  {
    $field = $field == '' ? $type . '_name' : $field;

    if ($this->read_db->get_where($type, array($type . '_id' => $type_id))->num_rows() > 0) {
      return $this->read_db->get_where($type, array($type . '_id' => $type_id))->row()->$field;
    } else {
      return "";
    }
  }

  function get_type_record_by_id($type, $type_id)
  {

    if ($this->read_db->get_where($type, array($type . '_id' => $type_id))->num_rows() > 0) {
      return $this->read_db->get_where($type, array($type . '_id' => $type_id))->row_array();
    } else {
      return array();
    }
  }

  function get_type_records_by_foreign_key_id($type, $foreign_type, $foreign_key_id)
  {

    if ($this->read_db->get_where($type, array('fk_' . $foreign_type . '_id' => $foreign_key_id))->num_rows() > 0) {
      return $this->read_db->get_where($type, array('fk_' . $foreign_type . '_id' => $foreign_key_id))->result_array();
    } else {
      return array();
    }
  }

  function get_type_record_by_foreign_key_id($type, $foreign_type, $foreign_key_id)
  {

    if ($this->read_db->get_where($type, array('fk_' . $foreign_type . '_id' => $foreign_key_id))->num_rows() > 0) {
      return $this->read_db->get_where($type, array('fk_' . $foreign_type . '_id' => $foreign_key_id))->row_array();
    } else {
      return array();
    }
  }

  function overwrite_field_value_on_post(array $post_array, String $insert_table, String $field_to_overwrite, int $original_value_to_check, int $overwrite_value, array $checking_condition)
  {
    $param_to_overwrite_value = $post_array['header'][$field_to_overwrite];

    if (count($checking_condition) > 0) {
      $this->read_db->where($checking_condition);
    }

    $count_records = $this->read_db->get($insert_table)->num_rows();

    if ($param_to_overwrite_value == $original_value_to_check && $count_records > 0) {
      $post_array['header'][$field_to_overwrite] = $overwrite_value;
    }

    return $post_array;
  }

  function not_exists_sub_query($lookup_table, $association_table, $string_condition = '')
  {
    $this->read_db->where('NOT EXISTS (SELECT * FROM ' . $association_table . ' WHERE ' . $association_table . '.fk_' . $lookup_table . '_id=' . $lookup_table . '.' . $lookup_table . '_id ' . $string_condition . ')', '', FALSE);
  }


  function get_unused_lookup_values(&$lookup_values, $lookup_table, $association_table, $not_exist_string_condition = '')
  {

    $this->read_db->where('NOT EXISTS (SELECT * FROM ' . $association_table . ' WHERE ' . $association_table . '.fk_' . $lookup_table . '_id=' . $lookup_table . '.' . $lookup_table . '_id ' . $not_exist_string_condition . ')', '', FALSE);

    if ($this->config->item('drop_transacting_offices')) {
      $this->read_db->where(array('office_is_readonly' => 0));
    }

    if ($lookup_table == 'office' && !$this->session->system_admin) {
      $hierarchy_offices = array_column($this->session->hierarchy_offices, 'office_id');
      $this->read_db->where_in('office_id', $hierarchy_offices);
    }

    $this->read_db->select(array($lookup_table . '_id', $lookup_table . '_name'));
    $lookup_values[$lookup_table] = $this->read_db->get($lookup_table)->result_array();
  }


  function event_tracker()
  {
    // $event = $this->input->post();

    // $event_name = $this->action;
    // $approve_item_id = 1;//$this->read_db->get_where('approve_item',array('approve_item_name'=>strtolower($this->controller)))->row()->approve_item_id;
    // $event_action = $this->action;
    // $event_json_string = "{}";
    // $user_id = $this->session->user_id;

    // $header['event_track_number'] = $this->generate_item_track_number_and_name('event')['event_track_number'];
    // $header['event_name'] = $event_name;
    // $header['fk_approve_item_id'] = $approve_item_id;
    // $header['event_action'] = 'list';
    // $header['event_json_string'] = $event_json_string;
    // $header['event_record_id'] = 0;
    // $header['fk_user_id'] = $user_id;

    // $header['event_created_by'] = $user_id;
    // $header['event_created_date'] = date('Y-m-d');
    // $header['event_last_modified_by'] = $user_id;

    // $header['fk_approval_id'] = 0;//$this->insert_approval_record('event');
    // $header['fk_status_id'] = 0;//$this->initial_item_status('event');
    // //print_r($header);
    // $this->write_db->insert('event',$header);
  }
  
  function create_table_approvers_columns($table_name){
    if (!$this->write_db->field_exists($table_name.'_approvers', $table_name))
    {
      $this->load->dbforge();

      // Define the column details
      $fields = array(
        $table_name.'_approvers' => array(
              'type' => 'JSON',
              'null' => TRUE,
          ),
      );

      // Add the column to the 'voucher' table
      $this->dbforge->add_column($table_name, $fields);
    }
  }
  
}
