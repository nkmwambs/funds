<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 *  @package grants <Finance management system for NGOs>
 *	@author  Livingstone Onduso <londuso@ke.ci.org>
 *	@date		 20th Aug, 2021
 *  @method void __construct() main method, first to be executed and initializes variables.
 *  @method void index() has no method body.
 *  @method void get_menu_list() empty method.
 *  @method void upload_csv_data_to_table() uploads csv document containing participants.
 *  @method array result($id) retunrs results.
 *  @method array columns() returns columns for rendering on list page.
 *  @method void check_if_connect_id_exists() checks if connect id exists.
 *  @method void medical_claim_scenerios() returns the settings of reimbursement claim.
 *  @method void reimbursement_illiness_category() helps to render illinesses category with ajax call.
 *	@see https://techsysnow.com
 */

class Reimbursement_claim extends MY_Controller
{

  /**
   * __construct(): This is the primary or main method that initializes variables to be used other methods
   * @author Livingstone Onduso
   * @access public
   * @return not applicable
   */

  public function __construct()
  {
    parent::__construct();

    $this->load->library('medical_claim_library');

    $this->load->model('reimbursement_claim_model');

    $this->load->model('medical_claim_setting_model');

    $this->load->model('country_currency_model');

    $this->load->model('health_facility_model');

    $this->load->library('Aws_attachment_library');
  }

   /**
   * index(): empty method <no body>
   * @author Livingstone Onduso
   * @access public
   * @return void
   */

  public function index():void
  {
    //Empty
  }

  /**
   * result(): This method return an array of columns to be used on list page
   * @author Livingstone Onduso
   * @access public
   * @return array
   * @param int The ID passed from the url segment
   */

  public function result($id = ''):array
  {

    if ($this->action == 'single_form_add') {

      $fcps_arr = $this->reimbursement_claim_model->get_fcp_number();

      $allowed_claim_days= $this->reimbursement_claim_model->get_country_medical_settings(3);

      $caregiver_amount= $this->reimbursement_claim_model->fcp_rembursable_amount_from_caregiver();

      $result['caregiver_contribution_flag'] = $this->reimbursement_claim_model->get_country_medical_settings(1);
      
      $result['national_health_cover_flag'] = $this->reimbursement_claim_model->get_country_medical_settings(2);
      $result['country_medical_settings_allowed_claimable_days'] =$allowed_claim_days;
      $result['minmum_rembursable_amount'] = $this->reimbursement_claim_model->get_country_medical_settings(5);
      $result['user_fcps'] = $fcps_arr;
      $result['country_currency_code'] = $this->country_currency_model->get_country_currency_code();
      $result['health_facility_types'] = $this->reimbursement_claim_model->pull_health_facility_types();
      $result['vouchers_and_total_costs'] = $this->reimbursement_claim_model->get_vouchers_for_medical_claim();
      $result['already_reimbursed_amount'] = $this->reimbursement_claim_model->get_already_reimbursed_amount();
      $result['fcp_rembursable_amount_from_caregiver'] =$caregiver_amount;

      $result['reimbursement_app_types']=$this->reimbursement_claim_model->reimbursement_app_types();

      return $result;
    } elseif ($this->action == 'view') {

      $reimbursement_claim_id = hash_id($this->id, 'decode');

      $attachments=$this->reimbursement_claim_model->get_reimbursement_claim_attachments();

      $reimbursement_data=$this->reimbursement_claim_model->get_medical_claim_for_an_office($reimbursement_claim_id);

      $result['reimbursement_claim_attachments'] = $attachments;

      $result['status_data'] = $this->general_model->action_button_data($this->controller);

      $result['reimbursement_claim_data']=$reimbursement_data;

      return $result;

    } elseif ($this->action == 'list') {

      $status_data = $this->general_model->action_button_data($this->controller);

      $attachments=$this->reimbursement_claim_model->get_reimbursement_claim_attachments();

      $columns = $this->columns();
      array_shift($columns);
      $result['columns'] = $columns;
      $result['has_details_table'] = false;
      $result['has_details_listing'] = false;
      $result['is_multi_row'] = false;
      $result['show_add_button'] = true;
      $result['status_data'] =  $status_data;
      $result['reimbursement_claim_data'] = $this->reimbursement_claim_model->get_medical_claim_for_an_office();
      $result['reimbursement_claim_attachments'] = $attachments;
      $result['initial_status'] = $this->grants_model->initial_item_status('reimbursement_claim');

      //Filter records details [Get clusters and status]
      $result['clusters'] = $this->reimbursement_claim_model->get_user_clusters(true);
      
      return $result;
    } elseif ($this->action == 'edit') {


      $allowed_claim_days=$this->reimbursement_claim_model->get_country_medical_settings(3);

      $result['medical_info'] = $this->get_reimbursement_claim_record_to_edit();
      $result['country_medical_settings_allowed_claimable_days'] = $allowed_claim_days;
      $result['health_facility_types'] = $this->reimbursement_claim_model->pull_health_facility_types();

      return $result;
    } else {
      return parent::result($id = '');
    }
  }

