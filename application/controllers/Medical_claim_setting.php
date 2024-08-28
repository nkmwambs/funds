<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 *	@author Livingstone Onduso <londuso@ke.ci.org>
 *	@date		20th Aug, 2021
 *  @package grants <Finance management system for NGOs
 *  @method void get_menu_list()empty methods
 *  @method void index() empty method
 *  @method return type not applicable __construct() main method
 *  @method void edit_medical_claim_setting_record() helps edit a claim record
 *  @method array get_medical_setting_to_edit() returns a record to be edited
 *  @method check_if_record_exists() checks if the record exists
 *  @method array columns() returns columns for rendering on list page
 *  @method void save_claim_settings() helps store claim settings
 *  @method array get_medical_settings() retunrs medical claim settings
 *  @method int count_medical_claim_setting_country() return count of settings
 *  @method void show_list() helps render the list of participants
 *	@see  https://techsysnow.com
 */

class Medical_claim_setting extends MY_Controller
{

   /**
   * __construct(): This is the primary or main method that initializes variables to be used other methods
   * @author Livingstone Onduso
   * @access public
   * @return not applicable
   */

  public function __construct()
  {
    parent::__construct();

    $this->load->library('medical_claim_setting_library');
    
  }

   /**
   * index(): empty method <no body>
   * @author Livingstone Onduso
   * @access public
   * @return void
   */

  public function index():void
  {
    //empty
  }

   /**
   * get_menu_list(): This is an referenced method from my_controller that writes the menus
   * @author Livingstone Onduso
   * @access public
   * @return void
   */


 public static function get_menu_list():void
 {
  //empty
 }

  /**
   * result(): This method return an array of columns to be used on list page
   * @author Livingstone Onduso
   * @access public
   * @return array
   * @param int The ID passed from the url segment
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

      $result['show_add_button'] = true;

    } elseif ($this->action=='single_form_add') {

      $result['admin_settings']=$this->medical_claim_setting_model->admin_settings();
      
    } elseif ($this->action=='edit') {
      
      $result['admin_settings']=$this->medical_claim_setting_model->admin_settings();

      $result['medical_setting_for_edit']=$this->medical_claim_setting_model->get_medical_setting_for_edit();

      $result['account_systems']=$this->medical_claim_setting_model->retrieve_account_systems();
    }else {
      $result = parent::result($id);
    }
    

    return $result;
  }

   /**
   * edit_medical_claim_setting_record(): This method used to enable a user edit a claim setting
   * @author Livingstone Onduso
   * @access public
   * @return void
   */

  public function edit_medical_claim_setting_record():void
  {

    $post = $this->input->post();

    echo $this->medical_claim_setting_model->edit_medical_claim_setting_record($post);
  }

   /**
   * get_medical_setting_to_edit(): This method is used to return a record to edit
   * @author Livingstone Onduso
   * @access public
   * @return array
   */

  public function get_medical_setting_to_edit():array
  {

    return $this->medical_claim_setting_model->get_medical_setting_to_edit();

  }

  /**
   * check_if_record_exists(): This method is used to check and verify if a record to exists
   * @author Livingstone Onduso
   * @access public
   * @return void
   */

  public function check_if_record_exists($medical_claim_setting_type):void
  {

    $check_record_exists=$this->medical_claim_setting_model->check_if_record_exists($medical_claim_setting_type);

    echo json_encode(['value'=>$check_record_exists]);

  }
 
 /**
   * columns(): This method is used to return an array of coloumns
   * @author Livingstone Onduso
   * @access public
   * @return array
   */

  public function columns():array
  {
    return [
      'medical_claim_setting_id',
      'medical_claim_setting_track_number',
      'medical_claim_admin_setting_name',
      'medical_claim_setting_value',
    ];

  }
  
   /**
   * save_claim_settings(): This method help the user to save the settings
   * @author Livingstone Onduso
   * @access public
   * @return void
   */

