<?php

use function JmesPath\search;

 if (!defined('BASEPATH')) exit('No direct script access allowed');

 define('DS', DIRECTORY_SEPARATOR);
/**
 * This is the main helper file
 */

require_once(FCPATH . '/vendor/autoload.php');

if (!function_exists('fk_to_name_field')) {
    function fk_to_name_field($fk_field = '')
    {
        $xlpd = explode('_', substr($fk_field, 0, -3));
        unset($xlpd[0]);
        return implode("_", $xlpd) . "_name";
    }
}

if (!function_exists('elevate_array_element_to_key')) {
    function elevate_array_element_to_key($unevelavated_array, $element_to_elevate)
    {
        $elevated_array = array();
        foreach ($unevelavated_array as $item) {

            //Cast $item to array if object
            $item = is_object($item) ? (array)$item : $item;

            $elevated_array[$item[$element_to_elevate]] =  $item;

            unset($elevated_array[$item[$element_to_elevate]][$element_to_elevate]);
        }

        return $elevated_array;
    }
}

if (!function_exists('elevate_assoc_array_element_to_key')) {
    function elevate_assoc_array_element_to_key($unevelavated_array, $element_to_elevate)
    {
        $elevated_array = array();
        $cnt = 0;
        foreach ($unevelavated_array as $item) {

            //Cast $item to array if object
            $item = is_object($item) ? (array)$item : $item;

            $elevated_array[$item[$element_to_elevate]][$cnt] =  $item;

            unset($elevated_array[$item[$element_to_elevate]][$cnt][$element_to_elevate]);
            $cnt++;
        }

        return $elevated_array;
    }
}

if (!function_exists('hash_id')) {
    function hash_id($id, $action = 'encode')
    {
        $hashids = new Hashids\Hashids('#Compassion321', 10);

        if ($action == 'encode') {
            return $hashids->encode($id);
        } elseif (isset($hashids->decode($id)[0])) {
            //print_r($hashids->decode($id));exit();
            return $hashids->decode($id)[0];
        } else {
            return null;
        }
    }
}

//Camel cases header elements of list table

if (!function_exists('camel_case_header_element')) {
    function camel_case_header_element($header_element)
    {
        return  get_phrase($header_element); //ucwords(str_replace('_',' ',$header_element));
    }
}

//Create the th elements of a list table with camel cased headers from keys elements of grants library list result return
// It escapes putting a field with Key and id in its string

if (!function_exists('render_list_table_header')) {
    function render_list_table_header($table_name, $header_array)
    {
        $string = '<tr><th nowrap="nowrap">' . get_phrase("action") . '</th>';

        foreach ($header_array as $th_value) {
            if (strpos($th_value, 'key') == true || strpos($th_value, '_id') == true) {
                continue;
            }

            $string .= '<th nowrap="nowrap">' . camel_case_header_element($th_value) . '</th>';
        }
        $string .= '</tr>';

        return $string;
    }
}

if (!function_exists('list_table_edit_action')) {
    function list_table_edit_action($table_controller, $primary_key, $status_id = 0)
    {

        $string = '<a class="list_edit_link" href="' . base_url() . ucfirst($table_controller) . '/edit/' . hash_id($primary_key, 'encode') . '">' . get_phrase("edit") . '</a>';

        return $string;
    }
}

if (!function_exists('list_table_delete_action')) {
    function list_table_delete_action($table_controller, $primary_key)
    {

        $string = '<a class="list_delete_link" href="' . base_url() . ucfirst($table_controller) . '/delete/' . hash_id($primary_key) . '">' . get_phrase("delete") . '</a>';

        return $string;
    }
}

if (!function_exists('list_table_approval_action')) {
    function list_table_approval_action($table_controller, $primary_key)
    {

        $string = '<a class="list_approval_link" href="' . base_url() . ucfirst($table_controller) . '/approve/' . hash_id($primary_key) . '">' . get_phrase("approve") . '</a>';

        return $string;
    }
}

if (!function_exists('list_table_decline_action')) {
    function list_table_decline_action($table_controller, $primary_key)
    {

        $string = '<a class="list_decline_link" href="' . base_url() . ucfirst($table_controller) . '/decline/' . hash_id($primary_key) . '">' . get_phrase("decline") . '</a>';

        return $string;
    }
}

if (!function_exists('add_record_button')) {
    function add_record_button($table_controller, $has_details, $id = null, $has_listing = false, $is_multi_row = false)
    {
        $add_view = $has_listing ? "multi_form_add" : "single_form_add";
        $add_view = $is_multi_row ? "multi_row_add" : $add_view;

        $link = "";
        $CI = &get_instance();

        if ($id !== null) {
            $link =  '<a href="' . base_url() . strtolower($table_controller) . '/' . $add_view . '/' . $id . '/' . $CI->controller . '" class="btn btn-default">' . get_phrase('add_' . strtolower($table_controller)) . '</a>';
        } else {
            $link =  '<a style="margin-bottom:-70px;z-index:100;position:relative;" href="' . base_url() . $table_controller . '/' . $add_view . '" class="btn btn-default">' . get_phrase('add_' . strtolower($table_controller)) . '</a>';
        }

        return $link;
    }
}

if (!function_exists('create_breadcrumb')) {
    function create_breadcrumb()
    {

        $CI = &get_instance();

        $CI->menu_library->create_breadcrumb();

        $breadcrumb_list = $CI->session->breadcrumb_list;

		$string = '<nav class = "hidden-print" aria-label="breadcrumb"><ol class="breadcrumb">';

        foreach ($breadcrumb_list as $menuItem) {
            if ($CI->read_db->get_where(
                'menu',
                array('menu_name' => $menuItem, 'menu_is_active' => 0)
            )->num_rows() > 0) continue;

            $string .= '<li class="breadcrumb-item"><a href="' . base_url() . $menuItem . '/list">' . get_phrase($menuItem) . '</a></li>';
        }

        $string .= '</ol></nav>';

        return $string;
    }
}


if (!function_exists('record_prefix')) {
    function record_prefix($string)
    {
        $lead_string = substr($string, 0, 2);
        $trail_string = substr($string, -2, 2);

        return strtoupper($lead_string . $trail_string);
    }
}

if (!function_exists('condition_operators')) {
    function condition_operators()
    {
        $operators = [
            'equal' => get_phrase('equal'),
            'great_than' => get_phrase('great_than'),
            'less_than' => get_phrase('less_than'),
            'less_or_equal' => get_phrase('less_or_equal'),
            'great_or_equal' => get_phrase('great_or_equal'),
            'between' => get_phrase('between'),
            'contains' => get_phrase('contains'),
        ];

        return $operators;
    }
}

