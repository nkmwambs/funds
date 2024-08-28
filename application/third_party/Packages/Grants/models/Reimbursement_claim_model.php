<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 *  @package grants <Finance management system for NGOs>
 *	@author  Livingstone Onduso <londuso@ke.ci.org>
 *	@date    20th Feb, 2023
 *  @method void __construct() main method, first to be executed and initializes variables.
 *  @method void index() has no method body.
 *  @method void get_menu_list() empty method.
 *  @method array lookup_tables(): return and array of tables that have relationship with reimbursement table
 *  @method void detail_multi_form_add_visible_columns(): has no method body.
 *  @method void medical_claim_scenerios() returns the settings of reimbursement claim.
 *  @method array get_user_clusters(): returns clusters of the logged in user.
 *  @method void detail_tables(): has no method body.
 *  @method array get_fcp_number(): return an fcp for the logged in user.
 *  @method array pull_fcp_beneficiaries(): return a list of participants or beneficiaries.
 *  @method array pull_health_facility_types(): returns a list of health facility types e.g. public or private.
 *  @method array get_medical_rembursable_expense_account(): returns an expense account used for reimbursement claiming.
 *  @method array get_country_medical_settings(): returns country settings for medical.
 *  @method array reimbursement_illiness_category() :returns illiness categories.
 *  @method void check_if_connect_id_exists() checks if connect id exists.
 *  @method array get_vouchers_for_medical_claim() returns voucher numbers that have related reimbursement claims.
 *  @method array amount_rembursable_to_fcp(): returns amount in associative array form.
 *  @method array get_already_reimbursed_amount() returns amount in associative array form.
 *  @method array populate_cluster_name() returns cluster name.
 *  @method array fcp_rembursable_amount_from_caregiver(): returns amount of caregiver
 *  @method void upload_csv_data_to_table() uploads csv document containing participants.
 *  @method array result($id) retunrs results.
 *  @method array columns() returns columns for rendering on list page.
 *	@see https://techsysnow.com
 */

class Reimbursement_claim_model extends MY_Model
{

    public $table = 'reimbursement_claim';
    public $dependant_table = '';
    public $name_field = 'reimbursement_claim_name';
    public $create_date_field = "reimbursement_claim_created_date";
    public $created_by_field = "reimbursement_claim_created_by";
    public $last_modified_date_field = "reimbursement_claim_last_modified_date";
    public $last_modified_by_field = "reimbursement_claim_last_modified_by";
    public $deleted_at_field = "reimbursement_claim_deleted_at";
    
   /**
   * __construct(): This is the primary or main method that initializes variables to be used other methods
   * @author Livingstone Onduso
   * @access public
   * @return not applicable
   */

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->helper('reimbursement');
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
   * looup_tables(): returns the list of tables that have relationship with reimbursement claim
   * @author Livingstone Onduso
   * @access public
   * @return array
   */

    public function lookup_tables():array
    {
        return array("office", "health_facility","medical_claim_type","voucher","attachment");
    }

   /**
   * detail_tables(): empty method <no body>
   * @author Livingstone Onduso
   * @access public
   * @return void
   */

    public function detail_tables(): void
    {
     //No method body
    }

    public function detail_multi_form_add_visible_columns():void
    {
       //No method body
    }

   /**
   * medical_claim_scenerios(): This method calls the medical claim
   * settings library and assign the settngs with related values
   * @author Livingstone Onduso
   * @access public
   * @return array
   * @param float $voucher_amount: passes amount on voucher;
   *        float $total_receipt_amount: total receipt amount;
   *        string $card_number: insurance number
   */

    public function medical_claim_scenerios(float $voucher_amount, float $total_receipt_amount, string $card_number = ''):array
    {
        $card_setting_arr=$this->get_country_medical_settings(2);
        $threshold_setting_arr=$this->get_country_medical_settings(5);
        $threshold_met=$this->get_country_medical_settings(6);
        $ctrbtn=$this->get_country_medical_settings(1);
        $ctrbtn_with_card=$this->get_country_medical_settings(7);
       

        //Get the settings
        $allow_use_insurance_card = !empty($card_setting_arr) && ($card_setting_arr[0] > 0) ? true : false;
        $threshold_amount = !empty($threshold_setting_arr) ? $threshold_setting_arr[0] : 0;
        $reimburse_all_when_threshold_met = !empty($threshold_met) && ($threshold_met[0] > 0) ? true : false;
        $caregiver_contribution_percentage = !empty($ctrbtn) && ($ctrbtn[0] > 0) ? $ctrbtn[0] : 0;
        $caregiver_with_card_contribution_percentage = !empty($ctrbtn_with_card) && ($ctrbtn_with_card[0] > 0) ? $ctrbtn_with_card[0] : 0;


        $params = compact('allow_use_insurance_card', 'threshold_amount', 'reimburse_all_when_threshold_met', 'caregiver_contribution_percentage', 'caregiver_with_card_contribution_percentage');

        $this->load->library('Medical_claim_scenerios_library', $params);


        //Pass values from the form
        //$v_amount=2000;
        //$total_receipt_amount=2000;
        //$insurance_card=9887676;

        $medical_claim_scenerios = $this->medical_claim_scenerios_library->compute_contribution_and_reibursement_amount($voucher_amount, $total_receipt_amount, $card_number);

        return $medical_claim_scenerios;
    }

