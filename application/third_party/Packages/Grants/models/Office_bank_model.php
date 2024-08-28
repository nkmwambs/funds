<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Office_bank_model extends MY_Model 
{
  public $table = 'office_bank'; // you MUST mention the table name
  //public $dependant_table = '';
  public $name_field = 'office_bank_name';
  public $create_date_field = "office_bank_created_date";
  public $created_by_field = "office_bank_created_by";
  public $last_modified_date_field = "office_bank_last_modified_date";
  public $last_modified_by_field = "office_bank_last_modified_by";
  public $deleted_at_field = "office_bank_deleted_at";


  function __construct(){
    parent::__construct();
  }

  function delete($id = null){

  }

  function index(){}

  public function lookup_tables(){
    return array('office','bank');
  }

  public function detail_tables(){
    $detail_tables = ['cheque_book'];

    if($this->session->system_admin || $this->config->item('link_new_project_allocations_only_to_default_bank_accounts')){
      $detail_tables[] = 'office_bank_project_allocation';
    }

    return $detail_tables;
  }

    public function master_table_visible_columns(){}

    public function master_table_hidden_columns(){}

    public function list_table_visible_columns(){}

    public function list_table_hidden_columns(){}

    public function detail_list_table_visible_columns(){
      return ['office_bank_track_number','office_bank_name','office_bank_is_active',
      'office_bank_account_number','office_name','bank_name','office_bank_chequebook_size','office_bank_is_default','status_name','approval_name'];
    }

    public function detail_list_table_hidden_columns(){}

    public function single_form_add_visible_columns(){
      return [
        'office_name',
        'office_bank_name',
        'bank_name',
        'office_bank_account_number',
        // 'office_bank_is_default',
        'office_bank_chequebook_size'
      ];
    }

    function edit_visible_columns(){
      return [
        'office_name',
        'bank_name',
        'office_bank_name',
        'office_bank_account_number',
        'office_bank_is_default',
        'office_bank_is_active',
        'office_bank_chequebook_size',
        'office_bank_book_exemption_expiry_date'
        
      ];
    }

    public function single_form_add_hidden_columns(){}

    public function multi_form_add_visible_columns(){}

    public function multi_form_add_hidden_columns(){}

    function detail_list(){}

    function action_before_insert($post_array){

      // Always make a new office bank active and default
      $post_array['header']['office_bank_is_default'] = 1;
      $post_array['header']['office_bank_is_active'] = 1;
      $office_id = $post_array['header']['fk_office_id'];


      // Disallow having 2 default banks per office
      //if($office_bank_is_default == 1){
        $this->make_exisiting_office_default_accounts_not_default($office_id);
      //}
   
      return $post_array;
    }

    function action_before_edit($post_array){
    
      $office_bank_id = hash_id($this->id, 'decode');
      $office_bank_is_default = $post_array['header']['office_bank_is_default'];
      $office_id = $post_array['header']['fk_office_id'];
      $office_bank_id = hash_id($this->id,'decode');
      $office_bank_is_active = $post_array['header']['office_bank_is_active'];

      $exemption_date = $post_array['header']['office_bank_book_exemption_expiry_date'];

      $post_array['header']['office_bank_book_exemption_expiry_date'] = $exemption_date == '' ? null : $exemption_date;

      // Prevent deactivating an account with a non zero balance
      $this->load->model('voucher_model');
      $reporting_month = date('Y-m-01',strtotime($this->voucher_model->get_voucher_date($office_id)));
      $account_balance = $this->office_bank_account_balance($office_bank_id, $reporting_month);

      if($account_balance > 0 && $this->has_office_bank_field_been_changed($office_bank_id, 'office_bank_is_active', $office_bank_is_active) && !$office_bank_is_active){
        return ['error' => get_phrase('disallow_office_bank_deactivation_with_funds','You are not allowed to deactivate office bank with a balance greater than zero.')];
      }

      // Prevent deactivating a default account. You can only deactivate a default account by creating another default account 
      if($this->has_office_bank_field_been_changed($office_bank_id, 'office_bank_is_default', $office_bank_is_default) && !$office_bank_is_default && $office_bank_is_active){
        return ['error' => get_phrase('disallow_default_office_bank_deactivation', 'You are not allowed to deactivate a default office bank unless you create another default office bank account')];
      }

      // Prevent deactivating an office bank when there not other office bank that is active and default
      $this->read_db->where(array('fk_office_id' => $office_id, 'office_bank_is_active' => 1, 'office_bank_is_default' => 1, 'office_bank_id <> ' => $office_bank_id));
      $count_other_default_office_bank_accounts = $this->read_db->get('office_bank')->num_rows();

      if($count_other_default_office_bank_accounts == 0 && $this->has_office_bank_field_been_changed($office_bank_id, 'office_bank_is_active', $office_bank_is_active) && !$office_bank_is_active){
        return ['error' => get_phrase('disallow_editing_office_bank_with_missing_other_default', 'You are not allowed to deactivate an office bank when there is no other default office bank present')];
      }

      // Prevent office from having no default office bank account
      if($count_other_default_office_bank_accounts == 0 && $this->has_office_bank_field_been_changed($office_bank_id, 'office_bank_is_active', $office_bank_is_default) && !$office_bank_is_default){
        return ['error' => get_phrase('disallow_editing_office_bank_with_missing_other_default', 'You must have at least one default office bank account. This change cannot be accepted.')];
      }

      // Disallow having 2 default banks per office
      if($office_bank_is_default == 1){
        $this->make_exisiting_office_default_accounts_not_default($office_id);
      }

      // Disallow editing bank name for an already used office bank account
      if(!$this->is_editing_office_bank_allowable($office_bank_id, $post_array['header'])){
        return ['error' => get_phrase('disallow_office_bank_edit','You are not allowed to edit the bank name and office name when the office bank is already used in vouchers')];
      }
   
      return $post_array;
      
  }

  private function has_office_bank_field_been_changed($office_bank_id, $field_name, $field_new_value){

    $has_office_bank_field_been_changed = false;

    $this->read_db->select(array($field_name));
    $this->read_db->where(array('office_bank_id' => $office_bank_id));
    $old_value = $this->read_db->get('office_bank')->row()->$field_name;

    if($old_value != $field_new_value){
      $has_office_bank_field_been_changed = true;
    }

    return $has_office_bank_field_been_changed;
  }

  private function is_editing_office_bank_allowable($office_bank_id, $current_office_bank_fields){
    $is_allowable = true;

    $current_office_bank = $this->get_office_bank_by_id($office_bank_id);
    $bank_account_is_used_in_vouchers = $this->check_if_bank_account_is_used_in_vouchers($office_bank_id);

    $current_office_id = $current_office_bank['fk_office_id'];
    $current_bank_id = $current_office_bank['fk_bank_id'];

    $new_office_id = $current_office_bank_fields['fk_office_id'];
    $new_bank_id = $current_office_bank_fields['fk_bank_id'];

    $is_office_changed = $current_office_id != $new_office_id ? true : false;
    $is_bank_changed = $current_bank_id != $new_bank_id ? true : false;

    // log_message('error', json_encode(['bank_account_is_used_in_vouchers' => $bank_account_is_used_in_vouchers, 'is_office_changed' => $is_office_changed, 'is_bank_changed' => $is_bank_changed]));

    if($bank_account_is_used_in_vouchers && ($is_office_changed  || $is_bank_changed)){
      $is_allowable = false;
    }

    return $is_allowable;
  }


  private function get_office_bank_by_id($office_bank_id){

    $this->read_db->where(array('office_bank_id' => $office_bank_id));
    $office_bank_obj = $this->read_db->get('office_bank');

    $office_bank = [];

    if($office_bank_obj->num_rows() > 0){
      $office_bank = $office_bank_obj->row_array();
    }
    
    return $office_bank;
  }


    function make_exisiting_office_default_accounts_not_default($office_id){

      $data['office_bank_is_default'] = 0;
      $this->write_db->where(array('fk_office_id' => $office_id, 'office_bank_is_default' => 1));
      $this->write_db->update('office_bank', $data);
    }

    function action_after_edit($post_array, $approval_id, $header_id){
      return $this->create_office_bank_project_association($post_array, $approval_id, $header_id);
    }

    function action_after_insert($post_array, $approval_id, $header_id){
      return $this->create_office_bank_project_association($post_array, $approval_id, $header_id);
    }

    function create_office_bank_project_association($post_array, $approval_id, $header_id) {
      // Create contra accounts for the newly added bank account

      $bank_to_bank_contra_effects = $this->read_db->get('voucher_type_effect')->result_array();

      $this->write_db->select(array('office_name','fk_account_system_id'));
      $this->write_db->join('office_bank','office_bank.fk_office_id=office.office_id');
      $this->write_db->where(array('office_bank_id'=>$header_id));
      $office_info = $this->write_db->get('office')->row();

      $this->write_db->trans_start();

      foreach($bank_to_bank_contra_effects as $bank_to_bank_contra_effect){

        if(
            $bank_to_bank_contra_effect['voucher_type_effect_code'] == 'bank_contra' ||
            $bank_to_bank_contra_effect['voucher_type_effect_code'] == 'cash_contra' ||
            $bank_to_bank_contra_effect['voucher_type_effect_code'] == 'bank_to_bank_contra' || 
            $bank_to_bank_contra_effect['voucher_type_effect_code'] == 'cash_to_cash_contra' 
          ){  

              $contra_account_name = '';
              $contra_account_code = '';
              $voucher_type_account_id = 0;
              //$voucher_type_effect_id = 0;

              if($bank_to_bank_contra_effect['voucher_type_effect_code'] == 'bank_contra'){
                $contra_account_name = $office_info->office_name." Bank to Cash";
                $contra_account_code = "B2C"; 
                $voucher_type_account_id = $this->read_db->get_where('voucher_type_account',
                array('voucher_type_account_code'=>'bank'))->row()->voucher_type_account_id;

              }elseif($bank_to_bank_contra_effect['voucher_type_effect_code'] == 'cash_contra'){
                $contra_account_name = $office_info->office_name." Cash to Bank";
                $contra_account_code = "C2B";
                $voucher_type_account_id = $this->read_db->get_where('voucher_type_account',
                array('voucher_type_account_code'=>'cash'))->row()->voucher_type_account_id;

              }elseif($bank_to_bank_contra_effect['voucher_type_effect_code'] == 'bank_to_bank_contra'){
                $contra_account_name = $office_info->office_name." Bank to Bank";
                $contra_account_code = "B2B";
                $voucher_type_account_id = $this->read_db->get_where('voucher_type_account',
                array('voucher_type_account_code'=>'bank'))->row()->voucher_type_account_id;

              }elseif($bank_to_bank_contra_effect['voucher_type_effect_code'] == 'cash_to_cash_contra'){
                $contra_account_name = $office_info->office_name." Cash to Cash";
                $contra_account_code = "C2C";
                $voucher_type_account_id = $this->read_db->get_where('voucher_type_account',
                array('voucher_type_account_code'=>'cash'))->row()->voucher_type_account_id;

              }


              $contra_account_record['contra_account_track_number'] = $this->grants_model->generate_item_track_number_and_name('contra_account')['contra_account_track_number'];
              $contra_account_record['contra_account_name'] = $contra_account_name;
              $contra_account_record['contra_account_code'] = $contra_account_code;
              $contra_account_record['contra_account_description'] = $contra_account_name;;
              $contra_account_record['fk_voucher_type_account_id'] = $voucher_type_account_id;//$voucher_type_account['voucher_type_account_id'];
              $contra_account_record['fk_voucher_type_effect_id'] = $bank_to_bank_contra_effect['voucher_type_effect_id'];
              $contra_account_record['fk_office_bank_id'] = $header_id;
              $contra_account_record['fk_account_system_id'] = $office_info->fk_account_system_id;

              $contra_account_data_to_insert = $this->grants_model->merge_with_history_fields('contra_account',$contra_account_record,false);

              $contra_account_count = $this->read_db->get_where('contra_account',array('fk_office_bank_id' => $header_id, 'contra_account_code' => $contra_account_code))->num_rows();

              if($contra_account_count == 0){
                $this->write_db->insert('contra_account',$contra_account_data_to_insert);
              }

            
        }

      }


      $this->create_default_project_allocation_and_link_to_account($post_array, $approval_id, $header_id);

      $this->deactivate_active_cheque_books_for_deactivated_office_bank($header_id, $post_array);

      $this->write_db->trans_complete();

      if ($this->write_db->trans_status() === FALSE)
        {
          return false;
        }else{
          return true;
        }
    }

  /**
   * get_active_cash_accounts(): get json string of voucher types
   * @author  Livingstone Onduso
   * @dated: 5/06/2023
   * @access public
   * @return void
   * @param int $account_system_id
   */
  public function get_active_office_bank(int $office_id){

    $this->read_db->select(['office_bank_id','office_bank_name']);
    $this->read_db->where(['office_bank_is_active'=>1,'fk_office_id'=>$office_id]);
    $office_bank=$this->read_db->get('office_bank')->result_array();
    return $office_bank;

  }

  /**
   * get_active_recipient_bank(): get json string of voucher types
   * @author  Livingstone Onduso
   * @dated: 5/06/2023
   * @access public
   * @return void
   * @param int $fk_voucher_id
   */
  public function get_active_recipient_bank(int $fk_voucher_id){

    $this->read_db->select(['office_bank_id','office_bank_name']);
    $this->read_db->join('cash_recipient_account','cash_recipient_account.fk_office_bank_id =office_bank.office_bank_id');
    $this->read_db->where(['office_bank_is_active'=>1,'fk_voucher_id'=>$fk_voucher_id]);
    $recipient_bank=$this->read_db->get('office_bank')->result_array();
    return $recipient_bank;

  }

    /**
     * deactivate_active_cheque_books_for_deactivated_office_bank
     * 
     * @author Nicodemus Karisa Mwambire
     * @reviewer None
     * @reviewed_date None
     * @bug - DE2376
     * @access private
     * 
     * @param int $office_bank_id - Id of the office bank being edited
     * @param array $post_array - Edit data
     * 
     * Deactive active cheque book and toggle the office bank to non default when deactivating an office bank
     * 
     * @return void
     */

    private function deactivate_active_cheque_books_for_deactivated_office_bank(int $office_bank_id, array $post_array) : void
    {

      if(isset($post_array['office_bank_is_active']) && $post_array['office_bank_is_active'] == 0){

        $cheque_book_data['cheque_book_is_active'] = 0;
        $this->write_db->where(array('fk_office_bank_id' => $office_bank_id));
        $this->write_db->update('cheque_book', $cheque_book_data);

        $office_bank_data['office_bank_is_default'] = 0;
        $this->write_db->where(array('office_bank_id' => $office_bank_id));
        $this->write_db->update('office_bank', $office_bank_data);

      }
    }

    // Deactivate non default office banks for a given office and their cheque books if they have a zero bank balance
    function deactivate_non_default_office_bank_by_office_id($office_id, $reporting_month){

      $this->load->model('cheque_book_model');

      $office_bank_ids = [];

      $this->read_db->select(array('office_bank_id'));
      $this->read_db->where(array('fk_office_id' => $office_id, 'office_bank_is_default' => 0, 'office_bank_is_active' => 1));
      $office_bank_obj = $this->read_db->get('office_bank');

      if($office_bank_obj->num_rows() > 0){
        $office_bank_ids = array_column($office_bank_obj->result_array(),'office_bank_id');

        for($i = 0; $i < count($office_bank_ids); $i++){
          
          $account_balance = $this->office_bank_account_balance($office_bank_ids[$i], $reporting_month);
          
          if($account_balance != 0){
            unset($office_bank_ids[$i]);
          }
        }
      }

      $this->write_db->trans_start();
      
      // log_message('error', json_encode($office_bank_ids));

      if(!empty($office_bank_ids)){
        $data['office_bank_is_active'] = 0;
        $this->write_db->where_in('office_bank_id', $office_bank_ids);
        $this->write_db->update('office_bank', $data);
  
        if($this->write_db->affected_rows()){
          $update_data['cheque_book_is_active'] = 0;
          $this->write_db->where_in('fk_office_bank_id', $office_bank_ids);
          $this->write_db->update('cheque_book',$update_data);
        }
      }

      $this->write_db->trans_complete();

      if($this->write_db->trans_status() == false){

      }

    }

    function create_default_project_allocation_and_link_to_account($post_array, $approval_id, $header_id){
      // log_message('error', json_encode($header_id));
      // Check if the bank account is default
      $office_bank_is_default = $post_array['office_bank_is_default'];
      $office_id = $post_array['fk_office_id'];

      if($office_bank_is_default){

        $this->read_db->where(array('fk_office_bank_id' => $header_id));
        $office_bank_allocations = $this->read_db->get('office_bank_project_allocation')->num_rows();

        if($office_bank_allocations == 0){

           // Get all allocations for a give office
          $this->read_db->join('project','project.project_id=project_allocation.fk_project_id');
          // $this->read_db->where(array('project_is_default'=>1,'fk_office_id'=>$office_id));
          $this->read_db->where(array('fk_office_id'=>$office_id));
          $office_project_allocation_object = $this->read_db->get_where('project_allocation');

            if($office_project_allocation_object->num_rows() > 0){
              // Link all the allocations for the default project to the bank account
              foreach($office_project_allocation_object->result_array() as $project_allocation){
                $office_bank_project_allocation['office_bank_project_allocation_name'] = $this->grants_model->generate_item_track_number_and_name('office_bank_project_allocation')['office_bank_project_allocation_name'];
                $office_bank_project_allocation['office_bank_project_allocation_track_number'] = $this->grants_model->generate_item_track_number_and_name('office_bank_project_allocation')['office_bank_project_allocation_track_number'];
                $office_bank_project_allocation['fk_office_bank_id'] = $header_id;
                $office_bank_project_allocation['fk_project_allocation_id'] = $project_allocation['project_allocation_id'];
                
                $office_bank_project_allocation_data_to_insert = $this->grants_model->merge_with_history_fields('office_bank_project_allocation',$office_bank_project_allocation,false);
                $this->write_db->insert('office_bank_project_allocation',$office_bank_project_allocation_data_to_insert);
    
              }
            }else{
              // If allocation are missing, create them and link
    
              $account_system_id = $this->read_db->get_where('office',
              array('office_id'=>$office_id))->row()->fk_account_system_id;
    
              $this->read_db->join('funder','funder.funder_id=project.fk_funder_id');
              $this->read_db->where(array('fk_account_system_id'=>$account_system_id,'project_is_default'=>1));
              $default_project_obj = $this->read_db->get('project');
    
              //echo json_encode($default_project_obj->result_array());exit;
    
              if($default_project_obj->num_rows() > 0){
                foreach($default_project_obj->result_array() as $project){
                  $project_allocation['project_allocation_track_number'] = $this->grants_model->generate_item_track_number_and_name('project_allocation')['project_allocation_track_number'];
                  $project_allocation['project_allocation_name'] = $this->grants_model->generate_item_track_number_and_name('project_allocation')['project_allocation_name'];
                  $project_allocation['fk_project_id'] = $project['project_id'];
                  $project_allocation['project_allocation_amount'] = 0;
                  $project_allocation['project_allocation_is_active'] = 1;
                  $project_allocation['fk_office_id'] = $office_id;
    
                  $project_allocation_data_to_insert = $this->grants_model->merge_with_history_fields('project_allocation',$project_allocation,false);
                  $this->write_db->insert('project_allocation',$project_allocation_data_to_insert);
    
                  $project_allocation_id = $this->write_db->insert_id();
    
                  $office_bank_project_allocation_inner['office_bank_project_allocation_name'] = $this->grants_model->generate_item_track_number_and_name('office_bank_project_allocation')['office_bank_project_allocation_name'];
                  $office_bank_project_allocation_inner['office_bank_project_allocation_track_number'] = $this->grants_model->generate_item_track_number_and_name('office_bank_project_allocation')['office_bank_project_allocation_track_number'];
                  $office_bank_project_allocation_inner['fk_office_bank_id'] = $header_id;
                  $office_bank_project_allocation_inner['fk_project_allocation_id'] = $project_allocation_id;
                  
                  $office_bank_project_allocation_inner_data_to_insert = $this->grants_model->merge_with_history_fields('office_bank_project_allocation',$office_bank_project_allocation_inner,false);
                  $this->write_db->insert('office_bank_project_allocation',$office_bank_project_allocation_inner_data_to_insert);
    
                }
              }
    
            }
    
          }
        }
    }

  
    function get_office_banks($office_id){

      $this->read_db->select(array('office_bank_id','office_bank_name'));
      $this->read_db->where(array('fk_office_id'=>$office_id));
      $office_banks = $this->read_db->get('office_bank')->result_array();

      return $office_banks;
    }
 /**
   * get_active_office_banks(): Returns an array of active banks
   * @author  Livingstone Onduso
   * @dated: 5/03/2024
   * @access public
   * @return array
   * @param int $office_id
   */
    function get_active_office_banks(int $office_id){
      $this->read_db->select(array('office_bank_id','office_bank_name'));
      $this->read_db->where(['fk_office_id'=>$office_id,'office_bank_is_active'=>1]);
      //$this->read_db->where(['office_bank_id <>'=>$recipient_bank_id,'office_bank_is_active'=>1]);

      
      $office_banks = $this->read_db->get('office_bank')->result_array();

      return $office_banks;
    }

    function get_cheque_book_size($office_bank_id){

      $this->read_db->select(array('office_bank_chequebook_size'));
      $this->read_db->where(array('office_bank_id' => $office_bank_id));
      $office_bank_chequebook_size_obj = $this->read_db->get('office_bank');
  
      $office_bank_chequebook_size = 100;
  
      if($office_bank_chequebook_size_obj->num_rows() > 0){
        $office_bank_chequebook_size = $office_bank_chequebook_size_obj->row()->office_bank_chequebook_size;
      }
  
      return $office_bank_chequebook_size;
    }

    /**
     * get_office_banks_for_office
     * 
     * @author Nicodemus Karisa Mwambire
     * @reviewed_by None
     * @reviewed_date None
     * @access public
     * 
     * @param int $office_id - Office Id
     * 
     * @return array - List of office banks grouped in require_chequebook, is_active and is_default keys
     * 
     * @todo:
     * Ready for Peer Review
     */

    public function get_office_banks_for_office($office_id): array {

      // $office_banks = [];

      $office_banks['chequebook_exemption_expiry_date'] = [];
      $office_banks['is_active'] = [];
      $office_banks['is_default'] = [];

      // Check if the Office has atleast one bank that requires a cheque book
      $this->read_db->where(array('fk_office_id' => $office_id));

      $office_banks_obj = $this->read_db->get('office_bank');

      if($office_banks_obj->num_rows()){
        $office_bank_raw = $office_banks_obj->result_array();

        foreach($office_bank_raw as $office_bank){
          $chequebook_exemption_expiry_date = isset($office_bank['office_bank_book_exemption_expiry_date']) ? $office_bank['office_bank_book_exemption_expiry_date']: NULL;
          $is_active = $office_bank['office_bank_is_active'];
          $is_default = $office_bank['office_bank_is_default'];

          if($chequebook_exemption_expiry_date != NULL){
            $office_banks['chequebook_exemption_expiry_date'][$office_bank['office_bank_id']] = $chequebook_exemption_expiry_date;
          }

          if($is_active){
            $office_banks['is_active'][$office_bank['office_bank_id']] = $is_active;
          }

          if($is_default){
            $office_banks['is_default'][$office_bank['office_bank_id']] = $is_default;
          }
        }
      }

      return $office_banks;
    }

    public function check_if_bank_account_is_used_in_vouchers($office_bank_id){

      $is_bank_account_used = true;
  
      $this->read_db->where(array('fk_office_bank_id' => $office_bank_id));
      $count_vouchers_using_bank_account = $this->read_db->get('voucher')->num_rows();
  
      if(!$count_vouchers_using_bank_account){
        $is_bank_account_used = false;
      }
  
      return $is_bank_account_used;
    }

    function office_bank_account_balance($office_bank_id, $reporting_month){
      
      $office_id = 0;

      $this->load->model('financial_report_model');

      $this->read_db->select(array('fk_office_id as office_id'));
      $this->read_db->where(array('office_bank_id' => $office_bank_id));
      $office_bank_obj = $this->read_db->get('office_bank');

      if($office_bank_obj->num_rows() > 0){
        $office_id = $office_bank_obj->row()->office_id;
      }

      $account_balance = $this->financial_report_model->compute_cash_at_bank([$office_id], $reporting_month, [], [$office_bank_id]);

      if($account_balance > -1 && $account_balance < 1){
        $account_balance = 0;
      }

      return $account_balance;
    }

    public function is_office_bank_obselete($office_bank_id, $reporting_month){
      // Office bank acount becomes obselete when all these conditions are met:
      // 1. Should not have funds
      // 2. Should not have outstanding cheques and deposit in transit
      // 3. Should not have vouchers in the given month
      // 4. Should be Inactive ***

      $this->read_db->where(array('office_bank_id' => $office_bank_id));
      $office_id = $this->read_db->get('office_bank')->row()->fk_office_id;

      $is_office_bank_obselete = false;

      $account_balance = $this->office_bank_account_balance($office_bank_id, $reporting_month);
      $office_bank_outstanding_cheques = $this->office_bank_outstanding_cheques($office_id, $reporting_month);
      $office_bank_transit_deposit = $this->office_bank_transit_deposit($office_id, $reporting_month);

      $office_bank_outstanding_cheques_amount = 0;

      if(!empty($office_bank_outstanding_cheques)){
        $office_bank_outstanding_cheques_amount = array_sum(array_column($office_bank_outstanding_cheques, 'amount'));
      }

      $office_bank_transit_deposit_amount = 0;

      if(!empty($office_bank_transit_deposit)){
        $office_bank_transit_deposit_amount = array_sum(array_column($office_bank_transit_deposit, 'amount'));
      }

      $office_bank_has_transaction_in_month = $this->office_bank_has_transaction_in_month($office_bank_id, $reporting_month);

      if($account_balance == 0 && $office_bank_outstanding_cheques_amount == 0 && $office_bank_transit_deposit_amount == 0 && !$office_bank_has_transaction_in_month){
        $is_office_bank_obselete = true;
      }

      // log_message('error', json_encode(['account_balance' => $account_balance, 'office_bank_outstanding_cheques_amount' => $office_bank_outstanding_cheques_amount, 'office_bank_transit_deposit_amount' => $office_bank_transit_deposit_amount, 'office_bank_has_transaction_in_month' => $office_bank_has_transaction_in_month]));

      return $is_office_bank_obselete;
    }

    private function office_bank_has_transaction_in_month($office_bank_id, $reporting_month){

      $office_bank_has_transaction_in_month = false;

      $start_month_date = date('Y-m-01', strtotime($reporting_month));
      $end_month_date = date('Y-m-t', strtotime($reporting_month));

      $this->read_db->where(array('voucher_date >= ' => $start_month_date, 'voucher_date <= ' => $end_month_date, 'fk_office_bank_id' => $office_bank_id));
      $count_of_vouchers = $this->read_db->get('voucher')->num_rows();

      $office_bank_has_transaction_in_month = $count_of_vouchers > 0;

      return $office_bank_has_transaction_in_month;

    }

    private function office_bank_outstanding_cheques($office_id, $reporting_month){
      $outstanding_cheques = [];
      $reporting_month = date('Y-m-01', strtotime($reporting_month));

      $this->load->model('financial_report_model');

      $outstanding_cheques = $this->financial_report_model->list_oustanding_cheques_and_deposits([$office_id], $reporting_month, 'expense', 'bank_contra', 'bank');

      return $outstanding_cheques;
    }

    private function office_bank_transit_deposit($office_id, $reporting_month){
      $deposit_in_transit = [];
      $reporting_month = date('Y-m-01', strtotime($reporting_month));

      $this->load->model('financial_report_model');

      $deposit_in_transit = $this->financial_report_model->list_oustanding_cheques_and_deposits([$office_id], $reporting_month, 'income', 'cash_contra', 'bank');

      return $deposit_in_transit;
    }

    function get_active_office_banks_by_reporting_month($office_ids, $reporting_month, $project_ids = [], $office_bank_ids = [])
  {

    $this->load->model('financial_report_model');

    $office_banks = $this->financial_report_model->get_office_banks($office_ids, $project_ids, $office_bank_ids);
  
    // log_message('error', json_encode($office_banks));
    $office_banks_array = [];

    $cnt = 0;
    for($i = 0; $i < count($office_banks); $i++){
      $is_office_bank_obselete = $this->is_office_bank_obselete($office_banks[$i]['office_bank_id'], $reporting_month);
      
      if(!$is_office_bank_obselete){
        // unset($office_banks[$i]);
        $office_banks_array[$cnt] = $office_banks[$i];
        $cnt++;
      }
    }
    
    return $office_banks_array;
  }

    function master_view(){}

    public function list(){}

    public function view(){}
    
}
