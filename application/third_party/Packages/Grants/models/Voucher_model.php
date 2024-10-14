<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 *    @author     : Nicodemus Karisa
 *    @date        : 27th September, 2018
 *    Finance management system for NGOs
 *    NKarisa@ke.ci.org
 */

class Voucher_model extends MY_Model
{
    public $table = 'voucher'; // you MUST mention the table name
    //public $primary_key = 'voucher_id'; // you MUST mention the primary key
    public $dependant_table = 'voucher_detail';
    public $name_field = 'voucher_name';
    public $create_date_field = "voucher_created_date";
    public $created_by_field = "voucher_created_by";
    public $last_modified_date_field = "voucher_last_modified_date";
    public $last_modified_by_field = "voucher_last_modified_by";
    public $deleted_at_field = "voucher_deleted_at";

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->model('financial_report_model');

        // Load dependant model
        $this->load_dependant_model();
    }

    public function index()
    {
    }

    public function lookup_tables()
    {
        // Do not put the Office Bank and Office Cash Tables here since only contra transaction will be listed
        return array('voucher_type', 'office', 'approval', 'status');
    }

    public function detail_tables()
    {
        return array('voucher_detail');
    }
    /**
     * @todo : Not yet
     */
    public function check_unsubmitted_cash_deposit_vouchers(int $office_id, int $status_id, string $voucher_date): array
    {

        $this->read_db->select(['fk_voucher_type_id']);

        $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');

        $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');

        $this->read_db->where(['voucher_type_effect_code' => 'expense', 'voucher.fk_office_id' => $office_id, 'voucher.fk_status_id' => $status_id, 'voucher.voucher_date >=' => $voucher_date, 'voucher_type.voucher_type_is_active' => 1]);

        $unsubmitted_cash_expense_vouchers = $this->read_db->get('voucher')->result_array();

        return array_column($unsubmitted_cash_expense_vouchers, 'fk_voucher_type_id');
    }
    /**
     *get_effect_code_and_account_code(): Returns a row of voucher_type_effect_code and voucher_type_account_code
     * @author Livingstone Onduso: Dated 08-04-2023
     * @access public
     * @param Int $voucher_type_id - voucher type id id
     * @return object - returns a row with effect code and account code
     */
    public function get_account_and_effect_codes(int $voucher_type_id): object
    {
        $this->read_db->select(array('voucher_type_effect_code', 'voucher_type_account_code'));
        $this->read_db->where(array('voucher_type_id' => $voucher_type_id));
        $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $this->read_db->join('voucher_type_account', 'voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
        return $this->read_db->get('voucher_type')->row();
    }

    /**
     *unapproved_month_vouchers(): Returns the total of unapproved vouchers for current month for an office
     *
     * @author Livingstone Onduso: Dated 08-04-2023
     * @access public
     * @param Int $office_id - Office primary key
     * @param String $reporting_month - Date of the month
     * @param String $effect_code - Effect code e.g. income or expense
     * @param String $account_code - Account code e.g cash or bank
     * @param Int $cash_type_id - Cash type e.g. petty cash
     * @param Int $office_bank_id - Cash type e.g. bank 1
     * @return float - True if reconciliation has been created else false
     */
    public function unapproved_month_vouchers(int $office_id, string $reporting_month, string $effect_code, string $account_code, array $funder_ids = [], ?int $cash_type_id = 0, ?int $office_bank_id = 0): float
    {

        $max_approval_status_ids = $this->general_model->get_max_approval_status_id('voucher', [$office_id]);

        $start_of_reporting_month = date('Y-m-01', strtotime($reporting_month));

        $end_of_reporting_month = date('Y-m-t', strtotime($reporting_month));

        $this->read_db->select_sum('voucher_detail_total_cost');
        $this->read_db->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
        $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $this->read_db->join('voucher_type_account', 'voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
        $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $this->read_db->where(['voucher.fk_office_id' => $office_id, 'voucher.voucher_date >=' => $start_of_reporting_month, 'voucher.voucher_date <=' => $end_of_reporting_month, 'voucher.fk_status_id!=' => $max_approval_status_ids[0]]);
        $this->read_db->where(['voucher_type_effect_code' => $effect_code, 'voucher_type_account_code' => $account_code]);

        if(!empty($funder_ids)){
            $this->read_db->where_in('voucher.fk_funder_id', $funder_ids);
        }

        if ($cash_type_id != 0) {
            $this->read_db->where(['fk_office_cash_id' => $cash_type_id]);
        } else if ($office_bank_id != 0) {

            $this->read_db->where(['fk_office_bank_id' => $office_bank_id]);
        }

        $this->read_db->group_by(array('voucher_detail.fk_voucher_id'));

        $results = $this->read_db->get('voucher_detail')->result_array();

        $totals_arr = array_column($results, 'voucher_detail_total_cost');

        return array_sum($totals_arr);
    }

    /**
     *Selected_voucher_income_total_cost(): Returns cash recieved in the bank or cash deposit in petty cash box on the selected voucher
     * @author Livingstone Onduso: Dated 08-04-2023
     * @access public
     * @param Int $voucher_id - voucher id
     * @return float - returns summed up cash
     */

    public function selected_voucher_income_total_cost(int $voucher_id): float
    {

        $this->read_db->select_sum('voucher_detail_total_cost');

        $this->read_db->where(['fk_voucher_id' => $voucher_id]);

        return $this->read_db->get('voucher_detail')->row()->voucher_detail_total_cost;
    }
    /**
     *get_effect_code_and_account_code(): Returns cash recieved in the bank or cash deposit in petty cash box on the selected voucher
     * @author Livingstone Onduso: Dated 08-04-2023
     * @access public
     * @param Int $voucher_id - voucher id
     * @return float - returns summed up cash
     */

    public function get_effect_code_and_account_code(int $voucher_type_id)
    {

        $this->read_db->select(array('voucher_type_effect_code', 'voucher_type_account_code'));
        $this->read_db->where(array('voucher_type_id' => $voucher_type_id));
        $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $this->read_db->join('voucher_type_account', 'voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
        return $this->read_db->get('voucher_type')->row();
    }

    /**
     * @todo not yet used
     */
    public function detail_table_relationships()
    {
        $relationship['voucher_detail']['foreign_key'] = 'fk_voucher_id';

        return $relationship;
    }

    public function get_journal_for_current_vouching_month($voucher_date, $office_id)
    {

        $this->read_db->select(['journal_month']);
        $this->read_db->where(['fk_office_id' => $office_id]);
        $this->read_db->where(['journal_month' => $voucher_date]);
        $this_month_journal_obj = $this->read_db->get('journal');

        $this_month_journal = [];

        if ($this_month_journal_obj->num_rows() > 0) {
            $this_month_journal = $this_month_journal_obj->row_array();
        }

        return $this_month_journal;
    }

    public function get_financial_report_for_current_vouching_month($voucher_date, $office_id)
    {

        $this->read_db->select(['financial_report_month']);
        $this->read_db->where(['fk_office_id' => $office_id]);
        $this->read_db->where(['financial_report_month' => $voucher_date]);
        $this_month_mfr_obj = $this->read_db->get('financial_report');

        $this_month_mfr = [];

        if ($this_month_mfr_obj->num_rows() > 0) {
            $this_month_mfr = $this_month_mfr_obj->row_array();
        }

        return $this_month_mfr;
    }

    public function detail_list()
    {
    }

    public function master_multi_form_add_visible_columns()
    {
        return array(
            'office_name', 'voucher_type_name', 'voucher_number', 'voucher_date', 'office_bank_name',
            'voucher_cheque_number', 'voucher_vendor', 'voucher_description',
        );
    }

    public function list()
    {
    }

    public function view()
    {
    }

    public function list_table_visible_columns()
    {

        return array(
            'voucher_track_number', 'voucher_number', 'voucher_date', 'voucher_cheque_number',
            'voucher_is_reversed', 'voucher_created_date', 'office_name', 'voucher_type_name', 'status_name',
        );
    }

    public function detail_list_table_visible_columns()
    {
        return array(
            'voucher_detail_track_number', 'voucher_detail_description', 'voucher_detail_quantity',
            'voucher_detail_cost', 'voucher_detail_total_cost', 'expense_account_name', 'income_account_name',
        );
    }

    public function master_table_visible_columns()
    {
        return array(
            'office_name', 'voucher_type_name', 'voucher_number', 'voucher_date', 'voucher_cheque_number',
            'voucher_vendor', 'voucher_description', 'voucher_created_date',
        );
    }

    public function edit_visible_columns()
    {
        return $this->master_table_visible_columns();
    }

    /**Local methods**/

    /**
     * Get Voucher Date
     *
     * This method computes the next valid vouching date for a given office
     * @param Int $office_id - The primary key of the office
     * @return String - The next valid vouching date
     *
     */

    public function get_voucher_date(Int $office_id, string $journal_month = ''): String
    {
        //return date('Y-m-t');
        $voucher_date = $this->read_db->get_where('office', array('office_id' => $office_id))->row()->office_start_date;

        $office_transaction_date = $this->get_office_transacting_month($office_id);

        // //if $journal_month!='' use journal month
        // if($journal_month!=''){
        //   $voucher_date=$journal_month;
        // }else{

        //   if (count($this->get_office_last_voucher($office_id)) > 0) {
        //     $voucher_date = $this->get_office_last_voucher($office_id)['voucher_date'];
        //   }

        //   if (strtotime($office_transaction_date) > strtotime($voucher_date)) {
        //     $voucher_date = $office_transaction_date;
        //   }
        // }

        //if $journal_month!='' use journal month

        if (count($this->get_office_last_voucher($office_id)) > 0) {
            $voucher_date = $this->get_office_last_voucher($office_id, $journal_month)['voucher_date'];
        }

        if (strtotime($office_transaction_date) > strtotime($voucher_date)) {
            $voucher_date = $office_transaction_date;
        }

        return $voucher_date;
    }

    /**
     * Get Voucher Number
     *
     * The method computes the next valid voucher number. The voucher numbers are in the format YYMMSS where YY is the fiscal year and MM is the month whe transaction
     * belongs to. SS is the voucher serial number incremented from 1 (First Voucher of the month)
     *
     * @param Int $office_id - The primary key of the office
     * @return Int - The next valid voucher number
     */
    public function get_voucher_number(Int $office_id, string $journal_month = ''): Int
    {

        $office_transacting_month = '';

        $office_transacting_month = $this->get_office_transacting_month($office_id);

        /*New code added from here. Date of addition 26-02-2024
        If reversal use the date of voucher as the transacting month else use the get_office_transacting_month to compute the transacting months.*/
        /*If current month report is submitted=true get the date on curent month use it to compute next serial new CJ
        If current month not submitted use it to get the last voucher date and use it to compute next serial

         */

        if ($journal_month != '') {

            $mfr_submitted = $this->financial_report_model->check_if_financial_report_is_submitted([$office_id], $journal_month);

            if ($mfr_submitted != true) {
                $office_transacting_month = $journal_month; //date('Y-m-01',strtotime($journal_month));
            }

        }

        //New code added here
        $next_serial_number = $this->get_voucher_next_serial_number($office_id, $office_transacting_month);

        // log_message('error', json_encode(['office_transacting_month' => $office_transacting_month, 'next_serial_number' => $next_serial_number]));

        return $this->compute_voucher_number($office_transacting_month, $next_serial_number);
    }

    /**
     * Check if Office Transaction Month Has Been Closed
     *
     * Finds out if the date passed as an argument belongs to a month whose vouching process has been closed based on whether the financial report (Bank Reconciliation)
     * has been created and submitted.
     *
     * @param Int $office_id - Office primary key
     * @param String $date_of_month - Date of the month in check
     * @return Bool - True if reconciliation has been created else false
     */
    public function check_if_office_transacting_month_has_been_closed(Int $office_id, String $date_of_month): Bool
    {
        // If the reconciliation of the max date month has been done and submitted,
        // then use the start date of the next month as the transacting date
        // *** Modify the query by checking if it has been submitted - Not yet done ****

        $check_month_reconciliation = $this->read_db->get_where(
            'financial_report',
            array(
                'financial_report_is_submitted' => 1, 'fk_office_id' => $office_id,
                'financial_report_month' => date('Y-m-01', strtotime($date_of_month)),
            )
        )->num_rows();

        return $check_month_reconciliation > 0 ? true : false;
    }
    /**
     *get_voucher_header_to_edit(): Returns a row of voucher information from voucher table [Main header table]
     * @author Livingstone Onduso: Dated 08-05-2023
     * @access public
     * @param Int $voucher_id - voucher id
     * @return array - returns one row of array
     */

    public function get_voucher_header_to_edit(int $voucher_id): array
    {

        $this->read_db->select(
            [
                'voucher_id', 
                'voucher.fk_funder_id as funder_id', 
                'funder_name', 
                'office_id', 
                'office_code',
                'office_name', 
                'voucher_type_account_name', 
                'voucher_type_effect_name', 
                'voucher_type_account_code', 
                'voucher_type_effect_code', 
                'voucher_type_is_cheque_referenced', 
                'voucher_number', 
                'voucher_date', 
                'fk_voucher_type_id as voucher_type_id', 
                'voucher_type_name', 
                'fk_office_bank_id as office_bank_id', 
                'fk_office_cash_id as office_cash_id', 
                'office_bank_name', 
                'voucher_cheque_number', 
                'voucher_vendor', 
                'voucher_vendor_address', 
                'voucher_description'
            ]);
        $this->read_db->join('office', 'office.office_id=voucher.fk_office_id');
        $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $this->read_db->join('voucher_type_account', 'voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
        $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $this->read_db->join('office_bank', 'office_bank.office_bank_id=voucher.fk_office_bank_id', 'left');
        $this->read_db->join('funder', 'funder.funder_id=voucher.fk_funder_id');
        $this->read_db->join('office_cash', 'office_cash.office_cash_id=voucher.fk_office_cash_id','left');
        $this->read_db->join('voucher_detail', 'voucher_detail.fk_voucher_id=voucher.voucher_id');
        $this->read_db->where(['voucher_id' => $voucher_id]);
        $voucher_to_edit = $this->read_db->get('voucher')->row_array();

        return $voucher_to_edit;
    }

    private function get_voucher_effect_code_by_voucher_id($voucher_id){
        $this->read_db->select('voucher_type_effect_code');
        $this->read_db->where(['voucher_id' => $voucher_id]);
        $this->read_db->join('voucher_type', 'voucher_type.fk_voucher_type_effect_id=voucher_type_effect.voucher_type_effect_id');
        $this->read_db->join('voucher', 'voucher.fk_voucher_type_id=voucher_type.voucher_type_id');
        $voucher_type_effect_code = $this->read_db->get('voucher_type_effect')->row_array()['voucher_type_effect_code'];

        return $voucher_type_effect_code;
    }
    /**
     *get_voucher_detail_to_edit(): Returns a rows of voucher details information from voucher_detail table
     * @author Livingstone Onduso: Dated 08-05-2023
     * @access public
     * @param Int $voucher_id - voucher id String voucher_effect_name
     * @return array - returns array
     */
    public function get_voucher_detail_to_edit(int $voucher_id): array
    {   

        $voucher_effect_code = $this->get_voucher_effect_code_by_voucher_id($voucher_id);
        // log_message('error', json_encode($voucher_effect_name));

        $this->read_db->select(['voucher_detail_id', 'voucher_detail_quantity', 'voucher_detail_unit_cost', 'voucher_detail_total_cost', 'voucher_detail_description', 'fk_project_allocation_id','project_allocation_id', 'project_name', 'project_id']);
        //Check if contra or expense. Always transaction will account_id so no need to check if income
        if ($voucher_effect_code == 'expense') {

            $this->read_db->select(['fk_expense_account_id as account_id', 'expense_account_name as account_name', 'expense_account.fk_income_account_id', 'income_account_name']);
            $this->read_db->join('expense_account', 'expense_account.expense_account_id=voucher_detail.fk_expense_account_id');
            $this->read_db->join('income_account', 'income_account.income_account_id=voucher_detail.fk_income_account_id');

        } elseif ($voucher_effect_code == 'cash_contra' || $voucher_effect_code == 'bank_contra') {

            $this->read_db->select(['contra_account_id as account_id','contra_account_name  as account_name']);
            $this->read_db->join('contra_account', 'contra_account.contra_account_id=voucher_detail.fk_contra_account_id');

        } elseif ($voucher_effect_code == 'income') {
            $this->read_db->select(array('income_account_name as account_name', 'fk_income_account_id as account_id'));
            $this->read_db->join('income_account', 'income_account.income_account_id=voucher_detail.fk_income_account_id');
        }

        $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');
        $this->read_db->join('project', 'project.project_id=project_allocation.fk_project_id');
        $this->read_db->where(['fk_voucher_id' => $voucher_id]);
        $voucher_detail_to_edit = $this->read_db->get('voucher_detail')->result_array();

        return $voucher_detail_to_edit;

    }

    /**
     * Check if Office Has Started Transacting
     *
     * Finds out if the argument offfice has began raising vouchers
     *
     * @param Int $office_id - Office in check
     * @return Bool - True if has began raising vouchers else false
     */
    public function check_if_office_has_started_transacting(Int $office_id): Bool
    {
        // If the office has not voucher yet, then the transacting month equals the office start date
        $count_of_vouchers = $this->read_db->get_where('voucher', array('fk_office_id' => $office_id))->num_rows();

        return $count_of_vouchers > 0 ? true : false;
    }

    /**
     * get_active_project_expenses_accounts
     * @date: 13 Nov 2023
     *
     * @return Array
     * @author Onduso
     */
    public function get_active_project_expenses_accounts(int $project_id, int $voucher_type_id = 0): array
    {

        //Get the voucher_type

        $voucher_type_effect = $this->get_voucher_type_effect($voucher_type_id)['voucher_type_effect_code'];

        //Get incomes for a given project then loop to regroup them
        $this->read_db->select(['fk_income_account_id']);
        $this->read_db->where(['fk_project_id' => $project_id]);
        $project_income_account_ids = $this->read_db->get('project_income_account')->result_array();

        $income_ids = [];

        foreach ($project_income_account_ids as $project_income_account_id) {
            $income_ids[] = $project_income_account_id['fk_income_account_id'];
        }

        //if voucher_type=income get the income names and codes
        if ($voucher_type_effect == 'income') {
            $accounts_ids_and_names = $this->get_accounts_ids_and_name('income_account', 'income_account_id', $income_ids); //array_combine($income_acc_ids,$income_acc_names);

        } else if ($voucher_type_effect == 'expense') {

            // //Get the expenses for each of the income_accounts
            $accounts_ids_and_names = $this->get_accounts_ids_and_name('expense_account', 'fk_income_account_id', $income_ids, true);
        } else if ($voucher_type_effect == 'bank_to_bank_contra') {

            //what of contra
            $accounts_ids_and_names = $this->get_accounts_ids_and_name('contra_account', 'contra_account_id', $income_ids);
        }

        return $accounts_ids_and_names;

    }
    /**
     * get_accounts_ids_and_name
     * @date: 18 Dec 2023
     *
     * @return Array
     * @access private
     * @author Onduso
     * @param : string $table, string $income_account_id_col, array $income_ids, $remove_T_expense_name=false
     */
    private function get_accounts_ids_and_name(string $table, string $income_account_id_col, array $income_ids, $remove_T_expense_name = false): array
    {

        $this->read_db->select([$table . '_id', $table . '_name']);
        $this->read_db->where_in($income_account_id_col, $income_ids);
        if ($table != 'contra_account') {
            $this->read_db->where([$table . '_is_active' => 1]);
        }

        if ($remove_T_expense_name == true) {
            $this->read_db->not_like($table . '_name', 'T', 'after');
        }

        $accounts = $this->read_db->get($table)->result_array();

        $account_ids = array_column($accounts, $table . '_id');
        $account_names = array_column($accounts, $table . '_name');

        $accounts_ids_and_names = array_combine($account_ids, $account_names);

        return $accounts_ids_and_names;
    }

    public function office_has_vouchers_for_the_transacting_month($office_id, $transacting_month)
    {

        $month_start_date = date('Y-m-01', strtotime($transacting_month));
        $month_end_date = date('Y-m-t', strtotime($transacting_month));

        // log_message('error', json_encode([$office_id,$month_start_date, $month_end_date]));

        $voucher_count_for_the_month = $this->read_db->get_where(
            'voucher',
            array('fk_office_id' => $office_id, 'voucher_date>=' => $month_start_date, 'voucher_date<=' => $month_end_date)
        )->num_rows();

        $journal_count_for_the_month = $this->read_db->get_where(
            'journal',
            array('fk_office_id' => $office_id, 'journal_month' => $month_start_date)
        )->num_rows();

        $financial_report_count_for_the_month = $this->read_db->get_where(
            'financial_report',
            array('fk_office_id' => $office_id, 'financial_report_month' => $month_start_date)
        )->num_rows();

        $office_has_vouchers_for_the_transacting_month = false;

        if ($voucher_count_for_the_month > 0 && $journal_count_for_the_month > 0 && $financial_report_count_for_the_month > 0) {
            $office_has_vouchers_for_the_transacting_month = true;
        }

        return $office_has_vouchers_for_the_transacting_month;
    }

    // /**
    //  * Get Office Last Voucher
    //  *
    //  * The methods get the last voucher record for a given office
    //  *
    //  * @param Int $office_id - Office in check
    //  * @return Array - a voucher record
    //  */
    // function get_office_last_voucher($office_id): array
    // {

    //   $last_voucher = [];
    //   $office_has_started_transacting = $this->check_if_office_has_started_transacting($office_id);

    //   // log_message('error', json_encode($office_has_started_transacting));

    //   if ($office_has_started_transacting) {

    //     $financial_report_month = '';

    //     //If voucher_reversal use the journal month and not report month

    //     // Get the oldest unsubmitted financial report for the office
    //     $this->read_db->select_min('financial_report_month');
    //     $this->read_db->where(array('financial_report_is_submitted' => 0, 'fk_office_id' => $office_id));
    //     $financial_report_month_obj = $this->read_db->get('financial_report');

    //     if ($financial_report_month_obj->row()->financial_report_month > 0) {

    //       //log_message('error','Has unsubmitted MFR');
    //       $financial_report_month = $financial_report_month_obj->row()->financial_report_month;

    //       // Check the max voucher id of the oldest unsubmitted reporting month for the office
    //       $this->read_db->where(array(
    //         'voucher.fk_office_id' => $office_id,
    //         'voucher_date >=' => date('Y-m-01', strtotime($financial_report_month)),
    //         'voucher_date <=' => date('Y-m-t', strtotime($financial_report_month))
    //       ));

    //       $voucher_id = $this->read_db->select_max('voucher_id')->get('voucher')->row()->voucher_id;

    //       $this->read_db->select(['voucher_id', 'voucher_number', 'voucher_date']);
    //       $this->read_db->where(['voucher_id' => $voucher_id]);
    //       $last_voucher = $this->read_db->get('voucher')->row_array();

    //       // Retrieve the voucher record for oldest unsubmitted reporting month

    //       // If voucher_id is null then no vouchers in tha month [e.g. all month vouchers have been deleted]
    //       if (empty($last_voucher)) {

    //         $office_transacting_month = $this->read_db->get_where('office', array('office_id' => $office_id))->row()->office_start_date;

    //         $start_office_month = date('Y-m-01', strtotime($office_transacting_month));

    //         $calculated_month_from_voucher = date('Y-m-01', strtotime($financial_report_month . '- 1 months'));

    //         // $calculated_month= date('m',strtotime($calculated_month_from_voucher));

    //         // echo $calculated_month; echo '</br>';
    //         // echo $start_office_month; echo '</br>';
    //         //Check if the month calculated based on vouchers is below the office start date. If so use the the office_transacting_month to get the first voucher number
    //         if ($calculated_month_from_voucher > $start_office_month) {

    //           $this->read_db->where([
    //             'voucher_date >=' => date('Y-m-01', strtotime($financial_report_month . '- 1 months')),
    //             'voucher_date <=' => date('Y-m-t', strtotime($financial_report_month . '- 1 months')),
    //             'fk_office_id' => $office_id
    //           ]);

    //           $voucher_id = $this->read_db->select_max('voucher_id')->get('voucher')->row()->voucher_id;

    //           $last_voucher = $this->read_db->get_where('voucher', ['voucher_id' => $voucher_id])->row_array();
    //         } else {
    //           //Construct the first voucher of the month
    //           $year = date("y", strtotime($office_transacting_month));

    //           $month = date('m', strtotime($office_transacting_month));

    //           $voucher_number = $year . $month . '00';

    //           $last_voucher = ['voucher_date' => $office_transacting_month, 'voucher_number' => $voucher_number];
    //         }
    //       }
    //     } else {

    //       //log_message('error','All MFR Submitted');
    //       // Check the max voucher id of the office provided

    //       /*Original Code......................................................
    //         $voucher_id = $this->read_db->select_max('voucher_id')->get_where('voucher',
    //         array('fk_office_id'=>$office_id))->row()->voucher_id;

    //         $last_voucher = $this->read_db->get_where('voucher',array('voucher_id'=>$voucher_id))->row_array();
    //         End of old code with bug...........................................*/

    //       /*New code
    //         [Get submitted MFR with Max value of financial_report_month] [Bug referenced with SN ticket INC0197964]*/
    //       $this->read_db->select_max('financial_report_month');
    //       $this->read_db->where(array('fk_office_id' => $office_id));
    //       $financial_report_month_arr = $this->read_db->get('financial_report')->row_array();

    //       //Get max voucher_id based on max financial_report_month
    //       $this->read_db->select_max('voucher_id');
    //       $this->read_db->where(array(
    //         'fk_office_id' => $office_id, 'voucher_date >= ' => date('Y-m-01', strtotime($financial_report_month_arr['financial_report_month'])),
    //         'voucher_date <= ' => date('Y-m-t', strtotime($financial_report_month_arr['financial_report_month']))
    //       ));
    //       $voucher_id = $this->read_db->get('voucher')->row()->voucher_id;

    //       $last_voucher = $this->read_db->get_where('voucher', array('voucher_id' => $voucher_id))->row_array();

    //       //End of new code
    //     }
    //   }

    //   return $last_voucher;
    // }

    /**
     * Get Office Last Voucher
     *
     * The methods get the last voucher record for a given office
     *
     * @param Int $office_id - Office in check
     * @return Array - a voucher record
     */
    public function get_office_last_voucher($office_id, $journal_month = '')
    {

        $last_voucher = [];

        $office_has_started_transacting = $this->check_if_office_has_started_transacting($office_id);

        if ($office_has_started_transacting) {

            $financial_report_month = '';

            //If voucher_reversal use the journal month and not report month
            /*Scenerios:
            Scenerio 1: Report where reversal is happening is submitted [Find latest report and insert voucher there. Date should be computed for latest month]
            Scenerio 2: Repoert where reversal is happening is NOT submitted [Insert voucher in the same month where reversal happening
             */

            if ($journal_month != '') {

                //Check if report is submitted;if submitted get the max report then get lastest voucher date and there insert voucher
                $mfr_submitted = $this->financial_report_model->check_if_financial_report_is_submitted([$office_id], $journal_month);

                if ($mfr_submitted == true) {

                    //get max unsubmitted report and get the last transaction voucher
                    $financial_report_month_obj = $this->select_max_financial_report($office_id,true);

                    //if >0 get the last voucher date
                    if ($financial_report_month_obj->num_rows() > 0) {

                        $financial_report_month = $financial_report_month_obj->row()->financial_report_month;

                        $last_voucher = $this->get_max_voucher($office_id, $financial_report_month);

                        if (empty($last_voucher)) {
                            $last_voucher = $this->get_calculated_last_voucher($office_id, $financial_report_month_obj->row()->financial_report_month);
                        }

                    }


                } else {

                    // Check the max voucher id of the oldest unsubmitted reporting month for the office
                    $last_voucher= $this->get_max_voucher($office_id, $journal_month);

                }

            } else {

                // Get the oldest unsubmitted financial report for the office

                $financial_report_month_obj = $this->select_max_financial_report($office_id,false);

                if ($financial_report_month_obj->row()->financial_report_month > 0) {

                    //log_message('error','Has unsubmitted MFR');
                    $financial_report_month = $financial_report_month_obj->row()->financial_report_month;

                    // Check the max voucher id of the oldest unsubmitted reporting month for the office

                    $last_voucher=$this->get_max_voucher($office_id, $financial_report_month);

                    // Retrieve the voucher record for oldest unsubmitted reporting month
                    // If voucher_id is null then no vouchers in tha month [e.g. all month vouchers have been deleted]
                    if (empty($last_voucher)) {

                        $this->get_calculated_last_voucher($office_id, $financial_report_month);

                    }
                } else {

                    //log_message('error','All MFR Submitted');
                    // Check the max voucher id of the office provided

                    /*Original Code......................................................
                    $voucher_id = $this->read_db->select_max('voucher_id')->get_where('voucher',
                    array('fk_office_id'=>$office_id))->row()->voucher_id;

                    $last_voucher = $this->read_db->get_where('voucher',array('voucher_id'=>$voucher_id))->row_array();
                    End of old code with bug...........................................*/

                    /*New code
                    [Get submitted MFR with Max value of financial_report_month] [Bug referenced with SN ticket INC0197964]*/
                  
                    //Get max voucher_id based on max financial_report_month
                    $financial_report_month_obj=$this->select_max_financial_report($office_id,true);

                    $financial_report_month = $financial_report_month_obj->row()->financial_report_month;

                    $last_voucher=$this->get_max_voucher($office_id, $financial_report_month);
                   
                    //End of new code
                }

            }

        }

        return $last_voucher;
    }

    /**
     * get_max_voucher
     * Get the maximum voucher of the month
     * @param Int $office_id, string $financial_report_month
     * @author Livingstone Onduso.
     * @date 2024-03-15
     */
    private function get_max_voucher(int $office_id, string $financial_report_month)
    {

        //Get the max voucher of the passed month
        $this->read_db->select_max('voucher_id');
        $this->read_db->where(['voucher.fk_office_id' => $office_id, 'voucher_date >=' => date('Y-m-01', strtotime($financial_report_month)),
            'voucher_date <=' => date('Y-m-t', strtotime($financial_report_month))]);

        $voucher_id = $this->read_db->get('voucher')->row()->voucher_id;

        $this->read_db->select(['voucher_id', 'voucher_number', 'voucher_date']);

        $this->read_db->where(['voucher_id' => $voucher_id]);

        return $this->read_db->get('voucher')->row_array();
    }

    /**
     * select_max_financial_report
     * Get the maximum/minimum mfrs of the month
     * @param Int $office_id, bool $max_mfr
     * @author Livingstone Onduso.
     * @date 2024-03-15
     */
    private function select_max_financial_report(int $office_id, bool $max_mfr)
    {

      //Get the Max mfr or Min depending on the boolean '$max_mfr'
       if($max_mfr==true){

        $this->read_db->select_max('financial_report_month');

        $this->read_db->where(array('fk_office_id' => $office_id));

        $financial_report_month_obj=$this->read_db->get('financial_report');

       }else{

        $this->read_db->select_min('financial_report_month');

        $this->read_db->where(array('financial_report_is_submitted' => 0, 'fk_office_id' => $office_id));

        $financial_report_month_obj = $this->read_db->get('financial_report');
       }

       return $financial_report_month_obj;  
    }

     /**
     * get_calculated_last_voucher
     * Get the calculated last voucher of the months
     * @param Int $office_id, string $financial_report_month
     * @author Livingstone Onduso.
     * @date 2024-03-15
     */
    private function get_calculated_last_voucher(int $office_id, string $financial_report_month)
    {

        $office_transacting_month = $this->read_db->get_where('office', array('office_id' => $office_id))->row()->office_start_date;

        $start_office_month = date('Y-m-01', strtotime($office_transacting_month));

        $calculated_month_from_voucher = date('Y-m-01', strtotime($financial_report_month . '- 1 months'));

        // $calculated_month= date('m',strtotime($calculated_month_from_voucher));

        // echo $calculated_month; echo '</br>';
        // echo $start_office_month; echo '</br>';
        //Check if the month calculated based on vouchers is below the office start date. If so use the the office_transacting_month to get the first voucher number
        if ($calculated_month_from_voucher > $start_office_month) {

            $this->read_db->where([
                'voucher_date >=' => date('Y-m-01', strtotime($financial_report_month . '- 1 months')),
                'voucher_date <=' => date('Y-m-t', strtotime($financial_report_month . '- 1 months')),
                'fk_office_id' => $office_id,
            ]);

            $voucher_id = $this->read_db->select_max('voucher_id')->get('voucher')->row()->voucher_id;

            $last_voucher = $this->read_db->get_where('voucher', ['voucher_id' => $voucher_id])->row_array();
        } else {
            //Construct the first voucher of the month
            $year = date("y", strtotime($office_transacting_month));

            $month = date('m', strtotime($office_transacting_month));

            $voucher_number = $year . $month . '00';

            $last_voucher = ['voucher_date' => $office_transacting_month, 'voucher_number' => $voucher_number];
        }

        return $last_voucher;
    }

    /**
     * get_office_transacting_month
     *
     * This methods gives the date of the first day of the valid transaction month of an office
     *
     * @param Int $office - Office in check
     * @return String - Date of the first day of the valid transacting month
     */
    public function get_office_transacting_month(Int $office_id): String
    {

        $office_transacting_month = date('Y-m-01');

        //If count_of_vouchers eq to 0 then get the start date if the office
        if (!$this->check_if_office_has_started_transacting($office_id)) {
            $office_transacting_month = $this->read_db->get_where('office', array('office_id' => $office_id))->row()->office_start_date;
        } else {

            // Get the last office voucher date
            $voucher_date = $this->get_office_last_voucher($office_id)['voucher_date'];

            // Check if the transacting month has been closed based on the last voucher date

            if ($this->check_if_office_transacting_month_has_been_closed($office_id, $voucher_date)) {
                // echo $voucher_date; exit();
                $office_transacting_month = date('Y-m-d', strtotime('first day of next month', strtotime($voucher_date)));
            } else {
                $office_transacting_month = date('Y-m-01', strtotime($voucher_date));
            }
        }

        return $office_transacting_month;
    }

    /**
     * Get Voucher Next Serial Number
     *
     * Computes the next voucher serial number i.e. The 5th + digits in a voucher number
     *
     * @param Int $office_id - Office in Check
     * @return Int - Next voucher serial number
     */
    public function get_voucher_next_serial_number(Int $office_id, string $journal_month = ''): Int
    {

        // Set default serial number to 1 unless adding to a series in a month
        $next_serial = 1;

        $last_voucher = $this->get_office_last_voucher($office_id, $journal_month);

        // log_message('error', json_encode($last_voucher));

        // Start checking if the office has a last voucher record
        if (count((array) $last_voucher) > 0) {
            $last_voucher_number = $last_voucher['voucher_number'];
            $last_voucher_date = $last_voucher['voucher_date'];

            $transacting_month_has_been_closed = $this->check_if_office_transacting_month_has_been_closed($office_id, $last_voucher_date);
            // log_message('error', json_encode($transacting_month_has_been_closed));
            if (!$transacting_month_has_been_closed) {
                // Get the serial number of the last voucher, replace the month and year part of the
                // voucher number with an empty string to remain with only the voucher serial number
                //voucher format - yymmss or yymmsss
                $current_voucher_serial_number = substr_replace($last_voucher_number, '', 0, 4);
                $next_serial = $current_voucher_serial_number + 1;
            }
        }

        return $next_serial;
    }

    /**
     * Compute Voucher Number
     *
     * This method computes the next valid voucher number by concatenating the YY, MM and SS together.
     * YY - Vouching Year, MM - Vouching Month and SS - Voucher Serial Number in the month
     *
     * @param String $vouching_month - Date the voucher is being raised
     * @param Int $next_voucher_serial - Next valid voucher serial number
     * @return Int - A Voucher number
     */
    public function compute_voucher_number(String $vouching_month, Int $next_voucher_serial = 1): Int
    {

        $chunk_year_from_date = date('y', strtotime($vouching_month));
        $chunk_month_from_date = date('m', strtotime($vouching_month));

        if ($next_voucher_serial < 10) {
            $next_voucher_serial = '0' . $next_voucher_serial;
        }

        return $chunk_year_from_date . $chunk_month_from_date . $next_voucher_serial;
    }

    /**
     * Has Cheque Number Been Used
     *
     * Validates if a given cheque number has been used by a voucher
     *
     * @param Int $office_bank - Primary key of the associated bank to the this office
     * @param Int $cheque_number - The cheque number to check
     * @return Bool - True if cheque has been used else false
     */
    private function has_cheque_number_been_used($office_bank, $cheque_number)
    {
        // Check if the cheque number for the give bank has been used
        $count_of_used_cheque = $this->read_db->get_where(
            'voucher',
            array('fk_office_bank_id' => $office_bank, 'voucher_cheque_number' => $cheque_number)
        )->num_rows();

        return $count_of_used_cheque > 0 ? true : false;
    }

    /**
     * Is Next Valid Cheque Number
     *
     * Check if the passed cheque number is a next valid one.
     * It looks whether the check is not yet used in a voucher and is 1 step incremental from the maximum used cheque for a given bank.
     * If there are no vouchers for the office, the next valid cheque number is the starting cheque leaf serial as recorded in the system for the active cheque book.
     * @param Int $office_bank - Primary key of the associated back to the office
     * @param Int $cheque_number - the cheque number is check
     * @return Bool The next valid cheque number
     */
    private function is_next_valid_cheque_number(Int $office_bank, Int $cheque_number)
    {

        $valid_next_cheque_number = false;

        $active_cheque_book_obj = $this->read_db->select(array(
            'cheque_book_start_serial_number',
            'cheque_book_count_of_leaves',
        ))->get_where(
            'cheque_book',
            array('fk_office_bank_id' => $office_bank, 'cheque_book_is_active' => 1)
        );

        if ($active_cheque_book_obj->num_rows() > 0) {
            $valid_next_cheque_number = $active_cheque_book_obj->row()->cheque_book_start_serial_number;
        }

        // Max used cheque number for the bank
        $cheque_obj = $this->read_db->get_where(
            'voucher',
            array('fk_office_bank_id' => $office_bank)
        );

        if ($cheque_obj->num_rows() > 0) {
            $max_used_cheque_obj = $this->read_db->select_max('voucher_cheque_number')->get_where(
                'voucher',
                array('fk_office_bank_id' => $office_bank)
            );
            $valid_next_cheque_number = $max_used_cheque_obj->row()->voucher_cheque_number + 1;
        }

        return $valid_next_cheque_number != $cheque_number ? false : true;
    }

    /**
     * Is Cheque Leaf In Active Cheque Book
     *
     * It determines if the a cheque number is within the active cheque book for an office
     * @param Int $office_bank - Primary key of the associated bank to the office
     * @param Int $cheque_number - Cheque number is check
     * @return Bool - True if is within else false
     */
    private function is_cheque_leaf_in_active_cheque_book(Int $office_bank, Int $cheque_number): Bool
    {

        $cheque_number_in_active_cheque_book = false;

        // Check if the provided cheque number is within the current/active cheque book
        $active_cheque_book_obj = $this->read_db->select(array('cheque_book_start_serial_number', 'cheque_book_count_of_leaves'))->get_where(
            'cheque_book',
            array('fk_office_bank_id' => $office_bank, 'cheque_book_is_active' => 1)
        );

        if ($active_cheque_book_obj->num_rows() == 1) {

            $first_leaf_serial = $active_cheque_book_obj->row()->cheque_book_start_serial_number;
            $number_of_leaves = $active_cheque_book_obj->row()->cheque_book_count_of_leaves;

            $list_of_cheque_leaves = range($first_leaf_serial, $number_of_leaves);

            if (in_array($cheque_number, $list_of_cheque_leaves)) {
                $cheque_number_in_active_cheque_book = true;
            }
        }

        return $cheque_number_in_active_cheque_book;
    }

    /**
     * Validate Cheque Number
     *
     * Checks if a cheque number is valid for an office
     * A valid cheque number should be:
     * - not have been used,
     * - Be sequential in order (If the config item allow_skipping_of_cheque_leaves is set to false)
     * - Be a present leaf in the current active cheque book
     * @param String $office_bank - Office bank id
     * @param int $cheque_number - Cheque number
     * @return Bool - True is a valid cheque number else false
     * @todo This method is decaprecated - To be removed in the future implementations
     */
    public function validate_cheque_number(String $office_bank, int $cheque_number): Bool
    {

        $is_valid_cheque = true;

        if (
            $this->has_cheque_number_been_used($office_bank, $cheque_number)
            || (!$this->is_next_valid_cheque_number($office_bank, $cheque_number)
                && !$this->config->item("allow_skipping_of_cheque_leaves")
            )
            || !$this->is_cheque_leaf_in_active_cheque_book($office_bank, $cheque_number)
        ) {
            $is_valid_cheque = false;
        }

        return $is_valid_cheque;
    }

    /**
     * Populate Office Banks
     *
     * Gives an array of the banks associated to the office
     *
     * @param Int $office_id - Office to check
     * @return Array - An array if Office banks
     */
    public function populate_office_banks(Int $office_id): array
    {

        $office_banks = array();

        $office_banks_obj = $this->read_db->select(array('office_bank_id', 'office_bank_name'))->get_where(
            'office_bank',
            array('fk_office_id' => $office_id)
        );

        if ($office_banks_obj->num_rows() > 0) {
            $office_banks = $office_banks_obj->result_array();
        }

        return $office_banks;
    }

    /**
     * Get the signitories
     *
     * Gives an array of the voucher signitories
     *
     * @param Int $office - the id office
     * @return Array - An array
     * @author LOnduso
     */
    public function get_voucher_signitories(Int $office): array
    {

        $voucher_signatory = array();

        //Get the signitories of a given office of a given accounting system
        $this->read_db->select(array('voucher_signatory_name'));
        $this->read_db->join('account_system', 'account_system.account_system_id=voucher_signatory.fk_account_system_id');
        $this->read_db->join('office', 'office.fk_account_system_id=account_system.account_system_id');
        $this->read_db->where(array('office_id' => $office, 'voucher_signatory_is_active' => 1));
        $voucher_signatory = $this->read_db->get('voucher_signatory')->result_array();

        return $voucher_signatory;
    }

    /**
     * Get the get_cheques_for_office
     *
     * Gives an array of the voucher signitories
     *
     * @param Int $office - the id office
     * @return Array - An array
     * @author LOnduso
     */
    public function get_cheques_for_office(Int $office, Int $bank_office_id, Int $cheque_number): Int
    {

        //Get the cheque numbers for an office for a given bank office
        $this->read_db->select(array('voucher_cheque_number'));
        $this->read_db->where(array('fk_office_id' => $office, 'fk_office_bank_id' => $bank_office_id, 'voucher_cheque_number' => $cheque_number));
        $cheque_numbers = $this->read_db->get('voucher')->num_rows();

        return $cheque_numbers;
    }

    /**
     * Get Approveable Item Last Status
     *
     * Gives the Last Approval Status ID of the item as the set approval workflow
     * @param Int $approveable_item_id
     * @todo - to be transferred to the approve_item model. You will have to load the approve_item model in the voucher model class for this to work
     * @return Int - $status_id
     *
     * @todo - Refactor it by calling the "get_max_approval_status_id" from general model
     */
    public function get_approveable_item_last_status(Int $approveable_item_id): Int
    {

        $this->read_db->join('approval_flow', 'approval_flow.approval_flow_id=status.fk_approval_flow_id');
        $this->read_db->join('approve_item', 'approve_item.approve_item_id=approval_flow.fk_approve_item_id');
        $max_status_approval_sequence = $this->read_db->select_max('status_approval_sequence')->get_where(
            'status',
            array('approve_item_id' => $approveable_item_id)
        )->row()->status_approval_sequence;

        $status_id = $this->read_db->select(array('status_id'))->get_where(
            'status',
            array('status_approval_sequence' => $max_status_approval_sequence, 'fk_approve_item_id' => $approveable_item_id)
        )->row()->status_id;

        return $status_id;
    }

    /**
     * @todo - Not in use
     */
    public function conversion_approval_status($office_id): int
    {

        $approval_status_id = 0;

        $office_account_system_id = $this->read_db->get_where(
            'office',
            array('office_id' => $office_id)
        )->row()->fk_account_system_id;

        $request_conversion_obj = $this->read_db->get_where(
            'request_conversion',
            array('fk_account_system_id' => $office_account_system_id)
        );

        if ($request_conversion_obj->num_rows() > 0) {
            $approval_status_id = $request_conversion_obj->row()->conversion_status_id;
        }

        return $approval_status_id;
    }

    /**
     * Get Approved Unvouched Request Details
     *
     * List all the request details that have been finalised in the approval workflow
     * @return Array
     */
    public function get_approved_unvouched_request_details($office_id)
    {

        $max_approval_status_ids = $this->general_model->get_max_approval_status_id('request');

        $this->read_db->select(array('request_detail_id', 'request_track_number', 'request_detail_description', 'request_detail_quantity', 'request_detail_unit_cost', 'request_detail_total_cost', 'expense_account_name', 'project_name'));

        $this->read_db->join('expense_account', 'expense_account.expense_account_id=request_detail.fk_expense_account_id');
        $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=request_detail.fk_project_allocation_id');
        $this->read_db->join('project', 'project.project_id=project_allocation.fk_project_id');
        $this->read_db->join('request', 'request.request_id=request_detail.fk_request_id');
        $this->read_db->join('status', 'status.status_id=request.fk_status_id');

        $this->read_db->where_in('request.fk_status_id', $max_approval_status_ids);

        $this->read_db->where(array('fk_voucher_id' => 0, 'project_allocation.fk_office_id' => $office_id));
        return $this->read_db->get('request_detail')->result_array();
    }

    /**
     * get_office_project_allocation_for_voucher_details
     *
     * Find the office banks associated to the office being used in the voucher form
     * @return Array
     */
    public function get_office_project_allocation_for_voucher_details(): array
    {
        $office_project_allocation = $this->read_db->select(array('project_allocation_id', 'project_allocation_name'))->get_where(
            'project_allocation',
            array('fk_office_id' => $this->session->voucher_office, 'project_allocation_extended_end_date >= ' => date('Y-m-d'))
        )->result_array();

        return $office_project_allocation;
    }

    /**
     * Lookup Values
     * Options for voucher details tables select fields. Used both on header fields and detail fields
     * @return Array
     */
    public function lookup_values(): array
    {
        return array(
            'office' => $this->config->item('use_context_office') ? $this->session->context_offices : $this->session->hierarchy_offices,
            'project_allocation' => $this->get_office_project_allocation_for_voucher_details(),
        );
    }

    /**
     * get_count_of_request
     * @param
     * @return Integer
     * @author: Onduso
     * @Date: 4/12/2020
     */
    public function get_count_of_unvouched_request($office_id): int
    {

        //$office_id = $this->input->post('office_id');

        $this->read_db->join('request_detail', 'request.request_id=request_detail.fk_request_id');
        $this->read_db->where(array('fk_office_id' => $office_id));
        $this->read_db->where(array('request_is_fully_vouched' => 0));
        $this->read_db->where(array('fk_voucher_id' => 0));

        $unvouched_request = $this->read_db->get('request')->num_rows();

        return $unvouched_request;
    }

    /**
     * get_voucher_type_effect
     * @param int $voucher_type_id
     * @return Array
     * @access public
     * @author: Livingstone Onduso
     * @Date: 4/12/2022
     */
    public function get_voucher_type_effect(int $voucher_type_id): array
    {
        //return (object)['voucher_type_effect_id'=>1,'voucher_type_effect_name'=>'income'];
        $this->read_db->select(array('voucher_type_effect_code', 'voucher_type_id', 'voucher_type_effect_id'));
        $this->read_db->join('voucher_type', 'voucher_type.fk_voucher_type_effect_id=voucher_type_effect.voucher_type_effect_id');
        return $this->read_db->get_where('voucher_type_effect', array('voucher_type_id' => $voucher_type_id))->row_array();
    }

    /**
     *total_cost_for_voucher_to_edit(): Returns the total cost for the voicuher being edited
     * @author Livingstone Onduso: Dated 03-11-2023
     * @access public
     * @return float - json string
     */
    public function total_cost_for_voucher_to_edit(int $voucher_id): float
    {

        $this->read_db->select_sum('voucher_detail_total_cost');
        $this->read_db->where(['fk_voucher_id' => $voucher_id]);
        $total_cost = $this->read_db->get('voucher_detail')->row()->voucher_detail_total_cost;
        return $total_cost;
    }
/**
   * get_transaction_voucher
   * @param int $voucher_type_id
   * @return Array
   * @access public
   * @author: Livingstone Onduso
   * @Date: 24/9/2022
   */
  function get_transaction_voucher(string $id):array
  {

    // Create approvers column 
    $this->grants_model->create_table_approvers_columns('voucher');

    // log_message('error', json_encode($id));
    $this->read_db->select(array('voucher_id',
      'fk_office_id','fk_funder_id', 'fk_office_cash_id', 'voucher_date', 'voucher_number', 'fk_office_bank_id', 'fk_voucher_type_id', 'voucher_cheque_number',
      'voucher_vendor', 'voucher_reversal_from', 'voucher_reversal_to', 'voucher_vendor_address', 'voucher_description', 'voucher_created_date',
      'voucher.fk_status_id as status_id', 'voucher_created_by', 'voucher_is_reversed', 'voucher_type_effect_code', 'voucher_type_account_code'
    ));

    $this->read_db->select(array(
      'voucher_detail_quantity', 'voucher_detail_description', 'voucher_detail_unit_cost', 'voucher_detail_total_cost',
      'fk_expense_account_id', 'fk_income_account_id', 'fk_contra_account_id', 'fk_project_allocation_id','voucher_approvers'
    ));

    $this->read_db->join('voucher_detail', 'voucher_detail.fk_voucher_id=voucher.voucher_id');
    $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
    $this->read_db->join('voucher_type_account', 'voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
    $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');

    return $this->read_db->get_where('voucher', array('voucher_id' => $id))->result_array();
  }

    /**
     * get_voucher_type
     * @param int $voucher_type_id
     * @return object
     * @access public
     * @author: Livingstone Onduso
     * @Date: 4/12/2022
     */
    public function get_voucher_type(int $voucher_type_id): object
    {
        $this->read_db->select(array('voucher_type_effect_id', 'voucher_type_effect_code', 'voucher_type_id', 'voucher_type_name', 'voucher_type_abbrev', 'voucher_type_account_id', 'voucher_type_account_name', 'voucher_type_account_code'));
        $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $this->read_db->join('voucher_type_account', 'voucher_type_account.voucher_type_account_id=voucher_type.fk_voucher_type_account_id');
        $voucher_type = $this->read_db->get_where(
            'voucher_type',
            array('voucher_type_id' => $voucher_type_id)
        )->row();

        return $voucher_type;
    }
/**
 * get_office_bank
 * @param int $office_bank_id
 * @return mixed [Object or Array]
 * @access public
 * @author: Livingstone Onduso
 * @Date: 4/12/2022
 */
    public function get_office_bank(int $office_bank_id)
    {

        $this->read_db->join('bank', 'bank.bank_id=office_bank.fk_bank_id');
        $result = $this->read_db->get_where('office_bank', array('office_bank_id' => $office_bank_id));

        if ($result->num_rows() > 0) {
            return $result->row();
        } else {
            return [];
        }
    }

    /**
     * get_office_cash
     * @param int $account_system_id, int $office_cash_id = 0
     * @return mixed [Object or Array]
     * @access public
     * @author: Livingstone Onduso
     * @Date: 4/12/2022
     */
    public function get_office_cash(int $account_system_id, ?int $office_cash_id = 0)
    {

        $office_cash = [];

        $this->read_db->where(array('fk_account_system_id' => $account_system_id, 'office_cash_id' => $office_cash_id));
        $result = $this->read_db->get('office_cash');

        if ($result->num_rows() > 0) {
            $office_cash = $result->row();
        } 

        return $office_cash;
    }
    /**
     * get_project_allocation
     * @param int $allocation_id
     * @return mixed [Object or Array]
     * @access public
     * @author: Livingstone Onduso
     * @Date: 4/12/2022
     */
    public function get_project_allocation(int $allocation_id)
    {
        $this->read_db->join('project', 'project.project_id=project_allocation.fk_project_id');
        $result = $this->read_db->get_where('project_allocation', array('project_allocation_id' => $allocation_id));

        if ($result->num_rows() > 0) {
            return $result->row();
        } else {
            return [];
        }
    }

    public function list_table_where()
    {

        $max_approval_status_ids = $this->general_model->get_max_approval_status_id('voucher');

        $this->read_db->where_not_in($this->controller . '.fk_status_id', $max_approval_status_ids);

        if (!$this->session->system_admin) {
            $this->read_db->where_in('office.office_id', array_column($this->session->hierarchy_offices, 'office_id'));
        }
    }

    public function check_if_month_vouchers_are_approved($office_id, $month)
    {

        $start_month_date = date('Y-m-01', strtotime($month));
        $end_month_date = date('Y-m-t', strtotime($month));

        $this->load->model('journal_model');

        $approved_vouchers = count($this->journal_model->journal_records($office_id, $month));

        //return $approved_vouchers;
        $count_of_month_raised_vouchers = $this->read_db->get_where(
            'voucher',
            array(
                'fk_office_id' => $office_id, 'voucher_date>=' => $start_month_date,
                'voucher_date<=' => $end_month_date,
            )
        )->num_rows();

        return ($approved_vouchers == $count_of_month_raised_vouchers) && $count_of_month_raised_vouchers > 0 ? true : false;
    }

    public function access_add_form_from_main_menu(): bool
    {
        return true;
    }

    public function get_duplicate_cheques_for_an_office($office_id, $cheque_number, $office_bank_id, $hold_cheque_number_for_edit = 0, $has_eft = 0)
    {

        $duplicate_cheque_exist = 0;

        //get duplicate cheques
        $this->read_db->select(array('voucher_cheque_number'));
        $this->read_db->where(array('fk_office_id' => $office_id, 'voucher_cheque_number' => $cheque_number, 'fk_office_bank_id' => $office_bank_id));

        //Added by Onduso on 24th May 2023 to seperate EFT numbers with cheques
        if ($has_eft == 1) {
            $this->read_db->where(['voucher_type_is_cheque_referenced' => 0]);

        } else {
            $this->read_db->where(['voucher_type_is_cheque_referenced' => 1]);

        }
        $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');

        //End of addition
        $duplicate_cheque_number = $this->read_db->get('voucher')->num_rows();
        if ($hold_cheque_number_for_edit != $cheque_number) {
            //get duplicate cheques
            $this->read_db->select(array('voucher_cheque_number'));
            $this->read_db->where(array('fk_office_id' => $office_id, 'voucher_cheque_number' => $cheque_number, 'fk_office_bank_id' => $office_bank_id));
            $duplicate_cheque_number = $this->read_db->get('voucher')->num_rows();

            //if greater than zero then duplicates exists
            if ($duplicate_cheque_number > 0) {
                $duplicate_cheque_exist = 1;
            }
        }

        return $duplicate_cheque_exist;
    }

    public function month_cancelled_vouchers($first_voucher_date)
    {

        $start_month_date = date('Y-m-01', strtotime($first_voucher_date));
        $end_month_date = date('Y-m-t', strtotime($first_voucher_date));

        $this->read_db->select(array('voucher_id'));
        $this->read_db->where(array('voucher_is_reversed' => 1, 'voucher_reversal_to > ' => 0));
        $this->read_db->where(array('voucher_date >=' => $start_month_date, 'voucher_date <= ' => $end_month_date));

        if (!$this->session->system_admin) {
            $this->read_db->where_in('voucher.fk_office_id', array_column($this->session->hierarchy_offices, 'office_id'));
        }

        $month_cancelled_vouchers = $this->read_db->get('voucher');

        $vouchers_ids = [];

        if ($month_cancelled_vouchers->num_rows() > 0) {
            $vouchers_ids = array_column($month_cancelled_vouchers->result_array(), 'voucher_id');
        }

        return $vouchers_ids;
    }

    public function get_office_voucher_date($office_id)
    {
        // log_message('error', json_encode($office_id));

        $next_vouching_date = $this->get_voucher_date($office_id);
        $last_vouching_month_date = date('Y-m-t', strtotime($next_vouching_date));

        $voucher_date_field_dates = ['next_vouching_date' => $next_vouching_date, 'last_vouching_month_date' => $last_vouching_month_date];

        return $voucher_date_field_dates;
    }

    public function create_report_and_journal($office_id, $last_vouching_month_date)
    {

        // log_message('error', json_encode([$office_id, $last_vouching_month_date]));

        if (!$this->office_has_vouchers_for_the_transacting_month($office_id, $last_vouching_month_date)) {

            // Create a journal record
            $this->create_new_journal($office_id, date("Y-m-01", strtotime($last_vouching_month_date)));

            // Insert the month MFR Record

            $this->create_financial_report($office_id, date("Y-m-01", strtotime($last_vouching_month_date)));
        }
    }

    public function create_new_journal($office_id, $journal_date)
    {
        $new_journal = [];

        // Check if a journal for the same month and FCP exists
        $this->read_db->where(array('fk_office_id' => $office_id, 'journal_month' => $journal_date));
        $count_journals = $this->read_db->get_where('journal')->num_rows();

        if ($count_journals == 0) {
            $new_journal['journal_track_number'] = $this->grants_model->generate_item_track_number_and_name('journal')['journal_track_number'];
            $new_journal['journal_name'] = "Journal for the month of " . $journal_date;
            $new_journal['journal_month'] = $journal_date;
            $new_journal['fk_office_id'] = $office_id;
            $new_journal['journal_created_date'] = date('Y-m-d');
            $new_journal['journal_created_by'] = $this->session->user_id;
            $new_journal['journal_last_modified_by'] = $this->session->user_id;
            $new_journal['fk_approval_id'] = $this->grants_model->insert_approval_record('journal');
            $new_journal['fk_status_id'] = $this->grants_model->initial_item_status('journal');

            //$new_journal = $this->grants_model->merge_with_history_fields('financial_report',$new_journal,false);

            $this->write_db->insert('journal', $new_journal);
        }
        //return $this->write_db->insert_id();
    }

    public function create_financial_report($office_id, $financial_report_date)
    {

        // log_message('error', json_encode([$office_id, $financial_report_date]));

        // Check if a journal for the same month and FCP exists
        $this->read_db->where(array('fk_office_id' => $office_id, 'financial_report_month' => $financial_report_date));
        $count_financial_report = $this->read_db->get_where('financial_report')->num_rows();

        if ($count_financial_report == 0) {
            $new_mfr['financial_report_month'] = $financial_report_date;
            $new_mfr['fk_office_id'] = $office_id;
            $new_mfr['fk_status_id'] = $this->grants->initial_item_status('financial_report');

            $new_mfr_to_insert = $this->grants_model->merge_with_history_fields('financial_report', $new_mfr);

            $this->write_db->insert('financial_report', $new_mfr_to_insert);
        }
    }

    public function create_voucher($data)
    {

        $voucher_id = 0;

        extract($data);

        $this->write_db->trans_start();

        $this->write_db->insert('voucher', $header);

        $header_id = $this->write_db->insert_id();

        // log_message('error', $header_id);

        for ($i = 0; $i < sizeof($detail); $i++) {
            $detail[$i]['fk_voucher_id'] = $header_id;
        }

        // log_message('error',json_encode($detail));

        $this->write_db->insert_batch('voucher_detail', $detail);

        $this->write_db->trans_complete();

        if ($this->write_db->trans_status() != false) {
            $voucher_id = $header_id;
        }

        return $voucher_id;
    }

    public function month_funds_transfer_vouchers($office_ids, $reporting_month)
    {
        // return ["Hello","There"];
        $this->load->model('voucher_type_model');

        $voucher_type_ids = $this->voucher_type_model->office_hidden_bank_voucher_types($office_ids[0]);

        $vouchers = [];

        // log_message('error', json_encode($voucher_type_ids));

        if (count($voucher_type_ids) > 0) {
            $this->read_db->select(
                [
                    'voucher_date',
                    'voucher_number',
                    'voucher_id',
                    'funds_transfer_source_account_id',
                    'funds_transfer_target_account_id',
                    'funds_transfer_amount',
                    'funds_transfer_id',
                    'funds_transfer_created_date',
                    'funds_transfer_type',
                    'voucher_created_date',
                ]
            );
            $this->read_db->where(array('voucher_date>=' => date('Y-m-01', strtotime($reporting_month)), 'voucher_date<=' => date('Y-m-t', strtotime($reporting_month))));
            $this->read_db->where_in('fk_voucher_type_id', $voucher_type_ids);
            $this->read_db->join('funds_transfer', 'funds_transfer.fk_voucher_id=voucher.voucher_id');
            $this->read_db->where_in('voucher.fk_office_id', $office_ids);
            $vouchers_obj = $this->read_db->get('voucher');

            if ($vouchers_obj->num_rows() > 0) {
                $unformatted_accounts_vouchers = $vouchers_obj->result_array();

                $vouchers = $this->format_accounts_numbers($unformatted_accounts_vouchers);
            }
        }
        // log_message('error', json_encode($reporting_month));
        return $vouchers;
    }

    public function format_accounts_numbers($vouchers)
    {

        $income_accounts = [];
        $expense_accounts = [];
        // $accounts = [];

        $cnt = 0;
        foreach ($vouchers as $voucher) {
            if ($voucher['funds_transfer_type'] == 1) {
                $income_accounts[$voucher['funds_transfer_source_account_id']] = $voucher['funds_transfer_source_account_id'];
                $income_accounts[$voucher['funds_transfer_target_account_id']] = $voucher['funds_transfer_target_account_id'];
            } else {
                $expense_accounts[$voucher['funds_transfer_source_account_id']] = $voucher['funds_transfer_source_account_id'];
                $expense_accounts[$voucher['funds_transfer_target_account_id']] = $voucher['funds_transfer_target_account_id'];
            }
            $cnt++;
        }

        // log_message('error',json_encode($income_accounts));
        // log_message('error',json_encode($expense_accounts));

        if (!empty($income_accounts)) {

            $this->read_db->select(array('income_account_id', 'income_account_name'));
            $this->read_db->where_in('income_account_id', $income_accounts);
            $income_accounts = $this->read_db->get('income_account')->result_array();

            $income_account_ids = array_column($income_accounts, 'income_account_id');
            $income_account_names = array_column($income_accounts, 'income_account_name');

            $income_accounts = array_combine($income_account_ids, $income_account_names);
        }

        if (!empty($expense_accounts)) {
            $this->read_db->select(array('expense_account_id', 'expense_account_name'));
            $this->read_db->where_in('expense_account_id', $expense_accounts);
            $expense_accounts = $this->read_db->get('expense_account')->result_array();

            $expense_account_ids = array_column($expense_accounts, 'expense_account_id');
            $expense_account_names = array_column($expense_accounts, 'expense_account_name');

            $expense_accounts = array_combine($expense_account_ids, $expense_account_names);
        }

        for ($i = 0; $i < sizeof($vouchers); $i++) {
            if ($vouchers[$i]['funds_transfer_type'] == 1) {
                $vouchers[$i]['funds_transfer_source_account_id'] = $income_accounts[$vouchers[$i]['funds_transfer_source_account_id']];
                $vouchers[$i]['funds_transfer_target_account_id'] = $income_accounts[$vouchers[$i]['funds_transfer_target_account_id']];
            } else {
                $vouchers[$i]['funds_transfer_source_account_id'] = $expense_accounts[$vouchers[$i]['funds_transfer_source_account_id']];
                $vouchers[$i]['funds_transfer_target_account_id'] = $expense_accounts[$vouchers[$i]['funds_transfer_target_account_id']];
            }
        }

        return $vouchers;
    }

    /**
     * Duplicated in the journal model - To be removed from here in the later versions
     */

    // function reverse_voucher($voucher_id, $reuse_cheque = 1)
    // {

    //   $message = get_phrase("reversal_completed");

    //   // Get the voucher and voucher details
    //   $voucher = $this->read_db->get_where(
    //     'voucher',
    //     array('voucher_id' => $voucher_id)
    //   )->row_array();

    //   $this->write_db->trans_start();

    //   $new_voucher_id = $this->insert_voucher_reversal_record($voucher, $reuse_cheque);

    //   $this->update_cash_recipient_account($new_voucher_id, $voucher);

    //   $this->write_db->trans_complete();

    //   if ($this->write_db->trans_status() == false) {
    //     $message = get_phrase("reversal_failed");
    //   }
    //   // log_message('error', $message);
    //   echo $message;
    // }

    public function insert_voucher_reversal_record($voucher, $reuse_cheque, $journal_month = '')
    {

        //Unset the primary key field
        $voucher_id = array_shift($voucher);

        $voucher_details = $this->read_db->get_where(
            'voucher_detail',
            array('fk_voucher_id' => $voucher_id)
        )->result_array();

        //log_message('error', json_encode(['Test' => $journal_month]));
        // Get next voucher number
        $next_voucher_number = $this->get_voucher_number($voucher['fk_office_id'], $journal_month);
        $next_voucher_date = $this->get_voucher_date($voucher['fk_office_id'], $journal_month);

        // Replace the voucher number in selected voucher with the next voucher number
        $cleared_date = $voucher['voucher_transaction_cleared_date'];
        $cleared_month = $voucher['voucher_transaction_cleared_month'];
        $voucher_description = '<strike>' . $voucher['voucher_description'] . '</strike> [Reversal of voucher number ' . $voucher['voucher_number'] . ']';
        $voucher_transaction_cleared_date = $cleared_date == '0000-00-00' || $cleared_date == null ? null : $voucher['voucher_transaction_cleared_date'];
        $voucher_transaction_cleared_month = $cleared_month == '0000-00-00' || $cleared_month == null ? null : $voucher['voucher_transaction_cleared_month'];

        $voucher = array_replace($voucher, ['voucher_vendor' => '<strike>' . $voucher['voucher_vendor'] . '<strike>', 'voucher_is_reversed' => 1, 'voucher_reversal_from' => $voucher_id, 'voucher_cleared' => 1, 'voucher_date' => $next_voucher_date, 'voucher_cleared_month' => date('Y-m-t', strtotime($next_voucher_date)), 'voucher_number' => $next_voucher_number, 'voucher_description' => $voucher_description, 'voucher_transaction_cleared_date' => $voucher_transaction_cleared_date, 'voucher_transaction_cleared_month' => $voucher_transaction_cleared_month, 'voucher_cheque_number' => $voucher['voucher_cheque_number'] > 0 && $reuse_cheque == 1 ? -$voucher['voucher_cheque_number'] : $voucher['voucher_cheque_number']]);

        //Insert the next voucher record and get the insert id
        $this->write_db->insert('voucher', $voucher);

        $new_voucher_id = $this->write_db->insert_id();

        // Update details array and insert

        $updated_voucher_details = [];

        foreach ($voucher_details as $voucher_detail) {
            unset($voucher_detail['voucher_detail_id']);
            $updated_voucher_details[] = array_replace($voucher_detail, ['fk_voucher_id' => $new_voucher_id, 'voucher_detail_unit_cost' => -$voucher_detail['voucher_detail_unit_cost'], 'voucher_detail_total_cost' => -$voucher_detail['voucher_detail_total_cost']]);
        }

        $this->write_db->insert_batch('voucher_detail', $updated_voucher_details);

        // Update the original voucher record by flagging it reversed
        $this->write_db->where(array('voucher_id' => $voucher_id));
        $update_data['voucher_is_reversed'] = 1;
        $update_data['voucher_cleared'] = 1;
        $update_data['voucher_cleared_month'] = date('Y-m-t', strtotime($next_voucher_date));
        $update_data['voucher_cheque_number'] = $voucher['voucher_cheque_number'] > 0 ? -$voucher['voucher_cheque_number'] : $voucher['voucher_cheque_number'];
        $update_data['voucher_reversal_to'] = $new_voucher_id;
        $this->write_db->update('voucher', $update_data);

        return ['new_voucher_id' => $new_voucher_id, 'new_voucher' => $voucher, 'next_voucher_number' => $next_voucher_number];
        //return $new_voucher_id;
    }

    public function get_voucher_cash_recipients($new_voucher_id)
    {
        $this->read_db->select(array('fk_office_bank_id as office_bank_id', 'fk_office_cash_id as office_cash_id'));
        $this->read_db->where(array('fk_voucher_id' => $new_voucher_id));
        $cash_recipient_account_obj = $this->read_db->get('cash_recipient_account');

        $cash_recipient_account = [];

        if ($cash_recipient_account_obj->num_rows() > 0) {
            $cash_recipient_account = $cash_recipient_account_obj->row_array();
        }

        return $cash_recipient_account;
    }

    public function update_cash_recipient_account($new_voucher_id, $voucher)
    {

        $voucher_id = array_shift($voucher);
        // Insert a cash_recipient_account record if reversing voucher is bank to bank contra

        $this->read_db->where(array('voucher_type_id' => $voucher['fk_voucher_type_id']));
        $this->read_db->join('voucher_type', 'voucher_type.fk_voucher_type_effect_id=voucher_type_effect.voucher_type_effect_id');
        $voucher_type_effect_code = $this->read_db->get('voucher_type_effect')->row()->voucher_type_effect_code;

        if ($voucher_type_effect_code == 'bank_to_bank_contra') {

            $this->read_db->where(array('fk_voucher_id' => $voucher_id));
            $original_cash_recipient_account = $this->read_db->get('cash_recipient_account')->row_array();

            $cash_recipient_account_data['cash_recipient_account_name'] = $this->grants_model->generate_item_track_number_and_name('cash_recipient_account')['cash_recipient_account_name'];
            $cash_recipient_account_data['cash_recipient_account_track_number'] = $this->grants_model->generate_item_track_number_and_name('cash_recipient_account')['cash_recipient_account_track_number'];
            $cash_recipient_account_data['fk_voucher_id'] = $new_voucher_id;

            if ($voucher['fk_office_bank_id'] > 0) {
                $cash_recipient_account_data['fk_office_bank_id'] = $original_cash_recipient_account['fk_office_bank_id'];
            } elseif ($voucher['fk_office_cash_id'] > 0) {
                $cash_recipient_account_data['fk_office_cash_id'] = $original_cash_recipient_account['fk_office_cash_id'];
            }

            $cash_recipient_account_data['cash_recipient_account_created_date'] = date('Y-m-d');
            $cash_recipient_account_data['cash_recipient_account_created_by'] = $this->session->user_id;
            $cash_recipient_account_data['cash_recipient_account_last_modified_by'] = $this->session->user_id;

            $cash_recipient_account_data['fk_approval_id'] = $this->grants_model->insert_approval_record('cash_recipient_account');
            $cash_recipient_account_data['fk_status_id'] = $this->grants_model->initial_item_status('cash_recipient_account');

            $this->write_db->insert('cash_recipient_account', $cash_recipient_account_data);
        }
    }

    /**
     * Voucher form implementation
     */

    public function load_dependant_model()
    {
    }

    public function form_header_voucher_date()
    {
        return "form_header_voucher_date";
    }

    public function form_header_voucher_number()
    {
        return "form_header_voucher_number";
    }

    public function form_header_office_name()
    {
        return "form_header_office_name";
    }

    public function form_header_voucher_type_name()
    {
        return "form_header_voucher_type_name";
    }

    public function form_header_office_bank_name()
    {
        return "form_header_office_bank_name";
    }

    public function form_header_office_cash_name()
    {
        return "form_header_office_cash_name";
    }

    public function form_header_voucher_cheque_number()
    {
        return "form_header_voucher_cheque_number";
    }

    public function form_header_voucher_vendor()
    {
        return "form_header_voucher_vendor";
    }

    public function form_header_voucher_vendor_address()
    {
        return "form_header_voucher_vendor";
    }

    public function form_header_voucher_description()
    {
        return "form_header_voucher_vendor";
    }

    public function render_form()
    {
    }
}
