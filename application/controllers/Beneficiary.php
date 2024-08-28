<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 *  @package grants <Finance management system for NGOs>
 *	@author  Livingstone Onduso <londuso@ke.ci.org>
 *	@date		 20th Aug, 2021
 *  @method void get_menu_list() empty method
 *  @method void upload_large_csv_data_to_s3() uploads large csv document containing participants
 *  @method array result($id) retunrs results
 *  @method array columns() returns columns for rendering on list page
 *  @method array get_beneficiaries() get an array of participants
 *  @method int count_beneficiaries_for_country() returns the count of participants in the country
 *  @method void show_list() helps render the list of participants
 *	@see https://techsysnow.com
 */


class Beneficiary extends MY_Controller
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

    $this->load->library('beneficiary_library');
    

    
  }

  /**
   * get_menu_list(): This is an referenced method from my_controller that writes the menus
   * @author Livingstone Onduso
   * @access public
   * @return void
   */

  public static function get_menu_list():void
  {
    //Empty
  }

  /**
   * upload_csv_data_to_table(): This method allows use to upload participants using CSV formated document
   * @author Livingstone Onduso
   * @access public
   * @return void
   */
  public function upload_large_csv_data_to_s3():void
  {
    
    
    $this->load->library('aws_attachment_library');

    $csv_file = $_FILES['file']['name'];
    
    $country = $this->input->post('countries');

    if(empty($country)){
      $country=$this->session->user_account_system_id;
    }

    //Split to the extenson
    $ext_and_name=explode('.',$csv_file);

    //Sanitize the file name
    $sanitize_file_name=preg_replace('/[^A-Za-z0-9]/', '', $ext_and_name[0]);

    //Array for file parts
    $file_parts=[];

    $file_parts[0]=$sanitize_file_name;

    $file_parts[1]=$ext_and_name[1];

    echo $this->aws_attachment_library->s3_multi_part_upload($file_parts, $country);

    
   
    
  }


  // /**
  //  * upload_csv_data_to_table(): This method allows use to upload participants using CSV formated document
  //  * @author Livingstone Onduso
  //  * @access public
  //  * @return void
  //  */
  // public function upload_large_csv_data_to_s3():void
  // {
    
    
  //   $this->load->library('aws_attachment_library');

  //   $csv_file = $_FILES['file']['name'];

  //   $country = $this->input->post('countries');//Suscipicion for the error

  //   //Split to the extenson
  //   $ext_and_name=explode('.',$csv_file);

  //   //Sanitize the file name
  //   $sanitize_file_name=preg_replace('/[^A-Za-z0-9]/', '', $ext_and_name[0]);

  //   //Add back extension
  //   $sanitized_file_name_and_ext=$sanitize_file_name.'.'.$ext_and_name[1];

  //   echo $this->aws_attachment_library->s3_multi_part_upload($sanitized_file_name_and_ext, $country);
 
    
  //   // $file = $_FILES['file']['tmp_name'];

	// 	// $results =   $this->csvreader_library->read_CSV_file($file);

  //   // /*Modifiy the array from excel/csv to add account system id, created_date,
  //   // created_by, last_modified_date and last_modified_by*/

  //   // foreach ($results as $key => $result) {

  //   //   $result['fk_account_system_id']=$this->session->user_account_system_id;

  //   //   $result['beneficiary_created_date']=date('Y-m-d');

  //   //   $result['beneficiary_created_by']=$this->session->user_id;

  //   //   $result['beneficiary_last_modified_by']=$this->session->user_id;

  //   //   $date=null;

  //   //   if (trim($result['beneficiary_dob'])!='') {
        
  //   //     //Added this code to cater for '-' character in the DOB field
  //   //     if (strpos($result['beneficiary_dob'], '/') ){

  //   //       $explode_date=explode('/', $result['beneficiary_dob']);

  //   //     }elseif (strpos($result['beneficiary_dob'], '-')) {

  //   //       $explode_date=explode('-', $result['beneficiary_dob']);

  //   //     }
        
  //   //     $time = mktime(0, 0, 0, $explode_date[0], $explode_date[1], $explode_date[2]);
        
  //   //     $date=date('Y-m-d', $time);
       
  //   //   }
  //   //   $result['beneficiary_dob']=$date;

  //   //   $results[$key]=$result;
      
  //   // }
  //   // $msg = get_phrase('CSV_file_failed_to_upload_beficiary_records');

  //   // if (!empty($results)) {

  //   //   $this->read_db->trans_begin();

  //   //    //Delete the records
  //   //    $table=lcfirst($this->controller);

  //   //    $this->read_db->where("fk_account_system_id", $this->session->user_account_system_id);

  //   //    $this->read_db->delete($table);

  //   // //Insert the records
  //   // $this->read_db->insert_batch($table, $results);

  //   // if ($this->read_db->trans_status() === false) {

  //   //   $this->read_db->trans_rollback();

  //   // } else {

  //   //   $this->read_db->trans_commit();

  //   //   $msg = get_phrase('CSV_uploaded_successfully') . ". " . count($results) . " " . get_phrase("records_uploaded");
  //   // }

  //   // }
    
  //   // echo $msg;
    
  // }

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

    } else {
      $result = parent::result($id);
    }

    return $result;
  }

   /**
   * columns(): This method returns an array of columns to be used on list page
   * @author Livingstone Onduso
   * @access public
   * @return array The array of columns to be used on the listing page
   */

  public function columns():array
  {
    $columns= [
      'beneficiary_id',
      'beneficiary_name',
      'beneficiary_number',
      'beneficiary_gender',
      'beneficiary_dob',
      'account_system_name',
    ];

    return $columns;
  }

   /**
   * get_beneficiaries: This method return an array of participants that
   * will be listed to choose from when raisin a claim
   * @author Livingstone Onduso
   * @access public
   * @return array
   */

  public function get_beneficiaries():array
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

      $this->read_db->order_by('beneficiary_id DESC');

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

        foreach ($search_columns as $column){

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

     $this->read_db->where(['fk_account_system_id'=>$this->session->user_account_system_id]);

    }

    $this->read_db->select($columns);

    $this->read_db->join('account_system', 'account_system.account_system_id=beneficiary.fk_account_system_id');

    $result_obj = $this->read_db->get('beneficiary');
    
    $results = [];

    if ($result_obj->num_rows() > 0) {
      $results = $result_obj->result_array();
    }

    return $results;
  }

 /**
   * count_beneficiaries_for_country: This method returns an int of total count of beneficiaries in compassion country
   * @author Livingstone Onduso
   * @access public
   * @return int
   */

  public function count_beneficiaries_for_country(): int
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
    
    if (!$this->session->system_admin) {

      $this->read_db->where(['fk_account_system_id'=>$this->session->user_account_system_id]);

    }

    $this->read_db->join('account_system', 'account_system.account_system_id=beneficiary.fk_account_system_id');
    
    $this->read_db->from('beneficiary');

    return $this->read_db->count_all_results();
  }

   /**
   * show_list: This method returns an int of total count of beneficiaries in compassion country
   * @author Livingstone Onduso
   * @access public
   * @return int
   */

  public function show_list():void
  {
   
    $draw =intval($this->input->post('draw'));

    $beneficiaries = $this->get_beneficiaries();

    $count_beneficiaries_for_country = $this->count_beneficiaries_for_country();

    $result = [];

    $cnt = 0;
    foreach ($beneficiaries as $beneficiary) {

      array_shift($beneficiary);

      $row = array_values($beneficiary);

      $result[$cnt] = $row;

      $cnt++;
    }

    $response = [
      'draw'=>$draw,
      'recordsTotal'=>$count_beneficiaries_for_country,
      'recordsFiltered'=>$count_beneficiaries_for_country,
      'data'=>$result
    ];
    
    echo json_encode($response);
  }
}