if (!function_exists('model_exists')) {
    function model_exists($name)
    {
        $CI = &get_instance();
        foreach ($CI->config->_config_paths as $config_path) {
            if (file_exists(FCPATH . $config_path . 'models/' . $name . '.php')) {
                return true;
            } else {
                return false;
            }
        }
    }
}

if (!function_exists('combine_name_with_ids')) {
    function combine_name_with_ids($office_names_and_ids_arr, $id_field_name, $name_field_name)
    {

        // Onduso modified this code on 2/8/2022

        // Remove Context for office

        $names_and_ids_arr_without_context_for_office_part=[];
            
        foreach ($office_names_and_ids_arr as $name_and_ids) {

            if(strpos($name_and_ids[$name_field_name],'office')){
                $explode_to_remove_context_office_of_part = explode('Context for office', $name_and_ids[$name_field_name]);

                $name_and_ids[$name_field_name] = $explode_to_remove_context_office_of_part[1];

                $names_and_ids_arr_without_context_for_office_part[] = $name_and_ids;
            }else{
                $names_and_ids_arr_without_context_for_office_part[] = $name_and_ids;
            }

        }


        $names = array_column($names_and_ids_arr_without_context_for_office_part, $name_field_name);

        $ids = array_column($names_and_ids_arr_without_context_for_office_part, $id_field_name);

        return array_combine($ids, $names);
        //End of modification

    }
}

if (!function_exists('cap_url_controller')) {
    function cap_url_controller($url)
    {
        $url_segments = parse_url($url);

        $path_array = explode("/", ltrim($url_segments['path'], '/'));

        array_shift($path_array);

        $arguments_array = array_map(function ($segment, $index) {

            if ($index == 0) {
                return ucfirst($segment);
            } else {
                return $segment;
            }
        }, $path_array, array_keys($path_array));

        echo base_url() . implode("/", $arguments_array);
    }
}

if(!function_exists('approval_action_button')){
    function approval_action_button($table_name, $item_status, $item_id, $status_id, $item_initial_item_status_id, $item_max_approval_status_ids, $disable_btn=false, $confirmation_required = true, $custom_status_name = ''){
        $CI = &get_instance();

        // log_message('error', json_encode(['table_name' => $table_name, 'item_status' => $item_status, 'item_id' => $item_id, 'status_id' => $status_id, 'item_initial_item_status_id' => $item_initial_item_status_id, 'item_max_approval_status_ids' => $item_max_approval_status_ids, 'disable_btn' => $disable_btn, 'confirmation_required' => $confirmation_required]));

        $disable_class='';

        if($disable_btn){
          $disable_class='disabled';
        }
        
        $buttons = '';

        if(!isset($item_status[$status_id])){
            return $buttons;
        }

        $role_ids = $CI->session->role_ids; 
        $status = 0;
        $status_button_label = '';
        $status_decline_button_label = '';
        $status_name = $custom_status_name;
        $status_approval_sequence = 0;
        $status_approval_direction = 0;

		$buttons = '';
		$role_ids = $CI->session->role_ids;	
		$status = $item_status[$status_id];
		$status_button_label = $item_status[$status_id]['status_button_label'] != '' ? $item_status[$status_id]['status_button_label'] : $item_status[$status_id]['status_name'];
		$status_decline_button_label = $item_status[$status_id]['status_decline_button_label'] != "" ? $item_status[$status_id]['status_decline_button_label']: get_phrase('return');
		$status_name = $item_status[$status_id]['status_name'];
		$status_approval_sequence =  $item_status[$status_id]['status_approval_sequence'];
		$status_approval_direction = $item_status[$status_id]['status_approval_direction'];

        if(isset($item_status[$status_id])){
            $status = $item_status[$status_id];
            $status_button_label = $item_status[$status_id]['status_button_label'] != '' ? $item_status[$status_id]['status_button_label'] : $item_status[$status_id]['status_name'];
            $status_decline_button_label = $item_status[$status_id]['status_decline_button_label'] != "" ? $item_status[$status_id]['status_decline_button_label']: get_phrase('decline');
            $status_name = $item_status[$status_id]['status_name'];
            $status_approval_sequence =  $item_status[$status_id]['status_approval_sequence'];
            $status_approval_direction = $item_status[$status_id]['status_approval_direction'];
        }
        

        $approve_next_status = 0;
        $decline_next_status = 0;
        

        // log_message('error',json_encode($item_status));

        // $available_status = array_keys($item_status);

        // $available_approval_sequences = array_column($item_status, 'status_approval_sequence');


        foreach($item_status as $id_status => $status_data){

            // Forward Jump
            if(
                $status_data['status_approval_sequence'] ==  $status_approval_sequence + 1 && 
                !in_array($status_id, $item_max_approval_status_ids) &&
                $status_data['status_approval_direction'] == 1 &&
                ($status_approval_direction == 1 ||$status_approval_direction == 0)
                ){
                $approve_next_status = $id_status;
            }

            // For Reinstating
            if(
                $status_data['status_approval_sequence'] ==  $status_approval_sequence && 
                $status_id != $item_initial_item_status_id &&
                $status_data['status_approval_direction'] == 0 &&
                $status_approval_direction == -1
            ){
                $approve_next_status = $id_status;
            }

            // For Approving Reinstatement
            if(
                $status_data['status_approval_sequence'] ==  $status_approval_sequence + 1 && 
                $status_id != $item_initial_item_status_id &&
                $status_data['status_approval_direction'] == 1 &&
                $status_approval_direction == 0
            ){
                $approve_next_status = $id_status;
            }

            // For Declining
            if(
                $status_data['status_approval_sequence'] ==  $status_approval_sequence && 
                $status_id != $item_initial_item_status_id &&
                $status_data['status_approval_direction'] == -1
                ){
                $decline_next_status = $id_status;
            }

            // Approving reinstated item that was declined from full approval status

            if(
                $status_data['status_approval_sequence'] ==  $status_approval_sequence && 
                $status_id != $item_initial_item_status_id &&
                $status_data['status_approval_direction'] == 0 &&
                $status_approval_direction == 0
            ){
                $approve_next_status = $item_max_approval_status_ids[0];
            }

        }

        $match_roles = isset($status['status_role']) ? array_intersect($status['status_role'],$role_ids) : [];
        //print_r($match_roles);
        $info_color = 'info';

        if(in_array($status_id,$item_max_approval_status_ids)){
            $info_color = "primary";
        }

        if(sizeof($match_roles) > 0){
            // Show action button with button label
            if(!in_array($status_id,$item_max_approval_status_ids)){
                $color = 'success';

				if($status_approval_direction == -1){
					$color = 'danger';
				}
				//echo $status_button_label;
				$buttons = "<button id= '".$item_id."' type='button' style='margin-right:5px' data-table='".$table_name."' data-item_id='".$item_id."' data-confirmation='".$confirmation_required."' data-current_status='".$status_id."' data-next_status='".$approve_next_status."' class='btn btn-".$color." item_action ".$disable_class."'>".$status_button_label."</button>";
			}else{
				$buttons .= "<button id= '".$item_id."' type='button' style='margin-right:5px' class='btn btn-".$info_color." disabled final_status'>".$status_name."</button>";
			}
			
			// Show decline button with decline button label
			if( $status_id != $item_initial_item_status_id && $status_approval_direction != -1){
				$buttons .= "<button id= 'decline_btn_".$item_id."' type='button' data-table='".$table_name."' data-confirmation='".$confirmation_required."' data-item_id='".$item_id."' data-current_status='".$status_id."' data-next_status='".$decline_next_status."' class='btn btn-danger item_action'>".$status_decline_button_label."</button>";
			}	
		}else{
			// Show status name/label
			
			$buttons = "<button type='button' style='margin-right:5px' class='btn btn-".$info_color." disabled final_status'>".$status_name."</button>";
		}

        return $buttons;
    }
}

