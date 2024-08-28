<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Permission_template_model extends MY_Model{

    public $table = 'permission_template'; 
    public $dependant_table = '';
    public $name_field = 'permission_template_name';
    public $create_date_field = "permission_template_created_date";
    public $created_by_field = "permission_template_created_by";
    public $last_modified_date_field = "permission_template_last_modified_date";
    public $last_modified_by_field = "permission_template_last_modified_by";
    public $deleted_at_field = "permission_template_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    function action_before_insert($post_array): array
    {
      //$post_array['header']['user_password'] = md5($post_array['header']['user_password']);
      $menus = $this->menu_library->getMenuItems();
      $this->menu_model->upsert_menu($menus);
  
      return $post_array;
    }

    public function lookup_tables(){
        return array('role_group','permission');
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}

    public function single_form_add_visible_columns()
    {
        return ['role_group_name','permission_name'];
    }

    function edit_visible_columns(){
        return ['role_group_name','permission_name','permission_template_is_active'];
    }

    public function detail_list_table_visible_columns()
    {
        return ['permission_template_track_number',
        'role_group_name','permission_name',
        'permission_template_is_active',
        'permission_template_created_date',
        'permission_template_last_modified_date'];
    }

    function multi_select_field(){
        return 'permission';
    }

    function lookup_values()
    {
        $lookup_values = parent::lookup_values();

        if(!$this->session->system_admin){
            $this->read_db->select(array('permission_id','permission_name'));
            $this->read_db->where(array('permission_is_global'=>0));
            $this->read_db->where('NOT EXISTS (SELECT * FROM permission_template WHERE permission_template.fk_permission_id=permission.permission_id AND fk_role_group_id = '.hash_id($this->id,'decode').')','',FALSE);
            $lookup_values['permission'] = $this->read_db->get('permission')->result_array();
        }

        return $lookup_values;
    }
}