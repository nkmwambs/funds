<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org/ Londuso@ke.ci.org.....
 */


class Financial_review_dashboard extends MY_Controller
{

  function __construct()
  {
    parent::__construct();
    $this->load->model('financial_report_model');
    $this->load->library('financial_review_dashboard_library');
  }

  function index()
  {
  }

  
  function result($id = '')
  {

    if($this->action == 'list'){
        $columns = $this->columns();
        array_shift($columns); // Removing financial_report_id
        array_shift($columns); // Removing office_id
        $columns = array_column(alias_columns($columns,'@'),'list_columns');
        $result['columns'] = $columns;
        $result['has_details_table'] = false; 
        $result['has_details_listing'] = false;
        $result['is_multi_row'] = false;
        $result['show_add_button'] = false;
  
        return $result;
    } else {
      return parent::result($id);
    }
  }

  function columns(){
    
    $columns = [
      'financial_report_id',
      'office_id',
      'financial_report_track_number @ track_number',
      'office_code',
      'office_name',
      'context_cluster_name @ cluster_name',
      'financial_report_month @ reporting_month',
      'financial_report_created_date @ first_voucher_date',
      'financial_report_is_submitted @ submission_status',
      'financial_report_submitted_date @ submitted_date',
      'financial_report_approved_date @ approved_date ',
      'count_of_uploaded_statements @ statement_count',

      'closing_fund_balance_data @ fund_balance',
      'closing_total_statement_balance_data @ statement_balance',
      'closing_total_cash_balance_data @ bank_balance',
      'closing_cash_balance_data @ cash_balance',
      'closing_outstanding_cheques_data	 @ outstanding_cheques',
      'closing_transit_deposit_data @ in_transit_deposit',
      'closing_overdue_cheques_data @ overdue_cheques',
      'closing_overdue_deposit_data @ overdue_deposit',
      'financial_report_is_reconciled @ is_report_reconciled?',

      'status_name'
    ];
    
    return $columns;

  }

  function get_financial_reports(){

    $columns = $this->columns();
    $columns = array_column(alias_columns($columns, '@'),'query_columns');
    // $columns = array_diff($columns, $this->non_query_columns());
    array_push($columns, 'status_id');
    // $search_columns = $columns;

    // log_message('error', json_encode($columns));

    // Limiting records
    $start = intval($this->input->post('start'));
    $length = intval($this->input->post('length'));

    $this->read_db->limit($length, $start);

    // Ordering records

    $order = $this->input->post('order');
    $col = '';
    $dir = 'desc';
    
    if(!empty($order)){
      $col = $order[0]['column'];
      $dir = $order[0]['dir'];
    }
          
    if( $col == ''){
      $this->read_db->order_by('financial_report_id DESC');
    }else{
      $this->read_db->order_by($columns[$col],$dir); 
    }

    // Searching
    $this->searchbuilder->searchbuilder_query_group($this->columns());

    $this->read_db->select($columns);
    $this->read_db->join('status','status.status_id=financial_report.fk_status_id');
    $this->read_db->join('office','office.office_id=financial_report.fk_office_id');

    $this->read_db->join('context_center','context_center.fk_office_id=office.office_id');
    $this->read_db->join('context_cluster','context_cluster.context_cluster_id=context_center.fk_context_cluster_id');

    $this->read_db->join('reconciliation','reconciliation.fk_financial_report_id=financial_report.financial_report_id');
    $this->read_db->join('financial_report_uploaded_statements','financial_report.financial_report_id=financial_report_uploaded_statements.fk_financial_report_id', 'LEFT');
    // $this->read_db->join('closing_financial_report_total','financial_report.financial_report_id=closing_financial_report_total.fk_financial_report_id', 'LEFT');

  
    if(!$this->session->system_admin){
      $this->read_db->where_in('financial_report.fk_office_id',array_column($this->session->hierarchy_offices,'office_id'));
    }

    $this->read_db->group_by('financial_report_id');

    $result_obj = $this->read_db->get('financial_report');
    
    $results = [];

    if($result_obj->num_rows() > 0){
      $results = $result_obj->result_array();
    }

    return $results;
  }