    /**
     * get_user_clusters(): This method return clusters that user logged in user has visibility to
     * @author Livingstone Onduso
     * @access public
     * @return array
     * @param bool $get_clusters_ids: passes a boolean true or false;
   
     */

    public function get_user_clusters(bool $get_clusters_ids = false):array
    {
        $hierarchy_offices = array_column($this->session->hierarchy_offices, 'office_id');

        $this->read_db->select(array('office.office_name', 'office.office_id', 'context_cluster.context_cluster_id'));
        $this->read_db->join('context_cluster', 'context_cluster.context_cluster_id=context_center.fk_context_cluster_id');
        $this->read_db->join('office', 'office.office_id=context_cluster.fk_office_id');
        $this->read_db->where_in('context_center.fk_office_id', $hierarchy_offices);
        $clusters = $this->read_db->get('context_center')->result_array();

        $array_column_clusters = array_column($clusters, 'office_name');

        //If Context Cluster Ids
        if ($get_clusters_ids) {

            $cluster_ids = array_column($clusters, 'context_cluster_id');

            return array_combine($cluster_ids, $array_column_clusters);
        
        } else { //If Office Ids

            $office_id = array_column($clusters, 'office_id');

            return array_combine($office_id, $array_column_clusters);
        }

    }

    /**
     * get_fcp_number(): This method returns fcp for the user
     * @author Livingstone Onduso
     * @access public
     * @return array
     */

    public function get_fcp_number():array
    {
        $user_fcps = [];

        // if (!$this->session->system_admin) {
        $hierarchy_offices = array_column($this->session->hierarchy_offices, 'office_id');

        $this->read_db->select(array('office_code', 'office_id'));
        $this->read_db->where_in('office_id', $hierarchy_offices);
        $this->read_db->where(array('fk_context_definition_id' => 1)); //FCPs only
        $user_fcps = $this->read_db->get('office')->result_array();
        // }

        $office_code = array_column($user_fcps, 'office_code');

        $office_id = array_column($user_fcps, 'office_id');

        return array_combine($office_id, $office_code);
    }

    /**
     * pull_fcp_beneficiaries(): This method returns participants/beneficiaries for a given fcp.
     * @author Livingstone Onduso
     * @access public
     * @return array
     * @param string $fcp_number: passes the FCP code
     */

    public function pull_fcp_beneficiaries(string $fcp_number = ''):array
    {

        $beneficiaries = [];

        $this->read_db->select(array('beneficiary_number', 'beneficiary_name'));

        if ($fcp_number != '') {
            $this->read_db->like('beneficiary_number', $fcp_number);
        }

        $beneficiaries = $this->read_db->get('beneficiary')->result_array();

        return $beneficiaries;
    }

    /**
     * pull_health_facility_types(): This method returns health facility types.
     * @author Livingstone Onduso
     * @access public
     * @return array
     */

    public function pull_health_facility_types():array
    {

        $health_facility_types = [];

        $this->read_db->select(array('health_facility_name', 'health_facility_id'));

        $this->read_db->where(array('fk_account_system_id' => $this->session->user_account_system_id));

        $health_facility_types = $this->read_db->get('health_facility')->result_array();

        $health_facility_name = array_column($health_facility_types, 'health_facility_name');

        $health_facility_id = array_column($health_facility_types, 'health_facility_id');


        return array_combine($health_facility_id, $health_facility_name);
    }

    /**
     * get_medical_rembursable_expense_account(): This method gets the reimbursement expense account.
     * @author Livingstone Onduso
     * @access private
     * @return array
     */

