<?php 

class Datatable {
    public $table = '';
    public $columns = [];
    public $record_id = 0;
    public $record_status_id = 0;
    public $records = [];
    public $count_records = 0;
    public $result_obj = null;
    public $CI = null;


    function __construct($table_name){
        $this->CI =& get_instance();
        $this->table = $table_name[0];

        $this->CI->load->database(); // To be removed
    }



  public function get_list_view(){
    $this->CI->load->model($this->table.'_model');
    $columns = $this->CI->{$this->table.'_model'}->list_table_visible_columns();

    if(reset($columns) == $this->table.'_id') array_shift($columns);
    
    $result['columns'] = $columns;
    $result['has_details_table'] = false; 
    $result['has_details_listing'] = false;
    $result['is_multi_row'] = false;
    $result['show_add_button'] = true;

    return $result;
  }


  private function get_records($columns){

    $search_columns = $columns;

    // Limiting records
    $start = intval($this->CI->input->post('start'));
    $length = intval($this->CI->input->post('length'));

    // log_message('error', json_encode(['start' => $start, 'length' => $length ]));

    $this->CI->read_db->limit($length, $start);

    // Ordering records

    $order = $this->CI->input->post('order');
    $col = '';
    $dir = 'desc';
    
    if(!empty($order)){
      $col = $order[0]['column'];
      $dir = $order[0]['dir'];
    }
          
    if( $col == ''){
      $this->CI->read_db->order_by('funds_transfer_id DESC');
    }else{
      $this->CI->read_db->order_by($columns[$col],$dir); 
    }

    // Searching

    $search = $this->CI->input->post('search');
    $value = $search['value'];

    array_shift($search_columns);

    if(!empty($value)){
      $this->CI->read_db->group_start();
      $column_key = 0;
        foreach($search_columns as $column){
          if($column_key == 0) {
            $this->CI->read_db->like($column,$value,'both'); 
          }else{
            $this->CI->read_db->or_like($column,$value,'both');
        }
          $column_key++;				
      }
      $this->CI->read_db->group_end();      
    }

    $results = [];
    $result_obj = null;

    if(method_exists($this->CI->{$this->table.'_model'}, 'datatable_select_query')){
        $result_obj = $this->CI->{$this->table.'_model'}->datatable_select_query(); // This query should not return results with result_array(), row() etc
    }
    
    if($result_obj->num_rows() > 0){
        $results = $result_obj->result_array();
    }
    
    $this->records = $results;

    // log_message('error', json_encode(count($this->records)));

    return $this->records;
  }

  private function count_records($columns){

    $search_columns = $columns;

    // Searching

    $search = $this->CI->input->post('search');
    $value = !isset($search['value']) ? '' : $search['value'];

    array_shift($search_columns);

    if(!empty($value)){
      $this->CI->read_db->group_start();
      $column_key = 0;
        foreach($search_columns as $column){
          if($column_key == 0) {
            $this->CI->read_db->like($column,$value,'both'); 
          }else{
            $this->CI->read_db->or_like($column,$value,'both');
        }
          $column_key++;				
      }
      $this->CI->read_db->group_end();
    }

    $result_obj = null;

    if(method_exists($this->CI->{$this->table.'_model'}, 'datatable_select_query')){
        $result_obj = $this->CI->{$this->table.'_model'}->datatable_select_query(); // This query should not return results with result_array(), row() etc
    }

    $count_all_results = $result_obj->num_rows();

    $this->count_records = $count_all_results;

    return $this->count_records;
  }

  private function add_primary_key_columns(){
    $this->CI->load->model($this->table.'_model');
    $columns = $this->CI->{$this->table.'_model'}->list_table_visible_columns();
    if(reset($columns) !== $this->table.'_id') array_unshift($columns, $this->table."_id"); 

    return $columns;
  }

  public function select_columns_with_status_id(){
    $columns = $this->add_primary_key_columns(); 

    array_push($columns,$this->table.'.fk_status_id as status_id');

    return $columns;
  }

  public function show_list(){

    // $result_obj = null;

    // if(method_exists($this->CI->{$this->table.'_model'}, 'datatable_select_query')){
    //     $result_obj = $this->CI->{$this->table.'_model'}->datatable_select_query(); // This query should not return results with result_array(), row() etc
    // }
    
    $columns = $this->add_primary_key_columns(); 
   
    $draw =intval($this->CI->input->post('draw'));
    $this->get_records($columns);
    $this->count_records($columns);

    $result = [];

    $cnt = 0;

    $this->CI->load->model('General_model');
    $status_data = $this->CI->General_model->action_button_data($this->table);
    extract($status_data);
    

    foreach($this->records as $record){
        $this->record_id = array_shift($record);
        $this->record_status_id = array_pop($record);

        $record_track_number = $record[$this->table.'_track_number'];
        $record[$this->table.'_track_number'] = '<a href="'.base_url().$this->table.'/view/'.hash_id($this->record_id,'encode').'">'.$record_track_number.'</a>';
        
        if(method_exists($this->CI->{$this->table.'_model'}, 'modify_datatable_columns')){
            $record = $this->CI->{$this->table.'_model'}->modify_datatable_columns($record);
        }

        $row = array_values($record);

        $action = approval_action_button($this->table,$item_status, $this->record_id,  $this->record_status_id, $item_initial_item_status_id, $item_max_approval_status_ids);
    
        array_unshift($row,$action);

        $result[$cnt] = $row;

      $cnt++;
    }

    $response = [
      'draw'=>$draw,
      'recordsTotal'=>$this->count_records,
      'recordsFiltered'=>$this->count_records,
      'data'=>$result
    ];
    
    return $response;
  }
}