if (!function_exists('approval_action_buttons')) {
    function approval_action_buttons($logged_role_id, $table, $primary_key, $show_as_button = true)
    {
?>
        <style>
            .btn {
                margin: 5px;
            }
        </style>
<?php

        $CI = &get_instance();

        $approver_status = $CI->general_model->display_approver_status_action($logged_role_id, $table, $primary_key);
        //print_r($approver_status);
        // exit;
        $current_user_roles = $CI->session->role_ids;
        $buttons = "";

        // log_message('error', json_encode(['current_user_role' => $current_user_role , 'current_actor' => $approver_status['current_actor_role_id']]));

        // log_message('error',json_encode(array_intersect($current_user_roles, $approver_status['current_actor_role_id'])));

        if ($show_as_button) {
            if (
                // in_array($CI->session->role_id, $approver_status['current_actor_role_id'])
                is_array(array_intersect($current_user_roles, $approver_status['current_actor_role_id']))
                &&
                $approver_status['show_label_as_button'] == true
            ) {
                $buttons = "<a id='approve_button' title='" . $approver_status['status_name'] . "' href='" . base_url() . $CI->controller . "/approve/" . $CI->id . "' class='btn btn-default'>" . $approver_status['button_label'] . "</a>";

                //if ($approver_status['show_decline_button'] == true) {
                    //$buttons .= "<a href='" . base_url() . $CI->controller . "/decline/" . $CI->id . "' class='btn btn-default' id='decline_button'>Decline</a>";
                //}
            }
        } else {
            $buttons = $approver_status['button_label'];
        }

        if($approver_status['show_decline_button'] == true){
            $buttons .= "<a href='" . base_url() . $CI->controller . "/decline/" . $CI->id . "' class='btn btn-default' id='decline_button'>".$approver_status['decline_button_label']."</a>";
        }



        return $buttons;
        //return json_encode($approver_status); //$current_user_role + " " + $approver_status['current_actor_role_id'];
    }
}

if (!function_exists('directory_iterator')) {
    function directory_iterator($path)
    {

        $array = array();

        if (file_exists($path)) {
            foreach ($iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $path,
                    RecursiveDirectoryIterator::SKIP_DOTS
                ),
                RecursiveIteratorIterator::SELF_FIRST
            ) as $item) {
                // Note SELF_FIRST, so array keys are in place before values are pushed.

                $subPath = $iterator->getSubPathName();
                if ($item->isDir()) {
                    // Create a new array key of the current directory name.
                    $array[$subPath] = array();
                } else {
                    // Add a new element to the array of the current file name.
                    $array[$subPath]['file_name'] = $subPath;
                    $array[$subPath]['file_size'] = human_filesize(filesize($path . DIRECTORY_SEPARATOR . $subPath));
                    $array[$subPath]['last_modified_date'] = date('Y-m-d', filemtime($path . DIRECTORY_SEPARATOR . $subPath));
                    $array[$subPath]['url'] = $path . DIRECTORY_SEPARATOR . $subPath;
                }
            }
        }
        return $array;
    }
}
//This helper was added by Onduso on 20/5/2022

//Draw_and populate table for documents uploads

if(!function_exists('draw_and_populate_table')){
    function draw_and_populate_table($uploded_docs, $controller) {

        echo "<script> 
         var uploded_docs=<?=$uploded_docs?>
         var rebuild_table_original_before_uploads = '';

         let table_id_for_uploads = $('#uploaded_documents tbody');
          
         table_id_for_uploads.html('');

         var url = '<?= base_url() . <?=$controller?> ?>/get_current_status_of_item';

         $.get(url, function(response) {

            var disable = ''
            if (response == -1) {
                disable = 'disabled';
            }

            //Build the table
            if (uploded_docs.length == 0) {

                rebuild_table_original_before_uploads = rebuild_table_original_before_uploads + '<div style='color:green;'> <h3> No Uploads to view.</h3></div>';

            } else {

                rebuild_table_original_before_uploads = '<tr><td nowrap width='100%'> <h4><u>Delete File</u></h4> </td> <td nowrap width='100%'><h4><u>File Name</u></h4> </td></tr>';

                $.each(uploded_docs, function(i, e) {
                    //Rebuiding table with new uplaoded documents

                    rebuild_table_original_before_uploads = rebuild_table_original_before_uploads + '<tr><td ><i id=' + e.attachment_id +  ' class='btn  fa fa-trash delete_attachment aria-hidden='true' '+  disable+'></i></td><td ><a target= '__blank' href=' + e.attachment_url + '>' + e.attachment_name + '</a></td></tr>'

                });
            }

            return table_id_for_uploads.html(rebuild_table_original_before_uploads);

        });
        
        </script>";

    }

}

//This helper was added by Onduso on [28/07/2022]

if(!function_exists('return_sanitized_code')){

    function return_sanitized_code($code_value_from_postarray){
        //Sanitize the Project Code 
        $unsanitized_code=trim($code_value_from_postarray);

        $unsanitized_code_without_special_characters = preg_replace("~[-:_+=/#$@!%*><)(&]~", " ", $unsanitized_code);
  
         //Split with spaces and join the words
         $code_splited_arr=explode(" ",$unsanitized_code_without_special_characters);
          
         $sanitized_code=$unsanitized_code;
  
         if(sizeof($code_splited_arr)>0){
  
          $sanitized_code=implode("",$code_splited_arr);
          
         }
  
         return strtoupper($sanitized_code);
      }
  
}


