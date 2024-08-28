<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*This autoloads all the classes in third_party folder subdirectories e.g. Output
  The third_party houses the reusable API or code systemwise
 */
require_once APPPATH . "third_party" . DIRECTORY_SEPARATOR . "Api" . DIRECTORY_SEPARATOR . "autoload.php";

define('VALIDATION_ERROR', 'VALIDATION_ERROR');
define('VALIDATION_SUCCESS', 'VALIDATION_SUCCESS');

class MY_Controller extends CI_Controller
{

  private $list_result;

  /**
   * @var String $current_library - this holds value of active feature library
   */
  public $current_library;

  /**
   * @var String $current_model - this holds value of active feature model
   */
  public $current_model;

  /**
   * @var String $controller - this holds value of active feature controller
   */
  public $controller;

  /**
   * @var String $action - this holds value of 2nd URI segment 
   * (i.e. type of a page that will open e.g. view, single_form_add, edit_form etc) 
   */

  public $action;

  /**
   * @var String $id - this holds the primary key value of a record. 
   * This is 3rd URI segment (mostly used on edit, view and delete a record)
   * This $id value is always null if not the actions above 
   */

  public $id = null;
  /**
   * @var String $master_table - this holds a value of a parent table 
   * (mostly is in view action pages) 
   */
  public $master_table = null;

  /**
   * @var Bool $has_permission - this holds value of true or false to check if the user has permissions 
   * to access the a particular page that you are trying to access. Action like edit point to apage
   */
  public $has_permission = false;
  public $sub_action = null;
  public $capped_controller;

  //public $widget = null;

  public $max_status_id = null;

  public $write_db = null;
  public $read_db = null;

  public $primary_key_field_name = null;

  public $default_language_code = '';

  function __construct()
  {

    parent::__construct();

    $this->write_db = $this->load->database('write_db', true); // Master DB on Port 3306
    $this->read_db = $this->grants_model->read_database_connection();

    $segment = $this->uri->segment(1, 'dashboard');
    $action = $this->uri->segment(2, 'list');

    $this->current_library = strtolower($segment . '_library');
    $this->current_model = strtolower($segment . '_model');
    $this->controller = strtolower($segment);
    $this->capped_controller = ucfirst($segment);

    $this->action = $action;
    $this->sub_action = $this->uri->segment(4, null);;

    //Unset master_table session is exists
    // $this->session->has_userdata('master_table') ? $this->session->unset_userdata('master_table') : "";

    // $this->load->add_package_path(APPPATH . 'third_party' . DIRECTORY_SEPARATOR . 'Packages' . DIRECTORY_SEPARATOR . 'Core');
    // $this->load->add_package_path(APPPATH . 'third_party' . DIRECTORY_SEPARATOR . 'Packages' . DIRECTORY_SEPARATOR . $this->session->package);

    $this->load->model('autoloaded/menu_model');
    $this->load->library('autoloaded/menu_library');
    $this->load->library('fields/Element');
    $this->load->library('fields/Input_element');
    $this->load->helper('elements');

    //Temporary, should be done on login/ auto load/ or check if already loaded
    $this->load->model('office_model');
    $this->load->model('approval_model');
    $this->load->model('general_model');
    $this->load->model('message_model');
    $this->load->model('attachment_model');
    $this->load->model('status_model');
    $this->load->model('general_model');
    $this->load->model('searchbuilder');

    check_and_load_account_system_model_exists('As_' . $this->controller . '_model', 'Grants', 'model');

    $this->load->library($this->current_library);
    $this->load->model($this->current_model);

    //Set the Language Context
    $this->load->model('language_model');
    $this->load->library('Language_library');
    $this->load->library('Grants_S3_lib');

    // Load package library, helper and model

    if(!is_cli()){
      $this->session_based_constructor_set();
    }
    
   
    // #1292 - Incorrect date value: '0000-00-00' [duplicate] 
    // Read article:
    // https://stackoverflow.com/questions/37292628/1292-incorrect-date-value-0000-00-00
    // https://stackoverflow.com/questions/36374335/error-in-mysql-when-setting-default-value-for-date-or-datetime/36374690#36374690
    
    $this->read_db->query("SET sql_mode = ''");
    $this->read_db->query("SET sql_mode = ''");
    $this->read_db->query("SET sql_mode = ''");

  }