    private function get_medical_rembursable_expense_account():array
    {

        //Get medical rembursable expense account
        $this->read_db->select(['expense_account_code']);
        $this->read_db->join('income_account', 'income_account.income_account_id=expense_account.fk_income_account_id');
        $this->read_db->join('account_system', 'account_system.account_system_id=income_account.fk_account_system_id');
        $this->read_db->where(['expense_account_is_medical_rembursable' => 1, 'expense_account_is_active' => 1]);
        if (!$this->session->system_admin) {
            $this->read_db->where(['income_account.fk_account_system_id' => $this->session->user_account_system_id]);
        }

        $medical_rembursable_expense_acc = $this->read_db->get('expense_account');
        $medical_rembursable_expense_acc_array_col = [];
        if ($medical_rembursable_expense_acc) {
            $expense_acc = $medical_rembursable_expense_acc->result_array();

            $medical_rembursable_expense_acc_array_col = array_column($expense_acc, 'expense_account_code');
        }

        return $medical_rembursable_expense_acc_array_col;
    }

      /**
     * get_country_medical_settings(): This method return medical settings.
     * @author Livingstone Onduso
     * @access public
     * @return array
     * @param int $medical_setting: passes the id of the setting.
     */

    public function get_country_medical_settings(int $medical_setting):array
    {
        /*
        
        Note: These values will be always the same since they are static in the table created by super admin)
          
          1) percentage_caregiver_contribution=1
          
          2) national_health_cover_flag=2

          3) valid_claiming_days=3

          4) medical_claiming_expense_accounts=4 [This was modified to get the expense accounts
                                                  from expense account table.
                                                  The table column'expense_account_is_medical_rembursable'
                                                  was added to the table allow the change]

          5) minimum_claimable_amount=5
        
        */
        $medical_claim_country_setting = [];

        $this->read_db->select(array('medical_claim_setting_value'));

        $this->read_db->join('medical_claim_admin_setting', 'medical_claim_admin_setting.medical_claim_admin_setting_id=medical_claim_setting.fk_medical_claim_admin_setting_id');

        $this->read_db->where(array('medical_claim_setting.fk_medical_claim_admin_setting_id' => $medical_setting));

        if (!$this->session->system_admin) {
            $this->read_db->where(array('medical_claim_setting.fk_account_system_id' => $this->session->user_account_system_id, 'medical_claim_setting.fk_medical_claim_admin_setting_id' => $medical_setting));
        }
        $medical_settings = $this->read_db->get('medical_claim_setting');

        if ($medical_settings) {
            $medical_claim_country_setting = $medical_settings->result_array();
        }


        return array_column($medical_claim_country_setting, 'medical_claim_setting_value');
    }

  /**
     * get_vouchers_for_medical_claim(): This method returns vouchers related to reimbursement claims.
     * @author Livingstone Onduso
     * @access public
     * @return array
     */
    public function get_vouchers_for_medical_claim():array
    {
        $max_approved_status = $this->general_model->get_max_approval_status_id('voucher');


        //Get Threshold amount and reimburse_all_flag
        $medical_threshold_amount = $this->medical_claim_setting_model->get_threshold_amount_or_reimburse_all_flag(5);

        $medical_reimburse_all_flag = $this->medical_claim_setting_model->get_threshold_amount_or_reimburse_all_flag(6);

        //Get the Expense account codes for medical reimbursement from the medical_claim_setting table

        $medical_claims_expense_acc = $this->get_medical_rembursable_expense_account();


        $valid_days_for_medical_claims = $this->get_country_medical_settings(3);


        //Default number of valid days for a claim
        $valid_days_for_claiming = $this->config->item('valid_days_for_medical_claims');

        if (sizeof($valid_days_for_medical_claims) > 0) {

            $valid_days_for_claiming = $valid_days_for_medical_claims[0];
        }
        //Get the office
        $office_ids = array_column($this->session->hierarchy_offices, 'office_id');

        $this->read_db->select(array('v.voucher_number', 'v.voucher_id','vd.voucher_detail_id','vd.voucher_detail_total_cost', 'e.expense_account_code')); //,'e.expense_account_code'
        //$this->read_db->select_sum('vd.voucher_detail_total_cost');
        $this->read_db->join('voucher as v', 'v.voucher_id=vd.fk_voucher_id');
        $this->read_db->join('expense_account as e', 'e.expense_account_id=vd.fk_expense_account_id');
        $this->read_db->join('office as o', 'o.office_id=v.fk_office_id');
        //$this->read_db->where('voucher_created_date BETWEEN CURDATE() - INTERVAL 30 DAY AND CURDATE()');
        $this->read_db->where("DATEDIFF(CURRENT_DATE(), voucher_created_date) BETWEEN -1 AND " . $valid_days_for_claiming);
        $this->read_db->where(['v.fk_status_id' => $max_approved_status[0]]);
        $this->read_db->where_in('o.office_id', $office_ids);

        // if (sizeof($medical_claims_expense_acc) > 0) {
        $this->read_db->where_in('e.expense_account_code', $medical_claims_expense_acc);
        //}


        //$this->read_db->group_by(array('v.voucher_number'));

        $vouchers_with_medical_expense_related_accs = $this->read_db->get('voucher_detail as vd'); //->result_array();

        $results = [];

        if ($vouchers_with_medical_expense_related_accs) {
            $results = $vouchers_with_medical_expense_related_accs->result_array();
        }

        $rebuild_results = [];
        foreach ($results as $result) {
            
            if($medical_reimburse_all_flag==false){//for Malawi case
                $result['voucher_detail_total_cost'] = $result['voucher_detail_total_cost']- $medical_threshold_amount;
            }
            else{
                $result['voucher_detail_total_cost'] = $result['voucher_detail_total_cost'];
            }


            $rebuild_results[] = $result;
        }


        return   $rebuild_results;


    }

