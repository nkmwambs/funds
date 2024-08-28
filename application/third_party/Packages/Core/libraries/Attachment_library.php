<?php


if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Attachment_library extends Grants
{

  private $CI;

  function __construct(){
    parent::__construct();
    $this->CI =& get_instance();

    $this->CI->load->library('Grants_S3_lib');
  }

  function index(){}

  function attachment_record_with_s3_preassigned_url($approve_item_name,$record_primary_key, $file_name){
    $this->CI->read_db->where(array('approve_item_name'=>$approve_item_name,
    'attachment_primary_id'=>$record_primary_key,'attachment_name'=>$file_name));
    
    $this->CI->read_db->join('approve_item','approve_item.approve_item_id=attachment.fk_approve_item_id');
    $attachment_obj = $this->CI->read_db->get('attachment')->row();

    $attachment_url = $attachment_obj->attachment_url;
    $attachment_name = $attachment_obj->attachment_name;
    $objectKey = $attachment_url.'/'.$attachment_name;

    $s3_preassigned_url = $this->CI->config->item('upload_files_to_s3')?$this->CI->grants_s3_lib->s3_preassigned_url($objectKey):$this->get_local_filesystem_attachment_url($objectKey);

    return [
      'attachment_name'=>$attachment_name,
      'attachment_size'=>formatBytes($attachment_obj->attachment_size),
      'attachment_last_modified_date'=>$attachment_obj->attachment_last_modified_date,
      'attachment_file_type'=>$attachment_obj->attachment_file_type,
      's3_preassigned_url'=> $s3_preassigned_url
    ];
  }

  function get_local_filesystem_attachment_url($objectKey){
    return base_url().$objectKey;
  }

} 