  function session_based_constructor_set(){
    
    if ($this->session->has_userdata('package')) {
      $this->load->helper($this->session->package . '_package');
      $this->load->library($this->session->package . '_package_library');
      $this->load->model($this->session->package . '_package_model');
    } else {
      ////$this->session->sess_destroy();
      redirect(base_url() . 'login/logout');
    }
  

  //Setting the session of master table. For view action always the master table= the controler u are in.
  //Will alwasy be null for other actions

  
    if ($this->action == 'view') {

      $this->session->has_userdata('master_table') ? $this->session->unset_userdata('master_table') : "";
      $this->session->set_userdata('master_table', ucfirst($this->controller));
      $this->id = hash_id($this->uri->segment(3, 0), 'decode'); // Not sure what's this line does

      // $this->session->has_userdata('master_table') ? $this->session->unset_userdata('master_table') : "";
      // $this->session->set_userdata('master_table', ucfirst($this->controller));
      // $this->id = hash_id($this->uri->segment(3, 0), 'decode'); // Not sure what's this line does
    } elseif ($this->action == 'single_form_add' && $this->uri->total_segments() == 4) {
      $this->session->has_userdata('master_table') ? $this->session->unset_userdata('master_table') : "";
      $this->session->set_userdata('master_table', $this->uri->segment(4, 0));
      $this->id = hash_id($this->uri->segment(3, 0), 'decode'); // Not sure what's this line does
    } elseif ($this->action == 'list') {
      $this->session->set_userdata('master_table', null);
      $this->id = hash_id($this->uri->segment(3, 0), 'decode'); // Use by filters only
    }

    $this->id = $this->uri->segment(3, null);

    $this->max_status_id = $this->general_model->get_max_approval_status_id($this->controller);

    

    $this->language_library->set_language($this->session->user_locale);

    if (!$this->session->user_id) {
      redirect(base_url() . 'login', 'refresh');
    }

    $this->config->set_item('language', $this->session->user_account_system);
    $this->lang->load($this->session->user_locale);

     // User location cookie
    // setcookie('last_visited_page_' . hash_id($this->session->user_id, 'encode'), str_replace('index.php/', '', current_url()), time() + (86400 * 30), "/"); // 86400 = 1 day

  }

  /**
   * result() 
   * This method returns the contents that will be consumed in the view file
   * @param String $id: this is the primary key of selected record mostly in view and edit
   * @return Mixed 
   * @todo {seperate the method that uses ajax to post from result methods}
   */

  function result($id = "")
  {

    $action = $this->action . '_output';

    $lib = strtolower($this->current_library);

    /*Makes a decision if we are posting to db table when the $this->input->post() 
    return true otherwise load the page to add records*/
    if ($this->input->post()) {
      /*If $id> 0 mean has paased by code and not URI. The $id can be null if is not passed in URI segment
      e.g.In case of delete when the URI is not modified to have $id it will be passed as argument 
      from a clickable link
      The elseif takes effect when the $id is passed URI

      The if and elseif condtion handles edit and add. The else part of condition handles the new record post
      */
      if ($id > 0) {
        $this->$lib->$action($id);
      } elseif ($this->id !== null) {
        $this->$lib->$action($this->id);
      } elseif ($this->action == 'list' || $this->action == 'view') {
        // Just to test if the third party API are working for list output
        // This is the way to go for all outputs. Move all outputs to the Output API  
        // Applies when using page view: See View Widget
        return Output_base::load($this->action);
      } else {
        $this->$lib->$action();
      }


      exit;
    }

    $render_model_result = 'render_' . $this->action . '_page_data';

    if ($this->action == 'list' || $this->action == 'view' || $this->action == 'multi_row_add') {

      if (!$this->render_data_from_model($render_model_result)) {
        // Render from default API
        $this->list_result = \Output_base::load($this->action);
      }
    } else {
      if (!$this->render_data_from_model($render_model_result)) {
        // Render from default API
        $this->list_result = $this->$lib->$action();
      }
    }

    return $this->list_result;
  }

