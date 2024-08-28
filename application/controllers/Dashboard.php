<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	date		: 6th June, 2018
 *	AFR Staff Recognition system
 *	https://www.compassion.com
 *	NKarisa@ke.ci.org
 */

class Dashboard extends MY_Controller{

public $auth;

    function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->library('dashboard_library');
        $this->load->model('financial_report_model');
        $this->load->model('Fund_balance_summary_report_model');

        // $this->load->model('budget_tag_model');
        // $this->load->model('custom_financial_year_model');
        // $this->load->model('voucher_model');
        // $this->load->model('office_bank_model');
    }


    public function index(){
        
    }

    function render_dashboard_metrics(){
        $overdue_transit_deposit = $this->overdue_transit_deposit();
        $count_of_late_mfr = $this->count_of_late_mfr();
        $stale_cheques = $this->stale_cheques();

        echo json_encode(['overdue_transit_deposit' => $overdue_transit_deposit, 'count_of_late_mfr' => $count_of_late_mfr, 'stale_cheques' => $stale_cheques]);
    }

    public function overdue_transit_deposit(){

        $count_of_overdue_transit_deposit = 0;

        $overdue_transit_deposit = $this->financial_report_model->overdue_transit_deposit();

        if(!empty($overdue_transit_deposit)){
            $count_of_overdue_transit_deposit = count($overdue_transit_deposit);
        }

        echo $count_of_overdue_transit_deposit;
    }

    public function count_of_late_mfr(){

        $centers_with_late_mfrs = 0;

        $last_financial_reports = $this->financial_report_model->last_month_submitted_financial_reports();

        if(!empty($last_financial_reports)){
            $centers_with_late_mfrs = count($last_financial_reports);
        }

        echo $centers_with_late_mfrs;
    }

    public function stale_cheques(){
        
        $stale_cheques = 0;

        $stale_cheques_list = $this->financial_report_model->stale_cheques();

        if(!empty($stale_cheques_list)){
            $stale_cheques = count($stale_cheques_list);
        }

        echo $stale_cheques;
    }

    function result($id = 0){
        
        $result = parent::result($id);

        if($this->action == 'list'){
            
            if(!$this->session->data_privacy_consented){

                $this->load->model('unique_identifier_model');
                $unique_identifier = $this->unique_identifier_model->get_account_system_unique_identifier($this->session->user_account_system_id);
            
                $user = $this->user_model->get_user_info($this->session->user_id);
                 
                $consent_contents = file_get_contents("assets/temp/personal_data_consent/eng.txt");
               
                $data['user_employment_date'] = $user['user_employment_date'];
                $data['identifier_number'] = $user['user_unique_identifier'];
                $data['unique_identifier'] = $unique_identifier;
				$data['content'] =  translate_text($consent_contents, [
                    'user_fullname' => $user['fullname'], 
                    'data_officer_email' => 'dataprivacy@us.ci.org',
                    'company' => 'Compassion International',
                    'address' => 'Kerarapon Road, Kenya',
                    'email' => 'info@compassion.com',
                    'phone' => '+254711808080',
                    'consent_date' => date('jS F Y')
                ]);
            
                $data['user_unique_identifier_uploads'] = $this->unique_identifier_model->user_unique_identifier_uploads($this->session->user_id);

                
                $result['personal_data_consent'] = $this->load->view('user/personal_data_consent', $data,true);
            }
        }

        return $result;
    }

    static function get_menu_list(){

    }


}
