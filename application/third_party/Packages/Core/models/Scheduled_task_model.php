<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *  @author     : Nicodemus Karisa
 *  @date       : 27th September, 2018
 *  Finance management system for NGOs
 *  https://techsysnow.com
 *  NKarisa@ke.ci.org
 */

class Scheduled_task_model extends MY_Model{

    public $table = 'scheduled_task'; 
    public $dependant_table = '';
    public $name_field = 'scheduled_task_name';
    public $create_date_field = "scheduled_task_created_date";
    public $created_by_field = "scheduled_task_created_by";
    public $last_modified_date_field = "scheduled_task_last_modified_date";
    public $last_modified_by_field = "scheduled_task_last_modified_by";
    public $deleted_at_field = "scheduled_task_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array();
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}

    function list_table_visible_columns()
    {
        return [
            'scheduled_task_track_number',
            'scheduled_task_name',
            'scheduled_task_minute',
            'scheduled_task_hour',
            'scheduled_task_day_of_month',
            'scheduled_task_month',
            'scheduled_task_day_of_week',
            'scheduled_task_is_active',
            'scheduled_task_last_run',
            'scheduled_task_next_run'
        ];
    }

    function show_add_button()
    {
        if(!$this->session->system_admin){
            return false;
        }else{
            return true;
        }
    }

    function single_form_add_visible_columns()
    {
        return [
            'scheduled_task_name',
            'scheduled_task_minute',
            'scheduled_task_hour',
            'scheduled_task_day_of_month',
            'scheduled_task_month',
            'scheduled_task_day_of_week',
            'scheduled_task_is_active'
        ];
    }

    function edit_visible_columns()
    {
        return [
            'scheduled_task_name',
            'scheduled_task_minute',
            'scheduled_task_hour',
            'scheduled_task_day_of_month',
            'scheduled_task_month',
            'scheduled_task_day_of_week',
            'scheduled_task_is_active',
            'scheduled_task_next_run'
        ];
    }
}


