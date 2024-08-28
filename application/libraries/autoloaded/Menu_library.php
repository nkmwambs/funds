<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/***
 * File: (Codeigniterapp)/libraries/Controllerlist.php
 *
 * A simple library to list all your controllers with their methods.
 * This library will return an array with controllers and methods
 *
 * The library will scan the "controller" directory and (in case of) one (1) subdirectory level deep
 * for controllers
 *
 */

class Menu_library {

    /**
     * Codeigniter reference
     */
    private $CI;
    private $EXT;

    /**
     * Array that will hold the controller names and methods
     */
    private $aControllers;

    // Construct
    function __construct() {
        // Get Codeigniter instance
        $this->CI =& get_instance();
        $this->CI->EXT = ".php";

        $this->CI->load->model('autoloaded/menu_model');

    }

    /**
     * getMenuItems
     * 
     * Read all controller classes that have the static method get_menu_list and add it as member of an array
     * 
     * @return Array
     */
    function getMenuItems():Array{

      $controllers = $this->CI->grants_model->get_all_tables();//$this->getControllers();

      $tables_not_required_in_menu = $this->CI->config->item('tables_not_required_in_menu');
      $controllers = array_diff($controllers,$tables_not_required_in_menu);      

      $menu_items = array();

      foreach($controllers as $controller){

        //if (in_array('get_menu_list',$methods) ) {
            $menu_items[ucfirst($controller)] = [];
        //}
      }
        //print_r($menu_items);
        return $menu_items;
    }