    /**
     * check_if_connect_id_exists(): This method verifies if the connect id exists
     * @author Livingstone Onduso
     * @access public
     * @return int
     * @param int
     */
    public function check_if_connect_id_exists(int $connect_incident_id):int
    {

        $transform_incident_id = 'I-' . $connect_incident_id;

        $this->read_db->select(['reimbursement_claim_incident_id']);
        $this->read_db->where(['reimbursement_claim_incident_id' => trim($transform_incident_id)]);
        $medical_claim_incident_id = $this->read_db->get('reimbursement_claim')->row();

        if ($medical_claim_incident_id) {
            return 1;
        }
        return 0;
    }

    /**
     * get_already_reimbursed_amount(): This method returns the a mount in associative array form.
     * @author Livingstone Onduso
     * @access public
     * @return array
     */

    public function get_already_reimbursed_amount():array
    {

        //Get the office
        $office_ids = array_column($this->session->hierarchy_offices, 'office_id');

        return $this->amount_rembursable_to_fcp('reimbursement_claim_amount_reimbursed', $office_ids);
    }

     /**
     * fcp_rembursable_amount_from_caregiver(): This method returns the amount for caregiver contribution in associative array form.
     * @author Livingstone Onduso
     * @access public
     * @return array
     */

    public function fcp_rembursable_amount_from_caregiver():array
    {

        //Get the office
        $office_ids = array_column($this->session->hierarchy_offices, 'office_id');

        return $this->amount_rembursable_to_fcp('reimbursement_claim_caregiver_contribution', $office_ids);
    }

    /**
     * amount_rembursable_to_fcp(): This method verifies if the connect id exists
     * @author Livingstone Onduso
     * @access private
     * @return array
     * @param string $table_column_name: passes column name; array $office_ids: passes an array of office ids.
     */

    private function amount_rembursable_to_fcp(string $table_column_name, array $office_ids):array
    {

        $this->read_db->select($table_column_name);
        $this->read_db->select(array('fk_voucher_detail_id'));
        $this->read_db->where_in('fk_office_id', $office_ids);
        //$this->read_db->group_by(array('fk_voucher_id'));
        $already_reimbursed_amount = $this->read_db->get('reimbursement_claim')->result_array();

        $voucher_id_arr = array_column($already_reimbursed_amount, 'fk_voucher_detail_id');

        $medical_claim_amount_reimbursed_arr = array_column($already_reimbursed_amount, $table_column_name);

        return array_combine($voucher_id_arr, $medical_claim_amount_reimbursed_arr);
    }
   
    /**
     * populate_cluster_name(): This method returns cluster name to be displayed on the list
     * @author Livingstone Onduso
     * @access public
     * @return array
     * @param int $office_id: passes office id of an fcp.
     */