   /**
   * check_if_connect_id_exists(): This method verifies if the connect id exists
   * @author Livingstone Onduso
   * @access public
   * @return void
   * @param int
   */

  public function check_if_connect_id_exists(int $connect_incident_id=0):void
  {

   echo json_encode($this->reimbursement_claim_model->check_if_connect_id_exists($connect_incident_id));

}
  /**
   * medical_claim_scenerios(): This method calls a model and renders the settings for claims for a given country
   * @author Livingstone Onduso
   * @access public
   * @return void
   * @param float $voucher_amount: passes amount on voucher;
   *        float $claim_amount: amount to claim;
   *        string $card_number: insurance number
   */

  public function medical_claim_scenerios(float $v_amount, float $claim_amount, string $card_number = ''):void
  {

    $bal_amt_on_voucher = $v_amount;

    $amt_to_claim = $claim_amount;

    $card_no = $card_number;

    $settings=$this->reimbursement_claim_model->medical_claim_scenerios($bal_amt_on_voucher, $amt_to_claim, $card_no);

    echo json_encode($settings);
  }

  /**
   * reimbursement_illiness_category(): This method calls a model and renders the
   *                                    settings for claims for a given country
   * @author Livingstone Onduso
   * @access public
   * @return void
   * @param int $diagnosis_type: Passes the type of diagnosis;
   */

  public function reimbursement_illiness_category(int $diagnosis_type):void
  {
     
      echo json_encode($this->reimbursement_claim_model->reimbursement_illiness_category($diagnosis_type));
  }


  function get_medical_claim_attachment_by_Id($medical_id, $document_type, $support_doc_flag)
  {

    $attachments = $this->reimbursement_claim_model->get_reimbursement_claim_attachments($medical_id);

    $reconstruct_attachments_array = [];

    $array_column_url = array_column($attachments, 'attachment_url');

    $check_receipts_or_docs_exist = 0;

    //Check if docs already uploaded
    $check_receipts_or_docs_exist = $this->flag_up_if_medical_docs_uploaded($array_column_url, $support_doc_flag);

    //Loop and repopulate the array with attachements from the table to display
    foreach ($attachments as $attachment) {

      $explode_url = explode('/', $attachment['attachment_url']);

      //check receipts or support docs

      if (in_array($document_type, $explode_url)) {

        $attachment_url = $attachment['attachment_url'];

        $objectKey = $attachment_url . '/' . $attachment['attachment_name'];

        $url = $this->config->item('upload_files_to_s3') ? $this->grants_s3_lib->s3_preassigned_url($objectKey) : $this->attachment_library->get_local_filesystem_attachment_url($objectKey);

        $attachment['attachment_url'] = $url;

        $attachment['receipt_or_support_doc_flag'] = $check_receipts_or_docs_exist;


        $reconstruct_attachments_array[] = $attachment;
      }
    }
    echo json_encode($reconstruct_attachments_array);
  }