if (!function_exists('human_filesize')) {
    function human_filesize($bytes, $decimals = 2)
    {
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }
}


if (!function_exists('upload_url')) {
    function upload_url($controller, $record_id, $extra_keys = [])
    {
        //return "uploads".DS."attachments".DS.$controller.DS.$record_id.DS.implode(DS,$extra_keys);
        $s3_folder_path="uploads/attachments/" . $controller . "/" . implode("/", $extra_keys);
       
        if($record_id!=''){
            $s3_folder_path="uploads/attachments/" . $controller . "/" . $record_id . "/" . implode("/", $extra_keys);
        }
        return $s3_folder_path;
    }
}

if (!function_exists('currency_conversion')) {
    function currency_conversion($office_id)
    {

        $CI = &get_instance();

        $office_currency_id = $CI->db->get_where(
            'office',
            array('office_id' => $office_id)
        )->row()->fk_country_currency_id;

        $user_currency_id = $CI->session->user_currency_id;

        $base_currency_id = $CI->session->base_currency_id;

        $conversion_month = "2020-05-01"; // To be computed

        $CI->db->join('currency_conversion', 'currency_conversion.currency_conversion_id=currency_conversion_detail.fk_currency_conversion_id');
        $office_rate_obj = $CI->db->get_where(
            'currency_conversion_detail',
            array('fk_country_currency_id' => $office_currency_id)
        );

        $office_rate = 1;

        if ($office_rate_obj->num_rows() > 0) {
            $office_rate = $office_rate_obj->row()->currency_conversion_detail_rate;
        }


        $CI->db->join('currency_conversion', 'currency_conversion.currency_conversion_id=currency_conversion_detail.fk_currency_conversion_id');
        $user_rate_obj = $CI->db->get_where(
            'currency_conversion_detail',
            array('fk_country_currency_id' => $user_currency_id)
        );

        $user_rate = 1;

        if ($user_rate_obj->num_rows() > 0) {
            $user_rate = $user_rate_obj->row()->currency_conversion_detail_rate;
        }

        $computed_rate = 1;

        if ($user_currency_id !== $base_currency_id) {
            //if($user_rate > $office_rate){
            $computed_rate = $user_rate / $office_rate;
            //}else{
            //  $computed_rate = $office_rate/$user_rate;
            //}
        } else {
            $computed_rate = 1 / $office_rate;
        }

        return $computed_rate; // .' - '. $user_rate . ' - '.$office_rate;
    }
}
//Medical Claim Helper
if(!function_exists('disbale_ready_to_submit_btn')){
    function disbale_ready_to_submit_btn($medical_id){

        echo "<script> 
        $('.item_action').each(function(){
          var id=$(this).data('item_id');
          if(id==$medical_id){
          $(this).removeClass('disabled');                                                   
        }
       }); 
    </script>";
    }
}

if (!function_exists('show_logo')) {
    function show_logo($office_id)
    {
        $logo = "";
        $CI = &get_instance();

        if (!$CI->config->item('use_default_logo') && file_exists(APPPATH . "../uploads/office_logos/" . $office_id . ".png")) {
            $logo = '<img src="' . base_url() . 'uploads/office_logos/' . $office_id . '.png"  style="max-height:150px;" alt="Logo"/>';
        } else {
            //$logo = '<img src="' . base_url() . 'uploads/logo.png"  style="max-height:150px;" alt="Logo"/>';
        }

        return $logo;
    }
}

// Some how not working
if (!function_exists('is_valid_array_from_contract_method')) {
    function is_valid_array_from_contract_method($method_class_name, $contract_method, $check_if_result_is_array_not_empty = false)
    {
        $CI = &get_instance();
        $is_valid = false;

        if ($check_if_result_is_array_not_empty) {
            if (
                method_exists($CI->{$method_class_name}, $contract_method) &&
                is_array($CI->{$method_class_name}->{$contract_method}()) &&
                count($CI->{$method_class_name}->{$contract_method}()) > 0
            ) {
                $is_valid = true;
            }
        } else {
            if (method_exists($method_class_name, $contract_method)) {
                $is_valid = true;
            }
        }

        return $is_valid;
    }
}


if (!function_exists('check_and_load_account_system_model_exists')) {
    function check_and_load_account_system_model_exists($model_name, $package_name = 'Grants', $class_type = 'model')
    {
        $CI = &get_instance();
        $user_account_system = $CI->session->user_account_system;
        $is_existing = false;
        $class_type_dir = $class_type == 'model' ? 'models' : 'libraries';
        $path = APPPATH . 'third_party' . DS . 'Packages' . DS . $package_name . DS . $class_type_dir . DS . 'as_' . $class_type_dir . DS . $user_account_system . DS . $model_name . '.php';

        if (file_exists($path) && !$CI->load->is_loaded($model_name)) {
            $CI->load->{$class_type}('as_' . $class_type_dir . '/' . $user_account_system . '/' . $model_name);
            $is_existing = true;
        }

        return $is_existing;
    }
}

if (!function_exists('sanitize_characters')) {

    function sanitize_characters($string)
    {
        $string = str_replace(' ', '', $string); // Replaces all spaces with hyphens.
        return strtolower(preg_replace('/[^A-Za-z0-9]/', '', $string)); // Removes special chars.

    }
}


if (!function_exists('list_detail_tables')) {
    function list_detail_tables($master_table = '')
    {

        $CI = &get_instance();

        if ($master_table == '') {
            $master_table = strtolower($CI->controller);
        }

        $tables = $CI->grants_model->get_all_tables();

        foreach ($tables as $row_id => $table) {
            if (!in_array('fk_' . $master_table . '_id',  $CI->grants_model->get_all_table_fields($table))) {
                unset($tables[$row_id]);
            }
        }

        return $tables;
    }
}

if (!function_exists('tables_with_account_system_relationship')) {
    function tables_with_account_system_relationship()
    {

        $CI = &get_instance();

        $tables = $CI->grants_model->get_all_tables();

        $tables_with_account_system_relationship = [];

        foreach ($tables as $table) {
            $table_fields = $CI->grants_model->get_all_table_fields($table);

            foreach ($table_fields as $table_field) {
                if ($table_field == 'fk_account_system_id') {
                    $tables_with_account_system_relationship[] = $table;
                }
            }
        }

        return $tables_with_account_system_relationship;
    }
}