  public function save_claim_settings():void
  {

    $data=[];

    $message_flag=0;

    $data['fk_medical_claim_admin_setting_id']=$this->input->post('medical_claim_setting_type');

    $data['medical_claim_setting_name']=$this->input->post('medical_claim_name');

    $data['medical_claim_setting_value']=$this->input->post('medical_claim_setting_value');

    $data['fk_account_system_id']=$this->session->user_account_system_id;

    $data['medical_claim_setting_track_number'] = $this->grants_model->generate_item_track_number_and_name('medical_claim_setting')['medical_claim_setting_track_number'];

    $data['fk_approval_id'] =  $this->grants_model->insert_approval_record('medical_claim_setting');

    $data['fk_status_id'] = $this->grants_model->initial_item_status('medical_claim_setting');

    $data['medical_claim_setting_created_by'] = $this->session->user_id;

    $data['medical_claim_setting_created_date'] = date('Y-m-d');

    $data['medical_claim_setting_last_modified_by'] = $this->session->user_id;

    $last_inserted_id=$this->write_db->insert('medical_claim_setting',$data);

    if ($last_inserted_id>0) {
      $message_flag=1;
    }

    echo json_encode(['result'=>$message_flag]);
  }
  
  /**
   * get_medical_settings(): This method returns setting records to displayed as a list
   * @author Livingstone Onduso
   * @access public
   * @return array
   */

  public function get_medical_settings():array
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
    
    if(!empty($order)){
      $col = $order[0]['column'];
      $dir = $order[0]['dir'];
    }
          
    if ( $col == '') {

      $this->read_db->order_by('medical_claim_setting_id DESC');

    } else {

      $this->read_db->order_by($columns[$col],$dir);

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

            $this->read_db->like($column,$value,'both');

          } else {

            $this->read_db->or_like($column,$value,'both');
        }

          $column_key++;

      }

      $this->read_db->group_end();

    }
    
    if (!$this->session->system_admin) {

     $this->read_db->where(['medical_claim_setting.fk_account_system_id'=>$this->session->user_account_system_id]);

    }

    $this->read_db->select($columns);

    $this->read_db->join('account_system','account_system.account_system_id=medical_claim_setting.fk_account_system_id');

    $this->read_db->join('medical_claim_admin_setting','medical_claim_admin_setting.medical_claim_admin_setting_id=medical_claim_setting.fk_medical_claim_admin_setting_id');

    $result_obj = $this->read_db->get('medical_claim_setting');

    $results = [];

    if($result_obj->num_rows() > 0){
      $results = $result_obj->result_array();
    }

    return $results;
  }

   /**
   * count_medical_claim_setting_country(): This method returns the count of claim settings for a country
   * @author Livingstone Onduso
   * @access public
   * @return int
   */

  public function count_medical_claim_setting_country():int
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

            $this->read_db->like($column,$value,'both');

          } else {

            $this->read_db->or_like($column,$value,'both');

        }

          $column_key++;
      }

      $this->read_db->group_end();
    }


    $this->read_db->from('medical_claim_setting');

    return $this->read_db->count_all_results();
  }

 /**
   * show_list(): This method helps in rendering of records in the page
   * @author Livingstone Onduso
   * @access public
   * @return void
   */

  public function show_list():void
  {
  
    $draw =intval($this->input->post('draw'));

    $medical_settings = $this->get_medical_settings();

    $count_medical_settings_country = $this->count_medical_claim_setting_country();

    $result = [];

    $cnt = 0;
    foreach ($medical_settings as $medical_setting) {

      $medical_setting_id= array_shift($medical_setting);

      $claim_setting_track_number = $medical_setting['medical_claim_setting_track_number'];

      $medical_setting['medical_claim_setting_track_number'] = '<a href="'.base_url().$this->controller.'/view/'.hash_id($medical_setting_id).'">'.$claim_setting_track_number.'</a>';
      
      $row = array_values($medical_setting);

      $result[$cnt] = $row;

      $cnt++;
    }

    $response = [
      'draw'=>$draw,
      'recordsTotal'=>$count_medical_settings_country,
      'recordsFiltered'=>$count_medical_settings_country,
      'data'=>$result
    ];
    
    echo json_encode($response);
  }

}