  function render_data_from_model($render_model_result)
  {
    // Render custom data for list and view pages if render_list_page_data or 
    // render_view_page_data method are defined
    // in the specific feature models

    $is_model_set = false;
    if (
      check_and_load_account_system_model_exists('As_' . $this->controller . '_model') &&
      method_exists($this->{'As_' . $this->controller . '_model'}, $render_model_result)
    ) {
      // Render results from account system model
      $this->list_result = $this->{'As_' . $this->controller . '_model'}->{$render_model_result}();

      $is_model_set = true;
    } elseif (
      // Render results from feature model
      method_exists($this->{$this->controller . '_model'}, $render_model_result)
    ) {
      $this->list_result = $this->{$this->controller . '_model'}->{$render_model_result}();
      $is_model_set = true;
    }

    return $is_model_set;
  }
  /**
   * page_name() 
   * This method returns the name of the view to be loaded
   *@return String
   */
  function page_name(): String
  {
    //return the page name if the user has permissions otherwise error page of user not allowed access display

    if ((hash_id($this->id, 'decode') == null && $this->action == 'view') || !$this->has_permission) {
      return 'error';
    } else {
      return $this->action;
    }
  }
  /**
   * page_title() 
   * This method returns the title of the  page being loaded
   *@return String
   */
  function page_title(): String
  {
    $make_plural = $this->action == 'list' ? "s" : "";
    return get_phrase($this->action . '_' . $this->controller . $make_plural);
  }
  /**
   * views_dir() 
   * This method returns the folder path of the controller/feature file
   *@return String
   */
  function views_dir(): String
  {
    $view_path = strtolower($this->controller);
    $page_name = $this->page_name();

    if (file_exists(VIEWPATH . $view_path . '/' . $this->session->user_account_system . '/' . $page_name . '.php') && $this->has_permission) {
      $view_path .= '/' . $this->session->user_account_system;
    } elseif (!file_exists(VIEWPATH . $view_path . '/' . $page_name . '.php') || !$this->has_permission) {
      $view_path =  'templates';
    }

    return $view_path;
  }
  /**
   * load_template() 
   * This method returns object [this is a view object]
   * @param Array $page_data
   *@return Mixed
   */
  private function load_template(array $page_data)
  {
    return $this->load->view('general/index', $page_data);
  }
  /**
   * crud_view() 
   * This method returns an array. It packages the page name, page title , view folder and result of the page
   * @param String $id
   *@return Void
   */
  function crud_views(String $id = ''): Void
  {

    $result = $this->result($id);

    if(is_array($result)){
      
    }
    
    // Page name, Page title and views_dir can be overrode in a controller
    $page_data['page_name'] = $this->page_name();
    $page_data['page_title'] = $this->page_title();
    $page_data['views_dir'] = $this->views_dir();
    $page_data['result'] = $result;

    // Can be overrode in a specific controller
    //$this->load->add_package_path(APPPATH.'workplan', FALSE);
    $this->load_template($page_data);
  }
  /**
   * list() 
   * This method is an entry method for list action page. It loads user permission of the list page and assigns $has_permission
   *@return Void
   */
  function list(): Void
  {
    // log_message('error', json_encode($this->controller));
    $this->has_permission = $this->user_model->check_role_has_permissions(ucfirst($this->controller), 'read');
    $this->crud_views();
    //echo json_encode($this->result());
  }

  /**
   * view() 
   * This method is an entry method for view action page. It loads user permission of the view page and assigns $has_permission
   *@return Void
   */
  function view()
  {
    $this->has_permission = $this->user_model->check_role_has_permissions(ucfirst($this->controller), 'read');
    $this->crud_views();
  }
  /**
   * edit() 
   * This method is an entry method for edit action page. It loads user permission of 
   * the edit page and assigns $has_permission
   * @param String   
   *@return Void
   */
  function edit($id)
  {
    $this->has_permission = $this->user_model->check_role_has_permissions(ucfirst($this->controller), 'update');
    $this->crud_views($id);
  }
  /**
   * multi_form_add() 
   * This method is an entry method for multi_form_add action page. It loads user 
   * permission of the multi_form_add page and assigns $has_permission
   * @todo {observe the sitautaion when $id argument is used otherwise pass no argument in the function}
   *@return Void
   */
  function multi_form_add($id = null): Void
  {
    $this->has_permission = $this->user_model->check_role_has_permissions(ucfirst($this->controller), 'create');
    $this->id = $id;
    $this->grants_model->insert_status_if_missing(strtolower($this->controller));
    $this->crud_views();
  }
  /**
   * single_form_add() 
   * This method is an entry method for single_form_add action page. It loads user 
   * permission of the single_form_add page and assigns $has_permission
   * @todo {observe the sitautaion when $id argument is used otherwise pass no argument in the function}
   *@return Void
   */
  function single_form_add($id = null): Void
  {
    $this->has_permission = $this->user_model->check_role_has_permissions(ucfirst($this->controller), 'create');
    $this->id = $id;
    $this->grants_model->insert_status_if_missing(strtolower($this->controller));
    $this->crud_views();
  }

  function multi_row_add($id = null): Void
  {
    $this->has_permission = $this->user_model->check_role_has_permissions(ucfirst($this->controller), 'create');
    $this->id = $id;
    $this->crud_views();
  }

  /**
   * delete() 
   * This method is an entry method for delete action. It loads user 
   * permission of the delete action and assigns $has_permission
   * @param String
   *@return String
   */
  function delete($id = null)
  {
    $this->has_permission = $this->user_model->check_role_has_permissions(ucfirst($this->controller), 'delete');
    echo "Record deleted successful";
  }
  /**
   * detail_row() 
   * This method is triggered by insert_row_butron on multform add page to create the rows of details section of the page 

   *@return VOid
   */
  function detail_row()
  {
    $fields = $this->input->post('fields');

    $lib = $this->grants->dependant_table($this->controller) . '_library';

    $this->load->library($lib);

    echo json_encode($this->$lib->detail_row_fields($fields));
  }

