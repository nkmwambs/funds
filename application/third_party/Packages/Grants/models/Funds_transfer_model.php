<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Funds_transfer_model extends MY_Model{

    public $table = 'funds_transfer'; 
    public $dependant_table = '';
    public $name_field = 'funds_transfer_name';
    public $create_date_field = "funds_transfer_created_date";
    public $created_by_field = "funds_transfer_created_by";
    public $last_modified_date_field = "funds_transfer_last_modified_date";
    public $last_modified_by_field = "funds_transfer_last_modified_by";
    public $deleted_at_field = "funds_transfer_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();

        $this->load->library('datatable',['funds_transfer']);
    }

    function index(){}

    public function lookup_tables(){
        return array('office');
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}

    public function list_table_visible_columns()
    {
        return [
            "funds_transfer_track_number",
            "office_name",
            "funds_transfer_type",
            "funds_transfer_description",
            "funds_transfer_amount",
            "status_name",
        ];
    }
    
    public function modify_datatable_columns($record){
        $record['funds_transfer_type'] =  $record['funds_transfer_type'] == 1 ? get_phrase('income') : get_phrase('expense');
        $record['funds_transfer_amount'] =  number_format($record['funds_transfer_amount'],2);
            
        return $record;
    }

    function datatable_select_query(){
			
        $columns = $this->datatable->select_columns_with_status_id();
    
        $this->read_db->select($columns);
        $this->read_db->where_in('fk_office_id', array_column($this->session->hierarchy_offices,'office_id'));
      
        $this->read_db->join('status','status.status_id=funds_transfer.fk_status_id');
        $this->read_db->join('office','office.office_id=funds_transfer.fk_office_id');
        $this->read_db->order_by('funds_transfer_id', 'DESC');
        $result_obj = $this->read_db->get('funds_transfer');
    
        return $result_obj;
    }

    function post_approval_action_event($item){

        // log_message('error', json_encode($item));

        $status_data = $this->general_model->action_button_data($this->controller);
        $item_max_approval_status_ids = $status_data['item_max_approval_status_ids'];
        $next_status = $item['post']['next_status'];
        $current_status = $item['post']['current_status'];

        $next_status_approval_direction = $status_data['item_status'][$next_status]['status_approval_direction'];

        // log_message('error', json_encode($status_data));
        
        if(in_array($next_status, $item_max_approval_status_ids)){
            // log_message('error', $item['post']['next_status']);
            $funds_transfer_id = $item['post']['item_id'];
            
            // Check if a hidden bank income and expense voucher types are available for this accounting system
            // Create them if missing
            $is_hidden_voucher_type_available = $this->voucher_type_model->check_if_hidden_bank_income_expense_voucher_type_present();
            
            if($is_hidden_voucher_type_available){
                $this->create_funds_transfer_voucher($funds_transfer_id);
            }
            
        }elseif($next_status_approval_direction == -1 && in_array($current_status, $item_max_approval_status_ids)){

            $this->write_db->trans_start();
            // Check if the transfer was fully approved by checking if it has a voucher id assigned
            $this->read_db->where(array('fk_voucher_id > ' => 0,'funds_transfer_id' => $item['post']['item_id']));
            $funds_transfer = $this->read_db->get('funds_transfer');

            $is_approved_funds_transfer = $funds_transfer->num_rows();

            if($is_approved_funds_transfer > 0){

                // Reverse the voucher number. Append the funds transfer track number to the reversal voucher
                // $this->load->model('voucher_model');

                // $this->voucher_model->reverse_voucher($funds_transfer->row()->fk_voucher_id);

                $this->load->model('voucher_model');

                $this->voucher_model->reverse_voucher($funds_transfer->row()->fk_voucher_id);

                // Update the funds transfer record by removing the voucher id
                $this->write_db->where(array('funds_transfer_id' => $item['post']['item_id']));
                $this->write_db->update('funds_transfer',['fk_voucher_id' => 0]);
                
            }

            $this->write_db->trans_complete();

            if ($this->write_db->trans_status() === FALSE)
            {
                // Return the approval status of the transfer upon failure
                // log_message('error', json_encode(['funds_transfer_id' => $item['post']['item_id'], 'fk_status_id' => $current_status]));
                // $this->write_db->where(array('funds_transfer_id' => $item['post']['item_id']));
                // $this->write_db->update('funds_transfer', ['fk_status_id' => $current_status]);

                log_message('error', 'Funds transfer update on declining from fully approved failed');
            }
        }

    }

    function get_single_funds_transfer($funds_transfer_id){

        $this->read_db->select(
            [
                'office_name',
                'office_id',
                'office_bank_id',
                'funds_transfer_id',
                'funds_transfer_source_account_id',
                'funds_transfer_target_account_id',
                'funds_transfer_source_project_allocation_id',
                'funds_transfer_target_project_allocation_id',
                'funds_transfer_type',
                'funds_transfer_amount',
                'funds_transfer_description',
                'funds_transfer.fk_status_id as fk_status_id',
                'funds_transfer_created_date',
                'funds_transfer_created_by',
            ]
        );
        $this->read_db->join('office','office.office_id=funds_transfer.fk_office_id');
        $this->read_db->join('office_bank','office_bank.fk_office_id=office.office_id');
        $this->read_db->where(array('funds_transfer_id' => $funds_transfer_id,'office_bank_is_default' => 1, 'office_bank_is_active' => 1));
        $funds_transfer = $this->read_db->get('funds_transfer')->row();

        return $funds_transfer;
    }

    function create_funds_transfer_voucher($funds_transfer_id){
        
        // Get the Funds Transfer
        $funds_transfer = $this->get_single_funds_transfer($funds_transfer_id);
        $office_id = $funds_transfer->office_id;
        $office_name = $funds_transfer->office_name;
        $funds_transfer_creator = $funds_transfer->funds_transfer_created_by;
        $office_default_bank = $funds_transfer->office_bank_id;
        $funds_transfer_description = $funds_transfer->funds_transfer_description;
        $funds_transfer_amount = $funds_transfer->funds_transfer_amount;
        $funds_transfer_type = $funds_transfer->funds_transfer_type;
        $funds_transfer_source_account_id = $funds_transfer->funds_transfer_source_account_id;
        $funds_transfer_target_account_id = $funds_transfer->funds_transfer_target_account_id;
        $funds_transfer_source_project_allocation_id = $funds_transfer->funds_transfer_source_project_allocation_id;
        $funds_transfer_target_project_allocation_id = $funds_transfer->funds_transfer_target_project_allocation_id;

        // Get Current Voucher Date 
        $office_voucher_date = $this->voucher_model->get_office_voucher_date($office_id);
        $voucher_date = $office_voucher_date['next_vouching_date'];
        $last_vouching_month_date = $office_voucher_date['last_vouching_month_date'];

        // Get Current Voucher Number
        $voucher_number = $this->voucher_model->get_voucher_number($office_id);

        // Construct the Voucher Records Array

        $details = [
            [
                'voucher_detail_description' => get_phrase('funds_transfer_source_account'),
                'voucher_detail_unit_cost' => -$funds_transfer_amount,
                'voucher_detail_total_cost' => -$funds_transfer_amount,
                'fk_expense_account_id' => $funds_transfer_type == 2 ? $funds_transfer_source_account_id : 0,
                'fk_income_account_id' => $funds_transfer_type == 1 ? $funds_transfer_source_account_id : 0,
                'fk_project_allocation_id' => $funds_transfer_source_project_allocation_id,

            ],
            [
                'voucher_detail_description' => get_phrase('funds_transfer_target_account'),
                'voucher_detail_unit_cost' => $funds_transfer_amount,
                'voucher_detail_total_cost' => $funds_transfer_amount,
                'fk_expense_account_id' => $funds_transfer_type == 2 ? $funds_transfer_target_account_id : 0,
                'fk_income_account_id' => $funds_transfer_type == 1 ? $funds_transfer_target_account_id : 0,
                'fk_project_allocation_id' => $funds_transfer_target_project_allocation_id,
            ]
        ];

        $this->load->model('office_model');
        $office = $this->office_model->get_office_by_id($office_id);
      
        $cash_received_voucher_type = $this->voucher_type_model->get_hidden_voucher_type('IFTR', $office['account_system_id'])->voucher_type_id;

        $cash_expense_voucher_type = $this->voucher_type_model->get_hidden_voucher_type('EFTR', $office['account_system_id'])->voucher_type_id;

        $requestor = $this->user_model->get_user_full_name($funds_transfer_creator);

        $voucher_type_id = $funds_transfer->funds_transfer_type == 1 ? $cash_received_voucher_type : $cash_expense_voucher_type;

        $voucher_fully_approved_status = $this->general_model->action_button_data('voucher')['item_max_approval_status_ids'][0];

        $voucher_detail_fully_approved_status = $this->general_model->action_button_data('voucher_detail')['item_max_approval_status_ids'][0];

        $data['header']['voucher_track_number'] = $this->grants_model->generate_item_track_number_and_name('voucher')['voucher_track_number'];
        $data['header']['voucher_name'] = $this->grants_model->generate_item_track_number_and_name('voucher')['voucher_name'];
        $data['header']['voucher_date'] = $voucher_date;
        $data['header']['voucher_number'] = $voucher_number;
        $data['header']['fk_office_id'] = $office_id;
        $data['header']['fk_voucher_type_id'] = $voucher_type_id;
        $data['header']['voucher_cleared'] = 1;
        $data['header']['voucher_cleared_month'] = $last_vouching_month_date;
        $data['header']['fk_office_bank_id'] = $office_default_bank;
        $data['header']['fk_office_cash_id'] = 0;
        $data['header']['voucher_cheque_number'] = 'FTR';
        $data['header']['voucher_vendor'] = $requestor;
        $data['header']['voucher_vendor_address'] = $office_name;
        $data['header']['voucher_description'] = $funds_transfer_description;
        $data['header']['voucher_allow_edit'] = 0;
        $data['header']['voucher_is_reversed'] = 0;
        $data['header']['voucher_reversal_from'] = 0;
        $data['header']['voucher_reversal_to'] = 0;
        $data['header']['voucher_created_date'] = date('Y-m-d');
        $data['header']['voucher_last_modified_date'] = date('Y-m-d h:i:s');
        $data['header']['voucher_created_by'] = $funds_transfer_creator;
        $data['header']['voucher_last_modified_by'] = $funds_transfer_creator;
        $data['header']['fk_status_id'] = $voucher_fully_approved_status;

        for($i = 0; $i < sizeof($details); $i++){
            $data['detail'][$i]['voucher_detail_track_number'] = $this->grants_model->generate_item_track_number_and_name('voucher_detail')['voucher_detail_track_number'];
            $data['detail'][$i]['voucher_detail_name'] = $this->grants_model->generate_item_track_number_and_name('voucher_detail')['voucher_detail_name'];
            $data['detail'][$i]['voucher_detail_description'] = $details[$i]['voucher_detail_description'];
            $data['detail'][$i]['voucher_detail_quantity'] = 1;
            $data['detail'][$i]['voucher_detail_unit_cost'] = $details[$i]['voucher_detail_unit_cost'];
            $data['detail'][$i]['voucher_detail_total_cost'] = $details[$i]['voucher_detail_total_cost'];
            $data['detail'][$i]['fk_expense_account_id'] = $details[$i]['fk_expense_account_id'];
            $data['detail'][$i]['fk_income_account_id'] = $details[$i]['fk_income_account_id'];
            $data['detail'][$i]['fk_contra_account_id'] = 0;
            $data['detail'][$i]['fk_status_id'] = $voucher_detail_fully_approved_status;
            $data['detail'][$i]['fk_request_detail_id'] = 0;
            $data['detail'][$i]['fk_project_allocation_id'] = $details[$i]['fk_project_allocation_id'];;
            $data['detail'][$i]['voucher_detail_last_modified_date'] = date('Y-m-d h:i:s');
            $data['detail'][$i]['voucher_detail_last_modified_by'] = $funds_transfer_creator;
            $data['detail'][$i]['voucher_detail_created_by'] = $funds_transfer_creator;
            $data['detail'][$i]['voucher_detail_created_date'] = date('Y-m-d');
        }

        // Insert the Voucher Record If it doesn't exists and update funds transfer voucher id
        $voucher_id = $this->voucher_model->create_voucher($data);

        if($voucher_id > 0){
            $this->update_funds_transfer_voucher_id($funds_transfer_id,$voucher_id);

            // If the first voucher of the month, create a financial report and journal
            $this->voucher_model->create_report_and_journal($office_id, $last_vouching_month_date);
        }
    }

    private function update_funds_transfer_voucher_id($funds_transfer_id,$voucher_id){

        $data['fk_voucher_id'] = $voucher_id;
        $this->write_db->where(array('funds_transfer_id' => $funds_transfer_id));
        $this->write_db->update('funds_transfer',$data);
    }

    
    function format_funds_transfer_request($funds_transfer_request){

        // log_message('error', json_encode($funds_transfer_request));

        // Use the status name rather than status_id
        $actions = $this->general_model->action_button_data($this->controller);
        $status_data = $actions['item_status'];
        $funds_transfer_status = $funds_transfer_request['request_status'];
        $funds_transfer_request['request_status'] = $status_data[$funds_transfer_status]['status_name'];
  
        //Source account name
        $source_account = '';
        $destination_account = '';
  
        if($funds_transfer_request['funds_transfer_type'] == 1){
  
          $this->read_db->select(array('income_account_id as account_id','income_account_name as account_name'));
          $this->read_db->join('project_income_account','project_income_account.fk_income_account_id=income_account.income_account_id');
          $this->read_db->join('project','project.project_id=project_income_account.fk_project_id');
          $this->read_db->where_in('income_account_id', [$funds_transfer_request['source_account'], $funds_transfer_request['destination_account']]);
          $accounts = $this->read_db->get('income_account')->result_array();
  
          $account_ids = array_column($accounts,'account_id');
          $account_names = array_column($accounts,'account_name');
          
          $source_and_destination_account = array_combine($account_ids, $account_names);
  
          $source_account = $source_and_destination_account[$funds_transfer_request['source_account']];
          $destination_account  = $source_and_destination_account[$funds_transfer_request['destination_account']];
         
  
        }else{
  
          $this->read_db->select(array('expense_account_id as account_id','expense_account_name as account_name'));
          $this->read_db->join('income_account','income_account.income_account_id=expense_account.fk_income_account_id');
          $this->read_db->join('project_income_account','project_income_account.fk_income_account_id=income_account.income_account_id');
          $this->read_db->join('project','project.project_id=project_income_account.fk_project_id');
          $this->read_db->where_in('expense_account_id', [$funds_transfer_request['source_account'], $funds_transfer_request['destination_account']]);
          $accounts = $this->read_db->get('expense_account')->result_array();
  
          $account_ids = array_column($accounts,'account_id');
          $account_names = array_column($accounts,'account_name');
          
          $source_and_destination_account = array_combine($account_ids, $account_names);
  
          $source_account = $source_and_destination_account[$funds_transfer_request['source_account']];
          $destination_account  = $source_and_destination_account[$funds_transfer_request['destination_account']];
          
        }
  
        $this->read_db->select(array('project_allocation_id','project_name'));
        $this->read_db->join('project','project.project_id=project_allocation.fk_project_id');
        $this->read_db->where_in('project_allocation_id', [$funds_transfer_request['source_project_allocation_id'], $funds_transfer_request['target_project_allocation_id']]);
        $project_allocations = $this->read_db->get('project_allocation')->result_array();
  
        $project_allocation_ids = array_column($project_allocations,'project_allocation_id');
        $project_names = array_column($project_allocations,'project_name');
          
        $source_and_destination_allocation = array_combine($project_allocation_ids, $project_names);
  
        $source_allocation = $source_and_destination_allocation[$funds_transfer_request['source_project_allocation_id']];
        $destination_allocation  = $source_and_destination_allocation[$funds_transfer_request['target_project_allocation_id']];
  
  
        $final_approver = $this->get_final_approver($funds_transfer_request, $actions);
  
        $funds_transfer_request['funds_transfer_approved_by'] = $final_approver['funds_transfer_approved_by'];
        $funds_transfer_request['funds_transfer_approval_date'] = $final_approver['funds_transfer_approval_date'];

        $funds_transfer_request['funds_transfer_raise_date'] = $funds_transfer_request['raise_date'];
  
        $funds_transfer_request['source_account'] = $source_account;
        $funds_transfer_request['destination_account'] = $destination_account;
  
        $funds_transfer_request['source_allocation'] = $source_allocation;
        $funds_transfer_request['destination_allocation'] = $destination_allocation;
      
      return $funds_transfer_request;
    }
  
    private function get_final_approver($funds_transfer_request, $status_data){
  
      $approver_data = [];
  
      $approver_data['funds_transfer_approved_by'] = '';
      $approver_data['funds_transfer_approval_date'] = '';
  
      $funds_transfer_last_modified_by = $funds_transfer_request['funds_transfer_last_modified_by'];
      $funds_transfer_status_id = $funds_transfer_request['funds_transfer_status_id'];
      $funds_transfer_last_modified_date = $funds_transfer_request['funds_transfer_last_modified_date'];
      $item_max_approval_status_ids = $status_data['item_max_approval_status_ids'];
  
      if(in_array($funds_transfer_status_id, $item_max_approval_status_ids)){
        $approver_data['funds_transfer_approved_by'] = $this->user_model->get_user_full_name($funds_transfer_last_modified_by);
        $approver_data['funds_transfer_approval_date'] = $funds_transfer_last_modified_date;
      }
      
      return $approver_data;
    }
}