<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Funds_transfer extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->library('funds_transfer_library');

    $this->load->model('voucher_model');
    $this->load->model('voucher_type_model');
    $this->load->model('income_account_model');
    $this->load->model('financial_report_model');
    $this->load->library('datatable',['funds_transfer']);
  }

  function index(){}

  // function funds_transfer_accounts()
	// {

	// 	$post = $this->input->post();

	// 	$transfer_type = $post['transfer_type'];
	// 	$is_source_account_civ = $post['is_source_account_civ'];
	// 	$is_destination_civ = $post['is_destination_civ'];
  //   $office_id = $post['office_id'];

	// 	$accounts = $this->derive_funds_transfer_accounts($office_id, $transfer_type, $is_source_account_civ, $is_destination_civ);

	// 	echo json_encode($accounts);
	// }

  function get_transfer_accounts($office_id, $is_civ_accounts = false, $is_income_accounts = true)
	{

    $results = [];

    $this->read_db->where(array('project_allocation.fk_office_id'=> $office_id));

		if ($is_civ_accounts) {
      $this->read_db->where('project.project_end_date NOT LIKE "0000-00-00"');
      $this->read_db->or_where('project.project_end_date IS NOT  NULL'); //Added by Onduso because Nulling Date fields
		} else {
      $this->read_db->where('project.project_end_date LIKE "0000-00-00"');
      $this->read_db->or_where('project.project_end_date IS NULL'); //Added by Onduso because Nulling Date fields
		}

		if ($is_income_accounts) {

      $columns = array('CONCAT(income_account_id,"-",project_allocation_id) as account_id', 'income_account_name as account_name');

      if($is_civ_accounts){
        // $columns = array('project_allocation_id as account_id', 'project_name as account_name');
        $columns = array('CONCAT(income_account_id,"-",project_allocation_id) as account_id', "CONCAT(project_name,' [',income_account_name,']') as account_name");
      }

      $this->read_db->select($columns);

      $this->read_db->join('project_income_account','project_income_account.fk_income_account_id=income_account.income_account_id');
      $this->read_db->join('project','project.project_id=project_income_account.fk_project_id');
      $this->read_db->join('project_allocation','project_allocation.fk_project_id=project.project_id');

      $this->read_db->where(array('income_account_is_active' => 1));
      $results = $this->read_db->get('income_account')->result_array();
		}else{
      
      $columns = array('CONCAT(expense_account_id,"-",project_allocation_id) as account_id', 'expense_account_name as account_name');

      if($is_civ_accounts){
        // $columns = array('project_allocation_id as account_id', 'project_name as account_name');
        $columns = array('CONCAT(expense_account_id,"-",project_allocation_id) as account_id', 'CONCAT(project_name," [",expense_account_name,"]") as account_name');
      }

      $this->read_db->select($columns);
      $this->read_db->join('income_account','income_account.income_account_id=expense_account.fk_income_account_id');

      $this->read_db->join('project_income_account','project_income_account.fk_income_account_id=income_account.income_account_id');
      $this->read_db->join('project','project.project_id=project_income_account.fk_project_id');
      $this->read_db->join('project_allocation','project_allocation.fk_project_id=project.project_id');

      $this->read_db->where(array('expense_account_is_active' => 1));
      $results = $this->read_db->get('expense_account')->result_array();
    }


		$account_ids = array_column($results, 'account_id');
		$account_names = array_column($results, 'account_name');

		return array_combine($account_ids, $account_names);
	}

  function post_funds_transfer($funds_transfer_id = 0)
	{
		$post = $this->input->post();

    //echo json_encode($post);
    $source_account = $post['source_account'];
    $source_project_allocation = $post['source_allocation'];

    $destination_account = $post['destination_account'];
    $destination_project_allocation = $post['destination_allocation'];

		$data['funds_transfer_track_number'] = $this->grants_model->generate_item_track_number_and_name('funds_transfer')['funds_transfer_track_number'];
    $data['funds_transfer_name'] = $this->grants_model->generate_item_track_number_and_name('funds_transfer')['funds_transfer_name'];
    $data['fk_office_id'] = $post['office_id'];
    $data['funds_transfer_source_account_id'] =  $source_account;
    $data['funds_transfer_target_account_id'] =  $destination_account;
    $data['funds_transfer_source_project_allocation_id'] = $source_project_allocation;
    $data['funds_transfer_target_project_allocation_id'] = $destination_project_allocation;
    $data['funds_transfer_type'] = $post['transfer_type'];
    $data['funds_transfer_amount'] = $post['transfer_amount'];
    $data['funds_transfer_description'] = $post['transfer_description'];
    $data['fk_voucher_id'] = 0;

    if($funds_transfer_id == 0){
      $data['fk_status_id'] = $this->grants_model->initial_item_status('funds_transfer');  
    }
    
    // $data['fk_approval_id'] = $this->grants_model->insert_approval_record('funds_transfer'); //3230888
    $data['funds_transfer_created_date'] = date('Y-m-d');
    $data['funds_transfer_created_by'] = $this->session->user_id;
    $data['funds_transfer_last_modified_by'] = $this->session->user_id;
    $data['funds_transfer_last_modified_date'] = date('Y-m-d h:i:s');

		$message = "Request not created/updated";

		if ($funds_transfer_id == 0) {
			$this->write_db->insert('funds_transfer', $data);

      if ($this->write_db->affected_rows() > 0) {
        $message = "Request created successful";

        $funds_transfer_id = $this->write_db->insert_id();
      }
		} else {
			$this->write_db->where(array('funds_transfer_id' => $funds_transfer_id));
			//$data['accepted'] = 2;
			$this->write_db->update('funds_transfer', $data);
      
      if ($this->write_db->affected_rows() > 0) {
        $message = "Request Updated successful";
      }
			
		}


		if ($this->write_db->affected_rows() > 0) {

      $status_id = $this->write_db->get_where('funds_transfer',array('funds_transfer_id' => $funds_transfer_id))->row()->fk_status_id;
      
      $next_status_id = $this->general_model->next_status($status_id);
      $item['item'] = 'funds_transfer';
      $item['post']['next_status'] = $next_status_id;
      $item['post']['item_id'] = $funds_transfer_id;
      $item['post']['current_status'] = $status_id;
      
      $this->funds_transfer_model->post_approval_action_event($item);
			//$message = "Request created successful";

			// Send an email to PF for approval

			// $data['contacts'] = $this->get_fcp_facilitator($fcp_id);
			// $this->email_model->funds_transfer_notification('submit_transfer', $data);
		}

		echo $message;
	}

  function format_funds_transfer_request($funds_transfer_request){

    $funds_transfer_request = $this->funds_transfer_model->format_funds_transfer_request($funds_transfer_request);
    return $funds_transfer_request;
  }

  private function get_final_approver($funds_transfer_request, $status_data){
    $approver_data = $this->funds_transfer_model->get_final_approver($funds_transfer_request, $status_data);
    return $approver_data;
  }

  function get_single_funds_transfer_request($request_id){

    $columns = [
      'funds_transfer_id',
      'funds_transfer_created_date as raise_date',
      'fk_voucher_id as voucher_number',
      'office_name as office_name',
      'CONCAT(user_firstname, " " ,user_lastname) as requestor',
      'funds_transfer.fk_status_id as request_status',
      'funds_transfer_amount as amount',
      'funds_transfer_description as description',
      'funds_transfer_source_account_id as source_account',
      'funds_transfer_target_account_id as destination_account',
      'funds_transfer_source_project_allocation_id as source_project_allocation_id',
      'funds_transfer_target_project_allocation_id as target_project_allocation_id',
      'funds_transfer_type',
      'funds_transfer_last_modified_by',
      'funds_transfer.fk_status_id as funds_transfer_status_id',
      'funds_transfer_last_modified_date',
    ];
    $this->read_db->select($columns);
    $this->read_db->join('office','office.office_id=funds_transfer.fk_office_id');
    $this->read_db->join('user','user.user_id=funds_transfer.funds_transfer_created_by');
    $this->read_db->where(array('funds_transfer_id' => $request_id));
    $request = $this->read_db->get('funds_transfer')->row_array();

    //$assigned_voucher_numbers = [];
    $voucher_id = 0;

    foreach($request as $field_name => $field_value){
        if($field_name == 'voucher_number' && $field_value > 0){
          $voucher_id = $field_value;
        }
    } 

    $voucher = $this->read_db->get_where('voucher',array('voucher_id' => $voucher_id));

    if($voucher->num_rows() > 0){
      $request['voucher_number'] = "<a target='__blank' href='".base_url()."voucher/view/".hash_id($voucher_id,'encode')."'>".$voucher->row()->voucher_number."</a>";
    }
   

    return $request;
  }


