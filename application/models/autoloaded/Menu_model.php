<?php if (!defined('BASEPATH')) exit('No direct script access allowed');



class Menu_model extends CI_Model
{

  function __construct()
  {
    parent::__construct();
    $this->load->database();
  }

  function detail_tables()
  {
    return array('menu_user_order');
  }

  function new_menu_items(){

    $condition_array = array('menu_is_active' => 1);

    if (!$this->session->system_admin) {
      $this->db->where($condition_array);
    }

    $registered_menus = $this->db->get('menu')->result_array();
    $menus = array_column($registered_menus,'menu_name');

    $formatted_menus = array_map(array($this, 'toLower'), $menus);

    $specs = $this->grants_model->get_all_tables();

    $diff = array_diff($specs, $formatted_menus);

    unset($diff[array_search('menu',$diff)]);

    // log_message('error', json_encode($diff));

    return $diff;
  }

  function toLower($menu){
    return strtolower($menu);
  }

  function get_count_of_menu_items()
  {
    if (!$this->session->system_admin) {
      $this->db->where(array('menu_is_active' => 1));
    }

    return $this->db->get('menu')->num_rows();
  }

  function get_count_of_user_menu_items()
  {
    return $this->db->get_where('menu_user_order', array('fk_user_id' => $this->session->user_id))->num_rows();
  }

  function upsert_menu($menus)
  {

    $this->db = $this->load->database('write_db', true);

    $data = array();
    $this->load->model('permission_model');

    foreach ($menus as $menu => $menuItems) {
      $data['menu_name'] = $menu;
      $data['menu_derivative_controller'] = $menu;

      if (!$this->session->system_admin) {
        //$this->write_db->where(['menu_is_active'=>1]);
      }

      $count_of_active_menus = $this->write_db->get_where('menu', array('menu_derivative_controller' => $menu))->num_rows();

      if ($count_of_active_menus == 0) {
        $this->write_db->insert('menu', $data);

        $permission_data['menu_id'] = $this->write_db->insert_id();
        $permission_data['table_name'] = $menu;

        $this->permission_model->add($permission_data);

        $this->grants_model->insert_missing_approveable_item(strtolower($menu));
        $this->grants_model->mandatory_fields(strtolower($menu));
        $this->grants_model->insert_status_if_missing(strtolower($menu));
        $this->grants->create_resource_upload_directory_structure();
      } else {
        $this->write_db->where(array('menu_derivative_controller' => $menu));
        $this->write_db->update('menu', $data);
      }
    }

    //Array diff

    $arr_menu = array_column($this->db->get('menu')->result_array(), 'menu_derivative_controller');
    //$arr_controllers = array('Approval','Bank','Budget','Center');
    $removed_controllers = array_diff($arr_menu, array_keys($menus));

    // log_message('error', json_encode($arr_menu));

    if (count($removed_controllers) > 0) {
      foreach ($removed_controllers as $removed_controller) {

        $this->write_db->trans_start();

        $removed_menu = $this->write_db->get_where(
          'menu',
          array('menu_derivative_controller' => $removed_controller)
        )->row();

        // Get the all CRUD permission records from write db
        $this->write_db->where(array('fk_menu_id' => $removed_menu->menu_id));
        $all_menu_permissions_obj = $this->write_db->get('permission');

        if ($all_menu_permissions_obj->num_rows() > 0) {
          foreach ($all_menu_permissions_obj->result_object() as $permission) {
            $permission_id = $permission->permission_id;

            $this->write_db->where(array('fk_permission_id' => $permission_id));
            $this->write_db->delete('role_permission');
          }
        }

        // Remove all permission: Note that assigned permission in role_permission/ Permission templates tables will prevent the deletion of 
        // a permission since the constraint is set to restrict in the database
        $this->write_db->where(array('fk_menu_id' => $removed_menu->menu_id));
        $this->write_db->delete('permission');

        // Delete the menu
        $this->write_db->where(array('menu_derivative_controller' => $removed_controller));
        $this->write_db->delete('menu');

        $this->write_db->trans_commit();
      }
    }
  }

  function get_id_of_default_menu_item()
  {
    $menu_id = $this->db->get_where(
      'menu',
      array('menu_derivative_controller' => $this->config->item('default_launch_page'))
    )->row()->menu_id;

    return $menu_id;
  }