  function count_financial_reports(){

    $columns = $this->columns();
    $columns = array_column(alias_columns($columns, '@'),'query_columns');
    // $columns = array_diff($columns, $this->non_query_columns());
    $search_columns = $columns;

    // Searching

    // Searching

    $this->searchbuilder->searchbuilder_query_group($this->columns());
    
    if(!$this->session->system_admin){
      $this->read_db->where_in('financial_report.fk_office_id',array_column($this->session->hierarchy_offices,'office_id'));
    }

    $this->read_db->join('status','status.status_id=financial_report.fk_status_id');
    $this->read_db->join('office','office.office_id=financial_report.fk_office_id');
    
    $this->read_db->join('context_center','context_center.fk_office_id=office.office_id');
    $this->read_db->join('context_cluster','context_cluster.context_cluster_id=context_center.fk_context_cluster_id');

    $this->read_db->join('reconciliation','reconciliation.fk_financial_report_id=financial_report.financial_report_id');
    $this->read_db->join('financial_report_uploaded_statements','financial_report.financial_report_id=financial_report_uploaded_statements.fk_financial_report_id', 'LEFT');
    // $this->read_db->join('closing_financial_report_total','financial_report.financial_report_id=closing_financial_report_total.fk_financial_report_id', 'LEFT');

    $this->read_db->from('financial_report');
    $count_all_results = $this->read_db->count_all_results();

    return $count_all_results;
  }

  function draw_track_number(&$financial_report, $financial_report_id){
      $financial_report_track_number = $financial_report['financial_report_track_number'];
      $financial_report['financial_report_track_number'] = '<a target="__blank" href="'.base_url().'/financial_report/view/'.hash_id($financial_report_id).'">'.$financial_report_track_number.'</a>';
  }

  function format_cluster_name(&$financial_report){
      $context_cluster_name_explode = explode('office',$financial_report['context_cluster_name']); // All records bear the name of the context with a office in the string e.g. Context for office Turkana 
      $financial_report['context_cluster_name'] = count($context_cluster_name_explode) == 2 ? $context_cluster_name_explode[1] : $context_cluster_name_explode[0]; // This code is to help to still get the available name if the office seperator does not exist
  }

  function format_report_submission_checks(&$financial_report, $office_id, $reporting_month){

    $is_report_timely_submitted = $this->is_report_timely_submitted($financial_report['financial_report_month'], $financial_report['financial_report_submitted_date']);

    $format_color_submission_date = $is_report_timely_submitted ? 'green' : 'red';

    $financial_report['financial_report_is_submitted'] = $financial_report['financial_report_is_submitted'] == 1 ? get_phrase('yes') :  get_phrase('no');
    $financial_report['financial_report_submitted_date'] = '<span style = "color:'.$format_color_submission_date.';">'.$financial_report['financial_report_submitted_date'].'</span>';
    $financial_report['count_of_uploaded_statements'] = !empty($financial_report['count_of_uploaded_statements']) ? '<a href = "#" onclick = "showAjaxModal(\''.base_url().strtolower($this->controller).'/modal/modal_bank_statement/office_id/'.$office_id.'/reporting_month/'.$reporting_month.'\');">'.$financial_report['count_of_uploaded_statements'].'</a>' : '';
  
  }

