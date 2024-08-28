<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Contra_account_model extends MY_Model{

    public $table = 'Contra_account'; 
    public $dependant_table = '';
    public $name_field = 'Contra_account_name';
    public $create_date_field = "Contra_account_created_date";
    public $created_by_field = "Contra_account_created_by";
    public $last_modified_date_field = "Contra_account_last_modified_date";
    public $last_modified_by_field = "Contra_account_last_modified_by";
    public $deleted_at_field = "Contra_account_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array('account_system','voucher_type_account','office_bank');
    }

    public function detail_tables(){

    }

    // function list_table_visible_columns(){
    //     return ['contra_account_track_number','contra_account_name','contra_account_code','contra_account_description','voucher_type_account_name'];
    // }

    // function lookup_values(){

    //     // $lookup_values['office_bank'] = $this->read_db->get('office_bank')->result_array(); 

    //     // if(!$this->session->system_admin){

    //     //     $this->read_db->join('bank','bank.bank_id=office_bank.fk_bank_id');
    //     //     $this->read_db->join('account_system','account_system.account_system_id=bank.fk_account_system_id');
    //     //     $lookup_values['office_bank'] = $this->read_db->get_where('office_bank',array('account_system_code'=>$this->session->user_account_system))->result_array();
    //     // }
  
    //     // return $lookup_values;
    //   }
  
    public function single_form_add_visible_columns(){
        //return ['contra_account_name','contra_account_code','contra_account_description','account_system_name','voucher_type_account_name','office_bank_name'];
    }

    public function detail_multi_form_add_visible_columns(){}

    function multi_select_field(){
        return "office_bank";
    }

    // function transaction_validate_duplicates_columns(){
    //     return ['voucher_type_account','office_bank'];
    // }

    function show_add_button(){
        if(!$this->session->system_admin){
            return false;
        }else{
            return true;
        }
    }


    function add_contra_account($office_bank_id){
        // Create contra accounts for the newly added bank account
        
        $bank_to_bank_contra_effects = $this->read_db->get('voucher_type_effect')->result_array();
  
        $this->write_db->select(array('office_name','fk_account_system_id'));
        $this->write_db->join('office_bank','office_bank.fk_office_id=office.office_id');
        $this->write_db->where(array('office_bank_id'=>$office_bank_id));
        $office_info_obj = $this->write_db->get('office');

        $this->write_db->trans_start();

        if($office_info_obj->num_rows() > 0){
          $office_info = $office_info_obj->row();
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
                  
             
                  $this->read_db->where(
                    [
                      'fk_voucher_type_account_id'=>$voucher_type_account_id,
                      'fk_voucher_type_effect_id'=>$bank_to_bank_contra_effect['voucher_type_effect_id'],
                      'fk_office_bank_id'=>$office_bank_id,
                      'fk_account_system_id'=>$office_info->fk_account_system_id]
                    );
                  $contra_account_obj = $this->read_db->get('contra_account');
                  
                  if($contra_account_obj->num_rows() == 0){
                      $contra_account_record['contra_account_track_number'] = $this->grants_model->generate_item_track_number_and_name('contra_account')['contra_account_track_number'];
                      $contra_account_record['contra_account_name'] = $contra_account_name;
                      $contra_account_record['contra_account_code'] = $contra_account_code;
                      $contra_account_record['contra_account_description'] = $contra_account_name;;
                      $contra_account_record['fk_voucher_type_account_id'] = $voucher_type_account_id;//$voucher_type_account['voucher_type_account_id'];
                      $contra_account_record['fk_voucher_type_effect_id'] = $bank_to_bank_contra_effect['voucher_type_effect_id'];
                      $contra_account_record['fk_office_bank_id'] = $office_bank_id;
                      $contra_account_record['fk_account_system_id'] = $office_info->fk_account_system_id;
                      $contra_account_record['contra_account_created_by'] = $this->session->user_id;
                      $contra_account_record['contra_account_last_modified_by'] = $this->session->user_id;
      
                      $contra_account_data_to_insert = $this->grants_model->merge_with_history_fields('contra_account',$contra_account_record,false);
                      $this->write_db->insert('contra_account',$contra_account_data_to_insert);
                  }
                
             }
    
          }
        }
  
        $this->write_db->trans_complete();
  
        if ($this->write_db->trans_status() === FALSE)
          {
            return false;
          }else{
            return true;
          }
      }

}