if (!function_exists('list_lookup_tables')) {
    function list_lookup_tables($table_name = '')
    {

        $CI = &get_instance();

        $table_name = $table_name == '' ? $CI->controller : $table_name;

        $table_fields = $CI->grants_model->get_all_table_fields($table_name);

        $list_lookup_tables = [];

        foreach ($table_fields as $table_field) {
            if (substr($table_field, 0, 3) == 'fk_') {
                $list_lookup_tables[] = substr($table_field, 3, -3);
            }
        }

        // This is temporal fix for performance but there is a need to understand why the code slows loading Ex. Adding Expense account
        //unset($list_lookup_tables[array_search('approval',$list_lookup_tables)]);
        //unset($list_lookup_tables[array_search('status',$list_lookup_tables)]);

        return $list_lookup_tables;
    }
}

if (!function_exists('financial_year_quarter_months')) {
	function financial_year_quarter_months($month_number, $office_id = 0)
	{

        $CI = &get_instance();

		// $CI->read_db->select(array('month_number'));
		// $CI->read_db->order_by('month_order ASC');
		// $months = $CI->read_db->get('month')->result_array();

		// $month_mumbers = array_column($months, 'month_number');

		$month_numbers = array_column(month_order($office_id),'month_number');

		$CI->read_db->where(array('fk_account_system_id' => $CI->session->user_account_system_id));
		$count_of_reviews_in_year = $CI->read_db->get('budget_review_count')->row()->budget_review_count_number;
		$count_of_months_in_period = count($month_numbers) / $count_of_reviews_in_year;
		/**
		 * 1 - 12 
		 * 2 - 6  
		 * 3 - 4
		 * 4 - 3
		 */
		$range_of_reviews = range(1, $count_of_reviews_in_year); // [1,2,3,4] - Assume the $count_of_reviews_in_year = 4
		$month_arrays_in_period = array_chunk($month_numbers, $count_of_months_in_period); //[[7,8,9],[10,11,12],[1,2,3],[4,5,6]]

        $months_in_quarters = array_combine($range_of_reviews, $month_arrays_in_period);

        $current_quarter_months = [];

        foreach ($months_in_quarters as $quarter_number => $months_in_quarter) {
            if (in_array($month_number, $months_in_quarter)) {
                $current_quarter_months['quarter_number'] = $quarter_number;
                $current_quarter_months['months_in_quarter'] = $months_in_quarter;
            }
        }

        return $current_quarter_months;
    }
}

//Formerly as budget_review_buffer_month
if (!function_exists('month_after_adding_size_of_budget_review_period')) {
    function month_after_adding_size_of_budget_review_period($current_month)
    {

        $CI = &get_instance();

        $current_month_with_buffer = $current_month + $CI->config->item('size_in_months_of_a_budget_review_period');

        if ($current_month_with_buffer > 12) {

            if ($current_month_with_buffer > 24) {
                $current_month_with_buffer = $current_month_with_buffer % 12;
            } else {
                $current_month_with_buffer = $current_month_with_buffer - 12;
            }
        }

        return $current_month_with_buffer;
    }
}


if (!function_exists('addOrdinalNumberSuffix')) {
    function addOrdinalNumberSuffix($num)
    {
        if (!in_array(($num % 100), array(11, 12, 13))) {
            switch ($num % 10) {
                    // Handle 1st, 2nd, 3rd
                case 1:
                    return $num . 'st';
                case 2:
                    return $num . 'nd';
                case 3:
                    return $num . 'rd';
            }
        }
        return $num . 'th';
    }
}

/**
 * month_order
 * 
 * @author Nicodemus Karisa
 * @date 18th APril 2023
 * @reviewer None
 * @reviewed_date None
 * 
 * @param int office_id - Office Id 
 * 
 * @return array list of months in a year in order of an FCP
 * 
 * @todo:
 * Ready for Peer Review
 */

if(!function_exists('month_order')){
	function month_order($office_id, $budget_id = 0): array {

		$CI = &get_instance();

		$months = [];

		// log_message('error', json_encode([$office_id, $budget_id]));
		
		if($office_id == 0){
			$CI->read_db->select(array('month_id','month_order','month_number','month_name'));
			$CI->read_db->order_by('month_order', 'ASC');
			$months_array = $CI->read_db->get('month')->result_array();
			
			// $months = array_column($months_array, 'month_number');
			foreach($months_array as $month){
				$months[$month['month_order']] = $month;
			}
		}else{
			$office_fy_start_month = 7; // This is July - Default system year start month

			$CI->read_db->select(array('custom_financial_year_start_month'));
			$CI->read_db->where(array('custom_financial_year.fk_office_id' => $office_id, 'custom_financial_year_is_default' => 1));

			if($budget_id > 0){
				$CI->read_db->join('budget','budget.fk_custom_financial_year_id=custom_financial_year.custom_financial_year_id');
				$CI->read_db->where(array('budget_id' => $budget_id));
			}
			
			$office_fy_start_month_obj = $CI->read_db->get('custom_financial_year');
	
			if($office_fy_start_month_obj->num_rows() > 0){
				$office_fy_start_month = $office_fy_start_month_obj->row()->custom_financial_year_start_month;
			}
	
			$CI->read_db->select(array('month_id','month_order','month_number','month_name'));
			$months_array = $CI->read_db->get('month')->result_array();
	
			// log_message('error', json_encode($office_fy_start_month));

			$init_month_order = 1;
	
			$extended_month_order = (12 - $office_fy_start_month) + 2;
	
			foreach($months_array as $month){
				if($month['month_number'] < $office_fy_start_month){
					$months[$extended_month_order] = $month;
					$extended_month_order++;
				}else{
					$months[$init_month_order] = $month;
					$init_month_order++;
				}
			}
	
			ksort($months);
		}

		// log_message('error', json_encode($months));

		return $months;
	}
}

// if (!function_exists('fy_start_date')) {
//     function fy_start_date($date_string)
//     {

//         //$date_string = '2021-08-01';

//         $CI = &get_instance();

//         //$fy = get_fy($date_string,true);
//         //return $fy;

//         $date_month_number = date('n', strtotime($date_string));
//         $fy = date('Y', strtotime($date_string));
//         $fy_start_date = '';

//         $fy_year_reference = $CI->config->item('fy_year_reference');

//         $CI->read_db->select(array('month_number'));
//         $CI->read_db->order_by('month_order', 'ASC');
//         $months_array = $CI->read_db->get('month')->result_array();

