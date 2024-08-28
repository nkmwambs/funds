<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Financial_review_dashboard_model extends MY_Model
{

    public $table = 'financial_review_dashboard';
    public $dependant_table = '';
    public $name_field = 'financial_review_dashboard_name';
    public $create_date_field = "financial_review_dashboard_created_date";
    public $created_by_field = "financial_review_dashboard_created_by";
    public $last_modified_date_field = "financial_review_dashboard_last_modified_date";
    public $last_modified_by_field = "financial_review_dashboard_last_modified_by";
    public $deleted_at_field = "financial_review_dashboard_deleted_at";

    function __construct()
    {
        parent::__construct();
        $this->load->database();

        $this->load->model('financial_report_model');
    }

    function index()
    {
    }

    public function lookup_tables()
    {
        return array();
    }

    public function detail_tables()
    {
    }

    public function detail_multi_form_add_visible_columns()
    {
    }

    function month_income_opening_balance(array $office_ids, string $start_date_of_month, array $income_accounts, array $project_ids = [], array $office_bank_ids = []): array
    {

        $opening_balances = [];

        // This piece was left to allow backward compatibility. It will be removed in the future releases. 
        if (empty($opening_balances)) {
            foreach ($income_accounts as $income_account) {

                $opening_balances[$income_account['income_account_id']] = $this->_get_to_date_account_opening_balance($office_ids, $income_account['income_account_id'], $start_date_of_month, $project_ids, $office_bank_ids); //$this->_get_income_account_opening_balance($office_ids,$income_account['income_account_id'],$start_date_of_month,$project_ids,$office_bank_ids);
            }
        }

        return $opening_balances;
    }

    function _initial_opening_account_balance($office_ids, $income_account_id, $project_ids = [], $office_bank_ids = [])
    {
        $account_opening_balance = 0;

        $this->read_db->select_sum('opening_fund_balance_amount');
        $this->read_db->join('opening_fund_balance', 'opening_fund_balance.fk_system_opening_balance_id=system_opening_balance.system_opening_balance_id');

        if (count($office_bank_ids) > 0) {
            $this->read_db->where_in('opening_fund_balance.fk_office_bank_id', $office_bank_ids);
        }

        //echo json_encode($office_bank_ids);exit;

        if (count($project_ids) > 0) {
            $this->read_db->where_in('project.project_id', $project_ids);
            $this->read_db->join('income_account', 'income_account.income_account_id=opening_fund_balance.fk_income_account_id');
            $this->read_db->join('project_income_account', 'project_income_account.fk_income_account_id=income_account.income_account_id');
            $this->read_db->join('project', 'project.project_id=project_income_account.fk_project_id');
        }


        //echo json_encode($project_ids);exit;

        $this->read_db->group_by(array('fk_income_account_id'));
        $this->read_db->where_in('system_opening_balance.fk_office_id', $office_ids);
        $initial_account_opening_balance_obj = $this->read_db->get_where(
            'system_opening_balance',
            array('opening_fund_balance.fk_income_account_id' => $income_account_id)
        );


        if ($initial_account_opening_balance_obj->num_rows() == 1) {
            $account_opening_balance = $initial_account_opening_balance_obj->row()->opening_fund_balance_amount;
        }

        return $account_opening_balance;
    }

    function _get_account_last_month_income_to_date($office_ids, $income_account_id, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    {

        $previous_months_income_to_date = 0;
        $get_office_bank_project_allocation = !empty($project_ids) ? $project_ids : $this->financial_report_model->get_office_bank_project_allocation($office_bank_ids);

        $this->read_db->select_sum('voucher_detail_total_cost');
        $this->read_db->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
        $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $this->read_db->group_by('voucher_type_effect_code');
        $this->read_db->where_in('voucher.fk_office_id', $office_ids);

        if (count($project_ids) > 0) {
            $this->read_db->where_in('fk_project_id', $project_ids);
            $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');
        }

        if (!empty($office_bank_ids)) {

            $this->read_db->group_start();
            $this->read_db->where_in('voucher.fk_office_bank_id', $office_bank_ids);
            $this->read_db->where_in('voucher_detail.fk_project_allocation_id', $get_office_bank_project_allocation);
            $this->read_db->group_end();
        }

        $previous_months_income_obj = $this->read_db->get_where(
            'voucher_detail',
            array(
                'voucher_date<' => $start_date_of_month,
                'voucher_detail.fk_income_account_id' => $income_account_id, 'voucher_type_effect_code' => 'income'
            )
        );

        if ($previous_months_income_obj->num_rows() > 0) {
            $previous_months_income_to_date = $previous_months_income_obj->row()->voucher_detail_total_cost;
        }

        return $previous_months_income_to_date;
    }

    function _get_account_last_month_expense_to_date($office_ids, $income_account_id, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    {

        $previous_months_expense_to_date = 0;
        $get_office_bank_project_allocation = !empty($project_ids) ? $project_ids : $this->financial_report_model->get_office_bank_project_allocation($office_bank_ids);

        $this->read_db->select_sum('voucher_detail_total_cost');
        $this->read_db->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
        $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $this->read_db->join('expense_account', 'expense_account.expense_account_id=voucher_detail.fk_expense_account_id');
        $this->read_db->join('income_account', 'income_account.income_account_id=expense_account.fk_income_account_id');

        if (count($project_ids) > 0) {
            $this->read_db->where_in('fk_project_id', $project_ids);
            $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');
        }

        $this->read_db->group_by('voucher_type_effect_code');
        $this->read_db->where_in('voucher.fk_office_id', $office_ids);

        if (!empty($office_bank_ids)) {
            $this->read_db->group_start();
            $this->read_db->where_in('voucher.fk_office_bank_id', $office_bank_ids);
            $this->read_db->where_in('voucher_detail.fk_project_allocation_id', $get_office_bank_project_allocation);
            $this->read_db->group_end();
        }

        $previous_months_expense_obj = $this->read_db->get_where(
            'voucher_detail',
            array(
                'voucher_date<' => $start_date_of_month,
                'income_account_id' => $income_account_id, 'voucher_type_effect_code' => 'expense'
            )
        );

        if ($previous_months_expense_obj->num_rows() > 0) {
            $previous_months_expense_to_date = $previous_months_expense_obj->row()->voucher_detail_total_cost;
        }

        return $previous_months_expense_to_date;
    }

    function _get_to_date_account_opening_balance($office_ids, $income_account_id, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    {
        // log_message('error', json_encode($office_bank_ids));

        $initial_account_opening_balance = $this->_initial_opening_account_balance($office_ids, $income_account_id, $project_ids, $office_bank_ids);

        $account_last_month_income_to_date = $this->_get_account_last_month_income_to_date($office_ids, $income_account_id, $start_date_of_month, $project_ids, $office_bank_ids);

        $account_last_month_expense_to_date = $this->_get_account_last_month_expense_to_date($office_ids, $income_account_id, $start_date_of_month, $project_ids, $office_bank_ids);

        $account_opening_balance = $initial_account_opening_balance  + ($account_last_month_income_to_date - $account_last_month_expense_to_date);

        // log_message('error', json_encode($office_bank_ids)); 

        return $account_opening_balance;
    }

    function _get_account_month_income($office_ids, $income_account_id, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    {
        //echo $income_account_id;exit;
        $max_approval_status_ids = $this->general_model->get_max_approval_status_id('voucher');

        $last_date_of_month = date('Y-m-t', strtotime($start_date_of_month));
        $get_office_bank_project_allocation = !empty($project_ids) ? $project_ids : $this->financial_report_model->get_office_bank_project_allocation($office_bank_ids);

        $month_income = 0;

        $this->read_db->select_sum('voucher_detail_total_cost');
        $this->read_db->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
        $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $this->read_db->group_by(array('fk_income_account_id'));
        $this->read_db->where_in('voucher.fk_office_id', $office_ids);

        $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');

        if (count($project_ids) > 0) {
            $this->read_db->where_in('project_allocation.fk_project_id', $project_ids);
        }

        if (count($office_bank_ids) > 0) {
            $this->read_db->group_start();
            $this->read_db->where_in('voucher.fk_office_bank_id', $office_bank_ids);
            $this->read_db->where_in('voucher_detail.fk_project_allocation_id', $get_office_bank_project_allocation);
            $this->read_db->group_end();
        }

        $this->read_db->where_in('voucher.fk_status_id', $max_approval_status_ids);

        $month_income_obj = $this->read_db->get_where(
            'voucher_detail',
            array(
                'voucher_type_effect_code' => 'income',
                'fk_income_account_id' => $income_account_id, 'voucher_date>=' => $start_date_of_month,
                'voucher_date<=' => $last_date_of_month
            )
        );

        if ($month_income_obj->num_rows() > 0) {
            // log_message('error', json_encode($month_income_obj->row()));
            $month_income = $month_income_obj->row()->voucher_detail_total_cost;
        }

        return $month_income;
    }

    function get_office_banks($office_ids, $project_ids = [], $office_bank_ids = [])
    {


        $this->read_db->select(array('DISTINCT(office_bank_id)', 'office_bank_name'));
        $this->read_db->where_in('fk_office_id', $office_ids);
        $this->read_db->join('office_bank', 'office_bank.office_bank_id=office_bank_project_allocation.fk_office_bank_id');

        if (!empty($office_bank_ids)) {
            $this->read_db->where_in('fk_office_bank_id', $office_bank_ids);
        }

        $office_banks = $this->read_db->get('office_bank_project_allocation')->result_array();


        return $office_banks;
    }

    public function get_total_sum_of_bounced_opening_cheques($office_ids, $project_ids = [], $office_bank_ids = [])
    {

        if (sizeof($office_bank_ids) == 0) {
            $office_bank = $this->get_office_banks($office_ids);
            $office_bank_ids = array_column($office_bank, 'office_bank_id');
        }
        $this->read_db->select_sum('opening_outstanding_cheque_amount');
        $this->read_db->select(array('opening_outstanding_cheque_cleared_date'));
        $this->read_db->where_in('fk_office_bank_id', $office_bank_ids);
        $this->read_db->where(array('opening_outstanding_cheque_bounced_flag' => 1));
        $this->read_db->group_by(array('fk_office_bank_id', 'opening_outstanding_cheque_cleared_date')); // Modified by Nicodemus Karisa on 13th May 2022
        return $this->read_db->get('opening_outstanding_cheque')->result_array();
    }

    function bank_to_bank_contra_contributions($office_bank_ids = [], String $reporting_month): array
    {

        $bank_to_bank_contra_contributed_amounts = [];

        $end_of_reporting_month = date('Y-m-t', strtotime($reporting_month));

        if (count($office_bank_ids) > 0) {
            $this->read_db->select(array('income_account_id'));
            $this->read_db->select_sum('voucher_detail_total_cost');
            $this->read_db->group_by(array('income_account_id'));
            $this->read_db->where_in('office_bank_id', $office_bank_ids);
            $this->read_db->where(array('voucher_date <=' => $end_of_reporting_month));
            $voucher_detail_total_cost_obj = $this->read_db->get('bank_to_bank_contra_contributions');

            if ($voucher_detail_total_cost_obj->num_rows() > 0) {

                $income_account_grouped = $voucher_detail_total_cost_obj->result_array();

                foreach ($income_account_grouped as $row) {
                    if ($row['income_account_id'] != null) {
                        $bank_to_bank_contra_contributed_amounts[$row['income_account_id']] =  $row['voucher_detail_total_cost'] ? $row['voucher_detail_total_cost'] : 0;
                    }
                }
            }
        }

        return $bank_to_bank_contra_contributed_amounts;
    }

    /**
     * month_income_account_expenses
     * 
     * Gives an array of each income account id qwith its associated expense in the month as provided by the start_date_of_month
     * When the project ids or office_bank_ids are passed, the list will only give expense per income account account affecting the projects or office bank provided
     * 
     * @author Nicodemus Karisa
     * @authored_date Unknown
     * 
     * @param array $office_ids - List of office ids
     * @param string $start_date_of_month - Start date of the specified month
     * @param array $project_ids - Optional list of project ids
     * @param array $office_bank_ids - Optional list of office bank Ids
     * 
     * @example - The key is an income account id and the value is the total expense in the month in that income account
     * {"60":"1241000.00","61":0,"62":0,"63":0,"64":0,"65":0,"66":0,"67":"330000.00","68":0,"69":0,"70":0}
     * 
     * @return array - List of expenses per income accoutn id
     */

    function month_income_account_expenses(array $office_ids, string $start_date_of_month, array $project_ids = [], array $office_bank_ids = []): array
    {

        $income_accounts = $this->financial_report_model->income_accounts($office_ids, $project_ids);

        $expense_income = [];

        foreach ($income_accounts as $income_account) {
            $expense_income[$income_account['income_account_id']] = $this->_get_income_account_month_expense($office_ids, $income_account['income_account_id'], $start_date_of_month, $project_ids, $office_bank_ids);
        }

        return $expense_income;
    }

    function _get_income_account_month_expense($office_ids, $income_account_id, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    {
        $last_date_of_month = date('Y-m-t', strtotime($start_date_of_month));
        $get_office_bank_project_allocation = !empty($project_ids) ? $project_ids : $this->financial_report_model->get_office_bank_project_allocation($office_bank_ids);
        $expense_income = 0;

        $max_approval_status_ids = $this->general_model->get_max_approval_status_id('voucher');

        $this->read_db->select_sum('voucher_detail_total_cost');
        $this->read_db->join('voucher', 'voucher.voucher_id=voucher_detail.fk_voucher_id');
        $this->read_db->join('voucher_type', 'voucher_type.voucher_type_id=voucher.fk_voucher_type_id');
        $this->read_db->join('voucher_type_effect', 'voucher_type_effect.voucher_type_effect_id=voucher_type.fk_voucher_type_effect_id');
        $this->read_db->join('expense_account', 'expense_account.expense_account_id=voucher_detail.fk_expense_account_id');
        $this->read_db->join('income_account', 'income_account.income_account_id=expense_account.fk_income_account_id');
        $this->read_db->group_by('voucher_type_effect_code');
        $this->read_db->where_in('voucher.fk_office_id', $office_ids);

        $this->read_db->join('project_allocation', 'project_allocation.project_allocation_id=voucher_detail.fk_project_allocation_id');

        if (count($project_ids) > 0) {
            $this->read_db->where_in('project_allocation.fk_project_id', $project_ids);
        }

        if (count($office_bank_ids) > 0) {

            $this->read_db->group_start();
            $this->read_db->where_in('voucher.fk_office_bank_id', $office_bank_ids);
            $this->read_db->where_in('voucher_detail.fk_project_allocation_id', $get_office_bank_project_allocation);
            $this->read_db->group_end();
        }

        $this->read_db->where_in('voucher.fk_status_id', $max_approval_status_ids);

        $expense_income_obj = $this->read_db->get_where(
            'voucher_detail',
            array(
                'voucher_date>=' => $start_date_of_month, 'voucher_date<=' => $last_date_of_month,
                'income_account_id' => $income_account_id, 'voucher_type_effect_code' => 'expense'
            )
        );

        if ($expense_income_obj->num_rows() > 0) {
            $expense_income = $expense_income_obj->row()->voucher_detail_total_cost;
        }

        return $expense_income;
    }

    /**
     * @todo:
     * Awaiting documentation
     */

    function get_month_cancelled_opening_outstanding_cheques($office_ids, $start_date_of_month, $project_ids, $office_bank_ids, $aggregation_period = 'current_month')
    { // Options: current_month, past_months, to_date

        $sum_cancelled_cheques = 0;

        $first_month_date = date('Y-m-01', strtotime($start_date_of_month));
        $end_month_date = date('Y-m-t', strtotime($start_date_of_month));

        $this->read_db->select_sum('opening_outstanding_cheque_amount');
        $this->read_db->where_in('fk_office_id', $office_ids);
        $this->read_db->where(['opening_outstanding_cheque_bounced_flag' => 1]);

        $condition = ['opening_outstanding_cheque_cleared_date' =>  $end_month_date];

        if ($aggregation_period == 'past_months') {
            $condition = ['opening_outstanding_cheque_cleared_date < ' =>  $first_month_date];
        }

        if ($aggregation_period == 'to_date') {
            $condition = ['opening_outstanding_cheque_cleared_date <= ' =>  $end_month_date];
        }

        // $condition = 'opening_outstanding_cheque_cleared_date <> LAST_DAY(office_start_date)'; 

        $this->read_db->where($condition);

        if (!empty($office_bank_ids)) {
            $this->read_db->where_in('fk_office_bank_id', $office_bank_ids);
        }

        $this->read_db->group_by(array('fk_system_opening_balance_id'));
        $this->read_db->join('system_opening_balance', 'system_opening_balance.system_opening_balance_id=opening_outstanding_cheque.fk_system_opening_balance_id');
        $this->read_db->join('office', 'office.office_id=system_opening_balance.fk_office_id');
        $opening_outstanding_cheque_obj = $this->read_db->get('opening_outstanding_cheque');

        if ($opening_outstanding_cheque_obj->num_rows() > 0) {

            $sum_cancelled_cheques = $opening_outstanding_cheque_obj->row()->opening_outstanding_cheque_amount;
        }

        // log_message('error', json_encode([$sum_cancelled_cheques]));

        return $sum_cancelled_cheques;
    }

    function check_if_financial_report_is_submitted($office_ids, $reporting_month)
    {
        // log_message('error', $reporting_month);
        $report_is_submitted = false;

        if (count($office_ids) == 1) {

            $financial_report_is_submitted_obj = $this->read_db->get_where(
                'financial_report',
                array(
                    'fk_office_id' => $office_ids[0],
                    'financial_report_month' => date('Y-m-01', strtotime($reporting_month))
                )
            );



            if ($financial_report_is_submitted_obj->num_rows() > 0) {

                if ($financial_report_is_submitted_obj->row()->financial_report_is_submitted) {
                    $report_is_submitted = true;
                }
            }
        }

        return $report_is_submitted;
    }

    /**
     * @todo:
     * Awaiting documentation
     */

    function _fund_balance_report($office_ids, $start_date_of_month, $project_ids = [], $office_bank_ids = [])
    {

        // log_message('error', json_encode($office_bank_ids));

        $income_accounts =  $this->financial_report_model->income_accounts($office_ids, $project_ids, $office_bank_ids);
        //print_r($income_accounts);exit;
        $all_accounts_month_opening_balance = $this->month_income_opening_balance($office_ids, $start_date_of_month, $income_accounts, $project_ids, $office_bank_ids);
        $all_accounts_month_income = $this->financial_report_model->month_income_account_receipts($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);
        $all_accounts_month_expense = $this->month_income_account_expenses($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);

        $report = array();

        $month_cancelled_opening_oustanding_cheques = $this->get_month_cancelled_opening_outstanding_cheques($office_ids, $start_date_of_month, $project_ids, $office_bank_ids);
        $past_months_cancelled_opening_oustanding_cheques = $this->get_month_cancelled_opening_outstanding_cheques($office_ids, $start_date_of_month, $project_ids, $office_bank_ids, 'past_months');

        $itr = 0;

        foreach ($income_accounts as $account) {

            $month_opening_balance = isset($all_accounts_month_opening_balance[$account['income_account_id']]) ? $all_accounts_month_opening_balance[$account['income_account_id']] : 0;
            $month_income = isset($all_accounts_month_income[$account['income_account_id']]) ? $all_accounts_month_income[$account['income_account_id']] : 0;
            $month_expense = isset($all_accounts_month_expense[$account['income_account_id']]) ? $all_accounts_month_expense[$account['income_account_id']] : 0;

            if ($month_opening_balance == 0 && $month_income == 0 && $month_expense == 0) {
                continue;
            }

            if ($itr == 0) {
                $month_opening_balance = $month_opening_balance + $past_months_cancelled_opening_oustanding_cheques;
                $month_income = $month_income + $month_cancelled_opening_oustanding_cheques;
            }

            $report[] = [
                'account_name' => $account['income_account_name'],
                'month_opening_balance' => $month_opening_balance,
                'month_income' => $month_income,
                'month_expense' => $month_expense,
            ];

            $itr++;
        }

        //If the mfr has been submitted. Adjust the child support fund by taking away exact amount of bounced opening chqs This code was added during enhancement for cancelling opening outstanding chqs

        if ($this->check_if_financial_report_is_submitted($office_ids, $start_date_of_month) == true) {

            $sum_of_bounced_cheques = $this->get_total_sum_of_bounced_opening_cheques($office_ids);

            $total_amount_bounced = isset($sum_of_bounced_cheques[0]['opening_outstanding_cheque_amount']) ? $sum_of_bounced_cheques[0]['opening_outstanding_cheque_amount'] : 0;
            $bounced_date = isset($sum_of_bounced_cheques[0]['opening_outstanding_cheque_cleared_date']) ? $sum_of_bounced_cheques[0]['opening_outstanding_cheque_cleared_date'] : NULL;
            $mfr_report_month = date('Y-m-t', strtotime($start_date_of_month));

            if ($total_amount_bounced > 0 &&  $bounced_date > $mfr_report_month && sizeof($report) > 0) {

                $month_opening = $report[0]['month_opening_balance'];

                $report[0]['month_opening_balance'] = $month_opening - $total_amount_bounced;
            }

            // print_r($report);
            // exit;
        }

        return $report;
    }

}