  function upsert_user_menu()
  {

    // Get all menu elements
    if (!$this->session->system_admin) {
      $this->db->where(['menu_is_active' => 1]);
    }
    //$this->db->where(array('menu_is_active'=>1));
    $menu_elements =  $this->db->get('menu')->result_array();

    //Array of menu ids
    $menu_ids = array_column($menu_elements, 'menu_id');

    $sizeOfUserMenuItems = $this->get_count_of_user_menu_items();
    $sizeOfMenuItemsByDatabase = $this->get_count_of_menu_items();

    $user_menu_data = array();

    if ($sizeOfUserMenuItems !== $sizeOfMenuItemsByDatabase) {

      $menu_user_order_items = $this->db->get_where(
        'menu_user_order',
        array('fk_user_id' => $this->session->user_id)
      );

      $order = $menu_user_order_items->num_rows();

      foreach ($menu_ids as $menu_id) {
        // This allows making one of the menu items be of order 0
        $user_menu_data['menu_user_order_priority_item'] = 1;
        if (sizeof($menu_ids) - 1 == $order) {
          $user_menu_data['menu_user_order_priority_item'] = 0;
        } elseif ($order > $this->config->item('max_priority_menu_items') - 1) {
          $user_menu_data['menu_user_order_priority_item'] = 0;
        }

        if ($this->get_id_of_default_menu_item() ==  $menu_id) {
          $user_menu_data['menu_user_order_priority_item'] = 1;
        }

        $order++;

        $user_menu_data['fk_user_id'] = $this->session->user_id;
        $user_menu_data['fk_menu_id'] = $menu_id;
        $user_menu_data['menu_user_order_level'] = $order;

        if ($this->db->get_where(
          'menu_user_order',
          array('fk_user_id' => $this->session->user_id, 'fk_menu_id' => $menu_id)
        )->num_rows() == 0) {
          $this->write_db->insert('menu_user_order', $user_menu_data);
        }

        // else{
        //   $this->db->where(array('fk_user_id'=>$this->session->user_id,'fk_menu_id'=>$menu_id));
        //   $this->db->update('menu_user_order',$user_menu_data);
        // }

      }
    }

    //Get user menu items in their user defined order
    return $this->get_user_menu_items();
  }


  function get_user_menu_items()
  {
    $this->db->select(array('menu_name', 'menu_derivative_controller', 'menu_user_order_priority_item'));
    $this->db->join('menu', 'menu.menu_id=menu_user_order.fk_menu_id');
    $this->db->order_by('menu_user_order_level ASC,menu_name');
    if (!$this->session->system_admin) {
      $this->db->where(['menu_is_active' => 1]);
    }
    //$this->db->where(array('menu_is_active'=>1));
    return $this->db->get_where(
      'menu_user_order',
      array('fk_user_id' => $this->session->user_id, 'menu_user_order_is_active' => 1)
    )->result_array();
  }

  function get_favorite_menu_items(){

    $this->read_db->where(['fk_user_id' => $this->session->user_id, 'menu_user_order_is_favorite' => 1]);
    $count_of_favorites = $this->read_db->get('menu_user_order')->num_rows();

    $this->read_db->select(array('menu_id','menu_name', 'menu_derivative_controller'));
    $this->read_db->where(array('fk_user_id' => $this->session->user_id, 'menu_user_order_is_favorite' => 1));
    $this->read_db->join("menu",'menu_user_order.fk_menu_id=menu.menu_id');
    $items_obj = $this->read_db->get("menu_user_order");

    $items = [];

    if($items_obj->num_rows() > 0){
      $item_result = $items_obj->result_array();

      $item_names_raw = array_column($item_result,'menu_name');

      $item_names = array_map(function ($elem) {
        return get_phrase(strtolower($elem)); // Get Phrase helper should have the phrase all in lower case 
      }, $item_names_raw);
      
      $item_controllers = array_column($item_result,'menu_derivative_controller');

      $items = array_combine($item_controllers, $item_names);

    }
    
    $data['item_list'] = $items;
    $data['max_items_reached'] = $count_of_favorites  == $this->config->item('max_count_of_favorites_menu_items') ? true : false;

    return $data;
  }
}
