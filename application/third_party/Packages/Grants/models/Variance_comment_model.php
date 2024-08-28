<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Variance_comment_model extends MY_Model{

    public $table = 'variance_comment'; 
    public $dependant_table = '';
    public $name_field = 'variance_comment_name';
    public $create_date_field = "variance_comment_created_date";
    public $created_by_field = "variance_comment_created_by";
    public $last_modified_date_field = "variance_comment_last_modified_date";
    public $last_modified_by_field = "variance_comment_last_modified_by";
    public $deleted_at_field = "variance_comment_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();

        $this->load->model('budget_model');
    }

    function index(){}

    public function lookup_tables(){
        return array('budget','expense_account');
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}

    function get_expense_account_comment($expense_account_id,$budget_id,$report_id){

        $this->read_db->where(array('fk_expense_account_id'=>$expense_account_id,'fk_financial_report_id'=>$report_id,'fk_budget_id'=>$budget_id));
        $variance_comment_obj = $this->read_db->get('variance_comment');
    
        $commment = '';
    
        if($variance_comment_obj->num_rows() > 0){
          $commment = $variance_comment_obj->row()->variance_comment_text;
        }

        return $commment;
    }

    function add(){

        $post = $this->input->post();

        $expense_account_id = $post['expense_account_id'];
        $variance_comment_text = $post['variance_comment_text'];
        $office_id = $post['office_id'];
        $reporting_month = $post['reporting_month'];
        $report_id = $post['report_id'];

        $budget_id = $this->budget_model->get_budget_id_based_on_month($office_id,$reporting_month);

        $message = "";

        if($budget_id > 0){
            
            $this->read_db->where(array('fk_expense_account_id'=>$expense_account_id,
            'fk_financial_report_id'=>$report_id,'fk_budget_id'=>$budget_id));
            $variance_comment_obj = $this->read_db->get('variance_comment');
            
            $this->write_db->trans_start();
    
            if($variance_comment_obj->num_rows() == 0){
    
                $variance_comment_data['fk_budget_id'] = $budget_id;
                $variance_comment_data['fk_financial_report_id'] = $report_id;
                $variance_comment_data['fk_expense_account_id'] = $expense_account_id;
                $variance_comment_data['variance_comment_text'] = $variance_comment_text;
    
                $variance_comment_to_insert = $this->grants_model->merge_with_history_fields('variance_comment',$variance_comment_data);
    
                $this->write_db->insert('variance_comment',$variance_comment_to_insert);
    
                $message = "Comment created successfully";
            }else{
            $data['variance_comment_text'] = $variance_comment_text;
    
            $this->write_db->where(array('fk_expense_account_id'=>$expense_account_id,'fk_financial_report_id'=>$report_id));
        
            $this->write_db->update('variance_comment',$data);
    
            $message = "Comment updated successfully";
            }
    
    
            $this->write_db->trans_commit();
    
            if($this->write_db->trans_status() == false){
                $message = "Error occurred";
            }
        }else{
            $message = get_phrase("you_do_not_have_an_approved_budget_review_for_the_period");//"You don't have an approved budget review for the period";
        }


        return $message;
    }

    function get_all_expense_account_comment($budget_id,$report_id){
           
        $variance_comments_array = [];
        
        $this->read_db->select(array('fk_income_account_id as income_account_id','fk_expense_account_id as expense_account_id','variance_comment_text'));
        $this->read_db->where(['fk_budget_id'=>$budget_id,'fk_financial_report_id'=>$report_id]);
        $this->read_db->join('expense_account','expense_account.expense_account_id=variance_comment.fk_expense_account_id');
        $variance_comment_obj = $this->read_db->get('variance_comment');

        if($variance_comment_obj->num_rows() > 0){
            $variance_comments = $variance_comment_obj->result_array();

            foreach($variance_comments as $variance_comment){
                $variance_comments_array[$variance_comment['income_account_id']][$variance_comment['expense_account_id']] = $variance_comment['variance_comment_text'];
            }
        }

        return $variance_comments_array;
  }
}