  function format_balances_parameters(&$financial_report,$office_id, $reporting_month){

    $closing_total_statement_balance_data = 0;

    if($financial_report['closing_total_statement_balance_data'] != null){
      $decode_result = json_decode($financial_report['closing_total_statement_balance_data'],true);
      if(!empty($decode_result)){
        $closing_total_statement_balance_data = $decode_result['bank_statement_balance'];
      }
    }

    $closing_fund_balance_data = 0;

    if($financial_report['closing_fund_balance_data'] != null){
      $decode_result = json_decode($financial_report['closing_fund_balance_data'],true);
      if(!empty($decode_result)){
        $closing_fund_balance_data = array_sum($decode_result);
      }
    }

    $closing_bank_balance_data = 0;
    $closing_cash_balance_data = 0;

    if($financial_report['closing_total_cash_balance_data'] != null){
      $decode_result = json_decode($financial_report['closing_total_cash_balance_data'],true);
      if(!empty($decode_result)){
        $closing_bank_balance_data = $decode_result['cash_at_bank'];
        $closing_cash_balance_data = $decode_result['cash_at_hand'];

      }
    }

    $closing_outstanding_cheques_data = 0;

    if($financial_report['closing_outstanding_cheques_data'] != null){
      $decode_result = json_decode($financial_report['closing_outstanding_cheques_data'],true);
      if(!empty($decode_result)){
        $closing_outstanding_cheques_data = array_sum(array_column($decode_result,'amount'));
      }
    }

    $closing_transit_deposit_data = 0;

    if($financial_report['closing_transit_deposit_data'] != null){
      $decode_result = json_decode($financial_report['closing_transit_deposit_data'],true);
      if(!empty($decode_result)){
        $closing_transit_deposit_data = array_sum(array_column($decode_result,'amount'));
      }
    }

    $closing_overdue_cheques_data = 0;

    if($financial_report['closing_overdue_cheques_data'] != null){
      $decode_result = json_decode($financial_report['closing_overdue_cheques_data'],true);
      if(!empty($decode_result)){
        $closing_overdue_cheques_data = array_sum(array_column($decode_result,'amount'));
      }
    }

    $closing_overdue_deposit_data = 0;

    if($financial_report['closing_overdue_deposit_data'] != null){
      $decode_result = json_decode($financial_report['closing_overdue_deposit_data'],true);
      if(!empty($decode_result)){
        $closing_overdue_deposit_data = array_sum(array_column($decode_result,'amount'));
      }
    }

    $statement_balance_amount = $closing_total_statement_balance_data; // is_numeric($financial_report['reconciliation_statement_balance']) ? $financial_report['reconciliation_statement_balance'] : '0.00';
    $fund_balance_amount = $closing_fund_balance_data; // is_numeric($financial_report['closing_financial_report_total_fund_amount']) ? $financial_report['closing_financial_report_total_fund_amount'] : '0.00';
    $bank_balance_amount = $closing_bank_balance_data; // empty($financial_report['closing_financial_report_total_bank_amount']) ? '0.00':  $financial_report['closing_financial_report_total_bank_amount'];
    $cash_balance_amount = $closing_cash_balance_data; // empty($financial_report['closing_financial_report_total_cash_amount']) ? '0.00':  $financial_report['closing_financial_report_total_cash_amount'];
    $outstanding_cheque_amount = $closing_outstanding_cheques_data; // empty($financial_report['closing_financial_report_total_outstanding_amount']) ? '0.00':  $financial_report['closing_financial_report_total_outstanding_amount'];
    $transit_deposit_amount = $closing_transit_deposit_data; // empty($financial_report['closing_financial_report_total_transit_amount']) ? '0.00':  $financial_report['closing_financial_report_total_transit_amount'];

    $financial_report['closing_total_statement_balance_data'] = number_format($statement_balance_amount,2);
    $financial_report['closing_fund_balance_data'] = $fund_balance_amount != 0 ? '<a href="#" onclick = "showAjaxModal(\''.base_url().strtolower($this->controller).'/modal/modal_fund_balance_report/office_id/'.$office_id.'/reporting_month/'.$reporting_month.'\');">'.number_format($fund_balance_amount,2).'</a>' : $fund_balance_amount;
    $financial_report['closing_total_cash_balance_data'] = number_format($bank_balance_amount,2);
    $financial_report['closing_cash_balance_data'] = number_format($cash_balance_amount,2);

    $financial_report['closing_overdue_cheques_data'] = number_format($closing_overdue_cheques_data,2); // empty($financial_report['closing_financial_report_total_stale_cheques_amount']) ? '0.00' : number_format($financial_report['closing_financial_report_total_stale_cheques_amount'],2);
    $financial_report['closing_overdue_deposit_data'] = number_format($closing_overdue_deposit_data,2); //empty($financial_report['closing_financial_report_total_stale_deposit_amount']) ? '0.00' : number_format($financial_report['closing_financial_report_total_stale_deposit_amount'],2);

    $financial_report['closing_outstanding_cheques_data'] = $outstanding_cheque_amount != 0 ? '<a href="#" onclick = "showAjaxModal(\''.base_url().strtolower($this->controller).'/modal/modal_outstanding_cheques/office_id/'.$office_id.'/reporting_month/'.$reporting_month.'\');">'.number_format($outstanding_cheque_amount,2).'</a>' : number_format($outstanding_cheque_amount,2); // number_format($outstanding_cheque_amount,2);
    $financial_report['closing_transit_deposit_data'] = $transit_deposit_amount != 0 ? '<a href="#" onclick = "showAjaxModal(\''.base_url().strtolower($this->controller).'/modal/modal_transit_deposit/office_id/'.$office_id.'/reporting_month/'.$reporting_month.'\');">'.number_format($transit_deposit_amount,2).'</a>' : number_format($transit_deposit_amount,2); // number_format($transit_deposit_amount,2);
    
    $financial_report['financial_report_is_reconciled'] = $financial_report['financial_report_is_reconciled'] ? get_phrase('yes') : get_phrase('no');
  }