    public function populate_cluster_name(int $office_id):array
    {

        $this->read_db->select(array('context_cluster.context_cluster_name', 'context_cluster.context_cluster_id'));
        $this->read_db->join('context_center', 'context_center.fk_office_id=office.office_id');
        $this->read_db->join('context_cluster', 'context_cluster.context_cluster_id= context_center.fk_context_cluster_id');
        $this->read_db->where(['office.office_id' => $office_id]);
        $cluster_name = $this->read_db->get('office')->result_array();

        $cluster_name_with_context = $cluster_name[0]['context_cluster_name'];
        $cluster_id_key = $cluster_name[0]['context_cluster_id'];

        //remove context from the cluster name
        $cluster_name_with_no_context = explode('Context for office', $cluster_name_with_context)[1];

        $cluster_id[0]['context_cluster_id'] = $cluster_id_key;
        $cluster_id[0]['context_cluster_name'] =  $cluster_name_with_no_context;

        $cluster_i = array_column($cluster_id, 'context_cluster_id');
        $cluster_n = array_column($cluster_id, 'context_cluster_name');

        return array_combine($cluster_i, $cluster_n);
    }
    public function get_voucher_number_for_arow($voucher_detail_id)
    {

        // $this->read_db->select(array('voucher_number'));
        // $this->read_db->where(array('voucher_id' => $voucher_id));
        // return $this->read_db->get('voucher')->row()->voucher_number;

        $this->read_db->select(array('voucher_number'));
        $this->read_db->join('voucher_detail','voucher_detail.fk_voucher_id=voucher.voucher_id');
        $this->read_db->where(array('voucher_detail_id' => $voucher_detail_id));
        return $this->read_db->get('voucher')->row()->voucher_number;
    }


    public function update_medical_claim_attachment_id($last_insert_id)
    {
        $post = $this->input->post();

        $data['fk_attachment_id'] = $last_insert_id;
        $this->write_db->where(array('reimbursement_claim_id' => $post['reimbursement_claim_id']));
        $this->write_db->update('reimbursement_claim', $data);
    }

    function get_reimbursement_comments($reimbursement_id){

        $this->read_db->select(['reimbursement_comment_id','reimbursement_comment_detail', 'reimbursement_comment_created_date','user_lastname','fk_reimbursement_claim_id']);
        $this->read_db->join('user', 'user.user_id=reimbursement_comment.reimbursement_comment_created_by');
        $this->read_db->where(['fk_reimbursement_claim_id'=>$reimbursement_id]);
        $reimbursement_comments=$this->read_db->get('reimbursement_comment')->result_array();

        return $reimbursement_comments;
    }


    public function get_reimbursement_claim_attachments($medical_id = '')
    {

        //Get approve_item_id
        $approve_item_id = $this->read_db->get_where(
            'approve_item',
            ['approve_item_name' => lcfirst($this->controller)]
        )->row()->approve_item_id;

        //Get medical_claims_attachments
        $this->read_db->select(['attachment_id', 'attachment_name', 'attachment_url', 'attachment_primary_id']); //'medical_claim.support_documents_need_flag'
        //$this->read_db->join('medical_claim','medical_claim.fk_attachment_id= attachment.attachment_id');
        $this->read_db->where(['fk_approve_item_id' => $approve_item_id]);
        $this->read_db->where(['fk_account_system_id' => $this->session->user_account_system_id]);

        if ($medical_id != '') {
            $this->read_db->where(['attachment_primary_id' => $medical_id]);
        }

        $medical_claims_attachments = $this->read_db->get('attachment')->result_array();

        return $medical_claims_attachments;
    }


