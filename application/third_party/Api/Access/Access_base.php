<?php 

/**
* The Access_base class is part of the system APIs that controls the access permission for users.
*
* @author Nicodemus Karisa
* @package Grants Management System
* @copyright Compassion International Kenya
* @license https://compassion-africa.org/lisences.html
*
*/

defined('BASEPATH') OR exit('No direct script access allowed');
/**
* The Access_base class is part of the system APIs that controls the access permission for users.
*
* @author Nicodemus Karisa
* @package Grants Management System
* @copyright Compassion International Kenya
* @license https://compassion-africa.org/lisences.html
*
*/
class Access_base{

    /**
     * @var Object $CI - Holds CodeIgniter singleton object
     */
    private $CI = null;  

    /**
     * __construct
     * 
     * This is the class construct
     */
    function __construct(){
        $this->CI =& get_instance();
    }

    /**
    * control_column_visibility
    * 
    * This method checks if a field/column has permission to with a create label
    * @param String $table - Selected table
    * @param Array $visible_columns : Array of visible/ selected columns/ fields
    * @param String $permission_label : Can be create, update or read
    * 
    * @return Array
    */
    function control_column_visibility(String $table, Array $visible_columns, String $permission_label = 'create'): Array{
        $controlled_visible_columns = array();

        foreach($visible_columns as $column){
        if($this->check_role_has_field_permission($table,$permission_label,$column)){
            $controlled_visible_columns[] = $column;
        }  
        }

        return $controlled_visible_columns;
    }

    /**
     * check_role_has_field_permission
     * 
     * This method is a wrapper of the user_model check_role_has_field_permission method.
     * It helps to check if the logged user has permission to acccess a controlled field
     * Any field that has been flagged in the permission table is referred to as a controlled field
     * 
     * @param String $table - Selected table
     * @param String $permission_label - Can be 1 or 2
     * @param String $column - Selected column
     * @return Boolean
     */
    function check_role_has_field_permission(String $table, String $permission_label,String $column):bool{
        return $this->CI->user_model->check_role_has_field_permission(
        $table, $permission_label, $column
        );
    }

}