<?php 

$path_parts = pathinfo(__FILE__);

class View_output extends Output_template{

    function __construct(){
        parent::__construct();
    }

    /**
     * feature_model_master_table_visible_columns
     * 
     * Returns an array of selected fields in the master part of the master-detail view action pages from 
     * the feature model is specified
     * @return Array
     */
    function feature_model_master_table_visible_columns():Array {
        $model = $this->current_model;

        $master_table_visible_columns = [];
    
        if(
            method_exists($this->CI->$model,'master_table_visible_columns') &&
            is_array($this->CI->$model->master_table_visible_columns())
        ){
        
            $master_table_visible_columns = $this->CI->$model->master_table_visible_columns();
    
            //Add the table id columns if does not exist in $columns
            if(is_array($master_table_visible_columns) && 
            !in_array($this->CI->grants->primary_key_field($this->controller),
            $master_table_visible_columns)){
                array_unshift($master_table_visible_columns,
                $this->CI->grants->primary_key_field($this->controller));
            }
    
        }
    
        return $master_table_visible_columns;
    }
   
    /**
     * toggle_master_view_select_columns
     * 
     * This method creates an array of selected columns to be used in the master_view method in this model.
     * The master_view method of this model is used to implement the grants master_view method which finally feeds
     * to the view_output method.
     * 
     * This methods utilizes a feature model wrapper method master_table_visible_columns from grant library which
     * checks if the feature model has specified columns to be used in the query of the master table of a view action page
     * or If not specified, it uses all fields from the selected table, ensuring that the foreign keys in this case are unset
     * In both cases above, it ensures that the name fields of the lookup tables are added to this array
     * 
     * It finally implements the fields access permission checks and returns the final array
     * 
     * @return Array - Select columns
     */
    private function toggle_master_view_select_columns(){

        // Check if the table has list_table_visible_columns not empty
        $master_table_visible_columns = $this->feature_model_master_table_visible_columns();
        $lookup_tables = $this->CI->grants->lookup_tables();

        $get_all_table_fields = $this->CI->grants_model->get_all_table_fields();


        foreach ($get_all_table_fields as $get_all_table_field) {
        //Unset foreign keys columns
        if( substr($get_all_table_field,0,3) == 'fk_'){
            unset($get_all_table_fields[array_search($get_all_table_field,$get_all_table_fields)]);
        }
        }

        $visible_columns = $get_all_table_fields;
        $lookup_columns = array();

        if(is_array($master_table_visible_columns) && count($master_table_visible_columns) > 0 ){
        $visible_columns = $master_table_visible_columns;

            if(is_array($lookup_tables) && count($lookup_tables) > 0 ){
            foreach ($lookup_tables as $lookup_table) {
                
                // Add primary_keys for the lookup tables in the visible columns array
                $lookup_table_fields_data = $this->CI->grants_model->table_fields_metadata($lookup_table);
                
                foreach($lookup_table_fields_data as $field_data){
                if($field_data->primary_key == 1){
                    array_push($visible_columns,$field_data->name);
                 }
                }

                //$visible_columns = $this->CI->grants->default_unset_columns($visible_columns,array($lookup_table.'_id'));
            }
            }    

        }elseif(is_array($lookup_tables) && count($lookup_tables) > 0 ){
            $visible_columns = $this->add_lookup_name_fields_to_visible_columns($visible_columns, $lookup_tables);
        }

        //$default_unset_columns = $this->CI->grants->default_unset_columns($visible_columns,array($this->CI->controller.'_deleted_at'));

        // Add created_by and last_modified_by fields if not exists in columns selected
        $history_tracking_fields = $this->insert_history_tracking_fields_to_master_view($visible_columns);

        //Check if controller is not approval and find if status field is present and 
        //it has status in the lookup table
        $status_column = $this->insert_status_column_to_master_view($history_tracking_fields);
        
        // Unset deleted at field
        $unset_fields = [$this->CI->grants->history_tracking_field($this->controller,'deleted_at')];
        $this->CI->grants->default_unset_columns($status_column,$unset_fields);

        //Remove the primary key field from the master table
        unset($status_column[array_search($this->CI->grants->primary_key_field($this->CI->controller),$status_column)]);
        

        return $this->access->control_column_visibility($this->controller,$status_column,'read');

    }

