<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */


class Language extends MY_Controller
{

  function __construct(){
    parent::__construct();
    $this->load->helper('download');
    $this->load->library('upload');
  }

  // function delete($id = null){

  // }

  function index(){}

  // function result($id = 0){

  //   $result = parent::result($id);
    
  //   if($this->action == 'view'){

  //     $id = hash_id($this->id, 'decode');

  //     $language = $this->get_language_by_id($id);

  //     $result['language'] = $language;
  //     $result['phrases'] = $this->get_language_phrases($language['language_code']);
  //   }

  //   return $result;
  // }

  // function get_language_phrases($language_code){

  //   $user_account_system = $this->session->user_account_system;
  //   $phrases = $this->language_library->load_language();

  //   return $phrases;
  // }

  // function get_language_by_id($id){

  //   $this->read_db->select(array('language_id', 'language_name', 'language_code'));
  //   $this->read_db->where(array('language_id' => $id));
  //   $language = $this->read_db->get('language')->row_array();

  //   return $language;
  // }

  function switch_language($lang){

    $this->read_db->where(array('language_code' => $lang));
    $language_obj = $this->read_db->get('language')->row();

    $message = "You have not changed your language";

    if($this->session->user_locale != $lang){

      $data['fk_language_id	'] = $language_obj->language_id;
      $this->write_db->where(array('user_id' => $this->session->user_id));
      $this->write_db->update('user', $data);

      $language_id = $language_obj->language_id;
      $user_account_system_id = $this->session->user_account_system_id;

      $data['fk_language_id	'] = $language_id;
      $this->write_db->where(array('user_id' => $this->session->user_id));
      $this->write_db->update('user', $data);

      // Check if the language is available in the account system language
      $this->read_db->where(array('fk_language_id' => $language_id, 
      'fk_account_system_id' => $user_account_system_id));
      $account_system_language_count = $this->read_db->get('account_system_language')->num_rows();

      if($account_system_language_count == 0){
        
        $insert_data['account_system_language_name'] = $this->grants_model->generate_item_track_number_and_name('account_system_language')['account_system_language_name'];
        $insert_data['account_system_language_track_number'] = $this->grants_model->generate_item_track_number_and_name('account_system_language')['account_system_language_track_number'];
        $insert_data['fk_account_system_id'] = $user_account_system_id;
        $insert_data['fk_language_id'] = $language_id;
        $insert_data['account_system_language_created_date'] = date('Y-m-d');
        $insert_data['account_system_language_created_by'] = $this->session->user_id;;
        $insert_data['account_system_language_last_modified_by'] = $this->session->user_id;;
        $insert_data['fk_status_id'] = $this->grants_model->initial_item_status('voucher');

        $this->write_db->insert('account_system_language', $insert_data);
      }

      $message = "You language has been switched successfully";
    
    }

    $this->session->set_userdata('user_locale', $lang);

    echo get_phrase('language_switch_alert', $message);
  }

  // function translate_phrase(){
  //   $post = $this->input->post();

  //   $handle = $post['handle'];
  //   $phrase = $post['phrase'];

  //   $language_phrases = $this->get_language_phrases('sw');
  //   $language_phrases[$handle] = $phrase;

  //   log_message('error',json_encode($language_phrases));
    
  //   // $new_lang_phrase = "	\$lang['".$handle."'] = '".$phrase."';".PHP_EOL;
  //   // $fp = fopen($this->default_language_path.$this->language.'.php', 'a');
  //   // fwrite($fp, $new_lang_phrase);
  //   // fclose($fp);

  //   echo get_phrase('translation_successs_message', "Your phrase has been translated successfully");
  // }


  function result($id = 0){

    $result = parent::result($id);

    if($this->action == 'list'){
      $columns = $this->columns();
      array_shift($columns);
      $result['columns'] = $columns;
      $result['has_details_table'] = false; 
      $result['has_details_listing'] = false;
      $result['is_multi_row'] = false;
      $result['show_add_button'] = true;
    }elseif($this->action == 'view'){
      $result['master']['account_system_code'] = $this->session->user_account_system;
      $result['master']['language_code'] = $this->getLanguageById(hash_id($this->id, 'decode'))->language_code;
      $result['master']['account_systems'] = $this->getAccountSystems();
    }

    return $result;
  }

