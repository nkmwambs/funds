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

  }

  // function delete($id = null){

  // }

  function index(){}

  function result($id = 0){

    $result = parent::result($id);
    
    if($this->action == 'view'){

      $id = hash_id($this->id, 'decode');

      $language = $this->get_language_by_id($id);

      $result['language'] = $language;
      $result['phrases'] = $this->get_language_phrases($language['language_code']);
    }

    return $result;
  }

  function get_language_phrases($language_code){

    $user_account_system = $this->session->user_account_system;
    $phrases = $this->language_library->load_language();

    return $phrases;
  }

  function get_language_by_id($id){

    $this->read_db->select(array('language_id', 'language_name', 'language_code'));
    $this->read_db->where(array('language_id' => $id));
    $language = $this->read_db->get('language')->row_array();

    return $language;
  }

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

  function translate_phrase(){
    $post = $this->input->post();

    $handle = $post['handle'];
    $phrase = $post['phrase'];

    $language_phrases = $this->get_language_phrases('sw');
    $language_phrases[$handle] = $phrase;

    log_message('error',json_encode($language_phrases));
    
    // $new_lang_phrase = "	\$lang['".$handle."'] = '".$phrase."';".PHP_EOL;
    // $fp = fopen($this->default_language_path.$this->language.'.php', 'a');
    // fwrite($fp, $new_lang_phrase);
    // fclose($fp);

    echo get_phrase('translation_successs_message', "Your phrase has been translated successfully");
  }


  static function get_menu_list(){

  }

}