    /**
     * add_lookup_name_fields_to_visible_columns
     * 
     * This method adds name columns of the look up tables to the selected columns
     * 
     * @param Array $visible_columns - Selected columns
     * @param Array $lookup_table - Look up tables
     * 
     * @return Array - Update visible columns array
     */
    function add_lookup_name_fields_to_visible_columns(Array $visible_columns, Array $lookup_tables):Array {
        foreach ($lookup_tables as $lookup_table) {

            $lookup_table_columns = $this->CI->grants_model->get_all_table_fields($lookup_table);

            foreach ($lookup_table_columns as $lookup_table_column) {
                // Only include the name field of the look up table in the select columns
                if(
                    $this->CI->grants->is_primary_key_field($lookup_table,$lookup_table_column) || 
                    $this->CI->grants->is_name_field($lookup_table,$lookup_table_column)
                ){
                    array_push($visible_columns,$lookup_table_column);
               
                }

            }
        }

        return $visible_columns;
    }

    /**
     * insert_history_tracking_fields_to_master_view
     * 
     * This method inserts the created by and last modified by columns if not found in the selected columns
     * 
     * @param Array $visible_columns - Selected columns
     * @return Array - Update selected columns
     */
    function insert_history_tracking_fields_to_master_view(Array $visible_columns):Array{
        if( !in_array($this->CI->grants->history_tracking_field($this->controller,'created_by'),$visible_columns) || 
            !in_array($this->CI->grants->history_tracking_field($this->controller,'last_modified_by'),$visible_columns)
                
        ){
            array_push($visible_columns,$this->CI->grants->history_tracking_field($this->controller,'created_by'),
            $this->CI->grants->history_tracking_field($this->controller,'last_modified_by')); 
        }

        return $visible_columns;
    }

    /**
     * insert_status_column_to_master_view
     * 
     * Inserts a status name column if doesn't exist in the selceted/visible columns.
     * This is only done to tables other than approval and the status tbale should be listed
     * as a lookup table in the feature model
     * 
     * @param Array $visible_columns - Original selected columns
     * 
     * @return Array - Update selected columns array for the master view
     */
    function insert_status_column_to_master_view(Array $visible_columns): Array {
        
        $status_name_field =  $this->CI->grants->name_field('status');

        if($this->controller !== "approval"){
            if(
                in_array('status',$this->CI->grants->lookup_tables($this->controller)) && 
                !in_array($status_name_field,$visible_columns)   
           ){
               array_push($visible_columns,$status_name_field);
            }   
       }

       return $visible_columns;
    }

    /**
     * feature_model_detail_list_table_visible_columns
     * 
     * Returns an array of columns to be selected in a listing table in a master-detail view action page
     * 
     * @param $table String : Selected detail table
     * 
     * @return Array
     */
    function feature_model_detail_list_table_visible_columns(String $table) {

        $model = $this->CI->grants->load_detail_model($table);

        $detail_list_table_visible_columns = [];
    
        if(method_exists($this->CI->$model,'detail_list_table_visible_columns') && 
            is_array($this->CI->$model->detail_list_table_visible_columns())
        ){
        $detail_list_table_visible_columns = $this->CI->$model->detail_list_table_visible_columns();
    
        //Add the table id columns if does not exist in $columns
        if(is_array($detail_list_table_visible_columns) && 
            !in_array($this->CI->grants->primary_key_field($table),$detail_list_table_visible_columns)){
            array_unshift($detail_list_table_visible_columns,$this->CI->grants->primary_key_field($table));
        }

        if($table != 'status'){
            array_push($detail_list_table_visible_columns,$table.'.fk_status_id as status_id');
        }else{
            array_push($detail_list_table_visible_columns,$table.'.status_id');
        }
        

        //Remove status and approval columns if the approveable item is not approveable
        if(!$this->CI->grants_model->approveable_item($table)) {
            
            $cols = ['status_name','approval_name'];

            if ($table == 'status') {
                $cols = ['approval_name'];
            }
            
            $this->CI->grants->remove_mandatory_lookup_tables($detail_list_table_visible_columns,$cols);
          }else{
            $this->CI->grants->add_mandatory_lookup_tables($detail_list_table_visible_columns,['status_name','approval_name']);
          }
    
        }
    
        return $detail_list_table_visible_columns;
    }
  