  function list_ajax()
  {
    echo json_encode($this->grants->list_ajax_output());
  }

  function update_config()
  {

    //$config_name, $config_file = "config", $config_array_name = 'config'
    //Make this code for editing config items in the grants config

    $key = $this->input->post('key');
    $phrase = $this->input->post('phrase');
    $config_name = $this->input->post('config_name');
    $config_file = $this->input->post('config_file');
    $config_array_name = $this->input->post('config_array_name');

    //print_r($this->input->post());
    //echo $config_name;
    //exit();

    // log_message('error', APPPATH . $config_name . '/' . $config_file . '.php');

    $reading = fopen(APPPATH . $config_name . '/' . $config_file . '.php', 'r');
    $writing = fopen(APPPATH . $config_name .  '/myfile.tmp', 'w');

    $replaced = false;

    while (!feof($reading)) {
      $line = fgets($reading);

      if (stristr($line, $key)) {
        $line = "$" . $config_array_name . "['" . $key . "'] = '" . $phrase . "';\n";
        $replaced = true;
      }

      fputs($writing, $line);
    }
    fclose($reading);
    fclose($writing);
    // might as well not overwrite the file if we didn't replace anything
    if ($replaced) {
      rename(APPPATH . $config_name . '/myfile.tmp', APPPATH . $config_name  .'/'. $config_file . '.php');
    } else {
      unlink(APPPATH . $config_name . '/myfile.tmp');
    }

    //return $phrase;
  }

  function status_change($change_type = 'approve')
  {

    $status_id = $this->general_model->get_status_id($this->controller, hash_id($this->id, 'decode'));
    $is_max_approval_status_id = $this->general_model->is_max_approval_status_id($this->controller, $status_id);
    //echo $is_max_approval_status_id;exit;
    // Prevent update of status when max status id is reached
    if (!$is_max_approval_status_id) {

      // Get status of current id - to be taken to grants_model
      $master_action_labels = $this->grants->action_labels($this->controller, hash_id($this->id, 'decode'));


      $data['fk_status_id'] = $master_action_labels['next_approval_status'];

      // Insert Approval History
      $this->grants_model->create_change_history($data);


      //Update master record

      if ($change_type == 'decline') {
        $data['fk_status_id'] = $master_action_labels['next_decline_status'];
      }

      $this->write_db->where(array(strtolower($this->controller) . '_id' => hash_id($this->id, 'decode')));
      $this->write_db->update(strtolower($this->controller), $data);

      //$is_max_approval_status_id = $this->general_model->is_max_approval_status_id($this->controller,$data['fk_status_id']);

      $item_approval_id = $this->read_db->get_where(
        $this->controller,
        array($this->controller . '_id' => hash_id($this->id, 'decode'))
      )->row()->fk_approval_id;


      $this->write_db->where(array('approval_id' => $item_approval_id));
      $this->write_db->update('approval', array('fk_status_id' => $data['fk_status_id']));

    }


    redirect(base_url() . $this->controller . '/view/' . $this->id, 'refresh');
  }

  function approve()
  {
    $this->status_change('approve');
  }

  function decline()
  {
    $this->status_change('decline');
  }

  function post_chat()
  {

    $this->grants->table_setup($this->controller);

    $post = $this->input->post();
    $approve_item_id = $this->read_db->get_where('approve_item', array('approve_item_name' => $this->controller))->row()->approve_item_id;

    $message['message_track_number'] = "";
    $message['message_name'] = "";
    $message['fk_approve_item_id'] = $approve_item_id;
    $message['message_record_key'] = hash_id($post['item_id'], 'decode');
    $message['message_created_by'] = $this->session->user_id;
    $message['message_last_modified_by'] =  $this->session->user_id;
    $message['message_created_date'] = date('Y-m-d');
    $message['message_is_thread_open'] = 1;

    // Check if a message a thread is open for this item before posting
    $open_thread = $this->read_db->get_where(
      'message',
      array(
        'fk_approve_item_id' => $approve_item_id,
        'message_record_key' => 1, 'message_is_thread_open' => hash_id($post['item_id'], 'decode')
      )
    );

    $message_id = 0;

    if ($open_thread->num_rows() == 0) {
      $this->write_db->insert('message', $message);
      $message_id = $this->write_db->insert_id();
    } else {
      $message_id = $open_thread->row()->message_id;
    }

    $message_detail['message_detail_track_number'] = '';
    $message_detail['message_detail_name'] = '';
    $message_detail['fk_user_id'] = $this->session->user_id;
    $message_detail['message_detail_content'] = $post['message_detail_content'];
    $message_detail['fk_message_id'] = $message_id;
    $message_detail['message_detail_created_date'] = date('Y-m-d h:i:s');
    $message_detail['message_detail_created_by'] = $this->session->user_id;
    $message_detail['message_detail_last_modified_by'] = $this->session->user_id;
    $message_detail['message_detail_is_reply'] = 0;
    $message_detail['message_detail_replied_message_key'] = 0;

    $this->write_db->insert('message_detail', $message_detail);

    $returned_response = [
      'message' => $post['message_detail_content'],
      'message_date' => date('Y-m-d h:i:s'),
      'creator' => $this->session->name,
    ];
    echo json_encode($returned_response);
    //echo $post['item_id'];
  }

