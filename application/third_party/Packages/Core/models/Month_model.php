<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Month_model extends MY_Model
{

    public $table = 'month';
    public $dependant_table = '';
    public $name_field = 'month_name';
    public $create_date_field = "month_created_date";
    public $created_by_field = "month_created_by";
    public $last_modified_date_field = "month_last_modified_date";
    public $last_modified_by_field = "month_last_modified_by";
    public $deleted_at_field = "month_deleted_at";

    function __construct()
    {
        parent::__construct();
        $this->load->database();
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

    

   //Added by Onduso on 6/14/2022 due to month freezing at the begining of new FY except the month of June
   // Karisa  - Just modified the past_months_in_fy method with an additional parameter i.e. Budget year to achieve this effect

    // public function count_fys_occurances($office_id)
    // {

    //     //Get all FY budgets e.g. 21,22, 23
    //     $this->read_db->select(['budget_year']);
    //     $this->read_db->join('budget_tag', 'budget_tag.budget_tag_id=budget.fk_budget_tag_id');
    //     $this->read_db->where(['fk_office_id' => $office_id]);
    //     $fy_budgts_for_an_office = $this->read_db->get('budget')->result_array();

    //     //Get the array column of all fys and count the occurances of fys e.g 22=>2; 23=>1
    //     $fys = array_column($fy_budgts_for_an_office, 'budget_year');
    //     $count_fys_occurances = array_count_values($fys);

    //     //Get the last value of array which will be largest fy for FCP
    //     return  end($count_fys_occurances);
    // }
    //End of code

    
    // Karisa - Commented count_fys_occurances method and added an additional parameter to this method i.e. budget year
    public function past_months_in_fy($office_id, $budget_tag_level, $unfreeze_past_quarter = false)
    {
        $this->load->model('budget_review_count_model');

        $past_months_in_fy = [];

        // $budget_tag_level = $unfreeze_past_quarter ? $budget_tag_level - 1 : $budget_tag_level;

        $review_count = $this->budget_review_count_model->budget_review_count_by_office($office_id);

        $month_list_order = array_column(month_order($office_id),'month_number');

        $chunk_size = count($month_list_order) /  $review_count; 

        $year_periods = array_chunk($month_list_order, $chunk_size);

        for($i = 1; $i < $budget_tag_level; $i++){
            $past_months_in_fy = array_merge($past_months_in_fy, $year_periods[$i - 1]);
        }

        // log_message('error', json_encode($month_list_order));
        
        return $past_months_in_fy;
    }

    function default_fy_start_month(){
        $this->read_db->select(array('month_id','month_number', 'month_name'));
        $this->read_db->where(array('month_order' => 1));
        $first_month = $this->read_db->get('month')->row();

        return $first_month;
    }

    function intialize_table(array $foreign_keys_values = [])
    {

        $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

        $insert_ids = [];

        foreach ($months as $month_order => $month_name) {

            $month_order += 1;

            $months_data['month_track_number'] = $this->grants_model->generate_item_track_number_and_name('month')['month_track_number'];
            $months_data['month_name'] = $month_name;
            $months_data['month_number'] = $month_order;
            $months_data['month_order'] = $month_order;

            $months_data_to_insert = $this->grants_model->merge_with_history_fields('month', $months_data, false);
            $this->write_db->insert('month', $months_data_to_insert);

            $insert_ids[] = $this->write_db->insert_id();
        }


        return $insert_ids;
    }
}