function result($id = 0){

  $result = [];

  if($this->action == 'list'){
    
    $result = $this->datatable->get_list_view();

  }elseif($this->action == 'view'){

    $funds_transfer_request = $this->get_single_funds_transfer_request(hash_id($this->id,'decode'));
    $result['transfer_request'] = $this->format_funds_transfer_request($funds_transfer_request);
  
  }elseif($this->action == 'edit'){
    
    $request = $this->get_funds_transfer_requests(hash_id($this->id,'decode'));
		$office_id = $request['fk_office_id'];
    $source_account = $request['funds_transfer_source_account_id'];
    $destination_account = $request['funds_transfer_target_account_id'];
    $funds_transfer_type = $request['funds_transfer_type'];

    // $result['accounts'] = $this->derive_funds_transfer_accounts($request['fk_office_id'],$request['funds_transfer_type'], $request['funds_transfer_source_project_allocation_id'] > 0 ? 1 : 0, $request['funds_transfer_target_project_allocation_id'] > 0 ? 1 : 0);
    $result['transfer_request'] = $request; 
    $result['allocation_codes'] = $this->funds_transfer_allocations($office_id);
    $result['source_accounts'] = $this->funds_transfer_request_allocation_accounts($request['funds_transfer_type'], $request['funds_transfer_source_project_allocation_id']);
    $result['destination_accounts'] = $this->funds_transfer_request_allocation_accounts($request['funds_transfer_type'], $request['funds_transfer_target_project_allocation_id']);
    $result['source_fund_balance'] = number_format($this->_income_account_fund_balance($office_id, $source_account, $funds_transfer_type),2);
    $result['destination_fund_balance'] = number_format($this->_income_account_fund_balance($office_id, $destination_account, $funds_transfer_type),2);

  }elseif($this->action == 'single_form_add'){

  }else{
    $result = parent::result($id);
  }

  return $result;
}