  private function flag_up_if_medical_docs_uploaded($array_column_url, $support_doc_flag = 0)
  {

    $receipts = 'false';
    $support_docs = 'false';

    $required_medical_documents_already_uploaded = 'false';

    foreach ($array_column_url as $url) {
      $url_exploded = explode('/', $url);

      if (in_array('receipts', $url_exploded)) {
        $receipts = 'true';
      } else if (in_array('support_documents', $url_exploded)) {
        $support_docs = 'true';
      }
    }

    if ($receipts == 'true' && $support_docs == 'true') {

      $required_medical_documents_already_uploaded = 'true';
    } else if ($receipts == 'true' && $support_docs == 'false' && $support_doc_flag == 0) {
      $required_medical_documents_already_uploaded = 'true';
    }

    return $required_medical_documents_already_uploaded;
  }

  //EDIT METHODS
  function get_reimbursement_claim_record_to_edit()
  {

    //Fetch medical data
    $medical_record = $this->reimbursement_claim_model->get_reimbursement_claim_record_to_edit();

    //Get office_id and Office_code
    $office_id = array_column($medical_record, 'office_id');
    $fcp_number = array_column($medical_record, 'office_code');

    $medical_id = array_column($medical_record, 'reimbursement_claim_id');

    //Get the beneficiary number and name
    $beneficiary_number = array_column($medical_record, 'reimbursement_claim_beneficiary_number');
    $beneficiary_name = array_column($medical_record, 'reimbursement_claim_name');

    //Diagnosis
    $diagnosis = array_column($medical_record, 'reimbursement_claim_diagnosis');
    //Treatment Date
    $treatment_date = array_column($medical_record, 'reimbursement_claim_treatment_date');
    //   //Facility Name
    $health_facility = array_column($medical_record, 'reimbursement_claim_facility');

    //Health Facility Id and Type
    $health_facility_type_id = array_column($medical_record, 'fk_health_facility_id');

    //support_documents_need_flag
    $support_documents_need_flag = array_column($medical_record, 'support_documents_need_flag');

    //reimbursement_claim_incident_id
    $connect_incident_id = array_column($medical_record, 'reimbursement_claim_incident_id');

    //fk_voucher_id
    $voucher_id = array_column($medical_record, 'fk_voucher_id');

    //Govt_insurance_number
    $govt_insurance_number = array_column($medical_record, 'reimbursement_claim_govt_insurance_number');

    //Caregiver_contribution
    $caregiver_contribution = array_column($medical_record, 'reimbursement_claim_caregiver_contribution');

    //reimbursement_claim_amount_reimbursed
    $amount_reimbursed = array_column($medical_record, 'reimbursement_claim_amount_reimbursed');


    return [
      'medical_id' => $medical_id,
      'fcp_no' => array_combine($office_id, $fcp_number),
      'beneficiary_info' => array_combine($beneficiary_number, $beneficiary_name),
      'diagnosis' => $diagnosis,
      'treatment_date' => $treatment_date,
      'health_facility' => $health_facility,
      'health_facility_type' => $health_facility_type_id,
      'support_documents_need_flag' => $support_documents_need_flag,
      'connect_incident_id' => $connect_incident_id,
      'fk_voucher_id' => $voucher_id,
      'govt_insurance_number' => $govt_insurance_number,
      'caregiver_contribution' =>  $caregiver_contribution,
      'amount_reimbursed' => $amount_reimbursed,
      'country_currency_code' => $this->country_currency_model->get_country_currency_code(),
      'vouchers_and_total_costs' => $this->reimbursement_claim_model->get_vouchers_for_medical_claim(),
      'already_reimbursed_amount' => $this->reimbursement_claim_model->get_already_reimbursed_amount(),
      'national_health_cover_card' => $this->reimbursement_claim_model->get_country_medical_settings(2),
      'percentage_caregiver_contribution' => $this->reimbursement_claim_model->get_country_medical_settings(1),
      'caregiver_contribution_with_health_cover_card_percentage' => $this->reimbursement_claim_model->get_country_medical_settings(7),
      'minimum_claimable_amount' => $this->reimbursement_claim_model->get_country_medical_settings(5),
      'reimburse_all_when_therhold_met' => $this->reimbursement_claim_model->get_country_medical_settings(6),
    ];
  }
  //END of EDIT methods

  function columns()
  {
    $columns = [
      'reimbursement_claim_id',
      'reimbursement_claim_track_number',
      'reimbursement_claim_name',
      'status_name'
    ];

    return $columns;
  }



  // function show_list(){



  //   echo json_encode('Records');
  // }