    public function get_medical_claim_for_an_office($reimbursement_claim_id = '', $filter_medical_records_by_cluster = [], $filter_medical_records_by_status = [])
    {

        //Get the previous month and current month
        $previous_month_arr=$this->read_db->query("SELECT DATE_FORMAT( CURRENT_DATE - INTERVAL 1 MONTH, '%Y-%m-20')")->result_array()[0];
        
        $current_month_arr=$this->read_db->query("SELECT LAST_DAY(CURRENT_DATE)")->result_array()[0];

        $previous_month=array_values($previous_month_arr)[0];

        $current_month=array_values($current_month_arr)[0];

        //Get maximum id
        $max_status_id = $this->general_model->get_max_approval_status_id($this->controller);

        $data = [];

        $office_ids = array_column($this->session->hierarchy_offices, 'office_id');

        $this->read_db->select(['reimbursement_claim_id', 'reimbursement_claim_name',
                               'reimbursement_app_type_name', 'reimbursement_funding_type_name',
                               'voucher_number', 'status_name', 'reimbursement_claim_track_number',
                               'reimbursement_claim_facility', 'reimbursement_claim_incident_id',
                               'reimbursement_claim_beneficiary_number', 'reimbursement_claim_count',
                               'reimbursement_claim_treatment_date', 'reimbursement_claim_created_date',
                               'reimbursement_claim_diagnosis', 'reimbursement_claim_amount_reimbursed',
                               'reimbursement_claim_caregiver_contribution', 'reimbursement_claim_amount_reimbursed',
                               'fk_context_cluster_id', 'office_name', 'reimbursement_claim.fk_status_id',
                               'support_documents_need_flag', 'fk_voucher_detail_id','voucher_number']);

        $this->read_db->join('voucher_detail','voucher_detail.voucher_detail_id=reimbursement_claim.fk_voucher_detail_id');
        $this->read_db->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');

        $this->read_db->join('context_cluster', 'reimbursement_claim.fk_context_cluster_id=context_cluster.context_cluster_id');

        $this->read_db->join('office', 'office.office_id=context_cluster.fk_office_id');

        $this->read_db->join('status', 'reimbursement_claim.fk_status_id=status.status_id');
        $this->read_db->join('reimbursement_app_type', 'reimbursement_app_type.reimbursement_app_type_id=reimbursement_claim.fk_reimbursement_app_type_id');

        $this->read_db->join('reimbursement_funding_type', 'reimbursement_funding_type.reimbursement_funding_type_id=reimbursement_claim.fk_reimbursement_funding_type_id');

        $this->read_db->group_start();
            $this->read_db->where(['reimbursement_claim_created_date >='=>$previous_month, 'reimbursement_claim_created_date <'=>$current_month]);
            $this->read_db->or_group_start();
               $this->read_db->where(['reimbursement_claim.fk_status_id<>'=>$max_status_id[0]]);
            $this->read_db->group_end();
        $this->read_db->group_end();

        //$this->read_db->order_by('medical_claim.medical_claim_created_date', 'desc');

        if (!$this->session->system_admin) {
            $this->read_db->where_in('reimbursement_claim.fk_office_id', $office_ids);
        }

        if ($reimbursement_claim_id != '') {
            $this->read_db->where(['reimbursement_claim_id' => $reimbursement_claim_id]);
        }
        //$filter_cluster=implode(",",$filter_medical_records_by_cluster);
        //log_message('error',$filter_medical_records_by_cluster);
        //$filter_medical_records_by_cluster=explode(',',$filter_medical_records_by_cluster);
        //$filter_medical_records_by_status=explode(',',$filter_medical_records_by_status);

        if (!empty($filter_medical_records_by_cluster) && empty($filter_medical_records_by_status)) {
            
          $this->read_db->where_in('reimbursement_claim.fk_context_cluster_id', $filter_medical_records_by_cluster);
            //log_message('error', json_encode($test));
            
            $data = $this->read_db->get('reimbursement_claim')->result_array();
        }
        if (!empty($filter_medical_records_by_status) && empty($filter_medical_records_by_cluster)) {
            $this->read_db->where_in('reimbursement_claim.fk_status_id', $filter_medical_records_by_status);
            $data = $this->read_db->get('reimbursement_claim')->result_array();
        }
        if (!empty($filter_medical_records_by_status) && !empty($filter_medical_records_by_cluster)) {

            $this->read_db->where_in('reimbursement_claim.fk_context_cluster_id', $filter_medical_records_by_cluster);
            $this->read_db->where_in('reimbursement_claim.fk_status_id', $filter_medical_records_by_status);


            // log_message('error',json_encode($filter_medical_records_by_cluster));

            // log_message('error',json_encode($filter_medical_records_by_status));

            $data = $this->read_db->get('reimbursement_claim')->result_array();
        }
        if (empty($filter_medical_records_by_cluster) && empty($filter_medical_records_by_status)) {
            $data = $this->read_db->get('reimbursement_claim')->result_array();
        }


        //Filter results based on role

        $this->read_db->select(['role_name',]);
        $this->read_db->where(['role_id' => $this->session->role_id, 'fk_context_definition_id' => 4]);
        $role = $this->read_db->get('role')->row();

        $rebuild_data = [];

        if ($role) {
            foreach ($data as $medical_info) {

                $app_type = $medical_info['reimbursement_app_type_name'];

                if ($app_type === 'HVC-CPR' && (strpos(strtoupper($role->role_name), 'CPS'))) {

                    $rebuild_data[] = $medical_info;
                } elseif (($app_type === 'MEDICAL-CLAIM' || $app_type === 'CIV-MEDICAL' || $app_type === 'MED-TFI') && strpos(strtoupper($role->role_name), 'HEALTH')) {
                    $rebuild_data[] = $medical_info;
                }elseif(!strpos(strtoupper($role->role_name), 'CPS') && !strpos(strtoupper($role->role_name), 'HEALTH')){
                    $rebuild_data = $data;
                }
            }
        }else{
            $rebuild_data = $data;
        }


        return   $rebuild_data;
    }



