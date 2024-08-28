<?php

if(!function_exists('office_bank_select')){
    function office_bank_select($office_id,$project_allocation_id = 0){

        $CI =& get_instance();
        $CI->load->model('office_bank_model');

        $option = "<option value=''>".get_phrase('select_office_bank')."</option>";

        $office_banks = $CI->office_bank_model->get_office_banks($office_id);

        foreach($office_banks as $office_bank){
            $option .= "<option value='".$office_bank['office_bank_id']."'>".$office_bank['office_bank_name']."</option>";
        }

        return '<select data-project_allocation_id = "'.$project_allocation_id.'" class="form-control change_office_bank">'.$option.'</select>';
    }

    if(!function_exists('get_related_voucher')){
        function get_related_voucher($voucher_id){
            $CI =& get_instance();
            return $CI->db->get_where('voucher',array('voucher_id'=>$voucher_id))->row()->voucher_number;
        }
    }
}

if(!function_exists('check_submitted_financial_report_error_message')){
    function check_submitted_financial_report_error_message($post_array){

        $CI =& get_instance();

        $CI->load->model('financial_report_model');

        $office_bank_id = $post_array['header']['fk_office_bank_id'];

        $count_of_submitted_financial_reports = $CI->financial_report_model->count_of_submitted_financial_reports([$office_bank_id]);
        $office_bank_has_more_than_one_financial_report = $CI->financial_report_model->office_bank_has_more_than_one_financial_report([$office_bank_id]);

        // log_message('error', json_encode($office_bank_has_more_than_one_financial_report));
        // FCP should have only 1 MFR and none is submitted to allow the opening balances to be edited.

        if(($count_of_submitted_financial_reports == 0 && !$office_bank_has_more_than_one_financial_report) || $CI->session->system_admin){
            return $post_array;
        }else{
            return ['error'=> get_phrase('opening_balance_edit_error',"You cannot edit this record because there is a submitted financial report for the office or office has more than one financial report")];
        }
    }
}