  function populate_cluster_name($office_id)
  {
    echo json_encode($this->reimbursement_claim_model->populate_cluster_name($office_id));
  }

  public function get_reimbursement_claim_attachments($medical)
  {
    //get attachments
    $medical_claims_attachments = $this->reimbursement_claim_model->get_reimbursement_claim_attachments($medical);

    echo json_encode($medical_claims_attachments);
  }


  public function upload_reimbursement_claims_documents()
  {

    $result = [];

    $doc_type = $this->input->post('document_type');
    $reimbursement_claim_id = $this->input->post('reimbursement_claim_id');
    $voucher_id = $this->input->post('store_voucher_number');

    $file = $this->input->post('file');

    //Get Office Code
    $fcp_ids = array_column($this->session->hierarchy_offices, 'office_id');
    $office_code = $this->reimbursement_claim_model->get_office_code($fcp_ids);

    //get voucher number
    $voucher_number = $this->reimbursement_claim_model->get_voucher_number_for_arow($voucher_id);

    // log_message('error',json_encode($office_code));

    if ($office_code['message'] == 1) {
      //Pass the store folder path
      $record_identfyer = lcfirst($doc_type) . '-' . $reimbursement_claim_id;
      $storeFolder = upload_url($this->controller, $record_identfyer, [$this->session->user_account_system, $office_code['fcps'], $voucher_number]);


      if (
        is_array($this->attachment_model->upload_files($storeFolder)) &&
        count($this->attachment_model->upload_files($storeFolder)) > 0
      ) {
        $result = $this->attachment_model->upload_files($storeFolder);
      }
    } else {
      //When user oversees more than one project
    }


    echo json_encode($result);
  }
  function get_support_needed_docs_flag($health_facility_id)
  {
    echo $this->health_facility_model->get_support_needed_docs_flag($health_facility_id);
  }

  public function filter_claims()
  {

    
    $data['fk_cluster_ids'] = $this->input->post('fk_cluster_id[]');
    $data['fk_status_ids'] = $this->input->post('status_id[]');

    $medical_claim_data_filtered = $this->reimbursement_claim_model->get_medical_claim_for_an_office('', $data['fk_cluster_ids'], $data['fk_status_ids']);

    $reimbursement_claim_attachments = $this->reimbursement_claim_model->get_reimbursement_claim_attachments();

    $status_data = $this->general_model->action_button_data($this->controller);

    $initial_status = $this->grants_model->initial_item_status('reimbursement_claim');

    $has_permission_for_add_claim_button=$this->user_model->check_role_has_permissions(ucfirst($this->controller), 'create');

    extract($status_data);

    echo draw_list_of_claims($medical_claim_data_filtered, $reimbursement_claim_attachments, $status_data, $item_status, $initial_status, $item_initial_item_status_id, [$item_max_approval_status_ids], 'myTable_filtered', $has_permission_for_add_claim_button);
  }
  //Delete Comments

  function delete_reimbursement_comment(){

    $comment_id=$this->input->post('reimbursement_comment_id');

    echo $this->reimbursement_claim_model->delete_reimbursement_comment($comment_id);

}
  public function  get_reimbursement_comments($reimbursement_id){

    $reimbursement_comments=$this->reimbursement_claim_model->get_reimbursement_comments($reimbursement_id);

    if(count($reimbursement_comments)>0){

      echo json_encode($reimbursement_comments);

    }else{
      echo 0;
    }
    
    
  }
  public function add_reimbursement_comment()
  {

    
    $data['fk_reimbursement_claim_id'] = $this->input->post('fk_reimbursement_claim_id');
    $data['reimbursement_comment_detail'] = $this->input->post('reimbursement_comment_detail');
    $data['reimbursement_comment_created_by'] = $this->session->user_id;
    $data['reimbursement_comment_created_date'] = date('Y-m-d');
    $data['reimbursement_comment_track_number'] = $this->grants_model->generate_item_track_number_and_name('reimbursement_comment')['reimbursement_comment_track_number'];
    $data['reimbursement_comment_modified_by'] = $this->session->user_id;

    
    // //echo json_encode($data);
    $this->write_db->trans_begin();

    $this->write_db->insert('reimbursement_comment', $data);

    $insert_id = $this->write_db->insert_id();

    if ($this->write_db->trans_status() === FALSE) {
      $this->write_db->trans_rollback();
      echo 0;
    } else {
      $this->write_db->trans_commit();

      echo $insert_id;
    }
  }


