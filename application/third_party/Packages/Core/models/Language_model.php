<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Language_model extends MY_Model
{
  public $table = 'language'; // you MUST mention the table name
  public $primary_key = 'language_id'; // you MUST mention the primary key
  public $fillable = array(); // If you want, you can set an array with the fields that can be filled by insert/update
  public $protected = array(); // ...Or you can set an array with the fields that cannot be filled by insert/update

  function __construct(){
    parent::__construct();
    $this->load->database();

  }

  function index(){

  }

  function detail_tables(){
    return [];
  }
  
  function action_before_edit($post_array){

    $new_language_code = $post_array['header']['language_code'].'_lang';

    $language_id = hash_id($this->id, 'decode');

    $this->read_db->select(array('language_id', 'language_code'));
    $this->read_db->where(array('language_id' => $language_id));
    $language = $this->read_db->get('language')->row_array();

    $old_language_code = $language['language_code'].'_lang';

    $path = APPPATH.DIRECTORY_SEPARATOR.'language';

    rename_files_in_directory($path, $old_language_code, $new_language_code);

    // log_message('error', json_encode($language));
    // log_message('error', json_encode($post_array));

    return $post_array;
  }

  function action_after_insert($post_array, $approval_id, $header_id){

    $lang_code = $post_array['language_code'];

    return $this->language_library->create_language_files($lang_code, 'global');
  }

  function get_language_id_by_code($language_code){
    $language_id = 1;

    $this->read_db->where(array('language_code' => $language_code));
    $language_obj = $this->read_db->get('language');

    if($language_obj->num_rows() > 0){
      $language_id = $language_obj->row()->language_id;
    }

    return $language_id;
  }

  function catch_language_phrase(int $language_id, int $account_system_id, array $translations){
    $this->read_db->where(array('fk_language_id' => $language_id, 'fk_account_system_id' => $account_system_id));
    $language_phrases_obj = $this->read_db->get('language_phrase');

    if($language_phrases_obj->num_rows() > 0){
      // log_message('error', 'Here');
      $data['language_phrase_data'] = json_encode($translations);
      $data['language_phrase_last_modified_date'] = date('Y-m-d h:i:s');
      $data['language_phrase_last_modified_by'] = $this->session->user_id;

      $this->write_db->where(array('fk_language_id' => $language_id, 'fk_account_system_id' => $account_system_id));
      $this->write_db->update('language_phrase', $data);
    }else{
      // log_message('error', 'There');
      $data['fk_account_system_id'] = $account_system_id;
      $data['fk_language_id'] = $language_id;
      $data['language_phrase_data'] = json_encode($translations);
      $data['language_phrase_created_date'] = date('Y-m-d h:i:s');
      $data['language_phrase_created_by'] = $this->session->user_id;
      $data['language_phrase_last_modified_date'] = date('Y-m-d h:i:s');
      $data['language_phrase_last_modified_by'] = $this->session->user_id;
      $this->write_db->insert('language_phrase', $data);
    }
  }

  // function catch_language_phrase($handle, $translated_string, $language_id = 0, $account_system_id = 0){

  //   if($account_system_id == 0){
  //     $account_system_id = $this->session->user_account_system_id == null ? 1 : $this->session->user_account_system_id;
  //   }
    
  //   if($language_id == 0){
  //     $language_obj = $this->read_db->get_where('language', array('language_code' => $this->session->user_locale));
  //     $language_id =  $language_obj->num_rows() > 0 ? $language_obj->row()->language_id : 1;
  //   }

  //   $data['phrase'] = $handle;
  //   $data['phrase_translation'] = $translated_string;
  //   $data['fk_account_system_id'] = $account_system_id;
  //   $data['fk_language_id'] = $language_id;

  //   $this->read_db->where(array('phrase' => $handle, 'fk_account_system_id' => $account_system_id, 'fk_language_id' => $language_id));
  //   $count_language_phrase = $this->read_db->get('language_phrase')->num_rows();

  //   if($count_language_phrase == 0){
  //     $this->write_db->insert('language_phrase',$data);
  //   }else{
  //     $this->write_db->where(array('phrase' => $handle, 'fk_account_system_id' => $account_system_id, 'fk_language_id' => $language_id));
  //     $this->write_db->update('language_phrase',$data);
  //   }
    
  // }

  function get_user_available_languages(){
    $languages = [];
    
    $files = APPPATH.'language'.DIRECTORY_SEPARATOR.$this->session->user_account_system.DIRECTORY_SEPARATOR;

    if(!file_exists($files)){
      mkdir($files);
    }

    $i = 0;

    foreach (new DirectoryIterator($files) as $fileInfo) {
      if($fileInfo->isDot()) continue;

      $languages[$i] = explode('_',pathinfo($fileInfo->getFilename(), PATHINFO_FILENAME))[0]; // The array formed has the language code and _lang appended to it

      $i++;
    }


    $langs = [];

    if(!empty($languages)){

      $this->read_db->select(array('language_id','language_name','language_code'));
      $this->read_db->where_in('language_code',$languages);
      $langs_obj = $this->read_db->get('language');

      if($langs_obj->num_rows() > 0){
        $langs = $langs_obj->result_array();
      }
    }

    return $langs;
  }

  function list_table_where(){
    if (!$this->session->system_admin) {

      $languages = $this->get_user_available_languages();

      $language_codes = array_column($languages,'language_code');

      $this->read_db->where_in('language_code', $language_codes);
    }
  }

  function default_language(){

    $this->db->select(array('language_id', 'language_code', 'language_name', 'language_is_default'));
    $this->db->where(array('language_is_default' => 1));
    $lang_obj = $this->db->get('language');

    $default_lang = []; 

    if($lang_obj->num_rows() > 0){
      $default_lang = $lang_obj->row_array();
    }

    return $default_lang;

  }

  function intialize_table(Array $foreign_keys_values = []){  

    $language_data['language_track_number'] = $this->grants_model->generate_item_track_number_and_name('language')['language_track_number'];
    $language_data['language_name'] = 'English';
    $language_data['language_code'] = 'en';
        
    $language_data_to_insert = $this->grants_model->merge_with_history_fields('language',$language_data,false);
    $this->write_db->insert('language',$language_data_to_insert);

    return $this->write_db->insert_id();
}

}
