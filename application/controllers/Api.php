<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *  @author   : Nicodemus Karisa
 *  @date   : 27th September, 2018
 *  Finance management system for NGOs
 *  https://techsysnow.com
 *  NKarisa@ke.ci.org
 */

require_once 'vendor/autoload.php';

class Api extends CI_Controller{

  public $write_db = null;
  public $read_db = null;

  function __construct(){
    parent::__construct();

    $this->load->config('grants');

    // $this->load->database();

    date_default_timezone_set('Africa/Nairobi');

    $this->write_db = $this->load->database('write_db', true); // Master DB on Port 3306
    $this->read_db = $this->grants_model->read_database_connection();
  }
    
  function index(){
   log_message('error', 'Welcome to the API interphase');
  }

  function run_closing_balance_insert($reporting_date){

    // log_message('error', json_encode($reporting_date));

    $this->read_db->select(array('office_id'));
    $this->read_db->where(array('fk_context_definition_id' => 1, 'office_is_active' => 1, 'office_start_date < ' => date('Y-m-01', strtotime($reporting_date))));
    $offices = $this->read_db->get('office')->result_array();

    $office_ids = array_column($offices, 'office_id');

    $count = count($office_ids);
    $limit = 50;

    $res = range(0,$count,50);

    $last_offset = 0;
    $this->load->model('fund_balance_summary_report_model');
    foreach($res as $offset){
      $ids = array_slice($office_ids, $offset, $limit);
      $this->fund_balance_summary_report_model->log_balances($ids, $reporting_date, 0, 0);
    }
    
    echo $last_offset;
    
  }

  public function cli()
    {
      
      if(is_cli()){

        $now = date('Y-m-d h:i:s');

        $scheduled_tasks = $this->get_scheduled_tasks();
        
        $update_data = [];
        
        $cnt = 0;

        foreach($scheduled_tasks as $task_name => $task){

          // log_message('error', $task_name);
    
            extract($task);

            $timer = [$scheduled_task_minute, $scheduled_task_hour, $scheduled_task_day_of_month, $scheduled_task_month, $scheduled_task_day_of_week];
            
            $cron = new Cron\CronExpression(implode(' ',$timer));
            
            if(!$cron->isValidExpression(implode(' ',$timer))) {
              log_message('error', 'Invalid cron expression for task '. $task_name);
              continue;
            }
            
            if($now == $scheduled_task_next_run || $scheduled_task_next_run < $now || $scheduled_task_last_run == NULL){

              $next_run_date = $cron->getNextRunDate($now)->format('Y-m-d H:i:s');
              
              $task_name_parts = explode('|',$task_name);

              // log_message('error', json_encode($task_name_parts));
              
              $library = strtolower($task_name_parts[0]);
              $task_method = $task_name_parts[1];

              $packages = ['Core', 'Grants'];

              foreach($packages as $package){

                if(file_exists($this->config->item('library_path').$package.DIRECTORY_SEPARATOR.'libraries'.DIRECTORY_SEPARATOR.$library.'.php')){
    
                  // log_message('error', json_encode($library));
                  
                  $this->load->library($library);
                  
                  if(method_exists($this->{$library}, $task_method)){
    
                    $this->{$library}->{$task_method}();
    
                    $update_data[$cnt]['scheduled_task_name'] = $task_name;
                    $update_data[$cnt]['scheduled_task_last_run'] = $now;
                    $update_data[$cnt]['scheduled_task_next_run'] = $next_run_date;
                  }
                }
              }
              

              
              $cnt++;
            }
            
          }
        
          if(sizeof($update_data) > 0){
            $this->write_db->update_batch('scheduled_task', $update_data, 'scheduled_task_name');
          }

      }else{
        echo "Direct browser calls are not allowed. Only run on terminal";
      }
    }

    function get_scheduled_tasks(){

      $this->read_db->select(array(
        'scheduled_task_name',
        'scheduled_task_minute',
        'scheduled_task_hour',
        'scheduled_task_day_of_month',
        'scheduled_task_month',
        'scheduled_task_day_of_week',
        'scheduled_task_last_run',
        'scheduled_task_next_run'
      ));
      $this->read_db->where(array('scheduled_task_is_active' => 1));
      $scheduled_task_obj = $this->read_db->get('scheduled_task');

      $scheduled_task = [];

      if($scheduled_task_obj->num_rows() > 0){
        $scheduled_task_raw = $scheduled_task_obj->result_array();

        foreach($scheduled_task_raw as $task){
          $scheduled_task[$task['scheduled_task_name']] = $task;
        }
      }

      // log_message('error', json_encode($scheduled_task));

      return $scheduled_task;
    }

    function update_custom_fy_financial_report_budget_id(){
      // TO be run in preparation of the Custom FY enhancement
      $limits = [
        [21,1,'2020-07-01','2020-09-30'],
        [21,2,'2020-10-01','2020-12-31'],
        [21,3,'2021-01-01','2021-03-31'],
        [21,4,'2021-04-01','2021-06-30'],

        [22,1,'2021-07-01','2021-09-30'],
        [22,2,'2021-10-01','2021-12-31'],
        [22,3,'2022-01-01','2022-03-31'],
        [22,4,'2022-04-01','2022-06-30'],

        [23,1,'2022-07-01','2022-09-30'],
        [23,2,'2022-10-01','2022-12-31'],
        [23,3,'2023-01-01','2023-03-31'],
        [23,4,'2023-04-01','2023-06-30'],
        
        [24,1,'2023-07-01','2023-09-30'],
        [24,2,'2023-10-01','2023-12-31'],
      ];

      foreach($limits as $limit){
        $financial_report_budget_id_sql = "UPDATE `financial_report` 
        SET `financial_report`.`fk_budget_id` = (SELECT budget_id FROM `budget`
        JOIN budget_tag ON budget.fk_budget_tag_id=budget_tag.budget_tag_id
        WHERE `budget_year` = '".$limit[0]."' AND budget_tag.budget_tag_level = ".$limit[1]." AND budget.fk_office_id=financial_report.fk_office_id 
        AND `budget`.`fk_custom_financial_year_id` IS NULL)
        WHERE `financial_report_month` >= '".$limit[2]."' AND `financial_report_month` <= '".$limit[2]."'";

        $this->db->query($financial_report_budget_id_sql);
      }

      $sql_budget_limits = "UPDATE budget_limit SET budget_limit.fk_budget_id = 
      (SELECT budget_id FROM budget WHERE budget.fk_office_id = budget_limit.fk_office_id AND budget.budget_year = budget_limit.budget_limit_year AND budget.fk_budget_tag_id = budget_limit.fk_budget_tag_id LIMIT 1)";
      
      $this->db->query($sql_budget_limits);
    }
  
}