  public function add_reimbursement_claim()
  {

    $country_currency_code = 'USD';

    if (!$this->session->system_admin_id) {
      $country_currency_code = $this->country_currency_model->get_country_currency_code();
    }

    //Remove currency code and remove commas from digits
    $strip_currency_code_from_caregiver_contribution = floatval(preg_replace('/[^\d.]/', '', explode($country_currency_code, $this->input->post('reimbursement_claim_caregiver_contribution'))[1]));

    $strip_currency_code_from_amount_reimbursed = floatval(preg_replace('/[^\d.]/', '', explode($country_currency_code, $this->input->post('reimbursement_claim_amount_reimbursed'))[1]));

    $reimbursement_claim_amount_spent = floatval(preg_replace('/[^\d.]/', '', explode($country_currency_code, $this->input->post('reimbursement_claim_amount_spent'))[1]));

    //Medical claim count
    $this->read_db->select_max('reimbursement_claim_count');
    $this->read_db->where(array('reimbursement_claim_beneficiary_number' => $this->input->post('reimbursement_claim_beneficiary_number')));
    $reimbursement_claim_count = $this->read_db->get('reimbursement_claim')->row()->reimbursement_claim_count;
    //echo $this->input->post('fk_office_id'); exit();
    $data['fk_office_id'] = $this->input->post('fk_office_id');
    $data['fk_reimbursement_app_type_id'] = $this->input->post('fk_reimbursement_app_type_id');
    if($this->input->post('fk_reimbursement_funding_type_id')!='NULL'){
      $data['fk_reimbursement_funding_type_id'] = $this->input->post('fk_reimbursement_funding_type_id');
    }
    
    $support_documents_need_flag=$this->input->post('support_documents_need_flag');
    
    $data['reimbursement_claim_name'] = $this->input->post('reimbursement_claim_name');
    $data['reimbursement_claim_beneficiary_number'] = $this->input->post('reimbursement_claim_beneficiary_number');
    $data['reimbursement_claim_track_number'] = $this->grants_model->generate_item_track_number_and_name('reimbursement_claim')['reimbursement_claim_track_number'];
    $data['reimbursement_claim_treatment_date'] = $this->input->post('reimbursement_claim_treatment_date');
    $data['reimbursement_claim_facility'] = $this->input->post('reimbursement_claim_facility');
    $data['fk_health_facility_id'] = $this->input->post('fk_health_facility_id');
    $data['fk_context_cluster_id'] = $this->input->post('fk_context_cluster_id');
    $data['support_documents_need_flag'] = $support_documents_need_flag==1?$support_documents_need_flag:0;
    $data['reimbursement_claim_incident_id'] = $this->input->post('reimbursement_claim_incident_id');
    $data['fk_voucher_detail_id'] = $this->input->post('fk_voucher_detail_id');
    $data['reimbursement_claim_diagnosis'] = $this->input->post('reimbursement_claim_diagnosis');
    $data['reimbursement_claim_govt_insurance_number'] = $this->input->post('reimbursement_claim_govt_insurance_number');
    $data['reimbursement_claim_caregiver_contribution'] = $strip_currency_code_from_caregiver_contribution;
    $data['reimbursement_claim_amount_reimbursed'] = $strip_currency_code_from_amount_reimbursed;
    $data['reimbursement_claim_amount_spent'] = $reimbursement_claim_amount_spent;
    $data['reimbursement_claim_created_by'] = $this->session->user_id;
    $data['reimbursement_claim_created_date'] = date('Y-m-d');
    $data['reimbursement_claim_last_modified_by'] = $this->session->user_id;
    $data['fk_approval_id'] = $data['fk_approval_id'] = $this->grants_model->insert_approval_record('reimbursement_claim');
    $data['fk_status_id'] = $this->grants_model->initial_item_status('reimbursement_claim');
    $data['reimbursement_claim_count'] = $reimbursement_claim_count + 1;

    //echo json_encode($data);
    $this->write_db->trans_begin();

    $this->write_db->insert('reimbursement_claim', $data);

    $insert_id = $this->write_db->insert_id();

    if ($this->write_db->trans_status() === FALSE) {
      $this->write_db->trans_rollback();
      echo 0;
    } else {
      $this->write_db->trans_commit();

      echo $insert_id;
    }
  }

