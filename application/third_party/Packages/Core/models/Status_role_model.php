<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Status_role_model extends MY_Model
{

    public $table = 'status_role';
    public $dependant_table = '';
    public $name_field = 'status_role_name';
    public $create_date_field = "status_role_created_date";
    public $created_by_field = "status_role_created_by";
    public $last_modified_date_field = "status_role_last_modified_date";
    public $last_modified_by_field = "status_role_last_modified_by";
    public $deleted_at_field = "status_role_deleted_at";

    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    function index()
    {
    }

    public function lookup_tables()
    {
        return array('status', 'role');
    }

    public function detail_tables()
    {
    }

    public function detail_multi_form_add_visible_columns()
    {
    }


    function single_form_add_visible_columns()
    {
        return ['role_name'];
    }

    function edit_visible_columns()
    {
        return ['role_name', 'status_role_is_active'];
    }


    /**
     * @override
     */
    function transaction_validate_duplicates_columns()
    {
        return ['status_role_status_id', 'fk_role_id', 'status_role_is_active'];
    }

    function transaction_validate_by_computation_flag($status_role_data)
    {
        if ($status_role_data['fk_role_id'] == 1) {
            return VALIDATION_ERROR;
        } else {
            return VALIDATION_SUCCESS;
        }
    }

    function action_before_insert($post_array)
    {

        $post_array['header']['status_role_status_id'] = hash_id($this->id, 'decode');

        $status_name = $this->read_db->get_where(
            'status',
            array('status_id' => hash_id($this->id, 'decode'))
        )->row()->status_name;

        $post_array['header']['status_role_name'] = $status_name;

        return $post_array;
    }


    function detail_list_query()
    {
        $this->read_db->join('role', 'role.role_id=status_role.fk_role_id');
       
        $this->read_db->join('status', 'status.status_id=status_role.status_role_status_id');
        $this->read_db->where(array('status_role_status_id' => hash_id($this->id, 'decode')));
        $result = $this->read_db->get('status_role')->result_array();

        return $result;
    }

    function detail_list_table_visible_columns()
    {
        return ['status_role_track_number', 'role_name', 'status_role_is_active'];
    }

    function multi_select_field()
    {
        return 'role';
    }

    function lookup_values()
    {
        $lookup_values = parent::lookup_values();


        $this->read_db->select(array('role_id', 'role_name'));
        $this->read_db->where('NOT EXISTS (SELECT * FROM status_role WHERE status_role.fk_role_id=role.role_id AND status_role_status_id=' . hash_id($this->id, 'decode') . ')', '', FALSE);
        if (!$this->session->system_admin) {
            $this->read_db->where(array("fk_account_system_id" => $this->session->user_account_system_id));
        }

        $lookup_values['role'] = $this->read_db->get('role')->result_array();

        return $lookup_values;
    }

    function columns(){
        $columns = [
          'status_role_track_number',
        //   'status_role_name',
          'status_name',
          'role_name',
          'status_role_is_active',
          'status_role_created_date'
        ];
    
        return $columns;
      }
}