    public function delete_reciept_or_support_docs($attacheme_id)
    {

        $this->write_db->trans_start();

        $this->write_db->where(['attachment_id' => $attacheme_id]);
        $attachement_to_delete = $this->write_db->delete('attachment');

        $this->write_db->trans_complete();

        if ($this->write_db->trans_status() == FALSE) {
            return 0;
        } else {
            return 1;
        }
    }
    public function check_if_medical_app_only()
    {

        $this->read_db->where(['account_system_id' => $this->session->user_account_system_id]);
        $account_system_has_medical_app_only = $this->read_db->get('account_system')->row()->account_system_has_medical_app_only;

        return $account_system_has_medical_app_only;
    }

    public function reimbursement_type()
    {

        $this->read_db->select(['reimbursement_funding_type_id', 'reimbursement_funding_type_name']);
        $this->read_db->where(['reimbursement_funding_type_is_active'=>1]);
        $reimbursement_types = $this->read_db->get('reimbursement_funding_type')->result_array();

        $reimbursement_types_id = array_column($reimbursement_types, 'reimbursement_funding_type_id');

        $reimbursement_types_name = array_column($reimbursement_types, 'reimbursement_funding_type_name');

        return array_combine($reimbursement_types_id, $reimbursement_types_name);
    }

    public function reimbursement_app_types()
    {
        $this->read_db->select(['reimbursement_app_type_id', 'reimbursement_app_type_name']);

        $this->read_db->where(['fk_account_system_id' => $this->session->user_account_system_id]);
        $this->read_db->where(['reimbursement_app_type_is_active' => 1]);

        $app_types = $this->read_db->get('reimbursement_app_type')->result_array();

        $reimbursement_app_type_id = array_column($app_types, 'reimbursement_app_type_id');

        $reimbursement_app_type_name = array_column($app_types, 'reimbursement_app_type_name');



        return array_combine($reimbursement_app_type_id, $reimbursement_app_type_name);
    }

   /**
   * reimbursement_illiness_category(): This method calls a model and renders the
   *                                    settings for claims for a given country
   * @author Livingstone Onduso
   * @access public
   * @return array
   * @param int $diagnosis_type: passes the diagnosis type;
   */

    public function reimbursement_illiness_category(int $diagnosis_type):array
    {
        $this->read_db->select(['reimbursement_illiness_category_id', 'reimbursement_illiness_category_name']);
        $this->read_db->where(['reimbursement_illiness_category_is_active' => 1]);
        $this->read_db->where(['fk_reimbursement_diagnosis_type_id' => $diagnosis_type]);

        $illiness = $this->read_db->get('reimbursement_illiness_category')->result_array();


        $reimbursement_illiness_category_id = array_column($illiness, 'reimbursement_illiness_category_id');

        $reimbursement_illiness_category_name = array_column($illiness, 'reimbursement_illiness_category_name');

        return array_combine($reimbursement_illiness_category_id, $reimbursement_illiness_category_name);
    }
    public function reimbursement_diagnosis_type()
    {
        $this->read_db->select(['reimbursement_diagnosis_type_id', 'reimbursement_diagnosis_type_name']);
        $this->read_db->where(['reimbursement_diagnosis_type_is_active' => 1]);

        $reimbursement_diagnosis_type = $this->read_db->get('reimbursement_diagnosis_type')->result_array();


        $reimbursement_diagnosis_type_id = array_column($reimbursement_diagnosis_type, 'reimbursement_diagnosis_type_id');

        $reimbursement_diagnosis_type_name = array_column($reimbursement_diagnosis_type, 'reimbursement_diagnosis_type_name');

        return array_combine($reimbursement_diagnosis_type_id, $reimbursement_diagnosis_type_name);
    }

