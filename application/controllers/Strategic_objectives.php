<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

require_once('vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\IOFactory;

class Strategic_objectives extends MY_Controller
{

  function __construct()
  {
    parent::__construct();

    $this->load->database();
  }

  function index()
  {
  }

  function import_objectives($filePath)
  {

    $success = ['status' => false, 'message' => get_phrase('upload_failed')];
    $path_array = explode(DIRECTORY_SEPARATOR, $filePath);
    $fcpId = explode('_', end($path_array))[0];

    // log_message('error',json_encode($fcpId));

    if (class_exists('ZipArchive')) {
      log_message('info', 'ZipArchive is installed and available.');
      $success = ['status' => true, 'message' => get_phrase('upload_successful')];
    } else {
      log_message('error', 'ZipArchive is not installed.');
      $success = ['status' => false, 'message' => get_phrase('ziparchive_not_installed', 'ZipArchive is not installed')];
      return $success;
    }

    $objPHPExcel = IOFactory::load($filePath);

    $worksheet = $objPHPExcel->getActiveSheet();

    // Iterate through the rows and insert data into MySQL
    $count = 0;
    $insert_data = [];

    $column_mapping = [
      'Plan Name: FCP ID',
      'Plan Name: Strategic Plan Id',
      'Strategic Plan',
      'Plan Name: Plan ID',
      'Plan Name: Plan Name',
      'Strategic Objective',
      'Plan Intervention: ID',
      'Plan Intervention: Intervention Name',
      'Plan Name: Start Date',
      'Plan Name: End Date'
    ];

    foreach ($worksheet->getRowIterator() as $row) {

      $cellIterator = $row->getCellIterator();
      $cellIterator->setIterateOnlyExistingCells(FALSE); // Include empty cells

      $data = array();
      foreach ($cellIterator as $cell) {
        $data[] = $cell->getValue();
      }

      if ($count == 0) {
        $count++;

        if ($this->arraysDiffer($data, $column_mapping)) {
          log_message('error', 'The imported excel has column differences. Data not imported');
          $success = ['status' => false, 'message' => get_phrase('column_differences', 'The imported excel has column differences. Data not imported')];
          break;
        }

        continue;
      }

      $insert_data[] = $data;

      $count++;
    }

    if ($success['status']) {
      // log_message('error', 'Hello');
      $formated_data = $this->format_data($insert_data, $fcpId);

      if (!empty($formated_data)) {

        foreach ($formated_data as $plan_id => $plan_data) {

          $lock_status = $this->get_lock_status($plan_id);

          if ($lock_status == 'no-lock') {
            $this->write_db->trans_start();
            $this->insertRecord($plan_data);
            $this->upsert_lock($plan_id);
            $this->write_db->trans_complete();

            if ($this->write_db->trans_status() === FALSE) {
              $success = ['status' => false, 'message' => get_phrase('upload_failed')];
            } else {
              $success = ['status' => true, 'message' => get_phrase('upload_successful')];
            }
          }elseif($lock_status == 'unlock'){
            $this->write_db->trans_start();
            $this->updateRecord($plan_data);
            $this->upsert_lock($plan_id);
            $this->write_db->trans_complete();

            if ($this->write_db->trans_status() === FALSE) {
              $success = ['status' => false, 'message' => get_phrase('update_failed')];
            } else {
              $success = ['status' => true, 'message' => get_phrase('update_successful')];
            }
          } else {
            $success = ['status' => false, 'message' => get_phrase('zero_data_uploaded', 'Data already exists and is locked for update. Zero records updated. Ask your administrator for unlocking')];
          }
        }
      } else {
        $success = ['status' => false, 'message' => get_phrase('zero_data_uploaded', 'The data for the selected office is missing in the uploaded document')];
      }
    }

    return $success;
  }

  function arraysDiffer($array1, $array2)
  {
    // Step 1: Check if arrays have the same length
    if (count($array1) !== count($array2)) {
      return true; // Arrays have different lengths, so they are different
    }

    // Step 2: Iterate through the arrays and compare elements
    foreach ($array1 as $key => $value) {
      if ($value !== $array2[$key]) {
        return true; // Elements at the same index differ, so arrays are different
      }
    }

    // If we reach here, the arrays are the same
    return false;
  }

  function format_excel_date($excel_date)
  {
    $formated_date = date('Y-m-d');

    if (is_numeric($excel_date)) {
      $end_date_unix_timestamp = ($excel_date - 25569) * 86400;
      $formated_date = date('Y-m-d H:i:s', $end_date_unix_timestamp);
    } else {
      $datetime = DateTime::createFromFormat('m/d/Y', $excel_date);
      $formated_date = $datetime->format('Y-m-d');
    }

    return $formated_date;
  }

  function format_data($data_array, $fcpId)
  {
    $insertData = [];
    $office_codes = [];

    foreach ($data_array as $data) {

      $start_date = $this->format_excel_date($data[8]);
      $end_date = $this->format_excel_date($data[9]);

      $office_codes[$data[0]] = $data[0];

      $insertData[$data[3]][] = array(
        'fk_office_id' => $data[0],
        'pca_strategy_id' => $data[1],
        'pca_strategy_name' => $data[2],
        'pca_strategy_track_number' => '',
        'pca_strategy_plan_id' => $data[3],
        'pca_strategy_plan_name' => $data[4],
        'pca_strategy_objective_id' => base64_encode($data[5]),
        'pca_strategy_objective_name' => $data[5],
        'pca_strategy_intervention_id' => $data[6],
        'pca_strategy_intervention_name' => $data[7],
        'pca_strategy_start_date' => $start_date,
        'pca_strategy_end_date' => $end_date,
        'pca_strategy_created_date' => date('Y-m-d'),
        'pca_strategy_created_by' => !is_cli() ? $this->session->user_id : 1,
      );
    }

    $this->read_db->select(array('office_id', 'office_code'));
    $this->read_db->where_in('office_code', $office_codes);
    $office_ids_obj = $this->read_db->get('office');

    if ($office_ids_obj->num_rows() > 0) {
      $office_ids = $office_ids_obj->result_array();

      $codes = array_column($office_ids, 'office_code');
      $ids = array_column($office_ids, 'office_id');

      $codes_offices = array_combine($codes, $ids);

      foreach ($insertData as $plan_id => $data) {
        for ($i = 0; $i < count($data); $i++) {
          if ($codes_offices[$data[$i]['fk_office_id']] != $fcpId) {
            unset($insertData[$plan_id]);
            continue;
          }
          $insertData[$plan_id][$i]['fk_office_id'] = $codes_offices[$data[$i]['fk_office_id']];
        }
      }
    }

    return $insertData;
  }

  function get_lock_status($plan_id)
  {

    $lock_status = 'no-lock';

    $this->read_db->where(array('pca_plan_id' => $plan_id));
    $pca_plan_lock = $this->read_db->get('pca_plan_lock');

    if ($pca_plan_lock->num_rows() > 0) {
      $lock_status = $pca_plan_lock->row()->pca_plan_lock_status;
    }

    return $lock_status;
  }

  function upsert_lock($plan_id)
  {

    $this->read_db->where(array('pca_plan_id' => $plan_id));
    $pca_plan_lock = $this->read_db->get('pca_plan_lock');

    if ($pca_plan_lock->num_rows() == 0) {
      $insert_data = ['pca_plan_lock_last_created_date' => date('Y-m-d'), 'pca_plan_id' => $plan_id, 'pca_plan_lock_status' => 'lock'];

      $this->write_db->insert('pca_plan_lock', $insert_data);
    } else {
      $this->write_db->where(array('pca_plan_id' => $plan_id));
      $this->write_db->update('pca_plan_lock', ['pca_plan_lock_status' => 'lock']);
    }
  }

  function batch_update_records($data_array) {
    // $data_array is an array containing the data to be updated

    foreach ($data_array as $data) {
        // Assuming $data is an associative array with the composite key values
        $this->write_db->where('pca_strategy_id', $data['pca_strategy_id']);
        $this->write_db->where('pca_strategy_intervention_id', $data['pca_strategy_intervention_id']);
        $this->write_db->where('pca_strategy_plan_id', $data['pca_strategy_plan_id']);
        $this->write_db->update('pca_strategy', $data);
    }

    // Example return statement if needed
    return true;
}

  public function updateRecord($data)
  {
    // log_message('error', json_encode($data));
    // Define the table name and insert the data
    // $tableName = 'pca_strategy';

    // $this->write_db->update_batch($tableName, $data ,'pca_strategy_objective_id');

    $updatedRows = 1;

    // $affected_rows = $this->write_db->affected_rows();

    // if ($affected_rows > 0) {
    //   $updatedRows = $affected_rows;
    // }

    $this->batch_update_records($data);

    return $updatedRows;
  }

  public function insertRecord($data)
  {

    // Define the table name and insert the data
    $tableName = 'pca_strategy';

    $this->write_db->insert_batch($tableName, $data);

    $insertedRows = 0;

    $affected_rows = $this->write_db->affected_rows();

    if ($affected_rows > 0) {
      $insertedRows = $affected_rows;
    }

    return $insertedRows;
  }

  static function get_menu_list()
  {
  }

  function get_objectives_interventions()
  {
    $post = $this->input->post();

    $this->read_db->select(array('pca_strategy_intervention_name'));
    $this->read_db->where(array('pca_strategy_objective_id' => $post['objective_id']));
    $intervention_obj = $this->read_db->get('pca_strategy');

    $interventions = [];

    if ($intervention_obj->num_rows() > 0) {
      $interventions = $intervention_obj->result_array();
    }

    $intervention_names = '';

    if (is_array($interventions) && !empty($interventions)) {
      $intervention_names = array_column($interventions, 'pca_strategy_intervention_name');
    }

    echo json_encode($intervention_names);
  }

  function upload_strategic_objectives()
  {
    $message = get_phrase('upload_successful', 'Upload successful');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

      $post = $this->input->post();

      $office_id = $post['office_id'];
      $annual_plan_start_date = $post['annual_plan_start_date'];
      $annual_plan_end_date = $post['annual_plan_end_date'];

      $uploadDir = 'uploads' . DIRECTORY_SEPARATOR . 'strategic_objectives' . DIRECTORY_SEPARATOR;

      $postedFile = $uploadDir . basename($_FILES['file']['name']);
      $uploadFileType = strtolower(pathinfo($postedFile, PATHINFO_EXTENSION)); // Get the file extension

      $custom_filename = $office_id . '_' . $annual_plan_start_date . '_' . $annual_plan_end_date . '.' . $uploadFileType;
      $uploadFile = $uploadDir . $custom_filename; // $uploadDir . basename($_FILES['file']['name']);

      if (!move_uploaded_file($_FILES['file']['tmp_name'], $uploadFile)) {
        $message = get_phrase('upload_failed', 'File upload failed');
      } else {
        $success = $this->import_objectives($uploadFile);

        if ($success['status']) {
          $this->upload_to_s3($uploadFile, $_FILES['file'], $custom_filename);
        } else {
          $message = $success['message']; // get_phrase('upload_failed', 'File upload failed');
        }
      }
    }

    echo $message;
  }


  // function create_database_record($uploadFile)
  // {
  //   return $this->import_objectives($uploadFile);
  // }

  function upload_to_s3($uploadFile, $file, $custom_filename)
  {
    // log_message('error', json_encode($uploadFile));

    $this->load->library('aws_attachment_library');
    $storePath = 'uploads/strategic_objectives';
    $attachment_field = [
      'fk_approve_item_id' => $this->read_db->select(array('approve_item_id'))->get_where('approve_item', ['approve_item_name' => 'strategic_objectives'])->row()->approve_item_id,
      'attachment_primary_id' => 0,
      'fk_attachment_type_id' => $this->read_db->select(array('attachment_type_id'))->get_where('attachment_type', ['attachment_type_name' => 'strategic_objectives'])->row()->attachment_type_id,
      'attachment_created_date' => date('Y-m-d'),
      'attachment_created_by' => $this->session->user_id,
      'attachment_last_modified_by' => $this->session->user_id,
      'attachment_is_s3_upload' => 1
    ];
    $this->aws_attachment_library->upload_single_file_from_directory($storePath, $uploadFile, $file, $custom_filename, $attachment_field);

    unlink($uploadFile);
    return true;
  }
  function result($id = 0)
  {

    $result = [];

    if ($this->action == 'list') {
      $columns = $this->columns();
      array_shift($columns);
      $columns = array_column(alias_columns($columns), 'list_columns');

      $result['columns'] = $columns;
      $result['has_details_table'] = false;
      $result['has_details_listing'] = false;
      $result['is_multi_row'] = false;
      $result['show_add_button'] = true;
    } else {
      $result = parent::result($id);
    }

    return $result;
  }

  function columns()
  {
    $columns = [
      'DISTINCT(pca_strategy_objective_id) as pca_strategy_objective_id',
      'office_code',
      'pca_strategy_plan_name as strategy_plan_name',
      'pca_strategy_objective_name as objective_name',
      // 'pca_strategy_intervention_name as intervention_name',
      // 'pca_strategy_start_date as annual_plan_start_date',
      // 'pca_strategy_end_date as annual_plan_end_date'
    ];

    return $columns;
  }

  function get_strategic_objectives()
  {
    $columns = $this->columns();
    $search_columns = array_column(alias_columns($columns), 'query_columns'); // $columns;

    // Limiting records
    $start = intval($this->input->post('start'));
    $length = intval($this->input->post('length'));

    $this->read_db->limit($length, $start);

    // Ordering records

    $order = $this->input->post('order');
    $col = '';
    $dir = 'desc';

    if (!empty($order)) {
      $col = $order[0]['column'];
      $dir = $order[0]['dir'];
    }

    if ($col == '') {
      $this->read_db->order_by('fk_office_id DESC');
    } else {
      $this->read_db->order_by($columns[$col], $dir);
    }

    // Searching

    $search = $this->input->post('search');
    $value = $search['value'];

    array_shift($search_columns);

    if (!empty($value)) {
      $this->read_db->group_start();
      $column_key = 0;
      foreach ($search_columns as $column) {
        if ($column_key == 0) {
          $this->read_db->like($column, $value, 'both');
        } else {
          $this->read_db->or_like($column, $value, 'both');
        }
        $column_key++;
      }
      $this->read_db->group_end();
    }

    if (!$this->session->system_admin) {
      $this->read_db->join('office', 'office.office_id=pca_strategy.fk_office_id');
      $this->read_db->join('account_system', 'account_system.account_system_id=office.fk_account_system_id');
      $this->read_db->where(array('office.fk_account_system_id' => $this->session->user_account_system_id));
    }

    $this->read_db->select($columns);

    $result_obj = $this->read_db->get('pca_strategy');

    $results = [];

    if ($result_obj->num_rows() > 0) {
      $results = $result_obj->result_array();
    }

    return $results;
  }

  function count_strategic_objectives()
  {
    $columns = $this->columns();
    $search_columns = array_column(alias_columns($columns), 'query_columns'); // $columns;

    // Searching

    $search = $this->input->post('search');
    $value = $search['value'];

    array_shift($search_columns);

    if (!empty($value)) {
      $this->read_db->group_start();
      $column_key = 0;
      foreach ($search_columns as $column) {
        if ($column_key == 0) {
          $this->read_db->like($column, $value, 'both');
        } else {
          $this->read_db->or_like($column, $value, 'both');
        }
        $column_key++;
      }
      $this->read_db->group_end();
    }


    if (!$this->session->system_admin) {
      $this->read_db->join('office', 'office.office_id=pca_strategy.fk_office_id');
      $this->read_db->join('account_system', 'account_system.account_system_id=office.fk_account_system_id');
      $this->read_db->where(array('office.fk_account_system_id' => $this->session->user_account_system_id));
    }

    $this->read_db->select($columns);
    $count_all_results = $this->read_db->get('pca_strategy')->num_rows();

    return $count_all_results;
  }

  function show_list()
  {

    $draw = intval($this->input->post('draw'));
    $strategic_objectives = $this->get_strategic_objectives();
    $count_strategic_objectives = $this->count_strategic_objectives();

    $result = [];

    $cnt = 0;
    foreach ($strategic_objectives as $strategic_objective) {
      $strategic_objective_id = array_shift($strategic_objective);
      $strategic_objective['pca_strategy_objective_id'] = '<i class = "fa fa-plus" id = "' . $strategic_objective_id . '"></i>';

      $row = array_values($strategic_objective);

      $result[$cnt] = $row;

      $cnt++;
    }

    $response = [
      'draw' => $draw,
      'recordsTotal' => $count_strategic_objectives,
      'recordsFiltered' => $count_strategic_objectives,
      'data' => $result
    ];

    echo json_encode($response);
  }
}