    /**
     * toggle_detail_list_select_columns
     * 
     * It checks if the feature model select columns for detail lists have been defined or else
     * uses the fields of the detail table with created_by, last_modified_by and deleted_at fields 
     * unset.
     * 
     * @param String $table - Passed table name
     * @return Array - Columns to select
     */
    function toggle_detail_list_select_columns($table):Array {

        // Check if the table has list_table_visible_columns not empty
        $detail_list_table_visible_columns = $this->feature_model_detail_list_table_visible_columns($table);
        
        //Table lookup tables
        $lookup_tables = $this->CI->grants->lookup_tables($table);
       

        $get_all_table_fields = $this->CI->grants_model->get_all_table_fields($table);

        // Replace the list visible columns if the current controller is approval
        //$list_visible_columns = array();
            
        $model = $this->CI->grants->load_detail_model($table); 
       
        // Removing approval_id field in the select fields
        if( $this->CI->controller == 'approval'){
            if(is_array($detail_list_table_visible_columns) && 
            count($detail_list_table_visible_columns) > 0){
                array_unshift($detail_list_table_visible_columns,$this->CI->grants->primary_key_field($table));

                //$detail_list_table_visible_columns = $list_visible_columns;
            }
            
        }

        //print_r($list_visible_columns);exit;
        
        // Unset history fields
        foreach ($get_all_table_fields as $get_all_table_field) {
    
          //Unset foreign keys columns, created_by and last_modified_by columns
            
            if( substr($get_all_table_field,0,3) == 'fk_' ||
                $this->CI->grants->is_history_tracking_field($table,$get_all_table_field,'created_by') ||
                $this->CI->grants->is_history_tracking_field($table,$get_all_table_field,'last_modified_by') ||
                $this->CI->grants->is_history_tracking_field($table,$get_all_table_field,'deleted_at')
            ){      
                unset($get_all_table_fields[array_search($get_all_table_field,$get_all_table_fields)]);
            }
    
    
        }
    
    
        $visible_columns = $get_all_table_fields;

        if($table == 'status'){
            array_push($visible_columns,$table.'.status_id');
        }else{
            array_push($visible_columns,$table.'.fk_status_id as status_id');
        }
        
        //print_r($visible_columns);exit;
        

        
        if(is_array($detail_list_table_visible_columns) && count($detail_list_table_visible_columns) > 0 ){
          $visible_columns = $detail_list_table_visible_columns;
          //print_r($visible_columns);exit;
        }else{
          if(is_array($lookup_tables) && count($lookup_tables) > 0 ){
            foreach ($lookup_tables as $lookup_table) {
  
    
              $lookup_table_columns = $this->CI->grants_model->get_all_table_fields($lookup_table);
    
              foreach ($lookup_table_columns as $lookup_table_column) {
                // Only include the name field of the look up table in the select columns
                if($this->CI->grants->is_name_field($lookup_table,$lookup_table_column)){
                    array_push($visible_columns,$lookup_table.'_id');
                    array_push($visible_columns,$lookup_table_column);
                }
    
              }
            }
          }
        }
        //print_r($detail_list_table_visible_columns);exit;
        return $this->access->control_column_visibility($table,$visible_columns,'read');
    
      }