//         $months = array_column($months_array, 'month_number');

//         $first_month = current($months);
//         $last_month = end($months);

//         $formatted_month = strlen($first_month) == 1 ? '0' . $first_month : $first_month;

//         $half_year_months = array_chunk($months, 6);

//         if ($first_month != 1 && $last_month != 12) {

//             if (in_array($date_month_number, $half_year_months[1]) && $fy_year_reference == 'next') {
//                 $fy--;
//             }
//         }

//         $fy_start_date = $fy . '-' . $formatted_month . '-01';

//         return $fy_start_date;
//     }
// }

if (!function_exists('fy_start_date')) {
    function fy_start_date($date_string, $custom_financial_year)
    {
        
        $CI = &get_instance();

        // $date_month_number = date('n', strtotime($date_string));
        // $fy_year_reference = $CI->config->item('fy_year_reference');

        $CI->load->model('custom_financial_year_model');

        $startMonth = isset($custom_financial_year['custom_financial_year_start_month']) ? $custom_financial_year['custom_financial_year_start_month'] : 7;
        $month = strlen($startMonth) == 1 ? '0'.$startMonth : $startMonth;

        $months_order = [];

        if(isset($custom_financial_year['custom_financial_year_start_month'])){
            $months_order =  $CI->custom_financial_year_model->get_months_order_for_custom_year($custom_financial_year['custom_financial_year_id']);
        }else{
            $CI->read_db->select(array('month_number'));
            $CI->read_db->order_by('month_order ASC');
            $months_array = $CI->read_db->get('month')->result_array();
            $months_order = array_column($months_array, 'month_number');  
        }

        $first_month = current($months_order);
        $last_month = end($months_order);

        $half_year_months = array_chunk($months_order, 6);

        $fy = calculateFinancialYear($date_string, $startMonth, false);

        // log_message('error', json_encode(['fy_year_reference' => $fy_year_reference, 'fy' => $fy, 'half_year_months' => $half_year_months, 'first_month' => $first_month, 'last_month' => $last_month, 'date_month_number' => $date_month_number,'date_string' => $date_string, 'custom_financial_year' => $custom_financial_year, 'months_order' => $months_order]));

        if ($first_month != 1 && $last_month != 12) {
            if (in_array($startMonth, $half_year_months[0])) {
                $fy--;
            }
        }

        return $fy.'-'.$month.'-01';
    }}

if(!function_exists('calculateFinancialYear')){
    function calculateFinancialYear($inputDate, $startMonth = 7, $two_digit_year = true) {

        $fyString = '';

        // Parse the input date
        $date = new DateTime($inputDate);
        $year = (int)$date->format('Y');
        $month = (int)$date->format('n');

        // Maximum number of months in a year
        $max_count = 12;

        // Initialize variables
        $range_of_months_in_year = [];
        $current_month = $startMonth;

        // Get the list of months with months after 12 get to 13, 14, 15 .....
        for($i = 0; $i < $max_count; $i++){
            $range_of_months_in_year[$i] = $current_month;
            $current_month += 1;
        }
        
        // Sanitize the months numbered beyond 12 to reset them to 1, 2,3 .....
        for($x = 0; $x < count($range_of_months_in_year); $x++){
            if($range_of_months_in_year[$x] > $max_count){
                $range_of_months_in_year[$x] = $range_of_months_in_year[$x] - $max_count;
            }
        }

        // Get the month positions for the month in input date and max month
        $input_month_position = array_search($month, $range_of_months_in_year);
        $max_month_position = array_search($max_count, $range_of_months_in_year);

        // Check if fiscal year is in next year
        $is_fiscal_year_in_next_year = $input_month_position > $max_month_position;
        // 1,2,3,4,5,6,7,8,9,10,11,12

        if($two_digit_year){
            // The fiscal year ends in the next year 
            $fy = $year % 100 + 1;

            if($is_fiscal_year_in_next_year || $startMonth == 1){
                // The fiscal year ends in the current year
                $fy = $year % 100;
            }

            $fyString = str_pad($fy, 2, '0', STR_PAD_LEFT);
        }else{
            // The fiscal year ends in the next year 
            $fyString = $year + 1;

            if($is_fiscal_year_in_next_year || $startMonth == 1){
                // The fiscal year ends in the current year
                $fyString = $year;
            }
        }
        

        return  $fyString;
    }}

// if(!function_exists('calculateFinancialYear')){
//     function calculateFinancialYear($inputDate, $startMonth = 7) {

//         // log_message('error', json_encode(['inputDate' => $inputDate, 'startMonth' =>$startMonth]));

//         // Parse the input date
//         $date = new DateTime($inputDate);
//         $year = (int)$date->format('Y');
//         $month = (int)$date->format('n');

//         // log_message('error', json_encode(['year' => $year, 'month' => $month]));
        
//         // Determine the fiscal year
//         if ($month >= $startMonth) {
//             // The fiscal year ends in the next year
//             $fy = $year % 100 + 1;
//         } else {
//             // The fiscal year ends in the current year
//             $fy = $year % 100;
//         }
        
//         // Format the fiscal year as a two-digit string
//         $fyString = str_pad($fy, 2, '0', STR_PAD_LEFT);
        
//         // Display the fiscal year
//         // echo "The date $inputDate belongs to FY $fyString.\n";
//         return $fyString;
//     } 
// }

/**
 * get_fy
 * 
 * @author Nicodemus Karisa Mwambire
 * @date 18th April 2023
 * @reviewer None
 * @reviewed_date None
 * 
 * @param string mandatory $date_string - Date string
 * @param int optional  $office_id - Office Id
 * @param bool optional override_fy_year_digits_config - If true give 4 digit year otherwise give 2 digit year
 * 
 * @return int The FY of a given date
 * 
 * @todo:
 * Ready for Peer Review
 */

 if (!function_exists('get_fy')) {
	function get_fy($date_string, $office_id = 0 , $override_fy_year_digits_config = false) : int
	{

        $CI = &get_instance();
        $fy_year_digits = $CI->config->item('fy_year_digits');

		$date_month_number = date('n', strtotime($date_string));
		$fy = ($fy_year_digits == 4 && !$override_fy_year_digits_config) ? date('Y', strtotime($date_string)) : date('y', strtotime($date_string));

		$months = array_column(month_order($office_id),'month_number');

		// log_message('error', json_encode($months));

        $first_month = current($months);
        $last_month = end($months);

        $fy_year_reference = $CI->config->item('fy_year_reference');

        $half_year_months = array_chunk($months, 6);

        if ($first_month != 1 && $last_month != 12) {

            if (in_array($date_month_number, $half_year_months[0]) && $fy_year_reference == 'next') {
                $fy++;
            }
        }

		// log_message('error', json_encode($fy));

        return $fy;
    }
}