    function set_menu_sessions(){
      $menus = $this->getMenuItems();//$this->CI->grants_model->get_all_tables();
      
      $new_menu_items = $this->CI->menu_model->new_menu_items();

      // log_message('error',json_encode($new_menu_items));

      if(!empty($new_menu_items)){
        $this->CI->menu_model->upsert_menu($menus);
      }

      $sizeOfMenuItemsByController = count($menus);
      $sizeOfMenuItemsByDatabase = $this->CI->menu_model->get_count_of_menu_items();

      if($sizeOfMenuItemsByController !== $sizeOfMenuItemsByDatabase){
          $this->CI->session->unset_userdata('user_menu');
          $this->CI->session->unset_userdata('user_priority_menu');
          $this->CI->session->unset_userdata('user_more_menu');

          // Check if menu are there or insert
          //$this->CI->menu_model->upsert_menu($menus);
      }

      // Create a menu session
      if(!$this->CI->session->user_menu){

          // Check if logged user has any preferred menu order, if not create it
          $user_menu_items =  $this->CI->menu_model->upsert_user_menu();

          $full_user_menu =  elevate_array_element_to_key($user_menu_items,'menu_derivative_controller');

          $user_menu_by_priority_groups = elevate_assoc_array_element_to_key($user_menu_items,'menu_user_order_priority_item');
          
          $user_priority_menu = elevate_array_element_to_key($user_menu_by_priority_groups[1],'menu_derivative_controller');

          $user_more_menu = elevate_array_element_to_key($user_menu_by_priority_groups[0],'menu_derivative_controller');


          $this->CI->session->set_userdata('user_menu',$full_user_menu);

          // Build user priority and more menu based on user read label permission of the logged role
          if(!$this->CI->session->system_admin){

              $user_priority_menu_based_on_permissions = array();
              $user_more_menu_based_on_permissions = array();

              // Filter user priority menu based on the read label permission of the logged role
              foreach($user_priority_menu as $menu => $options ){
                //array_key_exists('read',$this->session->role_permissions[ucfirst($menu)[1]]);
                // if($this->CI->user_model->check_role_has_permissions($menu,'read')){
                //   $user_priority_menu_based_on_permissions[$menu] = $options;
                // } 

                if(isset($this->CI->session->role_permissions[ucfirst($menu)]) && array_key_exists('read',$this->CI->session->role_permissions[ucfirst($menu)][1])){
                  $user_priority_menu_based_on_permissions[$menu] = $options;
                } 
              }

              // Filter the user more menu based on the read label permission of the logged role
              foreach($user_more_menu as $menu => $options ){
                if(isset($this->CI->session->role_permissions[ucfirst($menu)])  && array_key_exists('read',$this->CI->session->role_permissions[ucfirst($menu)][1])){
                  $user_more_menu_based_on_permissions[$menu] = $options;
                } 
              }
              
              // Check if the filter priority menu has less than the config set max_priority_menu_items,
              // If yes, take the first max_priority_menu_items items from user more menu anf push them to the 
              // user priority menu

              if(
                  count($user_priority_menu_based_on_permissions) < $this->CI->config->item('max_priority_menu_items') - 1 && 
                  count($user_more_menu_based_on_permissions) > 0  
              ){

                  // Makes multiple arrays of user_more_menu_based_on_permissions of size of config max_priority_menu_items
                  // Take the first max_priority_menu_items elements to $user_priority_menu_based_on_permissions

                  $chunked_user_more = array_chunk($user_more_menu_based_on_permissions,
                  $this->CI->config->item('max_priority_menu_items') - 1,true);

                  foreach($chunked_user_more[0] as $menu => $options){
                    $user_priority_menu_based_on_permissions[$menu] = $options;
                  }

                  // Remove the first max_priority_menu_items elements from $user_more_menu_based_on_permissions 
                  // and assign the remaning to $user_more_menu_based_on_permissions 
                  $user_more_menu_based_on_permissions = array_slice($user_more_menu_based_on_permissions,
                  $this->CI->config->item('max_priority_menu_items') - 1);
                  
                  
              } 

              $this->CI->session->set_userdata('user_priority_menu',$user_priority_menu_based_on_permissions);

              $this->CI->session->set_userdata('user_more_menu',$user_more_menu_based_on_permissions);
            
            }else{
                
              $this->CI->session->set_userdata('user_priority_menu',$user_priority_menu);

              $this->CI->session->set_userdata('user_more_menu',$user_more_menu);
            
            }      

      }

    }
    
    
    function navigation(){
      
      $permission = $this->CI->session->role_permissions;

      $this->set_menu_sessions();

      $menus = $this->CI->session->user_priority_menu;

      $nav = "";

      $lib = "";
      $menu_icon = '';

      $all_active_menus_obj = $this->CI->db->get_where('menu',
          array('menu_is_active'=>1));
      
      $menu_derivative_controllers = array_column($all_active_menus_obj->result_array(),'menu_derivative_controller');    

      $unique_identifier = $this->CI->unique_identifier_model->get_account_system_unique_identifier($this->CI->session->user_account_system_id);

      foreach ($menus as $menu => $items) {
          if($this->CI->user_model->check_role_has_permissions($menu,'read') && 
            in_array(ucfirst($menu),$menu_derivative_controllers)){ 

              if(
                !$this->CI->session->data_privacy_consented && 
                $menu != ucfirst($this->CI->session->default_launch_page) && 
                !empty($unique_identifier))
              {
                continue;
              }
              
              $nav .= '
              <li class="menu_tab '.strtolower($menu).'">
                <a href="'.base_url().strtolower($menu).'/list">
                      <i class="'.$menu_icon.'"></i>
                      <span>'.get_phrase(strtolower($items['menu_name'])).'</span>
                  </a>
              </li>
              ';
          }  
          
      }

      if(count($this->CI->session->user_more_menu) > 0 ){
        $nav .= '
          <li class="">
              <a href="'.base_url().'Menu/list">
                  <span class="fa fa-plus"></span>
              </a>
             
          </li>
        ';
      }

      return $nav;
    }

    function access_add_form_or_list_from_main_menu($menu_name){
      
      $check_if_allowed_to_access_add_from_from_main_menu = $this->CI->grants->access_add_form_from_main_menu($menu_name);

      $add_form = $this->CI->grants->check_if_table_has_detail_listing($menu_name) ? "multi_form_add" : "single_form_add";

      $access_add_form_or_list_from_main_menu = "list";

      if($check_if_allowed_to_access_add_from_from_main_menu){
        $access_add_form_or_list_from_main_menu = $add_form;
      }

      return $access_add_form_or_list_from_main_menu;
    }

    function create_breadcrumb(){

        $breadcrumb_list = $this->CI->session->breadcrumb_list;

        if( $this->CI->uri->segment(2,'list') == 'list' ){
          $this->CI->session->set_userdata('breadcrumb_list',array($this->CI->uri->segment(1,'')));
        }

        if(array_pop($breadcrumb_list) !== $this->CI->uri->segment(1,'') ){
          $breadcrumb_list = $this->CI->session->breadcrumb_list;
          $new = array($this->CI->uri->segment(1,'') );

          if(!in_array($this->CI->uri->segment(1,''),$breadcrumb_list)){
            $breadcrumb_list = array_merge($breadcrumb_list,$new);
          }


          $this->CI->session->set_userdata('breadcrumb_list', $breadcrumb_list );
        }

    }

    function edit_output(){
      show_error('This feature is under development',500);
    }
    
}