  function getAccountSystems(){
    $this->read_db->select(array('account_system_id', 'account_system_code', 'account_system_name'));
    $account_systems = $this->read_db->get('account_system')->result_object();
    return $account_systems;
  }

  private function extract_lang_array($file_contents) {
    $lang = [];
    eval('?>' . $file_contents);
    return $lang;
}

private function array_to_csv($array) {
    // Create a file pointer
    $fp = fopen('php://temp', 'w+');
    // Write the header row
    fputcsv($fp, ['Key', 'Value']);
    // Write the array to the file pointer in CSV format
    foreach ($array as $key => $value) {
        fputcsv($fp, [$key, $value]);
    }
    // Rewind the file pointer
    rewind($fp);
    // Read the entire file content
    $csv_string = stream_get_contents($fp);
    // Close the file pointer
    fclose($fp);
    return $csv_string;
}

  function getLanguageById($id){
    $this->read_db->where(array('language_id' => $id));
    $language = $this->read_db->get('language')->row();
    return $language;
  }

  function download_language_file($account_system_code, $language_code){

    // $dBLanguagePhrases = $this->getLanguagePhrases($language_code, $account_system_code);

    // if(empty($dBLanguagePhrases)){

      $path = APPPATH."language/".$account_system_code."/".$language_code."_lang.php";
      if(!file_exists($path)){
        $path = APPPATH."language/global/".$language_code."_lang.php";
      }
      $file_contents = file_get_contents($path);
      // Extract the array from the file contents
      $dBLanguagePhrases = $this->extract_lang_array($file_contents);
    // }
    
    // Convert the $lang array to a CSV string
    $csv_string = $this->array_to_csv($dBLanguagePhrases);

    // force_download(APPPATH."language/".$account_system_code."/".$language_code."_lang.php", NULL);
    force_download($language_code."_lang.csv", $csv_string);
    
  }

  function getLanguagePhrases($languageCode, $accountSystemCode){

    $this->read_db->select(array('language_phrase_data'));
    $this->read_db->where(array('language_code' => $languageCode, 'account_system_code' => $accountSystemCode));
    $this->read_db->join('language','language.language_id=language_phrase.fk_language_id');
    $this->read_db->join('account_system','account_system.account_system_id=language_phrase.fk_account_system_id');
    $language_phrases_obj = $this->read_db->get('language_phrase');

    $language_phrases = [];

    if($language_phrases_obj->num_rows() > 0){
      $language_phrases = json_decode($language_phrases_obj->row_array());
    }

    return $language_phrases;
  }

  private function getLanguageByCode($languageCode){
    $this->read_db->where(array('language_code' => $languageCode));
    $language = $this->read_db->get('language')->row();
    return $language;
  }

  public function upload_language_file() {
    $account_system_code = $this->input->post('account_system_code');
    $language_code = $this->input->post('language_code');
    // Load the form helper
    $this->load->helper('form');

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
        // Check file extension
        $file_ext = pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION);
        if ($file_ext !== 'csv') {
            log_message('error', "Invalid file format. Please upload a CSV file.");
            return;
        }

        // Define the target path for the uploaded file
        $upload_path = APPPATH . 'language/'.$account_system_code.'/';
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0777, true);
        }

        
        $file_parts = explode('_',basename($_FILES['csv_file']['name']));
        if($file_parts[0] != $language_code){
          log_message('error', "File language code mismatch. Please rename the file to the same language code");
          return;
        }
        
        $target_file = $upload_path . basename($_FILES['csv_file']['name']);
        
        // Move the uploaded file to the target path
        if (move_uploaded_file($_FILES['csv_file']['tmp_name'], $target_file)) {
            // Convert CSV to array
            $csv_array = $this->csv_to_array($target_file);
            // Save array to PHP file
            $user_account_system_id = $this->getAccountSystemIdByCode($account_system_code);
            $language_id = $this->getLanguageByCode($language_code)->language_id;

            $this->language_model->catch_language_phrase($language_id, $user_account_system_id, $csv_array);
            
            $this->save_array_to_php_file($csv_array, APPPATH . 'language/'.$account_system_code.'/'.$language_code.'_lang.php');
            
            // Remove the CSV file
            unlink($target_file);

            log_message('info', "File uploaded and processed successfully!");
        } else {
            log_message('error', "There was an error uploading your file.");
        }
    } else{
      log_message('error', "There was an error uploading your file.");
    }
    
    header('Location: '.base_url().'language/list');
   
}