  function create_uploads_temp()
  {

    $string = $this->session->user_id . $this->controller . date('Y-m-d');

    $hash = md5($string);

    $storeFolder = "uploads/temps/" . $this->controller . "/" . $hash;

    if (
      is_array($this->grants->upload_files($storeFolder)) &&
      count($this->grants->upload_files($storeFolder)) > 0
    ) {
      $info = ['temp_id' => $hash];

      $files_array = array_merge($this->grants->upload_files($storeFolder), $info);

      if (!$this->session->has_userdata('upload_session')) {
        $this->session->set_userdata('upload_session', $hash);
      }
      echo json_encode($files_array);
    } else {
      echo 0;
    }
  }

  function upload_documents_for_any_feature()
  {

    $result = [];

    //feature_name/account_system_name/fcp_number/year/month/feature_id/image.png
    //vouher/tanzania/TZ303/2022/06/feature-id-869686/image.png

    $feature_name = $this->controller;

    $account_system_name = $this->session->user_account_system;

    //Get Office Code
    $fcp_ids = array_column($this->session->hierarchy_offices, 'office_id');

    $office_code = $this->office_model->get_office_code($fcp_ids);

    if ($office_code['message'] == 1) {
      $fcp_number = $office_code['fcps'];
    } else {

      //when oversees more than 1 project [This is to be implemented]

    }

    $year = date("Y");

    $month = date('M');

    //Get the item url and get the item ID
    $get_current_url =  $_SERVER['HTTP_REFERER'];

    $get_items_id = explode('/', $get_current_url)[6];

    $record_id = $feature_name . '-ID-' . hash_id($get_items_id, 'decode');

    //$storeFolder=$feature_name.'/'.$account_system_name.'/'.$fcp_number.'/'.$year.'/'.$month.'/'.$record_id;
    //Country/FCP/ITEM_ID/2022/Jun

   // $storeFolder = upload_url($feature_name, '', [$account_system_name, $fcp_number,$record_id, $year, $month]);

    $storeFolder = upload_url($feature_name, '', [$account_system_name, $fcp_number,$record_id.'-'.$year.'-'. $month]);

    //echo $storeFolder;
    if (is_array($this->attachment_model->upload_files($storeFolder)) && count($this->attachment_model->upload_files($storeFolder)) > 0) {
      $result = $this->attachment_model->upload_files($storeFolder);
    }

    if (!empty($result)) {
      echo json_encode($result);
    } else {
      echo 0;
    }
  }
  function delete_uploaded_document($uploaded_image_id)
  {


    $delete_message = $this->attachment_model->delete_uploaded_document($uploaded_image_id);

    echo $delete_message;
  }

  public function get_current_status_of_item()
  {

    //Get initial status
    $initial_status = $this->grants_model->initial_item_status($this->controller);

    //Get the item url and get the item ID
    $get_current_url =  $_SERVER['HTTP_REFERER'];

    $get_item_id = explode('/', $get_current_url)[6];

    $item_id = hash_id($get_item_id, 'decode');

    $this->read_db->select(['fk_status_id']);
    $this->read_db->where([$this->controller.'_id' => $item_id]);
    $current_status = $this->read_db->get($this->controller)->row_array()['fk_status_id'];

    if($initial_status==$current_status){
      echo 1;
    }else{
      echo -1;
    }

  }
  function get_uploaded_S3_documents($attachement_id = '')
  {

    $uploaded_docs = [];
    //Get the item_id from URL if attachement_id='' otherwise use the passed argument
    if ($attachement_id == '') {

      $get_current_url =  $_SERVER['HTTP_REFERER'];
      // log_message('error', json_encode($get_current_url));
      $get_current_url_arr = explode('/', $get_current_url);
      

      $get_items_id = isset($get_current_url_arr[6]) ? $get_current_url_arr[6] : 0;

      $approve_item_name = isset($get_current_url_arr[4]) ? $get_current_url_arr[4] : ucwords($this->controller);
      
      $attachment_id_picked_from_url_id = hash_id($get_items_id, 'decode');

      if ($attachment_id_picked_from_url_id!=null) {


        $uploaded_docs = $this->attachment_model->get_uploaded_S3_documents($attachment_id_picked_from_url_id, $approve_item_name);
      }

    } else {
      
      $uploaded_docs = $this->attachment_model->get_uploaded_S3_documents($attachement_id, ucwords($this->controller));
    }
    //echo json_encode($uploaded_docs);
    
    //Loop and repopulate the array with attachements from the table to display

    $reconstruct_attachments_array = [];

    if ($uploaded_docs > 0) {
      foreach ($uploaded_docs as $uploaded_doc) {

        $attachment_url = $uploaded_doc['attachment_url'];

        $objectKey = $attachment_url . '/' . $uploaded_doc['attachment_name'];

        $url = $this->config->item('upload_files_to_s3') ? $this->grants_s3_lib->s3_preassigned_url($objectKey) : $this->attachment_library->get_local_filesystem_attachment_url($objectKey);

        $uploaded_doc['attachment_url'] = $url;

        $reconstruct_attachments_array[] = $uploaded_doc;
      }
    }

    echo json_encode($reconstruct_attachments_array);

    
  }