      /**
     * detail_list_query
     * 
     * This is query result of the detail table. The result of this method will be used in the view_output
     * to create the detail list
     * 
     * @param $table String : The selected table
     * 
     * @return array
     * 
     */
    function toggle_detail_list_query(String $table): Array {
        $model = $this->CI->grants->load_detail_model($table);
    
        $detail_list_query = $this->detail_list_internal_query_result($table); // System generated query result
        
        
        if(method_exists($this->CI->$model,'detail_list_query') && 
            is_array($this->CI->$model->detail_list_query($table)) &&
            count($this->CI->$model->detail_list_query($table)) > 0
        ){
            $detail_list_query = $this->CI->$model->detail_list_query($table); // A full user defined query result
        } 

        //print_r($detail_list_query);
        //exit();

        $detail_list_query = $this->CI->grants->update_query_result_for_fields_changed_to_select_type($table,$detail_list_query);
  
    
        return $detail_list_query;
    }


      function detail_list_internal_query_result($table){

        //$lookup_tables = ($table == 'permission_template') ? ['role_group','permission'] : $this->CI->grants->lookup_tables($table);
        $lookup_tables = $this->CI->grants->lookup_tables($table);
        //print_r($lookup_tables);exit;
        $select_columns = $this->toggle_detail_list_select_columns($table);
     
        $filter_where = array($table.'.fk_'.strtolower($this->controller).'_id'=> hash_id($this->CI->uri->segment(3,0),'decode') );
        //print_r($filter_where);exit;
        return $this->CI->grants_model->run_list_query($table,$select_columns,$lookup_tables,'detail_list_table_where',$filter_where);
      }   

    /**
     * master_view_query_result
     * 
     * This method returns internal database query results. Its used when the feature model did not
     * run a model specific query.
     * 
     * @return Array - Database query result 
     */
    function master_view_query_result(): Array {

        $table = strtolower($this->controller);
    
        $model = $this->current_model;

        $select_columns = $this->toggle_master_view_select_columns();

        if($this->controller == 'status'){
            array_push($select_columns,$table.'.status_id');
        }else{
            array_push($select_columns,$table.'.fk_status_id as status_id');
        }
        
        

        $lookup_tables = $this->CI->grants->lookup_tables($table);
        
        $master_view_query_result = $this->CI->grants_model->run_master_view_query($table,$select_columns,$lookup_tables);
        
        return $this->CI->grants->update_query_result_for_fields_changed_to_select_type($this->controller,$master_view_query_result);
        
    }
  

    /**
     * toggle_master_view_query_result
     * 
     * This method checks if the feature model has query result for the selected record or if it misses
     * gets it from the internal grants model
     * 
     * @todo the "master_view" method in the feature specific models to be renamed to "master_view_feature_model_query_result"
     * 
     * @return array
     *  
     */

    function toggle_master_view_query_result(): Array {
        $model = $this->current_model;

        $master_view = $this->master_view_query_result();
    
        // Get result from grants model if feature model list returns empty
    
        if(method_exists($this->CI->$model,'master_view') &&
            is_array($this->CI->$model->master_view()) && 
            count($this->CI->$model->master_view()) > 0 
        ){
        
            $master_view = $this->CI->$model->master_view();
        
        }
        
    return $master_view;
  }

  
/**
 * detail_list_view
 * 
 * This method creates an array to be used in the view_output. It used to construct the table array_result
 * of each detail table
 * 
 * @param $table String : Selected table
 * 
 * @return array
 * 
 */
function detail_list_output(String $table): Array {

    // Query result of the detail table
    $result = $this->toggle_detail_list_query($table);
  
    // Selected column of the detail table
    $keys = $this->toggle_detail_list_select_columns($table);
  
    // Check if the detail table has also other detail tables. 
    // It makes its track number a link in the view if true
    $has_details = $this->CI->grants->check_if_table_has_detail_table($table);
  
    // It check if the detail table is approveable so as to show the approval links in the status action
    $is_approveable_item = $this->CI->grants->approveable_item($table);
  
    // Check if the add button is allowed to be shown
    $show_add_button = $this->CI->grants->show_add_button($table);
  
    // Checks if the detail table has a detail table to it
    $has_details_listing = $this->CI->grants->check_if_table_has_detail_listing($table);
    
    $converted_result = [];

    // foreach($result as $row){
    //     $converted_result[] = $this->currency_conversion($row,$table);
    // }

    return array(
      'keys'=> $keys,
      'table_body'=>$result,
      'table_name'=> $table,
      'has_details_table' => $has_details,
      'has_details_listing' => $has_details_listing,
      'is_approveable_item' => $is_approveable_item,
      'show_add_button'=>$show_add_button
    );
  }
  
