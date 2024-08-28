<?php


if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Role_user_library extends Grants
{

  private $CI;

  function __construct(){
    parent::__construct();
    $this->CI =& get_instance();
  }

  function index(){}

  function change_field_type(){
    $change_field_type = array();
    
    $this->CI->read_db->select(array('user_id','CONCAT(user_firstname," ",user_lastname," [",user_email,"]") as user_name'));
    if(!$this->CI->session->system_admin){
      $this->CI->read_db->where(array('fk_account_system_id'=>$this->CI->session->user_account_system_id)); 
    }
    
    //$this->CI->read_db->where(array('fk_role_id'=>'NOT EXISTS (SELECT * FROM role_user WHERE role_user.fk_user_id=user.user_id AND fk_role_id=' . hash_id($this->CI->id, 'decode') . ')', '', FALSE));
    $users_list = $this->CI->read_db->get('user')->result_array();

    
    $user_ids = array_column($users_list,'user_id');
    $user_names = array_column($users_list,'user_name');

    $users = array_combine($user_ids, $user_names);

    $change_field_type['user_name']['field_type']='select';
    $change_field_type['user_name']['options'] = $users;


    return $change_field_type;
  }

} 