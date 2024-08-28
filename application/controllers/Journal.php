<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Journal extends MY_Controller
{

  function __construct(){
    parent::__construct();

    $this->load->model('finance_model');
    $this->load->model('voucher_model');
    $this->load->model('financial_report_model');
    $this->load->model('cheque_book_model');

  }

  function index(){}

  function month_opening_bank_cash_balance($office_id,$transacting_month,$office_bank_id = 0){

    $balance = $this->journal_library->month_opening_bank_cash_balance($office_id,$transacting_month,$office_bank_id);
    
    // log_message('error', json_encode($office_bank_id));

    return [
      'bank_balance'=> $balance['bank'],
      'cash_balance'=> $balance['cash']
    ];
  }
  
 

  function journal_records($office_id,$transacting_month, $project_allocation_ids = [], $office_bank_id = 0){
    
      return $this->journal_library->journal_records($office_id,$transacting_month, $project_allocation_ids, $office_bank_id);
  }

  function get_office_data_from_journal($journal_id){
    return $this->journal_library->get_office_data_from_journal($journal_id);
  }

  function journal_navigation($office_id, $transacting_month){
    return $this->journal_library->journal_navigation($office_id, $transacting_month);
  }
  function check_if_voucher_is_reversed_or_cancelled($voucher_id){
    echo $this->journal_model->check_if_voucher_is_reversed_or_cancelled($voucher_id);
  }

  function financial_accounts($office_id, $transacting_month){
    return $this->journal_library->financial_accounts($office_id, $transacting_month);
  }


  function result($id = ''){
    if($this->action == 'view'){
      
      $journal_id = hash_id($this->id,'decode');
      $office_id = $this->get_office_data_from_journal($journal_id)->office_id;
      $transacting_month = $this->get_office_data_from_journal($journal_id)->journal_month;

      $status_data = $this->general_model->action_button_data('voucher', $this->session->user_account_system_id);

      $result['vouchers']=$this->get_vouchers_of_the_month($office_id,$transacting_month,$journal_id);
      
      $result['status_data'] = $status_data;

      $result['transacting_month']=$transacting_month;

      return   $result;

     //return $this->get_vouchers_of_the_month($office_id,$transacting_month,$journal_id);
    }elseif($this->action == 'list'){
      $columns = $this->columns();
      array_shift($columns);
      $result['columns'] = $columns;
      $result['has_details_table'] = false; 
      $result['has_details_listing'] = false;
      $result['is_multi_row'] = false;
      $result['show_add_button'] = false;

      return $result; 
    }else{
      return parent::result($id = '');
    }
  }

  function columns(){
    $columns = [
      'journal_id',
      'journal_track_number',
      'journal_name',
      'journal_month',
      'journal_created_date',
      'journal_last_modified_date',
      'office_name'
    ];

    return $columns;

  }

  function get_journals(){

    $columns = $this->columns();
    $search_columns = $columns;

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
      $this->read_db->order_by('journal_id DESC');
    }else{
      $this->read_db->order_by($columns[$col],$dir); 
    }

    // Searching

    $search = $this->input->post('search');
    $value = $search['value'];

    array_shift($search_columns);

    if(!empty($value)){
      $this->read_db->group_start();
      $column_key = 0;
        foreach($search_columns as $column){
          if($column_key == 0) {
            $this->read_db->like($column,$value,'both'); 
          }else{
            $this->read_db->or_like($column,$value,'both');
        }
          $column_key++;				
      }
      $this->read_db->group_end();       
    }

    $this->read_db->select($columns);
    $this->read_db->join('status','status.status_id=journal.fk_status_id');
    $this->read_db->join('office','office.office_id=journal.fk_office_id');

    if(!$this->session->system_admin){
      $this->read_db->where_in('journal.fk_office_id',array_column($this->session->hierarchy_offices,'office_id'));
    }

    $result_obj = $this->read_db->get('journal');
    
    $results = [];

    if($result_obj->num_rows() > 0){
      $results = $result_obj->result_array();
    }

    return $results;
  }

  function count_journals(){

    $columns = $this->columns();
    $search_columns = $columns;

    // Searching

    $search = $this->input->post('search');
    $value = $search['value'];

    array_shift($search_columns);

    if(!empty($value)){
      $this->read_db->group_start();
      $column_key = 0;
        foreach($search_columns as $column){
          if($column_key == 0) {
            $this->read_db->like($column,$value,'both'); 
          }else{
            $this->read_db->or_like($column,$value,'both');
        }
          $column_key++;				
      }
      $this->read_db->group_end();
    }
    
    if(!$this->session->system_admin){
      $this->read_db->where_in('journal.fk_office_id',array_column($this->session->hierarchy_offices,'office_id'));
    }

    $this->read_db->join('status','status.status_id=journal.fk_status_id');
    $this->read_db->join('office','office.office_id=journal.fk_office_id');
    $this->read_db->from('journal');
    $count_all_results = $this->read_db->count_all_results();

    return $count_all_results;
  }

  function show_list(){

    $draw =intval($this->input->post('draw'));
    $journals = $this->get_journals();
    $count_journals = $this->count_journals();

    $result = [];

    $cnt = 0;
    foreach($journals as $journal){
      $journal_id = array_shift($journal);
      $journal_track_number = $journal['journal_track_number'];
      $journal['journal_track_number'] = '<a href="'.base_url().$this->controller.'/view/'.hash_id($journal_id).'">'.$journal_track_number.'</a>';
      $row = array_values($journal);

      $result[$cnt] = $row;

      $cnt++;
    }

    $response = [
      'draw'=>$draw,
      'recordsTotal'=>$count_journals,
      'recordsFiltered'=>$count_journals,
      'data'=>$result
    ];
    
    echo json_encode($response);
  }

 

  private function get_vouchers_of_the_month($office_id,$transacting_month,$journal_id,$office_bank_id = 0, $project_allocation_ids = []){
   
    $this->load->model('office_bank_model');
    $active_office_banks_by_reporting_month = $this->office_bank_model->get_active_office_banks_by_reporting_month([$office_id], $transacting_month);

    $result = [
      'active_office_banks' => $active_office_banks_by_reporting_month,
      'office_bank_accounts'=>$this->grants_model->office_bank_accounts($office_id, $office_bank_id),
      'office_has_multiple_bank_accounts'=>$this->grants_model->office_has_multiple_bank_accounts($office_id),
      'transacting_month'=> $transacting_month,
      'office_id'=>$office_id,
      'office_name'=> $this->get_office_data_from_journal($journal_id)->office_name,
      'navigation'=>$this->journal_navigation($office_id, $transacting_month),
      'accounts'=>$this->financial_accounts($office_id, $transacting_month),
      'month_opening_balance'=>$this->month_opening_bank_cash_balance($office_id,$transacting_month, $office_bank_id),
      'vouchers'=>$this->journal_records($office_id,$transacting_month,$project_allocation_ids, $office_bank_id),
      'mfr_submited_status'=>$this->financial_report_model->check_if_financial_report_is_submitted([$office_id],$transacting_month), //Line added by ONDUSO on DEC 20 2022 for avoiding cancelling a voucher once mfr is submitted.
      'allow_skipping_of_cheque_leaves' => $this->cheque_book_model->allow_skipping_of_cheque_leaves(),
      //'mfr_current_status'=>$this->journal_model->current_mfr_status($office_id, $transacting_month),
      'financial_report_max_status'=>$this->general_model->get_max_approval_status_id('financial_report')[0],
      //'financial_report_initial_status'=>$this->grants_model->initial_item_status('financial_report'),
      //'selected_mfr_id_equals_max_mfr'=>$this->journal_model->is_selected_mfr_equals_max_mfr_id($office_id,$transacting_month),
      
    ];
     
     //print_r($result['month_opening_balance']);exit;

     return $result;
  }

  function get_office_bank_project_allocation_ids($office_bank_id){
    $records = $this->grants_model->get_type_records_by_foreign_key_id('office_bank_project_allocation','office_bank',$office_bank_id);

    return count($records) > 0 ? array_column($records,'fk_project_allocation_id') : [];
  }

  function get_office_bank_journal(){
     
    /**
     * Class parameters e.g. $this->action and $this->id from MY_Controller are not visible on ajax request
     */
    
    $office_bank_id = $this->input->post('office_bank_id');
    $office_id = $this->input->post('office_id');
    $transacting_month = $this->input->post('transacting_month');
    $journal_id = hash_id($this->input->post('journal_id'),'decode');

    $project_allocation_ids = $this->get_office_bank_project_allocation_ids($office_bank_id);

    $result = $this->get_vouchers_of_the_month($office_id,$transacting_month,$journal_id,$office_bank_id,$project_allocation_ids);
    // log_message('error', json_encode($result));
    $status_data = $this->general_model->action_button_data('voucher', $this->session->user_account_system_id);

    $result['result'] = $result;
    $result['result']['office_bank_name'] = $this->grants_model->get_type_name_by_id('office_bank',$office_bank_id);
    $result['result']['status_data'] = $status_data;

    $view_page =  $this->load->view('journal/ajax_view',$result,true);

    echo $view_page;
  }

  function view(){
    parent::view();
  }

  /**
   * Duplicated in the voucher model - To be removed from here in the later versions
   */

  function insert_voucher_reversal_record($voucher,$reuse_cheque){
    
    //Unset the primary key field
    $voucher_id =array_shift($voucher);

    $voucher_details = $this->read_db->get_where('voucher_detail',
    array('fk_voucher_id'=>$voucher_id))->result_array();

    // log_message('error', json_encode($voucher));
    // Get next voucher number
    $next_voucher_number = $this->voucher_model->get_voucher_number($voucher['fk_office_id']);
    $next_voucher_date = $this->voucher_model->get_voucher_date($voucher['fk_office_id']);

    
    // Array shift again to remove 'voucher_type_is_cheque_referenced' from array before replacing the cancelled/resused details voucher
   // array_shift($voucher);

    // Replace the voucher number in selected voucher with the next voucher number
    $voucher_description = '<strike>'.$voucher['voucher_description'].'</strike> [Reversal of voucher number '.$voucher['voucher_number'].']';
    $voucher = array_replace($voucher,['voucher_vendor'=>'<strike>'.$voucher['voucher_vendor'].'<strike>','voucher_is_reversed'=>1,'voucher_reversal_from'=>$voucher_id,'voucher_cleared'=>1,'voucher_date'=>$next_voucher_date,'voucher_cleared_month'=>date('Y-m-t',strtotime($next_voucher_date)),'voucher_transaction_cleared_month' => NULL,'voucher_transaction_cleared_date' => NULL,'voucher_number'=>$next_voucher_number,'voucher_description'=>$voucher_description,'voucher_cheque_number'=>$voucher['voucher_cheque_number'] > 0 && $reuse_cheque == 1 ? -$voucher['voucher_cheque_number'] : $voucher['voucher_cheque_number']]);
  
    //Insert the next voucher record and get the insert id
    $this->write_db->insert('voucher',$voucher);

    $new_voucher_id = $this->write_db->insert_id();

    // Update details array and insert 
    
    $updated_voucher_details = [];

    foreach($voucher_details as $voucher_detail){
      unset($voucher_detail['voucher_detail_id']);
      $updated_voucher_details[] = array_replace($voucher_detail,['fk_voucher_id'=>$new_voucher_id,'voucher_detail_unit_cost'=>-$voucher_detail['voucher_detail_unit_cost'],'voucher_detail_total_cost'=>-$voucher_detail['voucher_detail_total_cost']]);
    }

    $this->write_db->insert_batch('voucher_detail',$updated_voucher_details);

    // Update the original voucher record by flagging it reversed
    $this->write_db->where(array('voucher_id'=>$voucher_id));
    $update_data['voucher_is_reversed'] = 1;
    $update_data['voucher_cleared'] = 1;
    $update_data['voucher_cleared_month'] = date('Y-m-t',strtotime($next_voucher_date));
    $update_data['voucher_cheque_number'] = $voucher['voucher_cheque_number'] > 0 ? -$voucher['voucher_cheque_number'] : $voucher['voucher_cheque_number'];
    $update_data['voucher_reversal_to'] = $new_voucher_id;
    $this->write_db->update('voucher',$update_data);

    return ['new_voucher_id' => $new_voucher_id, 'new_voucher' => $voucher];
  }

  /**
   * Duplicated in the voucher model - To be removed from here in the later versions
   */

  function update_cash_recipient_account($new_voucher_id,$voucher){

    $voucher_id = array_shift($voucher);
    // Insert a cash_recipient_account record if reversing voucher is bank to bank contra

    $this->read_db->where(array('voucher_type_id'=>$voucher['fk_voucher_type_id']));
    $this->read_db->join('voucher_type','voucher_type.fk_voucher_type_effect_id=voucher_type_effect.voucher_type_effect_id');
    $voucher_type_effect_code = $this->read_db->get('voucher_type_effect')->row()->voucher_type_effect_code;

    if($voucher_type_effect_code == 'bank_to_bank_contra'){

      $this->read_db->where(array('fk_voucher_id'=>$voucher_id));
      $original_cash_recipient_account = $this->read_db->get('cash_recipient_account')->row_array();

      $cash_recipient_account_data['cash_recipient_account_name'] = $this->grants_model->generate_item_track_number_and_name('cash_recipient_account')['cash_recipient_account_name'];
      $cash_recipient_account_data['cash_recipient_account_track_number'] = $this->grants_model->generate_item_track_number_and_name('cash_recipient_account')['cash_recipient_account_track_number'];
      $cash_recipient_account_data['fk_voucher_id'] = $new_voucher_id;

      if($voucher['fk_office_bank_id'] > 0){
        $cash_recipient_account_data['fk_office_bank_id'] = $original_cash_recipient_account['fk_office_bank_id'];
      }elseif($voucher['fk_office_cash_id'] > 0){
        $cash_recipient_account_data['fk_office_cash_id'] = $original_cash_recipient_account['fk_office_cash_id'];
      }

      $cash_recipient_account_data['cash_recipient_account_created_date'] = date('Y-m-d');
      $cash_recipient_account_data['cash_recipient_account_created_by'] = $this->session->user_id;
      $cash_recipient_account_data['cash_recipient_account_last_modified_by'] = $this->session->user_id;

      $cash_recipient_account_data['fk_approval_id'] = $this->grants_model->insert_approval_record('cash_recipient_account');
      $cash_recipient_account_data['fk_status_id'] = $this->grants_model->initial_item_status('cash_recipient_account');

      $this->write_db->insert('cash_recipient_account',$cash_recipient_account_data);
    }

  }

  function reverse_voucher($voucher_id,$reuse_cheque = 1, $reusing_and_cancel_eft_or_chq='',$journal_month=''){
     
    $message = get_phrase("transaction_failed");
    $message_code = 'fail';

    if($reusing_and_cancel_eft_or_chq=='eft'){
      $message='Reusing/Cancellation of EFT Completed';
    } else if($reusing_and_cancel_eft_or_chq=='cheque'){
      $message='Reusing/Cancellation of Cheque Completed';
    }else{
      
      $message='Cancellation Completed';
      
    }
    

     // Get the voucher and voucher details
     $voucher = $this->read_db->get_where('voucher',array('voucher_id'=>$voucher_id))->row_array();

     //Count  of reuse
    $count_of_reuse = $this->cheque_book_model->get_reused_cheque_count($voucher['fk_office_bank_id'],-$voucher['voucher_cheque_number'], $reusing_and_cancel_eft_or_chq);

    // $count_of_reuse = 4; //$this->cheque_book_model->get_reused_cheque_count(-$voucher['voucher_cheque_number']);

    if($count_of_reuse < $this->config->item('cheque_cancel_and_resuse_limit') || $this->session->system_admin){
          $this->write_db->trans_start();
          
         // log_message('error', json_encode($journal_month));
          
          $insert_voucher = $this->voucher_model->insert_voucher_reversal_record($voucher,$reuse_cheque,$journal_month);

          $new_voucher_id = $insert_voucher['new_voucher_id'];
          
          $this->update_cash_recipient_account($new_voucher_id,$voucher);

          $this->journal_model->create_new_journal($insert_voucher['new_voucher']['voucher_date'], $voucher['fk_office_id']);
          
          $this->load->model('financial_report_model');
          $this->financial_report_model->create_financial_report(date("Y-m-01", strtotime($insert_voucher['new_voucher']['voucher_date'])), $voucher['fk_office_id']);
      
          $this->write_db->trans_complete();
      
          if($this->write_db->trans_status() == true){
            $message = get_phrase($message);
            $message_code = 'success';
          }
    }else{
      $message = get_phrase('exceed_reuse_limit',"You have exceeded the reuse limit of 3 for this bank reference. Kindly contact PF");
    }

    echo json_encode(['message_code' => $message_code, 'message' => $message,'next_voucher_number'=>$insert_voucher['next_voucher_number']]);
  }

  function edit_journal_description(){

    $message = "Update Successful";

    $this->write_db->trans_start();

    $post = $this->input->post();

    $update_data[$post['column']] = $post['content'];
    $this->write_db->where(array('voucher_id'=>$post['voucher_id']));

    $this->write_db->update('voucher',$update_data);

    $this->write_db->trans_complete();

    if($this->write_db->trans_status() == false){
      $message = "Update failed";
    }

    echo $message;
  }

  static function get_menu_list(){

  }

}