  function custom_ajax_call()
  {
    // This implementation has 2 predefined keys i.e. ajax_method and return_as_json and must be passed in the 
    // ajax post call from pages for this to work
    // ajax_method carries the method name of the implementing account system model while return_as_json is a bool
    // indicating if the returned result is in json or string format

    $post = $this->input->post();
    $model_name = 'As_' . $this->controller . '_model';
    $ajax_method = $post['ajax_method'];
    $return_as_json = !isset($post['return_as_json']) || $post['return_as_json'] == 'true' ? true : false;
    $package_name = !isset($post['package_name']) ? "Grants" : $post['package_name'];
    $return = [];

    if (check_and_load_account_system_model_exists($model_name, $package_name)) {
      if (is_valid_array_from_contract_method($model_name, $ajax_method)) {
        $return = $this->{$model_name}->{$ajax_method}();
      } elseif (
        method_exists($this->{$this->controller . '_model'}, $ajax_method) &&
        is_valid_array_from_contract_method($this->{$this->controller . '_model'}, $ajax_method)
      ) {
        $return = $this->{$this->controller . '_model'}->{$ajax_method}();
      } else {
        $return = "Missing method `" . $ajax_method . "` in the account system or feature model for `" . $this->controller . "`";
      }
    } elseif (
      method_exists($this->{$this->controller . '_model'}, $ajax_method) &&
      is_valid_array_from_contract_method($this->{$this->controller . '_model'}, $ajax_method)
    ) {
      $return = $this->{$this->controller}->{$ajax_method}();
    } else {
      $return = "Missing account system or feature model for `" . $this->controller . "`";
    }

    if ($return_as_json || is_array($return)) {
      echo json_encode($return);
    } else {
      echo $return;
    }
  }

  function event_tracker()
  {
    $this->grants_model->event_tracker();
  }

  // function show_list()
  // {

  //   $results = Output_base::load('list');
  //   $draw = intval($this->input->post('draw'));

  //   $records = [];
  //   $columns = $results['keys'];

  //   $cnt = 0;
  //   foreach ($results['table_body'] as $row) {
  //     $cols = 0;
  //     $primary_key = 0;
  //     foreach ($columns as $column) {
  //       if($column == strtolower($this->controller).'_id'){
  //         $primary_key = $row[$column];
  //         continue;
  //       }
  //       // if ($cols == 0) {
  //         // $primary_key = $row[$column];
  //         // $list_action_button_data['primary_key'] = $primary_key;
  //         // $records[$cnt][$cols] = $this->load->view('templates/list_action_button', $list_action_button_data, true);
  //       // } else {

  //         if (strpos($column, 'track_number') == true) {
  //           $track_number = '';
  //             // method_exists($this->{strtolower($this->controller) . '_model'}, 'hide_edit_action_base_on_column_value') &&
  //           if(
  //             $this->session->system_admin ||
  //             (
  //               $this->{$this->controller.'_model'}->show_list_edit_action($row) &&
  //               $this->user_model->check_role_has_permissions(strtolower($this->controller),'update')
  //             )
  //           ){
  //             $track_number .= '<a href="'.base_url().strtolower($this->controller).'/edit/'.hash_id($primary_key, 'encode').'"><i class = "fa fa-pencil"></i></a>';
  //           }
            
  //           $track_number .= ' <a href="' . base_url() . $this->controller . '/view/' . hash_id($primary_key) . '">' . $row[$column] . '</a>';
  //           $row[$column] = $track_number;

  //         } elseif (strpos($column, '_is_') == true) {
  //           $row[$column] =  $row[$column] == 1 ? "Yes" : "No";
  //         } elseif ($results['fields_meta_data'][$column] == 'int' || $results['fields_meta_data'][$column] == 'decimal') {
  //           // Defense code to ignore non numeric values when lookup values method changes value type from numeric to non numeric
  //           $row[$column] = is_numeric($row[$column]) ? number_format($row[$column], 2) : $row[$column];
  //         } else {
  //           $row[$column] = ucfirst(str_replace("_", " ", $row[$column]));
  //         }

