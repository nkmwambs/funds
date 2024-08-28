<?php

class Grants_package_library extends Grants{

    private $CI = null;

    function __construct()
    {
        parent::__construct();
        
        $this->CI =& get_instance();
    }

    function list_project_allocation_without_office_bank_linkage(){
        $this->CI->load->model('office_bank_project_allocation_model');
		$result['project_allocations'] = $this->CI->office_bank_project_allocation_model->get_office_project_allocation_without_office_bank_linkage();
        
        return $this->CI->load->view('voucher/project_allocation_missing_office_bank',$result,true);

    }
}