    public function get_health_facility_by_id($reimbursement_claim_id)
    {
        //Get by id


        $this->read_db->where(['reimbursement_claim_id' => $reimbursement_claim_id]);
        $fk_health_facility_id = $this->read_db->get('reimbursement_claim')->row()->fk_health_facility_id;

        //return $this->read_db->get_where('health_facility', ['health_facility_id' => $fk_health_facility_id])->row()->health_facility_name;

        $facility_name = $this->read_db->get_where('health_facility', ['health_facility_id' => $fk_health_facility_id])->row();

        $health_facility_name = get_phrase('NA', 'Not Applicable');

        if ($facility_name) {

            $health_facility_name = $this->read_db->get_where('health_facility', ['health_facility_id' => $fk_health_facility_id])->row()->health_facility_name;
        }

        return  $health_facility_name;
    }

    // public function get_voucher_number_id($voucher_id)
    // {
    //     //Get by id

    //     $this->read_db->where(['voucher_id' => $voucher_id]);
    //     $voucher_number = $this->read_db->get('voucher')->row()->voucher_number;

    //     return $voucher_number;
    // }



    public function get_office_code($fcp_ids)
    {
        //Get office code
        $this->read_db->select(array('office_code'));
        $this->read_db->where_in('office_id', $fcp_ids);
        $office_code = $this->read_db->get('office');

        $fcp_number = $office_code ? $office_code->result_array() : [];

        if (sizeof($fcp_number) == 1) {

            return ['message' => 1, 'fcps' => $fcp_number[0]['office_code']];
        } elseif ($fcp_number == 0) {
            return  ['message' => 0];
        } else {
            return  ['message' => -1];
        }
    }

    //Delete Comments

    function delete_reimbursement_comment(){

        $comment_id=$this->input->post('reimbursement_comment_id');

        $this->write_db->where(['reimbursement_comment_id'=>$comment_id]);
        $this->write_db->delete('reimbursement_comment');

        $deleted=0;

        if($this->write_db->affected_rows()){
            $deleted= 1;
        }
        return $deleted;
    }


    //EDIT METHODS
    function get_reimbursement_claim_record_to_edit()
    {

        //To be implemented when Staff supports more than one fcp [Use office_hierachy]to get the data

        //When Staff support one fcp
        $this->read_db->select([
            'reimbursement_claim_id', 'office.office_code', 'office.office_id', 'reimbursement_claim_name', 'reimbursement_claim_beneficiary_number',
            'reimbursement_claim_diagnosis', 'reimbursement_claim_treatment_date', 'fk_voucher_id', 'voucher_number','reimbursement_claim_govt_insurance_number', 'reimbursement_claim_amount_reimbursed',
            'reimbursement_claim_facility', 'fk_health_facility_id', 'support_documents_need_flag', 'reimbursement_claim_incident_id', 'reimbursement_claim_caregiver_contribution'
        ]);
        $this->read_db->join('office', 'reimbursement_claim.fk_office_id=office.office_id');
        $this->read_db->join('voucher_detail','voucher_detail.voucher_detail_id=reimbursement_claim.fk_voucher_detail_id');
        $this->read_db->join('voucher','voucher.voucher_id=voucher_detail.fk_voucher_id');
        //$this->read_db->join('beneficiary', 'beneficiary.beneficiary_number=reimbursement_claim.medical_beneficiary_number');
        $this->read_db->where(['reimbursement_claim_id' => hash_id($this->id, 'decode')]);
        $reimbursement_claim_record = $this->read_db->get('reimbursement_claim')->result_array();

        return $reimbursement_claim_record;
    }


    //END of EDIT methods

    //EDIT METHODS
    // function get_medical_record_to_edit_try()
    // {

    //     //To be implemented when Staff supports more than one fcp [Use office_hierachy]to get the data

    //     //When Staff support one fcp
    //     $this->read_db->select([
    //         'reimbursement_claim_id', 'office.office_code', 'office.office_id',  'reimbursement_claim_name',
    //         'reimbursement_claim_diagnosis', 'reimbursement_claim_treatment_date', 'fk_voucher_id', 'reimbursement_claim_govt_insurance_number', 'reimbursement_claim_amount_reimbursed',
    //         'reimbursement_claim_facility', 'fk_health_facility_id', 'support_documents_need_flag', 'reimbursement_claim_incident_id', 'reimbursement_claim_caregiver_contribution'
    //     ]);
    //     $this->read_db->join('office', 'reimbursement_claim.fk_office_id=office.office_id');
    //     //$this->read_db->join('beneficiary', 'beneficiary.beneficiary_number=reimbursement_claim.reimbursement_claim_beneficiary_number');
    //     $this->read_db->where(['reimbursement_claim_id' => hash_id($this->id, 'decode')]);
    //     $medical_record = $this->read_db->get('reimbursement_claim')->result_array();

    //     return $medical_record;
    // }
}