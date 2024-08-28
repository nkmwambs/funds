<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Attachment_model extends MY_Model
{

  public $table = 'attachment';
  public $dependant_table = '';
  public $name_field = 'attachment_name';
  public $create_date_field = "attachment_created_date";
  public $created_by_field = "attachment_created_by";
  public $last_modified_date_field = "attachment_last_modified_date";
  public $last_modified_by_field = "attachment_last_modified_by";
  public $deleted_at_field = "attachment_deleted_at";

  function __construct()
  {
    parent::__construct();
    $this->load->database();

    $this->load->library('Aws_attachment_library');
  }

  function index()
  {
  }

  public function lookup_tables()
  {
    return array('approve_item');
  }

  public function detail_tables()
  {
  }

  public function detail_multi_form_add_visible_columns()
  {
  }

  /**
   * upload_files
   */

  function upload_files($storeFolder, $attachment_type_name = "")
  {

    $path_array = explode("/", $storeFolder);
    $item_id = $path_array[3];
    //voucher_ID_355019

    //return  $path_array;
    //Added by Livingstone Onduso on 11/03/2022 incase of any error in upload function [For medical uploads to work]
    if (!is_numeric($item_id)) {

      //Medical uploading piece
      if (strpos($item_id, '-')) {

        $explode_item_id = explode('-', $item_id);

        $storeFolder = str_replace($item_id, $explode_item_id[0], $storeFolder);

        $item_id = $explode_item_id[1];
      }
      //Other uploads e.g. voucher or budget except mfr bank statement uploads
      else {

        $last_item_in_array = end($path_array); //The item ID

        $explode_last_item = explode('-', $last_item_in_array);

        $item_id = $explode_last_item[2]; //item ID at position 2
      }
    }

    //End of added piece

    $approve_item_id = $this->read_db->get_where(
      'approve_item',
      array('approve_item_name' => $path_array[2])
    )->row()->approve_item_id;

    $additional_attachment_table_insert_data = [];
 
    $additional_attachment_table_insert_data['fk_approve_item_id'] = $approve_item_id;
    $additional_attachment_table_insert_data['attachment_primary_id'] = $item_id;
    $additional_attachment_table_insert_data['attachment_is_s3_upload'] = 1;
    $additional_attachment_table_insert_data['attachment_created_by'] = $this->session->user_id;
    $additional_attachment_table_insert_data['attachment_last_modified_by'] = $this->session->user_id;
    $additional_attachment_table_insert_data['attachment_created_date'] = date('Y-m-d');
    $additional_attachment_table_insert_data['attachment_track_number'] = $this->grants_model->generate_item_track_number_and_name('attachment')['attachment_track_number'];
    $additional_attachment_table_insert_data['fk_approval_id'] = $this->grants_model->insert_approval_record('attachment');
    $additional_attachment_table_insert_data['fk_status_id'] = $this->grants_model->initial_item_status('attachment');
    $additional_attachment_table_insert_data['fk_attachment_type_id'] = $this->get_attachment_type_id($attachment_type_name);

    $attachment_where_condition_array = [];

    $attachment_where_condition_array = array(
      'fk_approve_item_id' => $approve_item_id,
      'attachment_primary_id' => $item_id
    );

    $preassigned_urls =  $this->aws_attachment_library->upload_files($storeFolder, $additional_attachment_table_insert_data, $attachment_where_condition_array);

    return $preassigned_urls;
  }

  function get_attachment_type_id($attachment_type_name = "")
  {

    $this->read_db->select(array('attachment_type_id'));

    if($attachment_type_name != ""){
      $this->read_db->where(array('attachment_type_name' => $attachment_type_name));
    }else{
      // This code is to manage backward compatibility
      $this->read_db->where(array('approve_item_name' => strtolower($this->controller)));
    }
    
    $this->read_db->join('approve_item', 'approve_item.approve_item_id=attachment_type.fk_approve_item_id');
    $attachment_type_id = $this->read_db->get('attachment_type')->row()->attachment_type_id;

    return $attachment_type_id;
  }
  function get_uploaded_S3_documents($attachment_id, $approve_item_name)
  {

    $this->read_db->select(['attachment_id', 'attachment_name', 'attachment_url']);
    $this->read_db->where([
      'fk_account_system_id' => $this->session->user_account_system_id, 
      'attachment_primary_id' => $attachment_id,
      'approve_item_name' => $approve_item_name
    ]);
    $this->read_db->join('approve_item','approve_item.approve_item_id=attachment.fk_approve_item_id');
    $uploaded_docs = $this->read_db->get('attachment')->result_array();

    return $uploaded_docs;
  }

  function delete_uploaded_document($uploaded_image_id)
  {

    $this->write_db->trans_start();
    $this->write_db->where(['attachment_id' => $uploaded_image_id]);
    $this->write_db->delete('attachment');

    $this->write_db->trans_complete();

    if ($this->write_db->trans_status() == FALSE) {
      return 0;
    } else {
      return 1;
    }
  }
}