  function currency_conversion(&$query_output,$table_name = ""){
    
    $table_name = $table_name == ""?$this->controller:$table_name;

    if(method_exists($this->CI->{$table_name.'_model'},'currency_fields') && 
        is_array($this->CI->{$table_name.'_model'}->currency_fields()) && 
            count($this->CI->{$table_name.'_model'}->currency_fields()) > 0){
            
                $currency_fields = $this->CI->{$table_name.'_model'}->currency_fields();

                foreach($currency_fields as $currency_field){
                    if(isset($query_output[$currency_field])){
                        
                        $id = hash_id($this->CI->id,'decode');
                        
                        $rate = currency_conversion($this->CI->grants_model->get_record_office_id($this->CI->controller,$id));

                        //$query_output[$currency_field] = $query_output[$currency_field] * $rate;
                        $query_output[$currency_field] = $query_output[$currency_field] * $rate;
                    }
                    
                }
    }

    return $query_output;
}

    /**
     * view_output
     * 
     * This method returns the output of all view action views
     * 
     * @return array
     * 
     */

    function _output(){

        $table = $this->controller;
    
        $model = $table.'_model';

        $master_additional_fields = [];

        if(method_exists($this->CI->$model,'master_table_additional_fields')){
            $master_additional_fields = $this->CI->$model->master_table_additional_fields(hash_id($this->CI->uri->segment(3,0),'decode'));
        }
    
        $query_output = $this->toggle_master_view_query_result();

        $keys = $this->toggle_master_view_select_columns();
        
        // Add additional fields
        if(is_array($master_additional_fields) && count($master_additional_fields) > 0){
            $query_output = array_merge($query_output,$master_additional_fields);
            array_push($keys,implode(',',array_keys($master_additional_fields)));
        }

        // Apply currency conversion
       $this->currency_conversion($query_output);


        $has_details = $this->CI->grants->check_if_table_has_detail_table($table);
        $is_approveable_item = $this->CI->grants->approveable_item();

        $look_tables_name_fields = $this->CI->grants->tables_name_fields(
            $this->CI->grants->lookup_tables()
        );
    
        $result['master'] = array(
            'keys'=> $keys,
            'table_body'=>$query_output,
            'table_name'=> $table,
            'has_details_table' => $has_details,
            'is_approveable_item' => $is_approveable_item,
            'lookup_name_fields' => $look_tables_name_fields,
            'action_labels'=>$this->CI->grants->action_labels($table,hash_id($this->CI->uri->segment(3,0),'decode'))
        );
    
        $detail_tables = $this->CI->grants->detail_tables($table);
    
        $result['detail'] = array();
        //is_multi_row
        if($has_details){
            $detail = array();
            foreach ($detail_tables as $detail_table) {
                $detail[$detail_table] = $this->detail_list_output($detail_table);
                $detail[$detail_table]['fields_meta_data'] = $this->CI->grants_model->fields_meta_data_type_and_name($detail_table);
                $detail[$detail_table]['is_multi_row'] = $this->CI->grants->check_if_table_is_multi_row($detail_table);
            }
    
            $result['detail'] = $detail;
        }

        //$result['master']['master_additional_fields'] = array('total_amount'=>54000);
    
        return $result;
    
    }


}

require_once(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'create_instance.php');