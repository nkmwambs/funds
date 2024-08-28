<?php

class Access_output{
    
  protected $db = null;

  function __construct(){
      $this->CI =& get_instance();
      $this->db = $this->CI->db;
  }

    function index(){

    }

    function check_role_department_strictness($role_id){
      $role_is_department_strict = $this->CI->db->get_where('role',
      array('role_id'=>$role_id))->row()->role_is_department_strict;
  
      return $role_is_department_strict;
      
    } 

      /**
   * user_department
   * 
   * Check if a logged user has a department association if not return empty array
   * A user can be associated to multiple departments
   * 
   * @param int $user_id - Queried user
   * @return Array - Array of department ids associated to the use
   */

   function user_department(int $user_id):Array{

    $this->CI->db->select(array('fk_department_id'));
    $user_department = $this->CI->db->get_where('department_user',
    array('fk_user_id'=>$user_id));

    $department_ids = array();

    if($user_department->num_rows()>0){
      $department_ids = array_column($user_department->result_array(),'fk_department_id');
    }

    return $department_ids;
 }

    /**
    * get_user_context_definition
    *
    * Retrieves the user's context definition record with 4 fields 
    * i.e. context_definition_id, context_definition_name, context_definition_level and context_definition_is_active
    * 
    * A user can only have 1 context definition relationship as defined in their user record fk_context_definition_id
    *
    * @param int $user_id
    * @return Array - Array of context definition
    */
    function get_user_context_definition(int $user_id):Array{
      $user_context_definition = [];
  
      $this->CI->db->select(array('context_definition_id','context_definition_name',
      'context_definition_level','context_definition_is_active'));
      
      $this->CI->db->join('user','user.fk_context_definition_id=context_definition.context_definition_id');
      $user_context_definition_obj = $this->CI->db->get_where('context_definition',
      array('user_id'=>$user_id));
  
      if($user_context_definition_obj->num_rows() > 0){
        $user_context_definition =  $user_context_definition_obj->row_array();
      }
      
      return $user_context_definition;
    }

    function output(...$args){
      $called_method_name = array_shift($args);
      
      $called_method_arguments = implode(',',$args);

      return $this->{$called_method_name}($called_method_arguments);
    }
}
