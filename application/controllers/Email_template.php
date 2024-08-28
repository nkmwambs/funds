<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Email_template extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('email_template_library');
  }

  function index(){}

  function result($id = 0){

    if($this->action == 'edit'){

      $this->read_db->where(array('email_template_id' => hash_id($this->id,'decode')));
      $email_template = $this->read_db->get('email_template')->row();

      $result['email_tags'] = $this->email_template_model->get_email_template_placeholder_fields($email_template->fk_approve_item_id);

      return array_merge(parent::result($id), $result);
    }

    return parent::result($id);
  }


  function get_email_tags(){

    // Post from an ajax call
    $post = $this->input->post();

    // Get the approve item id from the ajax post array
    $approve_item_id = $post['approve_item_id'];

    // List all the fields that can be used as email template placeholders
    $fields = $this->email_template_model->get_email_template_placeholder_fields($approve_item_id);

    // Format the fields to placeholders/tags
    $tags = [];

    foreach($fields as $field){
      $tags[] = '<b>{'.$field.'}</b>';
    }
    
    $placeholder = implode(',',$tags);

    echo $placeholder;
  }

  static function get_menu_list(){}

}