  //Edit Claim

  public function edit_medical_claim()
  {

    $country_currency_code = 'USD';

    if (!$this->session->system_admin_id) {
      $country_currency_code = $this->country_currency_model->get_country_currency_code();
    }

    //Remove currency code and remove commas from digits
    $strip_currency_code_from_caregiver_contribution = floatval(preg_replace('/[^\d.]/', '', explode($country_currency_code, $this->input->post('reimbursement_claim_caregiver_contribution'))[1]));

    $strip_currency_code_from_amount_reimbursed = floatval(preg_replace('/[^\d.]/', '', explode($country_currency_code, $this->input->post('reimbursement_claim_amount_reimbursed'))[1]));

    $reimbursement_claim_amount_spent = floatval(preg_replace('/[^\d.]/', '', explode($country_currency_code, $this->input->post('reimbursement_claim_amount_spent'))[1]));

    $data['fk_office_id'] = $this->input->post('fk_office_id');
    $data['reimbursement_claim_name'] = $this->input->post('reimbursement_claim_name');
    $data['reimbursement_claim_beneficiary_number'] = $this->input->post('reimbursement_claim_beneficiary_number');
    //$data['medical_claim_track_number']=$this->grants_model->generate_item_track_number_and_name('medical_claim')['medical_claim_track_number'];
    $data['reimbursement_claim_treatment_date'] = $this->input->post('reimbursement_claim_treatment_date');
    $data['reimbursement_claim_facility'] = $this->input->post('reimbursement_claim_facility');
    $data['fk_health_facility_id'] = $this->input->post('fk_health_facility_id');
    $data['support_documents_need_flag'] = $this->input->post('support_documents_need_flag');
    $data['reimbursement_claim_incident_id'] = $this->input->post('reimbursement_claim_incident_id');
    $data['fk_voucher_id'] = $this->input->post('fk_voucher_id');
    $data['reimbursement_claim_diagnosis'] = $this->input->post('reimbursement_claim_diagnosis');
    $data['reimbursement_claim_govt_insurance_number'] = $this->input->post('reimbursement_claim_govt_insurance_number');
    $data['reimbursement_claim_caregiver_contribution'] = $strip_currency_code_from_caregiver_contribution;
    $data['reimbursement_claim_amount_reimbursed'] = $strip_currency_code_from_amount_reimbursed;
    $data['reimbursement_claim_amount_spent'] = $reimbursement_claim_amount_spent;
    $data['reimbursement_claim_created_by'] = $this->session->user_id;
    $data['reimbursement_claim_created_date'] = date('Y-m-d');
    $data['reimbursement_claim_last_modified_by'] = $this->session->user_id;


    $this->write_db->trans_start();

    $this->write_db->where(['reimbursement_claim_id' => $this->input->post('reimbursement_claim_id')]);
    $this->write_db->update('reimbursement_claim', $data);

    $this->write_db->trans_complete();

    if ($this->write_db->trans_status() == FALSE) {
      echo 0;
    } else {
      echo 1;
    }
  }

  public function delete_reciept_or_support_docs($attachement_id)
  {

    $deletion_message = $this->reimbursement_claim_model->delete_reciept_or_support_docs($attachement_id);

    echo $deletion_message;
  }

  public function update_medical_claim_status()
  {
    $post = $this->input->post();

    $data['fk_status_id'] = $post['next_status'];
    $this->write_db->where(array('reimbursement_claim_id' => $post['reimbursement_claim_id']));
    $this->write_db->update('reimbursement_claim', $data);

    // Get new status label
    $this->read_db->select(array('status_name'));
    $this->read_db->where(array('status_id' => $post['next_status']));
    $button_label = $this->read_db->get('status')->row()->status_name;

    $result['button_label'] = $button_label;

    echo json_encode($result);
  }

  static function get_menu_list()
  {
  }
}