function getAccountSystemIdByCode($account_system_code){
  $this->read_db->where(array('account_system_code' => $account_system_code));
  $account_system_id = $this->read_db->get('account_system')->row()->account_system_id;
  return $account_system_id;
}

private function csv_to_array($file_path) {
  $array = [];
  if (($handle = fopen($file_path, 'r')) !== FALSE) {
      $header = fgetcsv($handle, 1000, ','); // Skip the first row (header)
      while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
          $array[$data[0]] = $data[1];
      }
      fclose($handle);
  }
  return $array;
}


private function save_array_to_php_file($array, $file_path) {
    $php_code = "<?php\n\n";
    foreach ($array as $key => $value) {
        $php_code .= "\$lang['$key'] = \"" . addslashes($value) . "\";\n";
    }
    file_put_contents($file_path, $php_code);
}

  function columns(){
    $columns = [
      'language_id',
      'language_track_number',
      'language_name',
      'language_code',
    ];

    return $columns;
  }


  function get_languages(){

    $columns = $this->columns();
    $search_columns = $columns;

    // Limiting records
    $start = intval($this->input->post('start'));
    $length = intval($this->input->post('length'));

    $this->read_db->limit($length, $start);

    // Ordering records

    $order = $this->input->post('order');
    $col = '';
    $dir = 'desc';
    
    if(!empty($order)){
      $col = $order[0]['column'];
      $dir = $order[0]['dir'];
    }
          
    if( $col == ''){
      $this->read_db->order_by('language_id DESC');
    }else{
      $this->read_db->order_by($columns[$col],$dir); 
    }

    // Searching

    $search = $this->input->post('search');
    $value = $search['value'];

    array_shift($search_columns);

    if(!empty($value)){
      $this->read_db->group_start();
      $column_key = 0;
        foreach($search_columns as $column){
          if($column_key == 0) {
            $this->read_db->like($column,$value,'both'); 
          }else{
            $this->read_db->or_like($column,$value,'both');
        }
          $column_key++;				
      }
      $this->read_db->group_end();      
    }
    
    // if(!$this->session->system_admin){
    //   $this->read_db->where(array('bank.fk_account_system_id'=>$this->session->user_account_system_id));
    // }

    $this->read_db->select($columns);
    // $this->read_db->join('status','status.status_id=bank.fk_status_id');
    // $this->read_db->join('account_system','account_system.account_system_id=bank.fk_account_system_id');

    $result_obj = $this->read_db->get('language');
    
    $results = [];

    if($result_obj->num_rows() > 0){
      $results = $result_obj->result_array();
    }

    return $results;
  }

  function count_languages(){

    $columns = $this->columns();
    $search_columns = $columns;

    // Searching

    $search = $this->input->post('search');
    $value = $search['value'];

    array_shift($search_columns);

    if(!empty($value)){
      $this->read_db->group_start();
      $column_key = 0;
        foreach($search_columns as $column){
          if($column_key == 0) {
            $this->read_db->like($column,$value,'both'); 
          }else{
            $this->read_db->or_like($column,$value,'both');
        }
          $column_key++;				
      }
      $this->read_db->group_end();
    }
    
    // if(!$this->session->system_admin){
    //   $this->read_db->where(array('bank.fk_account_system_id'=>$this->session->user_account_system_id));
    // }

    // $this->read_db->join('status','status.status_id=bank.fk_status_id');
    // $this->read_db->join('account_system','account_system.account_system_id=bank.fk_account_system_id');
    

    $this->read_db->from('language');
    $count_all_results = $this->read_db->count_all_results();

    return $count_all_results;
  }

  function show_list(){
   
    $draw =intval($this->input->post('draw'));
    $languages = $this->get_languages();
    $count_languages = $this->count_languages();

    $result = [];

    $cnt = 0;
    foreach($languages as $language){
      $language_id = array_shift($language);
      $language_track_number = $language['language_track_number'];
      $language['language_track_number'] = '<a href="'.base_url().$this->controller.'/view/'.hash_id($language_id).'">'.$language_track_number.'</a>';
      $row = array_values($language);

      $result[$cnt] = $row;

      $cnt++;
    }

    $response = [
      'draw'=>$draw,
      'recordsTotal'=>$count_languages,
      'recordsFiltered'=>$count_languages,
      'data'=>$result
    ];
    
    echo json_encode($response);
  }


  static function get_menu_list(){

  }

}