if (!function_exists('year_month_order')) {
    function year_month_order($custom_financial_year)
    {
        $CI = &get_instance();
        $CI->load->model('custom_financial_year_model');
        $months_order = [];

        // log_message('error', json_encode($custom_financial_year));

        if(isset($custom_financial_year['custom_financial_year_start_month'])){
            $months_order =  $CI->custom_financial_year_model->get_months_order_for_custom_year($custom_financial_year['custom_financial_year_id']);
        }else{
            $CI->read_db->select(array('month_number'));
            $CI->read_db->order_by('month_order ASC');
            $months_array = $CI->read_db->get('month')->result_array();
            $months_order = array_column($months_array, 'month_number');  
        }

        return $months_order;
    }
}


if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        // Uncomment one of the following alternatives
        $bytes /= pow(1024, $pow);
        // $bytes /= (1 << (10 * $pow)); 

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
//The helper gets the DB errors

if (!function_exists('alert_error_message')) {

    function alert_error_message($error_messages)
    {

        $messages = array_column($error_messages, 'message');

        array_walk($messages, 'create_error_message');
    }
}

if (!function_exists('create_error_message')) {

    function create_error_message($message, $key)
    {

        $explode_msq = explode(':', $message)[0];

        if ($explode_msq != '') {
            echo '=>' . $explode_msq . "\n";
        }
    }
}



if (!function_exists('is_office_in_context_offices')) {

    function is_office_in_context_offices($office_id)
    {

        $CI = &get_instance();
        return in_array($office_id, array_column($CI->session->context_offices, 'office_id'));
    }
}


