<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Language_library extends Grants
{

  private $CI;
  private $language = '';
  private $default_language = '';
  private $account_system_code = 'global';
  private $default_language_path = APPPATH.'language'.DIRECTORY_SEPARATOR;
  private $global_language_path = APPPATH.'language'.DIRECTORY_SEPARATOR.'global'.DIRECTORY_SEPARATOR;
  //private $context = 'system';
  private $lang_strings = [];

  function __construct(){
    parent::__construct();
    $this->CI =& get_instance();

    $this->CI->load->model('language_model');

    $this->default_language = $this->CI->language_model->default_language()['language_code'];

    $this->language = $this->default_language;
  
    $this->account_system_code = $this->CI->session->has_userdata('user_account_system') ? $this->CI->session->user_account_system : $this->account_system_code;
    // log_message('error', $this->CI->session->user_account_system);
    $this->default_language_path = $this->default_language_path.$this->account_system_code.DIRECTORY_SEPARATOR;
    
  }

  function index(){

  }
  
  function set_language($locale){
    $this->language = $locale;
    return $this;
  }

  function create_language_files($language, $lang_file_group = 'global'){

    if($lang_file_group != "global"){
      $this->default_language_path = APPPATH.'language'.DIRECTORY_SEPARATOR.$lang_file_group.DIRECTORY_SEPARATOR;
    }

    if(!file_exists($this->default_language_path)){
      mkdir($this->default_language_path);
    }

    // log_message('error', json_encode($this->default_language_path.$language.'_lang.php'));

    if(!file_exists($this->default_language_path.$language.'_lang.php')){
      $fp = fopen($this->default_language_path.$language.'_lang.php', 'a');
      fwrite($fp,'<?php '.PHP_EOL);

      if($this->account_system_code != 'global' ){
        if (
              file_exists($this->default_language_path.$language.'_lang.php') 
              && file_exists($this->global_language_path.$language.'_lang.php')
              && !copy($this->global_language_path.$language.'_lang.php', $this->default_language_path.$language.'_lang.php')) 
        {
            log_message('error', 'Coyping language file failed');
        }
      }else{
        $this->CI->session->set_userdata('user_locale', $this->default_language);
        if (!copy($this->global_language_path.$this->default_language.'_lang.php', $this->default_language_path.$language.'_lang.php')) {
          log_message('error', 'Coyping language file failed');
        }
      }

      // Get Language id
      $language_id = $this->CI->language_model->get_language_id_by_code($language);
      // log_message('error', json_encode($language_id));
      // Update the account system language setting
      $this->CI->load->model('account_system_language_model');
      $this->CI->account_system_language_model->insert_new_account_system_language($language_id);

      // Remove duplicate lines from the lang file
      $this->remove_duplicate_language_keys($this->default_language_path.$this->language.'_lang.php');

    }


    return true;
  }

  function remove_duplicate_language_keys($lang_file_path){

    /**
     * https://stackoverflow.com/questions/13384490/remove-duplicate-data-in-a-text-file-with-php
     */

    $lines = file($lang_file_path);
    $lines = array_unique($lines);
    file_put_contents($lang_file_path, implode($lines));
  }
	
	public function load_language()
	{
		$lang = array();
    
    $this->create_language_files($this->language, $this->account_system_code);

    ob_start();
		include($this->default_language_path.$this->language.'_lang.php');
    ob_get_contents();
    ob_end_clean();

    $this->lang_strings = $lang;

    return $this->lang_strings;

	}
	
	
	// function language_phrase($handle, $translated_string = "", $translate = false){

  //   $this->load_language();

	// 	if(strlen($handle) > 1  && !array_key_exists($handle, $this->lang_strings) && !is_numeric($handle))
	// 	{
				
	// 		$phrase = $translated_string == "" ? ucwords(implode(" ",explode("_", $handle))) : $translated_string;

  //     $handle = strtolower($handle);

  //     // log_message('error', json_encode($this->lang_strings));
			
  //     if(!array_key_exists($handle, $this->lang_strings)){
  //       //Add the new lang phrase to the language file
  //       $new_lang_phrase = "	\$lang['".$handle."'] = '".$phrase."';".PHP_EOL;
  //       $fp = fopen($this->default_language_path.$this->language.'.php', 'a');
  //       fwrite($fp, $new_lang_phrase);
  //       fclose($fp);
  //     }elseif($translate){
        
  //     }
			
	// 		$this->lang_strings[$handle] = $phrase;
	// 	}
			
  //   return isset($this->lang_strings[$handle])?$this->lang_strings[$handle]:"";
	// }	
	

	
	
}
