<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Funder_model extends MY_Model 
{
  public $table = 'funder';
  public $dependant_table = "";
  // public $hidden_columns = array();
  // private $lookup_tables = array();

  function __construct(){
    parent::__construct();

  }

  function delete($id = null){

  }

  function index(){}

    public function lookup_tables(){
      //return ['status','approval'];
    }

    public function detail_tables(){
      return ['project','income_account',"office_bank"];
    }



    public function master_table_visible_columns(){
      return array('funder_name', 'funder_code', 'funder_is_active', 'funder_created_date', 'funder_last_modified_date', 'account_system_name');
    }

    public function master_table_hidden_columns(){
      
    }

    public function list_table_visible_columns(){
      return array('funder_track_number', 'funder_name', 'funder_code','office_name', 'funder_description', 'funder_is_active','funder_created_date', 'account_system_name');
    }

    public function list_table_hidden_columns(){}

    public function detail_list_table_visible_columns(){}

    public function detail_list_table_hidden_columns(){}

    public function single_form_add_visible_columns(){
      return array('funder_name', 'funder_code','office_name','funder_description', 'funder_is_active', 'account_system_name');
    }

    public function edit_visible_columns(){
      return array('funder_name', 'funder_code','funder_description', 'funder_is_active','office_name');
    }

    public function single_form_add_hidden_columns(){}

    public function master_multi_form_add_visible_columns(){
      return array('funder_name','funder_description');
    }

    public function detail_multi_form_add_visible_columns(){}

    public function master_multi_form_add_hidden_columns(){}

    public function detail_multi_form_add_hidden_columns(){}

    function detail_list(){}

    function master_view(){}

    public function list(){
      // $this->read_db->join('account_system','account_system.account_system_id=funder.fk_account_system_id');
      // return $this->read_db->get('funder')->result_array();
    }

    public function view(){}

    // public function detail_list_table_where(){
    //   $this->read_db->where(array('fk_funder_id'=> hash_id($this->id, 'decode')));
    // }
    public function list_table_where(){

      if(!$this->session->system_admin){
        if($this->session->context_definition['context_definition_level'] == 1){
          $this->read_db->where_in('fk_office_id', array_column($this->session->hierarchy_offices,'office_id'));
        }
        $this->read_db->where(array('fk_account_system_id'=>$this->session->user_account_system_id));
      }
  
    }

    function get_user_funders(){
      
      $transacting_offices = $this->user_model->direct_user_offices($this->session->user_id, $this->session->context_definition['context_definition_name']);

      $this->read_db->select(array('funder_id', 'funder_name'));
      // $this->read_db->where(array('fk_account_system_id' => $this->session->user_account_system_id));
      $this->read_db->where_in('project_allocation.fk_office_id', array_column($transacting_offices, 'office_id'));
      $this->read_db->join('project','project.fk_funder_id=funder.funder_id');
      $this->read_db->join('project_allocation','project_allocation.fk_project_id=project.project_id');
      $funder_obj = $this->read_db->get('funder');

      $funders = [];

      if ($funder_obj->num_rows() > 0) {
        $funders = $funder_obj->result_array();
      }

      return $funders;
    }

    function lookup_values(){
      $lookup_values = parent::lookup_values();

      if (!$this->session->system_admin) {
        $hierarchy_offices = array_column($this->session->hierarchy_offices, 'office_id');
        $this->read_db->select(array('office_id', 'office_name'));
        $this->read_db->where_in('office_id', $hierarchy_offices);
        $lookup_values['office'] = $this->read_db->get('office')->result_array();
      }

      return $lookup_values;
    }

    function get_funder_by_id($funder_id){
      // Get funder code 
      $this->read_db->where(['funder_id' => $funder_id]);
      $funder_obj = $this->read_db->get('funder');

      $funder = [];

      if($funder_obj->num_rows() > 0){
        $funder = $funder_obj->row_array();
      }

      return $funder;
    }
}