  //         $records[$cnt][$cols] = $row[$column];
  //       // }

  //       $cols++;
  //     }
  //     $cnt++;
  //   }

  //   $response = array(
  //     'draw' => $draw,
  //     'recordsTotal' => $results['total_records'],
  //     'recordsFiltered' => $results['total_records'],
  //     'data' => $records
  //   );

  //   echo json_encode($response);
  // }

  function show_list()
  {

    $results = Output_base::load('list', 'data');
    $draw = intval($this->input->post('draw'));
    // log_message('error', json_encode($results));
    $records = [];
    $columns = $results['keys'];

    $status_data = $this->general_model->action_button_data($this->controller);
    extract($status_data);

    $cnt = 0;
    foreach ($results['table_body'] as $row) {
      $cols = 0;
      $primary_key = 0;
      foreach ($columns as $column) {
        // log_message('error', json_encode($column));
        if($column == strtolower($this->controller).'_id'){
          $primary_key = $row[$column];
          $action_buttons = '<div class = "btn btn-info btn-icon"><i class = "fa fa-pencil"></i>'.get_phrase('edit').'</div>';
          // $action = approval_action_button($this->controller, $item_status, $primary_key, $voucher_status, $item_initial_item_status_id, $item_max_approval_status_ids, false, true, '',$voucher_missing_details);
          // log_message('error', json_encode($action_buttons));
          if(
            $this->session->system_admin ||
            (
              $this->{$this->controller.'_model'}->show_list_edit_action($row) &&
              $this->user_model->check_role_has_permissions(strtolower($this->controller),'update')
            )
          ){
            $action_buttons = '<a class = "btn btn-success btn-icon" href="'.base_url().strtolower($this->controller).'/edit/'.hash_id($primary_key, 'encode').'"><i class = "fa fa-pencil"></i>'.get_phrase('edit').'</a>';
          }
          $row[$column] = $action_buttons;
        }
          
          if (strpos($column, 'track_number') == true) {
            // method_exists($this->{strtolower($this->controller) . '_model'}, 'hide_edit_action_base_on_column_value') &&
            $track_number = ' <a href="' . base_url() . $this->controller . '/view/' . hash_id($primary_key) . '">' . $row[$column] . '</a>';
            $row[$column] = $track_number;

          } elseif (strpos($column, '_is_') == true) {
            $row[$column] =  $row[$column] == 1 ? "Yes" : "No";
          } elseif ($results['fields_meta_data'][$column] == 'int' || $results['fields_meta_data'][$column] == 'decimal') {
            // Defense code to ignore non numeric values when lookup values method changes value type from numeric to non numeric
            $row[$column] = is_numeric($row[$column]) ? number_format($row[$column], 2) : $row[$column];
          } else {
            $row[$column] = ucfirst(str_replace("_", " ", $row[$column]));
          }
  
          $records[$cnt][$cols] = $row[$column];
        // }

        $cols++;
      }
      $cnt++;
    }

    $response = array(
      'draw' => $draw,
      'recordsTotal' => $results['total_records'],
      'recordsFiltered' => $results['total_records'],
      'data' => $records
    );

    echo json_encode($response);
  }

  public function update_item_status($item)
  {

    // Check if <table_name>_approvers column exists if not create it
    $this->create_table_approvers_columns($item);

    $buttons = '<div class="badge badge-danger">' . get_phrase('approval_process_failed') . '</div>';

    $post = $this->input->post();

    $this->create_change_history([$item . '_id' => $post['item_id'], 'fk_status_id' => $post['next_status']], [$item . '_id' => $post['item_id'], 'fk_status_id' => $post['current_status']], $item);

    if ($post['next_status'] > 0) {
      
      $action_button_data = $this->general_model->action_button_data($item);
      
      $post['next_status'] = $this->status_model->exempt_status($item,$post['item_id'],$post['next_status']);

      // Once the update is successful, complete post update events
      if (method_exists($this->{$item . '_model'}, 'post_approval_action_event')) {
        $this->{$item . '_model'}->post_approval_action_event([
          'item' => $item,
          'post' => $post
        ]);
      }

  
      $buttons = approval_action_button($item, $action_button_data['item_status'], $post['item_id'], $post['next_status'], $action_button_data['item_initial_item_status_id'], $action_button_data['item_max_approval_status_ids']);
      

      $data['fk_status_id'] = $post['next_status'];
      $data[$item.'_last_modified_by'] = $this->session->user_id;
      $data[$item.'_last_modified_date'] = date('Y-m-d h:i:s');
      $data[$item.'_approvers'] = $this->update_approvers_list($this->session->user_id, $item, $post['item_id'], $post['current_status'], $post['next_status']);

      $this->write_db->where(array($item . '_id' => $post['item_id']));
      $this->write_db->update($item, $data);

    }

    echo $buttons;
  }