if(!function_exists('remove_directory')){
    function remove_directory($dir){
        $it = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($it,
                    RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file) {
            if ($file->isDir()){
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }
}

if(!function_exists('clear_cache_files')){
    function clear_cache_files($table){

        $cache_dirs_actions = ['view','list','edit','single_form_add','multi_form_add','show_list'];

        foreach($cache_dirs_actions as $action){
          $dir = APPPATH.'cache'.DS.$table.'+'.$action;
          
          if(!file_exists($dir)) continue;

          remove_directory($dir);
          
        }
    }
}


if(!function_exists('transfer_types')){
    function transfer_types(){
        return [1 => 'income_transfer', 2 => 'expense_transfer'];
    }
}


if(!function_exists('view_page_action_buttons')){
    function view_page_action_buttons(){
            $CI =& get_instance();

            $table = $CI->controller;
            $id = $CI->id;

            $CI->read_db->where(array($table.'_id' => hash_id($id,'decode')));
            $status_id = $CI->read_db->get($table)->row()->fk_status_id;

            $buttons = '';

            $action_labels = $CI->grants->action_labels($table,hash_id($id,'decode'));

            if( $CI->status_model->is_status_actionable_by_user($status_id, $table) ){
                        
                if($CI->user_model->check_role_has_permissions(ucfirst($table),'update')){
                    $buttons .= Widget_base::load('button',get_phrase('edit'),$table.'/edit/'.$id,'','hidden-print');
                }
        
                if($CI->user_model->check_role_has_permissions(ucfirst($table),'delete')){
                    $buttons .= Widget_base::load('button',get_phrase('delete'),$table.'/delete/'.$id,'','hidden-print');
                }
        
            }
                    
                    
            if(isset($action_labels['show_label_as_button']) && $action_labels['show_label_as_button']){         
                $buttons .= Widget_base::load('button',$action_labels['button_label'],$table.'/approve/'.$id,'','hidden-print');
            }
                    
            if(isset($action_labels['show_decline_button']) && $action_labels['show_decline_button']){
                $buttons .= Widget_base::load('button',get_phrase('decline'),$table.'/decline/'.$id,'','hidden-print');    
            }
            $onclick = "PrintElem('#print_view')";
            $buttons .= Widget_base::load('button',get_phrase('print'),'#','btn_print','hidden-print',$onclick);


        
        return $buttons;
    }
}

if(!function_exists('transfer_types')){
    function transfer_types(){
        return [1 => 'income_transfer', 2 => 'expense_transfer'];
    }
}

if(!function_exists('rename_files_in_directory')){
    function rename_files_in_directory($path, $search_file, $rename_to, $file_extension = 'php'){
    
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $fileinfo)
        {
    
                if (!$fileinfo->isDir()) {
    
                    if($fileinfo->getFilename() == $search_file . '.php'){
                        $path = dirname($fileinfo);
    
                        rename($path . DIRECTORY_SEPARATOR . $search_file . '.' . $file_extension , $path . DIRECTORY_SEPARATOR . $rename_to . '.' . $file_extension);
                    }
                    
                }
                
        }
    }
}

if(!function_exists('create_specs_array')){
    function create_specs_array(){
        
        $schema = file_get_contents(APPPATH . 'version' . DIRECTORY_SEPARATOR . 'spec.json');
        $specs_array = json_decode($schema, true);

        $path = APPPATH . 'version' . DIRECTORY_SEPARATOR.'extend';

        if(!file_exists($path)){
            mkdir($path);
        }

        $files = scandir($path);

        foreach ($files as $file) {
            // Skip over directories and hidden files
            if (is_dir($path . DIRECTORY_SEPARATOR . $file) || substr($file, 0, 1) === '.') {
                continue;
            }
        
            $contents = file_get_contents($path.DIRECTORY_SEPARATOR.$file);
            $ext_specs_array = json_decode($contents, true);

            if(sizeof((array)$ext_specs_array) == 0){
                continue;
            }

            foreach($ext_specs_array as $app_name => $tables){
                if(array_key_exists('tables', $tables)){
                    foreach($tables['tables'] as $table_name => $table_props){
                        if(is_string($table_name) && (is_array($table_props) || is_null($table_props))){
                            if(is_array($table_props) && (!array_key_exists('field_data',$table_props) && !array_key_exists('lookup_tables',$table_props) || empty($table_props['field_data']))){
                                continue;
                            }

                            $specs_array[$app_name]['tables'][$table_name] = $table_props;
                        }
                    }
                }
            }
            
        }

        // log_message('error', json_encode($specs_array['core']['tables']['user_switch']));

        return $specs_array;
      }
}

if(!function_exists('mail_logger')){
    function mail_logger(){

        $CI =& get_instance();

        $CI->load->model('email_template_model');

        $tags['{user}'] =  $user->user_firstname.' '. $user->user_lastname;
        $tags['{email}'] = $email;
        $tags['{password}'] = $new_password;

        $email_subject = get_phrase('password_reset_notification');

        $email_body = file_get_contents(APPPATH.'resources/email_templates/en/password_reset.txt'); // Template language should be from user session
             
        $mail_recipients['send_to'] = [$email]; // must be an array

        $CI->email_template_model->log_email($tags, $email_subject, $email_body, $mail_recipients);
    }
}


if(!function_exists('alias_columns')){
    function alias_columns($columns, $special_separator = 'as'){
    
        $cols = [];
    
        for($i = 0; $i < sizeof($columns); $i++){
          $col_explode = explode($special_separator, $columns[$i]);
        //   log_message('error', json_encode($col_explode));
          $cols[$i]['query_columns'] = trim($col_explode[0]);
          $cols[$i]['list_columns'] = isset($col_explode[1]) ? trim($col_explode[1]) : trim($col_explode[0]);
        }

        // log_message('error', json_encode($cols));
        return $cols;
      }
}


if(!function_exists('sanitize_column_aliases')){
    function sanitize_column_aliases($columns, $special_separator){
    
        $cols = [];
    
        for($i = 0; $i < sizeof($columns); $i++){
            $cols[$i] = str_replace($special_separator, 'as', $columns[$i]);
        }
        log_message('error', json_encode($cols));
        return $cols;
      }
}

// if(!function_exists('alias_columns')){
// 	function alias_columns($columns, $list_columns_name = false){
// 		for($i = 0; $i < count($columns); $i++){
// 			$column_array = explode('as',$columns[$i]);

// 			if($list_columns_name){
// 				$columns[$i] = isset($column_array[0]) ? $column_array[0] : $columns[$i];
// 			}else{
// 				$columns[$i] = isset($column_array[1]) ? $column_array[1] : $columns[$i];
// 			}
// 		  }
		
// 		  return $columns;
// 	}
// }

if(!function_exists('keyed_alias_columns')){
    function keyed_alias_columns($columns, $special_separator = 'as') {
        $alias_columns = alias_columns($columns, $special_separator);

        $list_columns = array_column($alias_columns, 'list_columns');
        $query_columns = array_column($alias_columns, 'query_columns');

        $rst = array_combine($list_columns, $query_columns);

        return $rst;
    }
}

if(!function_exists('get_query_column_for_list_column')){
    function get_query_column_for_list_column($columns, $list_column, $special_separator = 'as') {
        $keyed_alias_columns = keyed_alias_columns($columns, $special_separator);   
        
        // log_message('error', json_encode($keyed_alias_columns));

        $query_column = $keyed_alias_columns[$list_column];

        return $query_column;
    }
}

if(!function_exists('validate_date')){
	function validate_date($date, $format = 'Y-m-d')
	{
    	$d = DateTime::createFromFormat($format, $date);
    	// The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
    	return $d && $d->format($format) === $date;
	}
}


if(!function_exists('format_date')){
    function format_date($mysql_date_format, $format = 'uk'){
        // format can be uk, us
        // Replace the $format with a user session for locale
        $date = new DateTime($mysql_date_format);
        $formatted_date = '';

        if($format == 'us'){
            $formatted_date = $date->format('m/d/Y');
        }else{
            $formatted_date = $date->format('d/m/Y');
        }

        return $formatted_date;
    }
}

if(!function_exists('approval_steps')){
    function approval_steps($account_system_id, $approveable_item_name){
        $CI =& get_instance();

        $CI->load->model('status_model');
        
        $approval_steps = $CI->status_model->get_approval_steps_for_account_system_approve_item($account_system_id, $approveable_item_name);

        return $approval_steps;
    }
}

// if(!function_exists('approval_levels')){
//     function approval_levels($account_system_id, $approveable_item_name){
//         $CI =& get_instance();

//         $CI->load->model('status_model');
        
//         $approval_steps = $CI->status_model->get_approval_levels_for_account_system_approve_item($account_system_id, $approveable_item_name);

//         return $approval_steps;
//     }
// }

if(!function_exists('mark_note_as_read')){
    function mark_note_as_read($reader_user_id, $message_detail_id){
        $CI =& get_instance();

        $CI->load->model('message_model');

        $CI->message_model->mark_note_as_read($reader_user_id, $message_detail_id);
    }
}

if(!function_exists('create_select_from_ids_and_names')){
    function create_select_from_ids_and_names($ids_and_names_array, $custom_class, $label = ''){
        $select = '<select class = "form-control '.$custom_class.'">';
        $select .= '<option value = "">'.$label.'</option>';
        foreach($ids_and_names_array as $key => $value){
            $select .= '<option value = "'.$key.'">'.$value.'</option>';
        }
        $select .= '<select>';

        return $select;
    }
}

if ( ! function_exists('translate_text'))
{
	function translate_text($handle, $phrase_variables_values = []) {

		// Phrase tags variables replacement
		
		if(!empty($phrase_variables_values)){
			foreach ($phrase_variables_values as $placeholder => $replacement) {
                // log_message('error', json_encode(['placeholder' => $placeholder, 'replacement' => $replacement]));
				$placeholder = '{{' . $placeholder . '}}';
				$handle = str_replace($placeholder, $replacement, $handle);
			}
		}

		return $handle;


	}
}

if(!function_exists('isDateRangeWithinAnotherRange')){
    function isDateRangeWithinAnotherRange($parent_range, $child_range) {
    
        // Convert date strings to Unix timestamps
        $parentStartTimestamp = strtotime($parent_range['start']);
        $parentEndTimestamp = strtotime($parent_range['end']);
        $childStartTimestamp = strtotime($child_range['start']);
        $childEndTimestamp = strtotime($child_range['end']);
    
        // Check if the first range is within the second range
        return ($childStartTimestamp >= $parentStartTimestamp) && ($childEndTimestamp <= $parentEndTimestamp);
    }
}


if(!function_exists('removeCommaSeparator')){
    function removeCommaSeparator($string){
        return str_replace(',', '', $string);
    }
}


if (!function_exists('str_contains')) {
    function str_contains (string $haystack, string $needle)
    {
        return empty($needle) || strpos($haystack, $needle) !== false;
    }
}

if(!function_exists('transacting_offices')){
    function transacting_offices($hierarchy_offices){
      $transacting_offices = array_filter($hierarchy_offices, function ($office) {
          if(!$office['office_is_readonly']){
              return $office;  
          }
      });


      return $transacting_offices;
    }
}