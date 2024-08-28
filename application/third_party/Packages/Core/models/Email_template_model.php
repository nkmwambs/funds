<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Email_template_model extends MY_Model{

    public $table = 'email_template'; 
    public $dependant_table = '';
    public $name_field = 'email_template_name';
    public $create_date_field = "email_template_created_date";
    public $created_by_field = "email_template_created_by";
    public $last_modified_date_field = "email_template_last_modified_date";
    public $last_modified_by_field = "email_template_last_modified_by";
    public $deleted_at_field = "email_template_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array('approve_item','permission_label','account_system');
    }

    function list_table_visible_columns(){
        return [
            'email_template_track_number',
            'email_template_subject',
            'permission_label_name',
            'email_template_created_date'
        ];
    }

    function single_form_add_visible_columns(){
        return [
                'approve_item_name',
                'permission_label_name',
                'email_template_subject',
                'email_template_body',
                'account_system_name'
            ];
    }

    function edit_visible_columns(){
        return [
                'approve_item_name',
                'permission_label_name',
                'email_template_subject',
                'email_template_body',
                'account_system_name'
            ];
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}

    function lookup_values(){
        $lookup_values = parent::lookup_values();

        $this->read_db->select(array('approve_item_id','approve_item_name'));
        $this->read_db->where(array('approve_item_is_active' => 1));
        $lookup_values['approve_item'] = $this->read_db->get('approve_item')->result_array();

        // $this->read_db->select(array('permission_label_id','permission_label_name'));
        // $this->read_db->where_in('permission_label_name', ['create','update','delete']);
        // $lookup_values['permission_label'] = $this->read_db->get('permission_label')->result_array();


        return $lookup_values;
    }

    function get_email_template_placeholder_fields($approve_item_id){

        // Get approve item name given an id
        $this->read_db->select(array('approve_item_name'));
        $this->read_db->where(['approve_item_id' => $approve_item_id]);
        $table = $this->read_db->get('approve_item')->row()->approve_item_name;
    
        // Getting the lookup tables as listed in the model for the feature which the email template is being created for
        // The lookup tables should also be derived from the spec json files
        $this->load->model($table.'_model');
        $lookup_fields = $this->{$table.'_model'}->lookup_tables();
    
        // Formatting the look up tables to rebuild the foreign key names as appearing in the current feature table
        $formated_lookup_fields = [];
    
        foreach($lookup_fields as $lookup_field){
          $formated_lookup_fields[] = 'fk_'.$lookup_field.'_id';
        }
        
        // Unset the id, track number and approval id fields. These fields are not required as placeholders in a mail template
        $fields = $this->grants->list_fields($table);
        unset($fields[array_search($table.'_id',$fields)]);
        unset($fields[array_search($table.'_track_number',$fields)]);
        unset($fields[array_search('fk_approval_id',$fields)]);
    
        // Remove foreign keys that are not represented in the lookup tables array
        $formatted_fields = [];
    
        foreach($fields as $field){
          
          if(substr($field,0,2) == 'fk' && in_array($field,$formated_lookup_fields)){
        
            $arr = explode('_',$field);
            array_shift($arr); 
            array_pop($arr); 
            array_push($arr,'name');
      
            $field = implode('_',$arr);
            $formatted_fields[] = $field;
          }elseif(substr($field,0,2) == 'fk' && !in_array($field,$formated_lookup_fields)){
            unset($fields[array_search($field,$fields)]);
          }else{
            $formatted_fields[] = $field;
          }
    
          
        }
    
        return $formatted_fields;
      }
    

    function get_record_detail_for_email_template($approve_item_name, $approve_item_id, $item_id){

        // log_message('error',json_encode([$approve_item_name, $approve_item_id, $item_id]));

        $this->load->model($approve_item_name.'_model');
        $lookup_tables = $this->{$approve_item_name.'_model'}->lookup_tables();

        // log_message('error',json_encode($lookup_tables));

        $record = [];

        $columns = $this->get_email_template_placeholder_fields($approve_item_id);

        // log_message('error',json_encode($columns));

        $this->read_db->select($columns);

        foreach($lookup_tables as $lookup_table){
            $this->read_db->join($lookup_table,$lookup_table.'.'.$lookup_table.'_id='.$approve_item_name.'.fk_'.$lookup_table.'_id');
        }

        if(!in_array('status',$lookup_tables)){
            $this->read_db->join('status','status.status_id='.$approve_item_name.'.fk_status_id');
        }
        
        $this->read_db->where(array($approve_item_name.'_id' => $item_id));
        $record_obj = $this->read_db->get($approve_item_name);

        if($record_obj->num_rows() > 0){
            $record = $record_obj->row();
        }

        // log_message('error',$this->read_db->last_query());
        
        return $record;
    }

    function get_hierarchy_user_ids_for_office($office_id){

        $user_ids = [];

        // Center Users

        $this->read_db->select(array('user_id'));
        $this->read_db->join('context_center_user','context_center_user.fk_user_id=user.user_id');
        $this->read_db->join('context_center','context_center.context_center_id=context_center_user.fk_context_center_id');
        $this->read_db->where(array('context_center.fk_office_id' => $office_id,'user_is_active' => 1));
        $users_obj = $this->read_db->get('user');

        if($users_obj->num_rows() > 0){
            $users_list = $users_obj->result_array();
            $user_ids = array_column($users_list,'user_id');
        }

        // Cluster Users 

        $this->read_db->select(array('user_id'));
        $this->read_db->join('context_cluster_user','context_cluster_user.fk_user_id=user.user_id');
        $this->read_db->join('context_cluster','context_cluster.context_cluster_id=context_cluster_user.fk_context_cluster_id');
        $this->read_db->join('context_center','context_center.fk_context_cluster_id=context_cluster.context_cluster_id');
        $this->read_db->where(array('context_center.fk_office_id' => $office_id,'user_is_active' => 1));
        $users_obj = $this->read_db->get('user');

        if($users_obj->num_rows() > 0){
            $users_list = $users_obj->result_array();
            $user_ids = array_merge($user_ids, array_column($users_list,'user_id'));
        }


        // Cohort Users

        $this->read_db->select(array('user_id'));
        $this->read_db->join('context_cohort_user','context_cohort_user.fk_user_id=user.user_id');
        $this->read_db->join('context_cohort','context_cohort.context_cohort_id=context_cohort_user.fk_context_cohort_id');
        $this->read_db->join('context_cluster','context_cluster.fk_context_cohort_id=context_cohort.context_cohort_id');
        $this->read_db->join('context_center','context_center.fk_context_cluster_id=context_cluster.context_cluster_id');
        $this->read_db->where(array('context_center.fk_office_id' => $office_id,'user_is_active' => 1));
        $users_obj = $this->read_db->get('user');

        if($users_obj->num_rows() > 0){
            $users_list = $users_obj->result_array();
            $user_ids = array_merge($user_ids, array_column($users_list,'user_id'));
        }

        // Country Users

        $this->read_db->select(array('user_id'));
        $this->read_db->join('context_country_user','context_country_user.fk_user_id=user.user_id');
        $this->read_db->join('context_country','context_country.context_country_id=context_country_user.fk_context_country_id');
        $this->read_db->join('context_cohort','context_cohort.fk_context_country_id=context_country.context_country_id');
        $this->read_db->join('context_cluster','context_cluster.fk_context_cohort_id=context_cohort.context_cohort_id');
        $this->read_db->join('context_center','context_center.fk_context_cluster_id=context_cluster.context_cluster_id');
        $this->read_db->where(array('context_center.fk_office_id' => $office_id,'user_is_active' => 1));
        $users_obj = $this->read_db->get('user');

        if($users_obj->num_rows() > 0){
            $users_list = $users_obj->result_array();
            $user_ids = array_merge($user_ids, array_column($users_list,'user_id'));
        }
        
        return $user_ids;
    }

    function get_user_emails_by_roles($role_ids, $office_id, $approve_item_id){

        $user_ids = $this->get_hierarchy_user_ids_for_office($office_id);

        $user_emails = [];
        
        if(empty($role_ids)){
            return $user_emails;
        }

        $this->read_db->select(array('user_email'));
        $this->read_db->join('user','user.user_id=role_user.fk_user_id');
        $this->read_db->join('role','role.role_id=role_user.fk_role_id');
        $this->read_db->join('status_role','status_role.fk_role_id=role.role_id');
        $this->read_db->join('status','status.status_id=status_role.status_role_status_id');
        $this->read_db->join('approval_flow','approval_flow.approval_flow_id=status.fk_approval_flow_id');
        $this->read_db->where_in('role_user.fk_role_id',$role_ids);
        $this->read_db->where_in('user_id', $user_ids);
        $this->read_db->where(array('status_role_is_active' => 1, 'role_is_active' => 1,'user_is_active' => 1));
        $this->read_db->where(array('approval_flow.fk_approve_item_id' => $approve_item_id));
        $role_users_obj = $this->read_db->get('role_user');

        if($role_users_obj->num_rows() > 0){
            $users = $role_users_obj->result_array();
            $user_emails = array_merge($user_emails, array_column($users,'user_email'));
        }

        $this->read_db->select(array('user_email'));
        $this->read_db->where_in('user.fk_role_id',$role_ids);
        $this->read_db->where_in('user_id', $user_ids);
        $this->read_db->where(array('status_role_is_active' => 1, 'role_is_active' => 1,'user_is_active' => 1));
        $this->read_db->where(array('approval_flow.fk_approve_item_id' => $approve_item_id));
        $this->read_db->join('role','role.role_id=user.fk_role_id');
        $this->read_db->join('status_role','status_role.fk_role_id=role.role_id');
        $this->read_db->join('status','status.status_id=status_role.status_role_status_id');
        $this->read_db->join('approval_flow','approval_flow.approval_flow_id=status.fk_approval_flow_id');
        $users_obj = $this->read_db->get('user');

        if($users_obj->num_rows() > 0){
            $users = $users_obj->result_array();
            $user_emails = array_merge($user_emails, array_column($users,'user_email'));
        }

        return  array_unique($user_emails);
    }

    function get_user_email_by_id($user_id){

        $this->read_db->select(array('user_email'));
        $this->read_db->where(array('user_id' => $user_id));
        $user_email = $this->read_db->get('user')->row()->user_email;

        return $user_email;
    }

    function get_office_id($approve_item_name, $item_id){


        $office_id = 0;

        $this->load->model($approve_item_name.'_model');
        $lookup_tables = $this->{$approve_item_name.'_model'}->lookup_tables();

        foreach($lookup_tables as $lookup_table){
            $this->read_db->join($lookup_table,$lookup_table.'.'.$lookup_table.'_id='.$approve_item_name.'.fk_'.$lookup_table.'_id');
        }

        $this->read_db->where(array($approve_item_name.'_id' => $item_id));
        $record_obj = $this->read_db->get($approve_item_name); 

        if($record_obj->num_rows() > 0){

            $record = $record_obj->row_array();

            if(in_array('office_id',array_keys($record))){

                $office_id = $record['office_id'];

            }elseif(in_array('fk_office_id',array_keys($record))){
                
                $office_id = $record['fk_office_id'];
            
            }
            
        }

        return $office_id;
    }

    function template_email_recipients($approve_item_name, $permission_label_name, $item_id, $approve_item_id){

        $recipients = [];
        $send_to = [];
        $copy_to = [];

        $status = $this->grants->action_labels($approve_item_name, $item_id);

        // log_message('error', json_encode($status));

        $office_id = $this->get_office_id($approve_item_name, $item_id);
        
        $this->read_db->where(array($approve_item_name.'_id' => $item_id));
        $item_obj = $this->read_db->get($approve_item_name);

        $user_ids = [];

        if($item_obj->num_rows() > 0){

            $item = $item_obj->row();
            
            $current_actor_emails = $this->get_user_emails_by_roles($status['current_actor_role_id'], $office_id, $approve_item_id);
            $next_actor_emails = $this->get_user_emails_by_roles($status['next_actor_role_id'], $office_id, $approve_item_id);
    
            $modifier_email = $this->get_user_email_by_id($item->{$approve_item_name.'_last_modified_by'});
            $creator_email = $this->get_user_email_by_id($item->{$approve_item_name.'_created_by'});


            if($permission_label_name == 'approval'){

                $send_to = $current_actor_emails; 

                if(!in_array($creator_email, $send_to) &&  !in_array($creator_email, $copy_to)){ // Prevent copying a reciever & duplicting copy emails
                    array_push($copy_to, $creator_email);
                }

            }elseif($permission_label_name == 'update' || $permission_label_name == 'delete'){ // This should be a soft delete

                $send_to = array_merge($send_to, $current_actor_emails);

                array_push($send_to,$modifier_email);

                if(!in_array($creator_email, $send_to) && !in_array($creator_email, $copy_to)){ // Prevent copying a reciever & duplicting copy emails
                    array_push($copy_to, $creator_email);
                }

            }else{

                $send_to = array_merge($send_to, $current_actor_emails);

                array_push($send_to,$creator_email);

                if(in_array($creator_email, $next_actor_emails)){
                    unset($next_actor_emails[array_search($creator_email, $next_actor_emails)]); // Prevent copying a receiver
                }

                $copy_to = array_merge($copy_to, $next_actor_emails);
            }
        }

        $recipients = ['send_to' => array_unique($send_to), 'copy_to' => array_unique($copy_to)];

        return $recipients;
    }

    function send_mail_from_log($log){
        return true;
    }

    function check_declined_approval($approve_item_name, $item_id){

        $declined_approval = false;

        $this->read_db->where(array($approve_item_name.'_id' => $item_id));
        $status_id = $this->read_db->get($approve_item_name)->row()->fk_status_id;
        
        $action = $this->general_model->action_button_data($approve_item_name);

        if($action['item_status'][$status_id]['status_approval_direction'] == -1){
            $declined_approval = true;
        }

        return $declined_approval;
    }


    function get_email_body($email_notification, $permission_label_name, $item_id, $full_approval = false){

        $decline_approval = $this->check_declined_approval($email_notification['approve_item_name'], $item_id);

        if($full_approval){
            $template_body = file_get_contents(APPPATH.'resources/email_templates/'.$this->session->user_locale.'/item_fully_approved.txt');
        }elseif($decline_approval){
            $template_body = file_get_contents(APPPATH.'resources/email_templates/'.$this->session->user_locale.'/decline_item.txt');
        }else{
            $template_body = file_get_contents(APPPATH.'resources/email_templates/'.$this->session->user_locale.'/'.$permission_label_name.'.txt');

            if($permission_label_name == 'approval'){
                $this->read_db->where(array('fk_permission_label_id' => 2));
            }else{
                $this->read_db->where(array('fk_permission_label_id' => $email_notification['permission_label_id']));
            }
    
            $this->read_db->where(array('fk_approve_item_id' => $email_notification['approve_item_id'], 
            'fk_account_system_id' => $this->session->user_account_system_id));
    
            $email_template_obj = $this->read_db->get('email_template');
    
            if($email_template_obj->num_rows() > 0){
                $email_template = $email_template_obj->row();
    
                $template_body = $email_template->email_template_body;
    
            }
        }

        return  $template_body;
    }

    function customize_email_body_placeholders($approve_item_name, $approve_item_id, $item_id){
        $tags = [];

        // Construct the Standard tags/placeholders for all items types
        $user_fullname = $this->user_model->get_user_full_name($this->session->user_id);

        $tags['{user}'] = $user_fullname;

        if($item_id > 0){
            $tags['{url}'] = base_url().$approve_item_name.'/view/'.hash_id($item_id,'encode');
        }

        $tags['{item}'] = $approve_item_name;
        // Format the keys of the record being created, updated, deleted or approved to be valid placeholders
        $record_fields_values = $this->get_record_detail_for_email_template($approve_item_name, $approve_item_id,  $item_id);

        foreach($record_fields_values as $field => $value){

            if($field == $approve_item_name.'_created_by' || $field == $approve_item_name.'_last_modified_by') {
                $value = $this->user_model->get_user_full_name($value);
            }
            $tags['{'.$field.'}'] = $value;
        }

        // log_message('error', json_encode($record_fields_values));
        
        return $tags;
    }

    function log_email($tags, $template_subject, $template_body, $mail_recipients){

        $formatted_tags = [];

        foreach($tags as $tag => $value){
            $formatted_tags['{'.$tag.'}'] = $value;
        }

        if(!empty($formatted_tags)){
            // Replace tags into the template body
            $tag_keys = array_keys($formatted_tags);
            $tag_values = array_values($formatted_tags);

            // Assign values to the class properties (msg, sub, to)
            $msg = str_replace($tag_keys,$tag_values,$template_body);
            $sub = str_replace($tag_keys,$tag_values,$template_subject);

            // Log the mail to be sent in the mail log table. This email will await for a cron job to trigger to have it sent
            $email_template_history_fields = $this->grants_model->generate_item_track_number_and_name('mail_log');
            $data['mail_log_name'] = $email_template_history_fields['mail_log_track_number'];
            $data['mail_log_track_number'] = $email_template_history_fields['mail_log_track_number'];
            $data['mail_log_recipients'] = json_encode($mail_recipients);
            $data['mail_log_message'] =  '{"subject":"'.$sub.'","body":"'.$msg.'"}';
            $data['mail_log_created_date'] = date('Y-m-d');
            $data['mail_log_created_by'] = isset($this->session->user_id) ? $this->session->user_id: 1;
            $data['mail_log_last_modified_by'] = isset($this->session->user_id) ? $this->session->user_id: 1;
            $data['fk_status_id'] = $this->grants_model->initial_item_status('mail_log');;
           
            $this->write_db->insert('mail_log', $data);
        }

        return $tags;
    }

    function mail_notification_setting($approve_item_name, $permission_label_name){
        // Check if mail notification for the feature are allowed. Only log an email if the notification status is active
        $this->read_db->where(array('account_system_email_notification.fk_account_system_id' => $this->session->user_account_system_id, 
        'approve_item_name' => $approve_item_name, 'account_system_email_notification_is_active' => 1));
        
        $this->read_db->join('approve_item','approve_item.approve_item_id=account_system_email_notification.fk_approve_item_id');
        $this->read_db->join('account_system','account_system.account_system_id=account_system_email_notification.fk_account_system_id');

        if($permission_label_name != 'approval'){
            $this->read_db->where(array('permission_label_name' => $permission_label_name));
            $this->read_db->join('permission_label','permission_label.permission_label_id=account_system_email_notification.fk_permission_label_id');
        }
        
        $email_notification_obj = $this->read_db->get('account_system_email_notification');

        return $email_notification_obj;
    }

    function convert_email_template_to_body($permission_label_name, $approve_item_name, $item_id = 0){
       
        // Get approve item id
        $this->read_db->select(array('approve_item_id'));
        $this->read_db->where(array('approve_item_name' => $approve_item_name));
        $approve_item_id = $this->read_db->get('approve_item')->row()->approve_item_id;

        
        // Check if mail notification for the feature are allowed. Only log an email if the notification status is active
        $email_notification_obj = $this->mail_notification_setting($approve_item_name, $permission_label_name);

        if($email_notification_obj->num_rows() > 0){

            $email_notification = $email_notification_obj->row_array();

            $mail_recipients = $this->template_email_recipients($approve_item_name, $permission_label_name, $item_id, $approve_item_id);
           
            // Email subject
            $template_subject = get_phrase($approve_item_name.'_'.$permission_label_name.'_notification');
          
            // Construct the Standard tags/placeholders for all items types
            $tags = $this->customize_email_body_placeholders($approve_item_name, $approve_item_id, $item_id);

            if(!empty($mail_recipients['send_to'])){ // Only log email if their are recipients
                // Get the email template to be used for the email body
                $template_body = $this->get_email_body($email_notification, $permission_label_name, $item_id);

                // Log email 
                $this->log_email($tags, $template_subject, $template_body, $mail_recipients);
                
            }elseif(!empty($mail_recipients['copy_to'])){ // Only fully approved items gets to this loop

                $template_body = $this->get_email_body($email_notification, $permission_label_name, $item_id, true);

                // Alternate the copy to address to be sender address
                $mail_recipients['send_to'] = $mail_recipients['copy_to'];
                $mail_recipients['copy_to'] = []; // Empty the copy to address

                // Log email 
                $this->log_email($tags, $template_subject, $template_body, $mail_recipients);
            }
            
        }

    }

    function transaction_validate_duplicates_columns()
    {
      return ['fk_account_system_id', 'fk_approve_item_id', 'fk_permission_label_id'];
    }
    
}