  function show_list(){

    $draw =intval($this->input->post('draw'));
    $financial_reports = $this->get_financial_reports();
    $count_financial_reports = $this->count_financial_reports();
    
    $status_data = $this->general_model->action_button_data($this->controller);
    extract($status_data);

    $result = [];
    $cnt = 0;

    foreach($financial_reports as $financial_report){
      // log_message('error', json_encode($financial_report));
      $financial_report_id = array_shift($financial_report);
      $office_id = array_shift($financial_report);
      $reporting_month = $financial_report['financial_report_month'];
      $financial_status = array_pop($financial_report);

      // Formats the track number
      $this->draw_track_number($financial_report, $financial_report_id);

      // Format the cluster name
      $this->format_cluster_name($financial_report);

      // Formats if report is submitted late, format submission status to yes or no and provides a link to list of bank statements on the statement count
      $this->format_report_submission_checks($financial_report, $office_id, $reporting_month);

      // Provides links to fund balance, outstanding cheques and deposit in transit and highlights overdue outstanding cheques and deposit in transit
      $this->format_balances_parameters($financial_report, $office_id, $reporting_month);

      $row = array_values($financial_report);

      //$action = '';

      //if($this->session->context_definition['context_definition_id'] < 5){ // This means it applies to users from country level to center
        $action = '';//approval_action_button($this->controller, $item_status, $financial_report_id, $financial_status, $item_initial_item_status_id, $item_max_approval_status_ids,false,true,$financial_report['status_name']);
      //}
      array_unshift($row, $action);

      
      $result[$cnt] = $row;

      $cnt++;
    }

    $response = [
      'draw'=>$draw,
      'recordsTotal'=>$count_financial_reports,
      'recordsFiltered'=>$count_financial_reports,
      'data'=>$result
    ];
    
    echo json_encode($response);
  }

  function is_report_timely_submitted($financial_report_month, $financial_report_submitted_date){

    $is_report_timely_submitted = true;
    
    // Create a DateTime object from the given date
    $dateTime = new DateTime($financial_report_month);
    
    // Add one month to the given date
    $dateTime->add(new DateInterval('P1M'));
    
    // Set the day of the month to the fifth
    $dateTime->setDate($dateTime->format('Y'), $dateTime->format('m'), 5);
    
    // Get the result as a formatted date string
    $valid_submission_date = $dateTime->format('Y-m-d');
    
    $financial_report_submitted_date = new DateTime($financial_report_submitted_date);
    $valid_submission_date = new DateTime($valid_submission_date);
    
    if($financial_report_submitted_date > $valid_submission_date){
      $is_report_timely_submitted = false;
    }
    
    // log_message('error', json_encode(['financial_report_month' => $financial_report_month, 'valid_submission_date' => $valid_submission_date, 'financial_report_submitted_date' => $financial_report_submitted_date, 'is_report_timely_submitted' => $is_report_timely_submitted]));
    
    return $is_report_timely_submitted;
  }


  static function get_menu_list()
  {
  }
}
