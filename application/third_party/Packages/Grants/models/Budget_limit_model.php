<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Budget_limit_model extends MY_Model
{

    public $table = 'budget_limit';
    public $dependant_table = '';
    public $name_field = 'budget_limit_name';
    public $create_date_field = "budget_limit_created_date";
    public $created_by_field = "budget_limit_created_by";
    public $last_modified_date_field = "budget_limit_last_modified_date";
    public $last_modified_by_field = "budget_limit_last_modified_by";
    public $deleted_at_field = "budget_limit_deleted_at";

    function __construct()
    {
        parent::__construct();
        $this->load->database();

        $this->load->model('budget_model');
    }

    function index()
    {
    }

    public function lookup_tables()
    {
        return array('office', 'budget_tag', 'income_account');
    }

    function action_before_edit($post_array){
        $budget_limit_id = hash_id($this->id,'decode');
        $income_account_id = $post_array['header']['fk_income_account_id'];
        $new_budget_limit_amount = $post_array['header']['budget_limit_amount'];

        $total_budget_amount = $this->total_budgeted_amount_per_income_account_by_budget_limit($budget_limit_id, $income_account_id);

        if($new_budget_limit_amount < $total_budget_amount){
            return ['error' => 'The new limit '.number_format($new_budget_limit_amount,2).' amount is lower than the total budgeted amount '.number_format($total_budget_amount,2)];
        }

        return $post_array;
    }

    function total_budgeted_amount_per_income_account_by_budget_limit($budget_limit_id, $income_account_id){

        $budget_item_amount = 0;

        $this->read_db->select_sum('budget_item_detail_amount');
        $this->read_db->join('budget_item','budget_item.budget_item_id=budget_item_detail.fk_budget_item_id');
        $this->read_db->join('budget','budget.budget_id=budget_item.fk_budget_id');
        $this->read_db->join('budget_limit','budget_limit.fk_budget_id=budget.budget_id');
        $this->read_db->join('expense_account','expense_account.expense_account_id=budget_item.fk_expense_account_id');
        $this->read_db->where(array('budget_limit_id' => $budget_limit_id,'expense_account.fk_income_account_id' => $income_account_id));
        $this->read_db->group_by(array('expense_account.fk_income_account_id'));
        $budget_item_amount_obj = $this->read_db->get('budget_item_detail');

        if($budget_item_amount_obj->num_rows() > 0){
            $budget_item_amount = $budget_item_amount_obj->row()->budget_item_detail_amount;
        }

        return $budget_item_amount;
    }

    public function action_before_insert($post_array){

        // Check if there is a duplicate budget limit
        $budget_limit_exists = $this->check_duplicate_budget_limit_exists($post_array['header']);

        if($budget_limit_exists){
            return ['message' => get_phrase('budget_limit_exists','The budget limit you are creating already exists')];
        }
    
        return $post_array;
    }

    private function check_duplicate_budget_limit_exists($limit){
        $check_duplicate_budget_limit_exists = false;

        $this->read_db->where(array('fk_budget_id' => $limit['fk_budget_id'], 'fk_income_account_id' => $limit['fk_income_account_id']));
        $budget_limit_count = $this->read_db->get('budget_limit')->num_rows();

        if($budget_limit_count > 0){
            $check_duplicate_budget_limit_exists = true;
        }

        return $check_duplicate_budget_limit_exists;
    }

    public function detail_tables()
    {
    }

    public function detail_multi_form_add_visible_columns()
    {
    }

    public function single_form_add_visible_columns()
    {
        $cols = ["budget_name", "income_account_name", "budget_limit_amount"];

        // if($this->sub_action != null){
        //     $cols = ["budget_name", "income_account_name", "budget_limit_amount"];
        // }

        return $cols;
    }

    public function list_table_visible_columns()
    {
        return [
            "budget_limit_track_number",
            "office_name",
            // "budget_limit_year",
            "budget_name",
            "budget_tag_name",
            "income_account_name",
            "budget_limit_amount"
        ];
    }

    private function budget_limit_amount($budget_id, $income_account_id)
    {

        $budget_limit_amount = 0;

        $this->load->model('budget_model');

        // $budget_obj = $this->budget_model->get_budget_by_id($budget_id);
        // log_message('error', json_encode($budget_obj));
        $this->read_db->where(
            [
                'fk_budget_id' => $budget_id,
                'fk_income_account_id' => $income_account_id
            ]
        );
        $this->read_db->join('budget','budget.budget_id=budget_limit.fk_budget_id');
        $budget_limit_obj = $this->read_db->get('budget_limit');

        if ($budget_limit_obj->num_rows() > 0) {
            $budget_limit_amount = $budget_limit_obj->row()->budget_limit_amount;
        }

        return $budget_limit_amount;
    }

    private function budget_to_date_amount_by_income_account($budget_id, $income_account_id)
    {
        return $this->budget_model->budget_to_date_amount_by_income_account($budget_id, $income_account_id);
    }

    public function budget_limit_remaining_amount($budget_id, $income_account_id)
    {
        $budget_limit_amount = $this->budget_limit_amount($budget_id, $income_account_id);
        // log_message('error', json_encode($budget_limit_amount));
        $sum_year_budgeted_amount = $this->budget_to_date_amount_by_income_account($budget_id, $income_account_id);

        return $budget_limit_amount - $sum_year_budgeted_amount;
    }

    // function transaction_validate_duplicates_columns()
    // {
    //     return ['fk_office_id', 'fk_budget_tag_id', 'budget_limit_year','fk_income_account_id', 'fk_custom_financial_year_id'];
    // }

    public function lookup_values()
    {
        $lookup_values = parent::lookup_values();

        $lookup_values['income_account'] = [];

        if($this->action == 'edit' || $this->action == 'single_form_add'){
            $this->read_db->select(array('income_account_id','income_account_name'));
            $this->read_db->where(array('income_account_is_budgeted'=>1,'income_account_is_active' => 1,'fk_account_system_id'=>$this->session->user_account_system_id));
            $lookup_values['income_account'] = $this->read_db->get('income_account')->result_array();
        }

        $this->read_db->select(array('budget_id','CONCAT(office_code," ","FY",budget_year," ",budget_tag_name) as budget_name'));
        $this->read_db->where(array('status_approval_sequence' => 1));
        $this->read_db->join('status','status.status_id=budget.fk_status_id');
        $this->read_db->join('office','office.office_id=budget.fk_office_id');
        $this->read_db->join('budget_tag','budget_tag.budget_tag_id=budget.fk_budget_tag_id');
        $lookup_values['budget'] = $this->read_db->get('budget')->result_array();

        $this->read_db->select(array('office_id','office_name'));

        if($this->sub_action != null && $this->sub_action == 'budget'){
            $this->read_db->join('budget','budget.fk_office_id=office.office_id');
            $this->read_db->where(array('budget_id' => hash_id($this->id,'decode')));
        }elseif(!$this->session->system_admin){
            $this->read_db->where_in('office_id',array_column($this->session->hierarchy_offices,'office_id'));
        }
        $this->read_db->where(array('fk_context_definition_id' => 1,'office_is_active' => 1));
        $lookup_values['office'] = $this->read_db->get('office')->result_array();

        return $lookup_values;
    }

    function edit_visible_columns(){
        return [
            'income_account_name',
            'budget_limit_amount'
        ];
    }

    public function list_table_where()
    {
      if (!$this->session->system_admin) {
        $this->read_db->where_in('fk_office_id', array_column($this->session->hierarchy_offices,'office_id'));
      }
    }

    function get_budget_limit_by_budget_id($budget_id){

        $budget_limits = [];

        $this->read_db->select(array('budget_limit_id','budget_limit_track_number','office_code','budget_year','budget_tag_name','income_account_name','budget_limit_amount'));
        $this->read_db->where(array('budget_id' => $budget_id));
        $this->read_db->join('budget','budget.budget_id=budget_limit.fk_budget_id');
        $this->read_db->join('office','office.office_id=budget.fk_office_id');
        $this->read_db->join('budget_tag','budget_tag.budget_tag_id=budget.fk_budget_tag_id');
        $this->read_db->join('income_account','income_account.income_account_id=budget_limit.fk_income_account_id');
        $this->read_db->order_by('income_account_id');
        $budget_limit_obj = $this->read_db->get('budget_limit');

        if($budget_limit_obj->num_rows() > 0){
            $budget_limits = $budget_limit_obj->result_array();
        }

        return $budget_limits;
    }
}