  function update_approvers_list($user_id, $table_name, $item_id, $current_status, $next_status){

    $this->read_db->select(array('CONCAT(user_firstname, " ", user_lastname) as fullname', 'role_id', 'role_name'));
    $this->read_db->join('role','role.role_id=user.fk_role_id');
    $this->read_db->where(array('user_id' => $user_id));
    $user = $this->read_db->get('user')->row();

    $user_fullname = $user->fullname;
    $user_role_id = $user->role_id;
    $user_role_name = $user->role_name;
    // log_message('error', json_encode([$current_status, $next_status]));
    $this->read_db->select(array('status_id','status_name','status_approval_sequence','status_approval_direction','fk_approval_flow_id as approval_flow_id'));
    $this->read_db->where_in('status_id', [$current_status, $next_status]);
    $status_obj = $this->read_db->get('status')->result_array();

    $status = [];
    $approval_flow_id = 0;
    foreach($status_obj as $step){
      $approval_flow_id = $step['approval_flow_id'];
      if($step['status_id'] == $current_status){
        $status['current'] = $step;
      }else{
        $status['next'] = $step;
      }
    }

    $current_status_name = $status['current']['status_name'];
    $current_status_sequence = $status['current']['status_approval_sequence'];
    $current_approval_direction = $status['current']['status_approval_direction'];

    $reinstatement_status_id = 0;

    if($current_approval_direction == 0){
      $this->read_db->select(array('status_id','status_name','status_approval_sequence','status_approval_direction','fk_approval_flow_id as approval_flow_id'));
      $this->read_db->where(['fk_approval_flow_id' => $approval_flow_id]);
      $this->read_db->where(['status_approval_sequence' => $current_status_sequence, 'status_approval_direction' => 1]);
      $alt_status = $this->read_db->get('status')->row_array();
      
      $reinstatement_status_id = $current_status;
      $current_status = $alt_status['status_id'];
      $current_status_name = $alt_status['status_name'];
      $current_status_sequence = $alt_status['status_approval_sequence'];
      $current_approval_direction = $alt_status['status_approval_direction'];
    }
    // log_message('error', json_encode($status));
    $next_status_name = $status['next']['status_name'];
    $next_status_sequence = $status['next']['status_approval_sequence'];
    $next_approval_direction = $status['next']['status_approval_direction'];

    $this->read_db->where(array($table_name.'_id' => $item_id));
    $existing_approvers = $this->read_db->get($table_name)->row()->{$table_name.'_approvers'};

    $approvers = json_decode($existing_approvers);

    $new_approver = [
      'user_id' => $user_id, 
      'fullname' => $user_fullname, 
      'user_role_id' => $user_role_id,
      'user_role_name' => $user_role_name,
      'approval_date' => date('Y-m-d h:i:s'), 
      'status_id' => $next_approval_direction == 1 ? $current_status : $next_status,
      'status_name' => $next_approval_direction == 1 ?  $current_status_name  : $next_status_name, 
      'status_sequence' => $next_approval_direction == 1 ? $current_status_sequence : $next_status_sequence, 
      'approval_direction' => $next_approval_direction == 1 ? $current_approval_direction : $next_approval_direction, 
      'reinstatement_status_id' => $reinstatement_status_id
    ];

  if($existing_approvers == "" || $existing_approvers == "[]" || $existing_approvers == NULL){
    $approvers = [$new_approver];
  }else{
    array_push($approvers, $new_approver);
  }



    $approvers = json_encode($approvers);

    return $approvers;
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

  function create_change_history($new_data, $old_data, $table)
  {

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

  // function get_phrase_wrapper(){
  //   $post = $this->input->post();

  //   $phrase_key = $post['phrase_key'];
  //   $phrase_translation = $post['phrase_translation'];

  //   echo get_phrase($phrase_key, $phrase_translation);
  // }

  function modal($page_name , ...$params)
	{

    $page_data = [];

    for($i = 0; $i < count($params); $i++){
      if($i == 0 || $i%2 == 0){ // Even numbered params are keys
        $page_data[$params[$i]] = '';
      }

      if($i%2 != 0){ // Odd numbered params are values
        $page_data[$params[$i - 1]] = $params[$i];
      }
    }

    if(count($params)%2 == 0){
      $this->load->view($this->controller.'/modals/'.$page_name.'.php' ,$page_data);
    }else{
      $this->load->view('general/access_denied_error.php/' ,$page_data); // Has a loader error
    }

		
		echo '<script src="'.base_url().'assets/js/neon-custom-ajax.js"></script>';
	}

}
