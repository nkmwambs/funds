<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Approval_exemption_model extends MY_Model{

    public $table = 'approval_exemption'; 
    public $dependant_table = '';
    public $name_field = 'approval_exemption_name';
    public $create_date_field = "approval_exemption_created_date";
    public $created_by_field = "approval_exemption_created_by";
    public $last_modified_date_field = "approval_exemption_last_modified_date";
    public $last_modified_by_field = "approval_exemption_last_modified_by";
    public $deleted_at_field = "approval_exemption_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array('status','office');
    }

    function edit_visible_columns()
    {
        return ['office_name','approval_exemption_name', 'approval_exemption_is_active'];
    }

    function columns(){
        $columns = [
          'approval_exemption_track_number',
          'approval_exemption_name',
          'approve_item_name',
          'office_name',
          'approval_exemption_is_active',
          'approval_exemption_created_date'
        ];
    
        return $columns;
      }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}

    function action_before_insert($post_array)
    {

        $post_array['header']['approval_exemption_status_id'] = hash_id($this->id, 'decode');

        $status_name = $this->read_db->get_where(
            'status',
            array('status_id' => hash_id($this->id, 'decode'))
        )->row()->status_name;

        $post_array['header']['approval_exemption_name'] = $status_name;

        return $post_array;
    }

    function detail_list_query()
    {
        $this->read_db->join('office', 'office.office_id=approval_exemption.fk_office_id');
        $this->read_db->join('status', 'status.status_id=approval_exemption.approval_exemption_status_id');
        $this->read_db->where(array('approval_exemption_status_id' => hash_id($this->id, 'decode')));
        $result = $this->read_db->get('approval_exemption')->result_array();

        return $result;
    }

    function detail_list_table_visible_columns()
    {
        return ['approval_exemption_track_number', 'office_name', 'approval_exemption_is_active','approval_exemption_created_date'];
    }

    function multi_select_field()
    {
        return 'office';
    }

    function transaction_validate_duplicates_columns()
    {
        return ['approval_exemption_status_id', 'fk_office_id'];
    }

    function single_form_add_visible_columns()
    {
        return ['office_name'];
    }

    function lookup_values()
    {
        $lookup_values = parent::lookup_values();

        if(!$this->session->system_admin){
            $this->read_db->where(['fk_account_system_id' => $this->session->user_account_system_id]);
        }

        $this->read_db->select(array('office_id','office_name'));
        $this->read_db->where(array('fk_context_definition_id' => 1, 'office_is_active' => 1));
        $this->read_db->where('NOT EXISTS (SELECT * FROM approval_exemption WHERE approval_exemption.fk_office_id=office.office_id AND approval_exemption_status_id = '.hash_id($this->id, 'decode').')','',FALSE);
        $lookup_values['office'] = $this->read_db->get('office')->result_array();

        return $lookup_values;
    }
}