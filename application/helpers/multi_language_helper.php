<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('get_phrase'))
{
	function get_phrase($handle, $translated_string = "", $phrase_variables_values = []) {
		
		$CI =& get_instance();
		$CI->load->model('language_model');

		//return $CI->language_library->language_phrase($phrase);

		// return !lang($phrase) ? ucwords(str_replace("_", " ", $phrase)) : lang($phrase);

		$translation = ucwords(str_replace("_", " ", $handle));

		if($CI->lang->line($handle, false)){
			$translation =  $CI->lang->line($handle);
		}elseif($translated_string != ""){
			$translation = $translated_string;

			$CI->language_model->catch_language_phrase($handle, $translated_string);
		}else{
			$CI->language_model->catch_language_phrase($handle, $translated_string);
		}

		// Phrase tags variables replacement
		
		if(!empty($phrase_variables_values)){
			// log_message('error', json_encode($phrase_variables_values));
			foreach ($phrase_variables_values as $placeholder => $replacement) {
				$placeholder = '{{' . $placeholder . '}}';
				$translation = str_replace($placeholder, $replacement, $translation);
			}
		}

		return $translation;


	}
}