private function get_funds_transfer_requests($request_id){

  $this->read_db->select(
    array(
      'funds_transfer_id',
      'fk_office_id',
      'funds_transfer_type',
      'funds_transfer_source_account_id',
      'funds_transfer_target_account_id',
      'funds_transfer_source_project_allocation_id',
      'funds_transfer_target_project_allocation_id',
      'funds_transfer_amount',
      'funds_transfer_description',
    )
  );

  $this->read_db->where(array('funds_transfer_id' => $request_id));
  $request = $this->read_db->get('funds_transfer')->row_array();

  return $request;
}

function show_list(){
    
		$show_list = $this->datatable->show_list();

    // log_message('error', json_encode([$show_list['recordsTotal'], $show_list['recordsFiltered']]));
   
		echo json_encode($show_list);
	}

  function funds_transfer_allocations($office_id){

    $allocation_codes = [];

    $this->read_db->select(array('project_allocation_id', 'project_name'));
    $this->read_db->where(array('fk_office_id' => $office_id));
    $this->read_db->join('project','project.project_id=project_allocation.fk_project_id');
    $allocations = $this->read_db->get('project_allocation');

    if($allocations->num_rows() > 0){
      $project_allocation_ids = array_column($allocations->result_array(), 'project_allocation_id');
      $project_names = array_column($allocations->result_array(), 'project_name');

      $allocation_codes = array_combine($project_allocation_ids, $project_names);
    }

    return $allocation_codes;
  }

  function funds_transfer_allocation_codes(){
    $post = $this->input->post();
    $office_id = $post['office_id'];

    $allocation_codes = $this->funds_transfer_allocations( $office_id);

    echo  json_encode($allocation_codes);
  }

  function funds_transfer_request_allocation_accounts($funds_transfer_type, $allocation_id){
    $accounts = [];
    $accounts_obj = null;


    if($funds_transfer_type == 1){
      $this->read_db->select(array('income_account_id as account_id', 'income_account_name as account_name'));
      $this->read_db->join('project_income_account','project_income_account.fk_income_account_id=income_account.income_account_id');
      $this->read_db->join('project','project.project_id=project_income_account.fk_project_id');
      $this->read_db->join('project_allocation','project_allocation.fk_project_id=project.project_id');
      $this->read_db->where(array('project_allocation_id' => $allocation_id, 'income_account_is_active' => 1));
      $accounts_obj = $this->read_db->get('income_account');
    }else{
      $this->read_db->select(array('expense_account_id as account_id', 'expense_account_name as account_name'));
      $this->read_db->join('income_account','income_account.income_account_id=expense_account.fk_income_account_id');
      $this->read_db->join('project_income_account','project_income_account.fk_income_account_id=income_account.income_account_id');
      $this->read_db->join('project','project.project_id=project_income_account.fk_project_id');
      $this->read_db->join('project_allocation','project_allocation.fk_project_id=project.project_id');
      $this->read_db->where(array('project_allocation_id' => $allocation_id, 'expense_account_is_active' => 1));
      $accounts_obj = $this->read_db->get('expense_account');
    }
    
    if($accounts_obj->num_rows() > 0){
      $account_ids = array_column($accounts_obj->result_array(), 'account_id');
      $account_names = array_column($accounts_obj->result_array(), 'account_name');

      $accounts = array_combine($account_ids, $account_names);
    }

    return $accounts;
  }

  function funds_transfer_allocation_accounts(){

    $post = $this->input->post();

    $allocation_id = $post['allocation_id'];
    $funds_transfer_type = $post['funds_transfer_type'];

    $accounts = $this->funds_transfer_request_allocation_accounts($funds_transfer_type, $allocation_id);

    echo json_encode($accounts);
  }

  private function _income_account_fund_balance($office_id, $account_id, $project_allocation_id, $funds_transfer_type){
    // Get Current Voucher Date 
    $office_voucher_date = $this->voucher_model->get_office_voucher_date($office_id);
    $voucher_date = $office_voucher_date['next_vouching_date'];

    $income_account_id = $account_id;

    if($funds_transfer_type == 2){
      $income_account_id = $this->income_account_model->get_expense_income_account($account_id)->income_account_id;
    }

    // log_message('error', json_encode(['income_account_id' => $income_account_id]));
    $project_id = 0;
    $this->read_db->where(array('project_allocation_id' => $project_allocation_id));
    $project_allocation_obj = $this->read_db->get('project_allocation');

    if($project_allocation_obj->num_rows() > 0){
      $project_id = $project_allocation_obj->row()->fk_project_id;
    }
    
    $fund_balance_amount  = $this->financial_report_model->get_fund_balance_by_account($office_id,$income_account_id, date('Y-m-01',strtotime($voucher_date)), $project_id);

    // log_message('error', json_encode(['fund_balance_amount' => $fund_balance_amount]));

    return $fund_balance_amount;
  }

  function income_account_fund_balance(){
    $post = $this->input->post();

    $account_id = $post['account_id'];
    $project_allocation_id = $post['project_allocation_id'];
    $funds_transfer_type = $post['funds_transfer_type'];
    $office_id = $post['office_id'];

    // log_message('error', json_encode([
    //   'office_id' => $office_id, 
    //   'account_id' => $account_id, 
    //   'project_allocation_id' => $project_allocation_id, 
    //   'funds_transfer_type' => $funds_transfer_type
    // ]));

    $fund_balance_amount = $this->_income_account_fund_balance($office_id, $account_id, $project_allocation_id, $funds_transfer_type);

    echo number_format($fund_balance_amount,2);
  }

  static function get_menu_list(){}

}